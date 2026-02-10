<?php
/**
 * Migration: Phasen-System
 * 
 * Fügt die neuen Spalten für das Phasen-System hinzu:
 * - phase: Die aktuelle Phase innerhalb des Status
 * - phase_date: Optionales Datum für die Phase (z.B. "Geplant für März 2026")
 * - phase_notes: Optionale Notizen zur Phase
 * 
 * Verwendung: Dieses Script einmalig ausführen oder via setup.php
 */

// Sicherstellen dass die Datei nur direkt oder via setup.php aufgerufen wird
if (!defined('MANAGEPEOPLE_SETUP') && !defined('MANAGEPEOPLE_MIGRATION')) {
    // Kann auch direkt aufgerufen werden für manuelle Migration
    define('MANAGEPEOPLE_MIGRATION', true);

    // Config laden
    $configPath = __DIR__ . '/../config.php';
    if (!file_exists($configPath)) {
        die('Config nicht gefunden. Bitte zuerst setup.php ausführen.');
    }
    require_once $configPath;
    require_once __DIR__ . '/Database.php';
}

/**
 * Führt die Migration aus
 * @return array ['success' => bool, 'messages' => array]
 */
function migratePhaseSystem(): array
{
    $messages = [];
    $success = true;

    try {
        $db = Database::getInstance();

        // 1. Prüfen ob Spalten bereits existieren
        $columns = $db->fetchAll("PRAGMA table_info(contacts)");
        $columnNames = array_column($columns, 'name');

        // 2. Phase-Spalte hinzufügen
        if (!in_array('phase', $columnNames)) {
            $db->execute("ALTER TABLE contacts ADD COLUMN phase TEXT");
            $messages[] = "✓ Spalte 'phase' hinzugefügt";
        } else {
            $messages[] = "○ Spalte 'phase' existiert bereits";
        }

        // 3. Phase-Datum hinzufügen
        if (!in_array('phase_date', $columnNames)) {
            $db->execute("ALTER TABLE contacts ADD COLUMN phase_date DATE");
            $messages[] = "✓ Spalte 'phase_date' hinzugefügt";
        } else {
            $messages[] = "○ Spalte 'phase_date' existiert bereits";
        }

        // 4. Phase-Notizen hinzufügen
        if (!in_array('phase_notes', $columnNames)) {
            $db->execute("ALTER TABLE contacts ADD COLUMN phase_notes TEXT");
            $messages[] = "✓ Spalte 'phase_notes' hinzugefügt";
        } else {
            $messages[] = "○ Spalte 'phase_notes' existiert bereits";
        }

        // 5. Tasks-Tabelle erweitern für Phase-Tracking
        $taskColumns = $db->fetchAll("PRAGMA table_info(tasks)");
        $taskColumnNames = array_column($taskColumns, 'name');

        if (!in_array('triggered_by_phase', $taskColumnNames)) {
            $db->execute("ALTER TABLE tasks ADD COLUMN triggered_by_phase TEXT");
            $messages[] = "✓ Spalte 'triggered_by_phase' in tasks hinzugefügt";
        } else {
            $messages[] = "○ Spalte 'triggered_by_phase' existiert bereits";
        }

        // 6. AutomationRules erweitern für Phase-Trigger
        $ruleColumns = $db->fetchAll("PRAGMA table_info(automation_rules)");
        $ruleColumnNames = array_column($ruleColumns, 'name');

        if (!in_array('trigger_phase', $ruleColumnNames)) {
            $db->execute("ALTER TABLE automation_rules ADD COLUMN trigger_phase TEXT");
            $messages[] = "✓ Spalte 'trigger_phase' in automation_rules hinzugefügt";
        } else {
            $messages[] = "○ Spalte 'trigger_phase' existiert bereits";
        }

        // 7. Bestehende sub_status zu phase migrieren (wo sinnvoll)
        $migrated = migrateExistingSubStatusToPhase($db);
        if ($migrated > 0) {
            $messages[] = "✓ $migrated Kontakte: sub_status zu phase migriert";
        }

        $messages[] = "";
        $messages[] = "Migration erfolgreich abgeschlossen!";

    } catch (Exception $e) {
        $success = false;
        $messages[] = "✗ Fehler: " . $e->getMessage();
    }

    return ['success' => $success, 'messages' => $messages];
}

/**
 * Migriert bestehende sub_status Werte zu phase
 * Nur für Werte die eindeutig Phasen sind (nicht Interessensgebiete)
 */
function migrateExistingSubStatusToPhase($db): int
{
    // Mapping von alten sub_status zu neuen phase-Werten
    $phaseMapping = [
        // Status Offen
        'Vorgemerkt' => 'Vorgemerkt',
        'Geplant' => 'Geplant',
        'Angefragt' => 'Angefragt',

        // Status Interessent - nur Phasen, nicht Interessen
        'Feedback ausstehend' => 'Feedback ausstehend',

        // Status Kundin
        'Aktiv Kunde' => 'Aktiv',
        'Business Interesse' => 'Business Interesse',

        // Status Partnerin
        'Aktiv (Team)' => 'Aktiv mit Team',
        'Aktiv (Ohne Team)' => 'Aktiv ohne Team',
        'Hat Potenzial' => 'Hat Potenzial',
        'Inaktiv' => 'Inaktiv'
    ];

    $count = 0;

    foreach ($phaseMapping as $oldSubStatus => $newPhase) {
        $result = $db->execute(
            "UPDATE contacts SET phase = ? WHERE sub_status = ? AND (phase IS NULL OR phase = '')",
            [$newPhase, $oldSubStatus]
        );

        // SQLite gibt die Anzahl betroffener Zeilen zurück
        if ($result) {
            $affected = $db->fetch("SELECT changes() as count");
            $count += (int) ($affected['count'] ?? 0);
        }
    }

    return $count;
}

// Wenn direkt aufgerufen, Migration ausführen
if (defined('MANAGEPEOPLE_MIGRATION') && php_sapi_name() === 'cli') {
    echo "=== ManagePeople Phase-System Migration ===\n\n";

    $result = migratePhaseSystem();

    foreach ($result['messages'] as $msg) {
        echo $msg . "\n";
    }

    exit($result['success'] ? 0 : 1);
}
