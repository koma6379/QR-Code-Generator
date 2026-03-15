<?php
// CORS-Header
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Auth-Token");

// Preflight-Anfragen (OPTIONS) direkt beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
$request = explode('/', trim($pathInfo, '/'));

// ==========================================
// 1. INSTALLER & SETUP LOGIK
// ==========================================
$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    // Wenn das Frontend die Setup-Daten schickt
    if ($method === 'POST' && $request[0] === 'setup') {
        $data = json_decode(file_get_contents('php://input'), true);
        $h = $data['dbHost'] ?? 'localhost';
        $d = $data['dbName'] ?? '';
        $u = $data['dbUser'] ?? '';
        $p = $data['dbPass'] ?? '';
        $v = $data['vipCode'] ?? 'VIPcode';

        // 1. Testen, ob die Datenbankdaten stimmen
        try {
            $testPdo = new PDO("mysql:host=$h;dbname=$d;charset=utf8mb4", $u, $p, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // 2. Config-Datei schreiben
            $configContent = "<?php\n"
                           . "// Automatisch generierte Konfiguration\n"
                           . "\$host = '" . addslashes($h) . "';\n"
                           . "\$db = '" . addslashes($d) . "';\n"
                           . "\$user = '" . addslashes($u) . "';\n"
                           . "\$pass = '" . addslashes($p) . "';\n"
                           . "\$vipCode = '" . addslashes($v) . "';\n"
                           . "?>";
                           
            if (file_put_contents($configFile, $configContent) !== false) {
                echo json_encode(["success" => true]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Konnte config.php nicht speichern. Bitte Schreibrechte (CHMOD) prüfen!"]);
            }
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(["error" => "Datenbankverbindung fehlgeschlagen. Sind die Daten korrekt?"]);
        }
        exit;
    }

    // Wenn keine Setup-Daten gesendet wurden, melde dem Frontend: "Setup wird benötigt!"
    echo json_encode(["setupRequired" => true]);
    exit;
}

// ==========================================
// 2. NORMALER BETRIEB (Config existiert)
// ==========================================
require_once $configFile;

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Datenbankverbindung fehlgeschlagen"]);
    exit;
}

// --- TABELLEN ERSTELLEN & UPDATEN ---
try { 
    $pdo->query("SELECT password_hash FROM users LIMIT 1"); 
    $pdo->exec("RENAME TABLE users TO users_alt_" . time());
} catch (PDOException $e) { }

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(100),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS dynamic_codes (
    id VARCHAR(50) PRIMARY KEY,
    targetUrl TEXT NOT NULL,
    scanCount INT DEFAULT 0,
    qrImage LONGTEXT,
    user_id INT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS static_codes (
    id VARCHAR(50) PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    content TEXT NOT NULL,
    qrImage LONGTEXT,
    user_id INT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

try { $pdo->query("SELECT qrImage FROM dynamic_codes LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE dynamic_codes ADD COLUMN qrImage LONGTEXT"); }
try { $pdo->query("SELECT user_id FROM dynamic_codes LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE dynamic_codes ADD COLUMN user_id INT"); }
try { $pdo->query("SELECT user_id FROM static_codes LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE static_codes ADD COLUMN user_id INT"); }
try { $pdo->query("SELECT username FROM users LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE"); }
try { $pdo->query("SELECT password FROM users LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255)"); }
try { $pdo->query("SELECT token FROM users LIMIT 1"); } catch (PDOException $e) { $pdo->exec("ALTER TABLE users ADD COLUMN token VARCHAR(100)"); }


// --- HILFSFUNKTION FÜR AUTHENTIFIZIERUNG ---
function getUserId($pdo) {
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    if (!$token) return null;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$token]);
    return $stmt->fetchColumn() ?: null;
}

// ==========================================
// API ROUTING
// ==========================================

// --- AUTHENTIFIZIERUNG ---
if ($method === 'POST' && $request[0] === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $inviteCode = trim($data['inviteCode'] ?? ''); 

    // Prüfung gegen den Code aus der config.php
    if ($inviteCode !== $vipCode) {
        http_response_code(403);
        echo json_encode(["error" => "Ungültiger VIP-Code!"]);
        exit;
    }

    if (strlen($username) < 3 || strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(["error" => "Benutzername (min. 3) oder Passwort (min. 6) zu kurz"]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32)); 

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, token) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $token]);
        echo json_encode(["success" => true, "token" => $token, "username" => $username]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(["error" => "Dieser Benutzername ist bereits vergeben."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Datenbankfehler: " . $e->getMessage()]);
        }
    }
    exit;
}

