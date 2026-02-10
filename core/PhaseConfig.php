<?php

/**
 * PhaseConfig
 * 
 * Zentrale Konfiguration aller Phasen pro Status.
 * Phasen sind der zeitliche Fortschritt innerhalb eines Status,
 * getrennt von Sub-Status (Interessensgebiete etc.).
 */
class PhaseConfig
{
    /**
     * Phasen-Definitionen pro Status
     * 
     * Format:
     * 'Phase-Name' => [
     *     'description' => Kurzbeschreibung,
     *     'requires_date' => true/false - Ob ein Datum erforderlich ist,
     *     'date_label' => Label für das Datum-Feld,
     *     'date_format' => 'month_year' oder 'full_date',
     *     'auto_todo' => ToDo-Titel der automatisch erstellt wird (oder null),
     *     'todo_days_offset' => Tage bis Fälligkeit (relativ zum Phase-Datum oder heute),
     *     'icon' => Lucide-Icon Name
     * ]
     */
    const PHASES = [
        'Offen' => [
            'Vorgemerkt' => [
                'description' => 'Für später vorgemerkt, kein akuter Handlungsbedarf',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => null,
                'todo_days_offset' => 0,
                'icon' => 'bookmark'
            ],
            'Geplant' => [
                'description' => 'Kontaktaufnahme geplant für bestimmten Monat',
                'requires_date' => true,
                'date_label' => 'Geplant für',
                'date_format' => 'month_year',
                'auto_todo' => 'Kontakt aufnehmen',
                'todo_days_offset' => 0, // Am Anfang des geplanten Monats
                'icon' => 'calendar-clock'
            ],
            'Angefragt' => [
                'description' => 'Kontaktiert, warte auf Rückmeldung',
                'requires_date' => true,
                'date_label' => 'Angefragt am',
                'date_format' => 'full_date',
                'auto_todo' => 'Auf Rückmeldung prüfen',
                'todo_days_offset' => 7, // 7 Tage nach Anfrage nachhaken
                'icon' => 'send'
            ]
        ],
        'Interessent' => [
            'Erstgespräch ausstehend' => [
                'description' => 'Warte auf erstes Gespräch',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Erstgespräch vereinbaren',
                'todo_days_offset' => 3,
                'icon' => 'message-circle'
            ],
            'Feedback ausstehend' => [
                'description' => 'Warte auf Rückmeldung nach Gespräch',
                'requires_date' => true,
                'date_label' => 'Kontaktiert am',
                'date_format' => 'full_date',
                'auto_todo' => 'Nachhaken',
                'todo_days_offset' => 5,
                'icon' => 'clock'
            ],
            'Nachhaken' => [
                'description' => 'Erneuter Kontaktversuch nötig',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Erneut kontaktieren',
                'todo_days_offset' => 3,
                'icon' => 'repeat'
            ]
        ],
        'Kundin' => [
            'Aktiv' => [
                'description' => 'Nutzt Produkte regelmässig',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Business-Interesse nachfragen',
                'todo_days_offset' => 365, // Nach 1 Jahr
                'icon' => 'heart'
            ],
            'Business Interesse' => [
                'description' => 'Zeigt Interesse am Business',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Partner-Vorbereitung',
                'todo_days_offset' => 14,
                'icon' => 'trending-up'
            ],
            '1x Nein' => [
                'description' => 'Erstes Nein zu Business',
                'requires_date' => true,
                'date_label' => 'Nein gesagt am',
                'date_format' => 'full_date',
                'auto_todo' => 'Erneut nachfragen',
                'todo_days_offset' => 180, // In 6 Monaten
                'icon' => 'minus-circle'
            ],
            '2x Nein' => [
                'description' => 'Zweites Nein zu Business',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => null, // Keine weitere Aktion
                'todo_days_offset' => 0,
                'icon' => 'x-circle'
            ]
        ],
        'Partnerin' => [
            'Aktiv mit Team' => [
                'description' => 'Hat bereits ein Team aufgebaut',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => null,
                'todo_days_offset' => 0,
                'icon' => 'users'
            ],
            'Aktiv ohne Team' => [
                'description' => 'Baut gerade auf',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Coaching-Gespräch',
                'todo_days_offset' => 30,
                'icon' => 'user'
            ],
            'Hat Potenzial' => [
                'description' => 'Fokus für Coaching',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Potenzial-Coaching',
                'todo_days_offset' => 14,
                'icon' => 'sparkles'
            ],
            'Inaktiv' => [
                'description' => 'Momentan nicht aktiv',
                'requires_date' => true,
                'date_label' => 'Inaktiv seit',
                'date_format' => 'full_date',
                'auto_todo' => 'Nachfragen wegen Inaktivität',
                'todo_days_offset' => 180, // 6 Monate
                'icon' => 'pause-circle'
            ]
        ],
        'Stillgelegt' => [
            'Kein Interesse' => [
                'description' => 'Aktuell kein Interesse',
                'requires_date' => false,
                'date_label' => null,
                'date_format' => null,
                'auto_todo' => 'Rückfrage nach Interesse',
                'todo_days_offset' => 730, // 2 Jahre
                'icon' => 'pause'
            ]
        ],
        'Abgeschlossen' => []
    ];

