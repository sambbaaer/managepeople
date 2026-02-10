<?php
/**
 * Migration Runner
 * Führt neue Datenbank-Migrationen für bestehende Installationen aus
 * 
 * VERWENDUNG: Rufe diese Datei im Browser auf oder per CLI
 */

// Security check - nur ausführen wenn eingeloggt oder CLI
session_start();

// CLI Mode check
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/core/Auth.php';

    if (!Auth::check()) {
        die('Nicht autorisiert. Bitte zuerst einloggen.');
    }
}

require_once __DIR__ . '/core/Database.php';

echo $isCli ? "\n" : "<pre>";
echo "=== ManagePeople V3 Migration Runner ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Migrations-Ordner durchsuchen
    $migrationsDir = __DIR__ . '/core/migrations';
    $migrationFiles = glob($migrationsDir . '/*.sql');

    if (empty($migrationFiles)) {
        echo "Keine Migrations-Dateien gefunden.\n";
    } else {
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            echo "📄 Führe Migration aus: $filename\n";

            $sql = file_get_contents($file);

            // SQLite kann mehrere Statements in exec() ausführen
            $pdo->exec($sql);

            echo "   ✅ Erfolgreich!\n";
        }
    }

    echo "\n=== Migration abgeschlossen! ===\n";
    echo "\nDu kannst diese Datei jetzt löschen oder behalten für zukünftige Migrationen.\n";

} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
}

echo $isCli ? "\n" : "</pre>";

// Redirect-Link für Browser
if (!$isCli) {
    echo '<br><a href="index.php?page=workflows" style="color: #4ECDC4; font-weight: bold;">→ Zu den Abläufen</a>';
}