elseif ($method === 'POST' && $request[0] === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET token = ? WHERE id = ?")->execute([$token, $user['id']]);
        echo json_encode(["success" => true, "token" => $token, "username" => $username]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Falscher Benutzername oder Passwort"]);
    }
    exit;
}

// --- GESCHÜTZTE ROUTEN ---
$userId = getUserId($pdo);

if ($method === 'GET' && ($request[0] === 'redirect' || $request[0] === 'q')) {
    $id = $request[1] ?? '';
    $stmt = $pdo->prepare("SELECT targetUrl FROM dynamic_codes WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result) {
        $pdo->prepare("UPDATE dynamic_codes SET scanCount = scanCount + 1 WHERE id = ?")->execute([$id]);
        
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
        
        header("Location: " . $result['targetUrl'], true, 302);
        exit;
    } else {
        http_response_code(404);
        echo "QR-Code nicht gefunden oder wurde gelöscht.";
    }
}

// Wenn Setup erledigt ist und wir eine API Route ohne Token rufen -> Fehler
if ($request[0] !== 'init' && !$userId && $request[0] !== 'stats') {
    http_response_code(401);
    echo json_encode(["error" => "Nicht autorisiert. Bitte einloggen."]);
    exit;
}

if ($method === 'GET' && $request[0] === 'init') {
    echo json_encode(["setupRequired" => false]);
    exit;
}

// --- NEU: VIP CODE AUSLESEN ---
if ($method === 'GET' && $request[0] === 'vip-code') {
    echo json_encode(["vipCode" => $vipCode]);
    exit;
}

// --- DYNAMISCHE CODES ---
if ($method === 'POST' && $request[0] === 'dynamic') {
    $data = json_decode(file_get_contents('php://input'), true);
    $targetUrl = $data['targetUrl'] ?? '';
    $customId = $data['customId'] ?? '';
    $qrImage = $data['qrImage'] ?? '';

    if (!$targetUrl) {
        http_response_code(400);
        echo json_encode(["error" => "Ziel-URL fehlt"]);
        exit;
    }

    $id = $customId ? preg_replace('/[^a-z0-9-_]/', '-', strtolower($customId)) : substr(md5(uniqid()), 0, 8);

    $stmt = $pdo->prepare("SELECT id FROM dynamic_codes WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch() && $customId) {
        http_response_code(409);
        echo json_encode(["error" => "Dieser Name ist bereits vergeben."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO dynamic_codes (id, targetUrl, scanCount, qrImage, user_id) VALUES (?, ?, 0, ?, ?)");
    $stmt->execute([$id, $targetUrl, $qrImage, $userId]);

    echo json_encode(["id" => $id, "targetUrl" => $targetUrl, "scanCount" => 0]);
} 
elseif ($method === 'GET' && $request[0] === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM dynamic_codes WHERE user_id = ? ORDER BY createdAt DESC");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
}
elseif ($method === 'GET' && $request[0] === 'stats') {
    $id = $request[1] ?? '';
    $stmt = $pdo->prepare("SELECT scanCount FROM dynamic_codes WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    if ($result) echo json_encode($result);
    else { http_response_code(404); echo json_encode(["error" => "Nicht gefunden"]); }
}
elseif ($method === 'DELETE' && $request[0] === 'delete') {
    $id = $request[1] ?? '';
    $stmt = $pdo->prepare("DELETE FROM dynamic_codes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    echo json_encode(["success" => true]);
}
elseif ($method === 'PUT' && $request[0] === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $targetUrl = $data['targetUrl'] ?? '';

    $stmt = $pdo->prepare("UPDATE dynamic_codes SET targetUrl = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$targetUrl, $id, $userId]);
    echo json_encode(["success" => true]);
}

// --- STATISCHE CODES ---
elseif ($method === 'POST' && $request[0] === 'static') {
    $data = json_decode(file_get_contents('php://input'), true);
    $type = $data['type'] ?? 'text';
    $content = $data['content'] ?? '';
    $qrImage = $data['qrImage'] ?? '';
    $id = substr(md5(uniqid()), 0, 10); 

    $stmt = $pdo->prepare("INSERT INTO static_codes (id, type, content, qrImage, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $type, $content, $qrImage, $userId]);

    echo json_encode(["success" => true, "id" => $id]);
}
elseif ($method === 'GET' && $request[0] === 'list-static') {
    $stmt = $pdo->prepare("SELECT * FROM static_codes WHERE user_id = ? ORDER BY createdAt DESC");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
}
elseif ($method === 'DELETE' && $request[0] === 'delete-static') {
    $id = $request[1] ?? '';
    $stmt = $pdo->prepare("DELETE FROM static_codes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    echo json_encode(["success" => true]);
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Ungültiger API-Endpunkt"]);
}
?>