    /**
     * Holt alle Phasen für einen gegebenen Status
     */
    public static function getPhasesForStatus(string $status): array
    {
        return self::PHASES[$status] ?? [];
    }

    /**
     * Holt die Konfiguration einer bestimmten Phase
     */
    public static function getPhaseConfig(string $status, string $phase): ?array
    {
        $phases = self::PHASES[$status] ?? [];
        return $phases[$phase] ?? null;
    }

    /**
     * Prüft ob eine Phase ein Datum benötigt
     */
    public static function phaseRequiresDate(string $status, string $phase): bool
    {
        $config = self::getPhaseConfig($status, $phase);
        return $config['requires_date'] ?? false;
    }

    /**
     * Holt das Datum-Label für eine Phase
     */
    public static function getDateLabel(string $status, string $phase): ?string
    {
        $config = self::getPhaseConfig($status, $phase);
        return $config['date_label'] ?? null;
    }

    /**
     * Holt das Datum-Format für eine Phase
     */
    public static function getDateFormat(string $status, string $phase): ?string
    {
        $config = self::getPhaseConfig($status, $phase);
        return $config['date_format'] ?? null;
    }

    /**
     * Generiert das automatische ToDo für eine Phase
     * 
     * @param string $status Haupt-Status
     * @param string $phase Phase innerhalb des Status
     * @param string|null $phaseDate Datum der Phase (falls vorhanden)
     * @return array|null ToDo-Daten oder null
     */
    public static function generateAutoTodo(string $status, string $phase, ?string $phaseDate = null): ?array
    {
        $config = self::getPhaseConfig($status, $phase);

        if (!$config || !$config['auto_todo']) {
            return null;
        }

        // Fälligkeitsdatum berechnen
        $baseDate = $phaseDate ?? date('Y-m-d');

        // Bei month_year Format: Ersten des Monats nehmen
        if ($config['date_format'] === 'month_year' && $phaseDate) {
            $baseDate = date('Y-m-01', strtotime($phaseDate));
        }

        $dueDate = null;
        if ($config['todo_days_offset'] > 0) {
            $dueDate = date('Y-m-d', strtotime($baseDate . ' + ' . $config['todo_days_offset'] . ' days'));
        } elseif ($phaseDate) {
            $dueDate = $baseDate;
        } else {
            $dueDate = date('Y-m-d');
        }

        return [
            'title' => $config['auto_todo'],
            'description' => 'Automatisch erstellt für Phase "' . $phase . '"',
            'due_date' => $dueDate,
            'priority' => 'normal',
            'auto_generated' => 'phase_change',
            'triggered_by_phase' => $phase
        ];
    }

    /**
     * Formatiert ein Datum nach dem Phase-Format
     */
    public static function formatPhaseDate(string $status, string $phase, ?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $format = self::getDateFormat($status, $phase);
        $timestamp = strtotime($date);

        switch ($format) {
            case 'month_year':
                // Deutsche Monatsanzeige
                $months = [
                    1 => 'Januar',
                    2 => 'Februar',
                    3 => 'März',
                    4 => 'April',
                    5 => 'Mai',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'August',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Dezember'
                ];
                $month = (int) date('n', $timestamp);
                return $months[$month] . ' ' . date('Y', $timestamp);

            case 'full_date':
                return date('d.m.Y', $timestamp);

            default:
                return date('d.m.Y', $timestamp);
        }
    }

    /**
     * Hilfsmethode: Alle Phasen als flache Liste
     */
    public static function getAllPhases(): array
    {
        $all = [];
        foreach (self::PHASES as $status => $phases) {
            foreach ($phases as $phaseName => $config) {
                $all[] = [
                    'status' => $status,
                    'phase' => $phaseName,
                    'config' => $config
                ];
            }
        }
        return $all;
    }
}
