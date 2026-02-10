<?php

require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../Auth.php';

class ExportController
{
    protected $contactModel;
    protected $noteModel;
    protected $activityModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
        $this->noteModel = new Note();
        $this->activityModel = new Activity();
    }

    /**
     * Main CSV export function - generates ZIP with two CSV files
     */
    public function exportContactsCSV()
    {
        // Only owner is allowed to export
        Auth::denyMentorWriteAccess();

        try {
            // Generate both CSV files
            $contactsCSV = $this->generateContactsCSV();
            $historyCSV = $this->generateHistoryCSV();

            // Create ZIP archive
            $zipFilename = 'kontakte_export_' . date('Y-m-d') . '.zip';
            $zipPath = sys_get_temp_dir() . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Konnte ZIP-Archiv nicht erstellen');
            }

            $zip->addFromString('kontakte.csv', $contactsCSV);
            $zip->addFromString('kontakte_historie.csv', $historyCSV);
            $zip->close();

            // Send ZIP file to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);

            // Clean up temp file
            unlink($zipPath);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo 'Fehler beim Erstellen des Exports: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Generate main contacts CSV file
     */
    protected function generateContactsCSV()
    {
        $contacts = $this->contactModel->getAllForExport();

        // UTF-8 BOM for Excel compatibility
        $csv = "\xEF\xBB\xBF";

        // Headers (Schweizer Hochdeutsch, descriptive for laypeople)
        $headers = [
            'ID',
            'Name',
            'E-Mail',
            'Telefon',
            'WhatsApp',
            'Adresse',
            'Beziehung',
            'Status',
            'Sub-Status',
            'Phase',
            'Phase Datum',
            'Phase Notizen',
            'Geburtstag',
            'Instagram',
            'TikTok',
            'Facebook',
            'LinkedIn',
            'Empfohlen durch',
            'Tags',
            'Lieblingsprodukte',
            'Letzter Kontakt',
            'Erstellt am',
            'Aktualisiert am'
        ];

        $csv .= $this->formatCSVRow($headers);

        // Data rows
        foreach ($contacts as $contact) {
            $row = [
                $contact['id'],
                $contact['name'],
                $contact['email'] ?? '',
                $contact['phone'] ?? '',
                $contact['whatsapp'] ?? '',
                $contact['address'] ?? '',
                $contact['beziehung'] ?? '',
                $contact['status'] ?? '',
                $contact['sub_status'] ?? '',
                $contact['phase'] ?? '',
                $this->formatDate($contact['phase_date'] ?? ''),
                $this->cleanText($contact['phase_notes'] ?? ''),
                $this->formatDate($contact['birthday'] ?? ''),
                $contact['social_instagram'] ?? '',
                $contact['social_tiktok'] ?? '',
                $contact['social_facebook'] ?? '',
                $contact['social_linkedin'] ?? '',
                $contact['recommended_by'] ?? '',
                $contact['tags'] ?? '',
                implode(', ', $contact['products'] ?? []),
                $this->formatDateTime($contact['last_contacted_at'] ?? ''),
                $this->formatDateTime($contact['created_at'] ?? ''),
                $this->formatDateTime($contact['updated_at'] ?? '')
            ];

            $csv .= $this->formatCSVRow($row);
        }

        return $csv;
    }

    /**
     * Generate history/timeline CSV file
     */
    protected function generateHistoryCSV()
    {
        // Get all notes and activities
        $notes = $this->noteModel->getAllWithContactName();
        $activities = $this->activityModel->getAllWithContactName();

        // Combine and sort by contact_id and date
        $historyItems = [];

        // Add notes
        foreach ($notes as $note) {
            $historyItems[] = [
                'contact_id' => $note['contact_id'],
                'contact_name' => $note['contact_name'],
                'created_at' => $note['created_at'],
                'type' => 'Notiz',
                'description' => $this->cleanText($note['content']),
                'details' => ''
            ];
        }

        // Add activities
        foreach ($activities as $activity) {
            $details = '';
            if ($activity['old_value'] || $activity['new_value']) {
                $details = trim(($activity['old_value'] ?? '') . ' → ' . ($activity['new_value'] ?? ''));
            }

            $historyItems[] = [
                'contact_id' => $activity['contact_id'],
                'contact_name' => $activity['contact_name'],
                'created_at' => $activity['created_at'],
                'type' => $this->translateActivityType($activity['type']),
                'description' => $this->cleanText($activity['description'] ?? ''),
                'details' => $details
            ];
        }

        // Sort by contact_id, then date (newest first)
        usort($historyItems, function ($a, $b) {
            if ($a['contact_id'] != $b['contact_id']) {
                return $a['contact_id'] <=> $b['contact_id'];
            }
            return $b['created_at'] <=> $a['created_at'];
        });

        // UTF-8 BOM
        $csv = "\xEF\xBB\xBF";

        // Headers
        $headers = ['Kontakt ID', 'Kontakt Name', 'Datum', 'Typ', 'Beschreibung', 'Details'];
        $csv .= $this->formatCSVRow($headers);

        // Data rows
        foreach ($historyItems as $item) {
            $row = [
                $item['contact_id'],
                $item['contact_name'],
                $this->formatDateTime($item['created_at']),
                $item['type'],
                $item['description'],
                $item['details']
            ];
            $csv .= $this->formatCSVRow($row);
        }

        return $csv;
    }

    /**
     * Format a single CSV row with proper escaping
     */
    protected function formatCSVRow($data)
    {
        $escaped = array_map(function ($field) {
            // Convert to string
            $field = (string) $field;

            // Escape quotes
            $field = str_replace('"', '""', $field);

            // Wrap in quotes
            return '"' . $field . '"';
        }, $data);

        // Use semicolon as delimiter (German/Swiss standard)
        return implode(';', $escaped) . "\r\n";
    }

    /**
     * Format date to DD.MM.YYYY
     */
    protected function formatDate($dateString)
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('d.m.Y');
        } catch (Exception $e) {
            return $dateString;
        }
    }

    /**
     * Format datetime to DD.MM.YYYY HH:mm
     */
    protected function formatDateTime($dateString)
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('d.m.Y H:i');
        } catch (Exception $e) {
            return $dateString;
        }
    }

    /**
     * Clean text for CSV (replace newlines with separators)
     */
    protected function cleanText($text)
    {
        if (empty($text)) {
            return '';
        }

        // Replace newlines with pipe separator
        $text = str_replace(["\r\n", "\n", "\r"], ' | ', $text);

        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Translate activity type to German
     */
    protected function translateActivityType($type)
    {
        $translations = [
            'status_change' => 'Status-Änderung',
            'note_added' => 'Notiz hinzugefügt',
            'product_assigned' => 'Produkt hinzugefügt',
            'product_removed' => 'Produkt entfernt',
            'contact_created' => 'Kontakt erstellt',
            'phase_change' => 'Phasen-Änderung',
            'task_created' => 'Aufgabe erstellt',
            'workflow_started' => 'Workflow gestartet',
            'workflow_completed' => 'Workflow abgeschlossen'
        ];

        return $translations[$type] ?? $type;
    }
}
