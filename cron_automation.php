<?php
/**
 * Automation Scheduler
 * 
 * Führt Automatisierungsregeln periodisch aus.
 * Kann per Cronjob oder manuell aufgerufen werden.
 * 
 * Cronjob-Beispiel (alle 15 Minuten):
 * ,.* /15 * * * * php /path/to/cron_automation.php
 * 
 * Oder via Browser:
 * https://example.com/managepeopleV3/cron_automation.php?key=SECRET_KEY
 */

// Sicherheitscheck für Web-Aufruf
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    $secretKey = 'mp3_automation_' . substr(md5(__FILE__), 0, 8);
    $providedKey = $_GET['key'] ?? '';

    if ($providedKey !== $secretKey) {
        header('HTTP/1.1 403 Forbidden');
        echo "Zugriff verweigert. Bei erstem Aufruf erscheint der Secret Key in den Logs.";
        error_log("Automation Scheduler: Secret Key = $secretKey");
        exit;
    }
}

// Bootstrap
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/models/Settings.php';
require_once __DIR__ . '/core/models/AutomationRule.php';
require_once __DIR__ . '/core/models/AutomationCondition.php';
require_once __DIR__ . '/core/models/AutomationAction.php';
require_once __DIR__ . '/core/models/Contact.php';
require_once __DIR__ . '/core/models/Task.php';

// Prüfen ob Automation global aktiviert ist
$settings = new Settings();
if ($settings->get('automation_enabled') !== '1') {
    logOutput("Automation ist global deaktiviert. Abbruch.");
    exit;
}

logOutput("=== Automation Scheduler gestartet ===");

$ruleModel = new AutomationRule();
$contactModel = new Contact();

// Alle aktivierten Custom-Regeln laden
$customRules = $ruleModel->getAll(['type' => 'custom', 'is_enabled' => 1]);
logOutput("Gefunden: " . count($customRules) . " aktive Custom-Regeln");

if (empty($customRules)) {
    logOutput("Keine Regeln zu verarbeiten. Ende.");
    exit;
}

// Alle Kontakte laden
$contacts = $contactModel->getAll();
logOutput("Gefunden: " . count($contacts) . " Kontakte");

$totalActionsExecuted = 0;

foreach ($customRules as $rule) {
    logOutput("\n--- Prüfe Regel: {$rule['name']} (ID: {$rule['id']}) ---");

    $matchCount = 0;

    foreach ($contacts as $contact) {
        // Prüfen ob Regel auf diesen Kontakt zutrifft
        if ($ruleModel->evaluate($rule['id'], $contact)) {
            $matchCount++;

            // Prüfen ob Regel bereits für diesen Kontakt ausgeführt wurde (Deduplication)
            if (!hasRuleRunForContact($rule['id'], $contact['id'])) {
                logOutput("  → Treffer: {$contact['name']} - führe Aktionen aus");

                $results = $ruleModel->executeActions($rule['id'], $contact['id']);
                $totalActionsExecuted += count($results);

                // Markieren dass Regel für diesen Kontakt ausgeführt wurde
                markRuleRunForContact($rule['id'], $contact['id']);

                foreach ($results as $result) {
                    $status = $result['success'] ? '✓' : '✗';
                    logOutput("    $status {$result['action_type']}: {$result['message']}");
                }
            }
        }
    }

    logOutput("  Treffer: $matchCount Kontakte");
}

logOutput("\n=== Automation Scheduler beendet ===");
logOutput("Ausgeführte Aktionen: $totalActionsExecuted");

// --- Helper Functions ---

function logOutput($message)
{
    global $isCli;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message";

    if ($isCli) {
        echo $line . "\n";
    } else {
        echo htmlspecialchars($line) . "<br>";
    }

    // Optional: In Logdatei schreiben
    $logFile = __DIR__ . '/logs/automation.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, $line . "\n", FILE_APPEND);
}

/**
 * Prüft ob eine Regel für einen Kontakt bereits ausgeführt wurde
 * Nutzt die Datenbank um Duplikate zu vermeiden
 */
function hasRuleRunForContact($ruleId, $contactId)
{
    $db = Database::getInstance();

    // Tabelle erstellen falls nicht vorhanden
    $db->execute("CREATE TABLE IF NOT EXISTS automation_run_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        rule_id INTEGER NOT NULL,
        contact_id INTEGER NOT NULL,
        run_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(rule_id, contact_id)
    )");

    $result = $db->fetch(
        "SELECT id FROM automation_run_log WHERE rule_id = ? AND contact_id = ?",
        [$ruleId, $contactId]
    );

    return $result !== false;
}

/**
 * Markiert dass eine Regel für einen Kontakt ausgeführt wurde
 */
function markRuleRunForContact($ruleId, $contactId)
{
    $db = Database::getInstance();

    $db->execute(
        "INSERT OR IGNORE INTO automation_run_log (rule_id, contact_id, run_at) VALUES (?, ?, datetime('now'))",
        [$ruleId, $contactId]
    );
}

/**
 * Optional: Run-Log für eine Regel zurücksetzen (z.B. wenn Regel geändert wurde)
 */
function resetRuleRunLog($ruleId)
{
    $db = Database::getInstance();
    $db->execute("DELETE FROM automation_run_log WHERE rule_id = ?", [$ruleId]);
}
