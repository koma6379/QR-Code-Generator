# QR-Code-Generator

# Koller's QR Generator v1.0 🚀





## 🇩🇪 Deutsche Version

Ein modernes, selbstgehostetes System zum Erstellen, Verwalten und Tracken von professionellen QR-Codes. Komplett mit Multi-User-System, Setup-Assistent und PWA-Unterstützung (als App installierbar).

### ✨ Funktionen

* **Dynamische QR-Codes (Trackbar):**
    * Ziel-URL kann jederzeit nachträglich geändert werden, ohne dass der QR-Code neu gedruckt werden muss.
    * Zählt automatisch, wie oft der Code gescannt wurde.
    * Benutzerdefinierte Kurz-URLs (z.B. `deinedomain.de/q/mein-link`).
* **Statische QR-Codes:**
    * Unterstützt URLs, reinen Text und WLAN-Zugangsdaten (SSID, Passwort, Verschlüsselung).
* **Umfangreiche Design-Optionen:**
    * Vordergrund- und Hintergrundfarbe frei wählbar.
    * Muster anpassbar (klassische Quadrate oder moderne Punkte).
    * Eigenes Logo in der Mitte des QR-Codes einbetten (Größe anpassbar).
* **Benutzerverwaltung (Multi-User):**
    * Sicheres Login- und Registrierungssystem.
    * Registrierung ist nur mit einem geheimen **VIP-Einladungscode** möglich (verhindert Spam-Accounts).
    * Jeder Nutzer sieht nur seine eigenen QR-Codes in seinem Dashboard.
* **Integriertes Dashboard:**
    * Übersichtliche Tabellen für statische und dynamische Codes.
    * URLs bearbeiten, Codes löschen und hochauflösend als PNG herunterladen.
* **Progressive Web App (PWA):**
    * Kann auf iOS, Android, Windows und macOS wie eine native App installiert werden.
* **Automatischer Setup-Assistent:**
    * Die Installation erfolgt komplett über eine grafische Oberfläche im Browser. Tabellen werden automatisch angelegt.

### 🛠 Voraussetzungen

Um dieses Tool auf deinem eigenen Server zu hosten, benötigst du:

1.  **Webserver:** Apache, Nginx oder LiteSpeed.
2.  **PHP:** Version 8.0 oder neuer.
3.  **Datenbank:** MySQL oder MariaDB.
4.  **SSL-Zertifikat (HTTPS):** Zwingend erforderlich für die PWA-Installation und moderne Browser-Sicherheit.
5.  *(Optional aber empfohlen):* PDO-Erweiterung für PHP (ist bei den meisten Hostern Standard).

### 🚀 Installation

1.  Lade alle Dateien (Frontend-Build und `api.php`) auf deinen Webserver hoch.
2.  Lege in deinem Hosting-Panel (z.B. HestiaCP, cPanel) eine leere MySQL/MariaDB Datenbank an.
3.  Rufe deine Domain im Webbrowser auf.
4.  Der **Setup-Assistent** startet automatisch. Gib dort deine Datenbank-Daten ein und lege einen **VIP-Code** für künftige Registrierungen fest.
5.  Klicke auf Installieren. Das System legt die `config.php` sowie alle Datenbanktabellen selbstständig an.
6.  Registriere deinen ersten Account mit deinem soeben festgelegten VIP-Code. Fertig!

---

## 🇬🇧 English Version

A modern, self-hosted system for creating, managing, and tracking professional QR codes. Complete with a multi-user system, setup wizard, and PWA support (installable as an app).

### ✨ Features

* **Dynamic QR Codes (Trackable):**
    * The target URL can be changed at any time without having to reprint the QR code.
    * Automatically counts how many times the code has been scanned.
    * Custom short URLs (e.g., `yourdomain.com/q/my-link`).
* **Static QR Codes:**
    * Supports URLs, plain text, and WiFi credentials (SSID, Password, Encryption).
* **Extensive Design Options:**
    * Customizable foreground and background colors.
    * Customizable patterns (classic squares or modern dots).
    * Embed your own logo in the center of the QR code (adjustable size).
* **User Management (Multi-User):**
    * Secure login and registration system.
    * Registration is only possible with a secret **VIP invite code** (prevents spam accounts).
    * Each user only sees their own QR codes in their dashboard.
* **Integrated Dashboard:**
    * Clear tables for static and dynamic codes.
    * Edit URLs, delete codes, and download high-resolution PNGs.
* **Progressive Web App (PWA):**
    * Can be installed on iOS, Android, Windows, and macOS like a native app.
* **Automated Setup Wizard:**
    * Installation is done entirely via a graphical interface in the browser. Database tables are created automatically.

### 🛠 Requirements

To host this tool on your own server, you will need:

1.  **Web Server:** Apache, Nginx, or LiteSpeed.
2.  **PHP:** Version 8.0 or newer.
3.  **Database:** MySQL or MariaDB.
4.  **SSL Certificate (HTTPS):** Mandatory for PWA installation and modern browser security.
5.  *(Optional but recommended):* PDO extension for PHP (standard on most hosts).

### 🚀 Installation

1.  Upload all files (Frontend build and `api.php`) to your web server.
2.  Create an empty MySQL/MariaDB database in your hosting panel (e.g., HestiaCP, cPanel).
3.  Open your domain in a web browser.
4.  The **Setup Wizard** will start automatically. Enter your database credentials and set a **VIP Code** for future registrations.
5.  Click install. The system will automatically create the `config.php` and all required database tables.
6.  Register your first account using the VIP code you just created. Done!
