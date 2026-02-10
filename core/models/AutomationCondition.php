<?php

require_once __DIR__ . '/../Database.php';

/**
 * AutomationCondition Model
 * 
 * Verwaltet Bedingungen für Custom-Automatisierungen
 * Unterstützt Wenn/Und/Oder-Logik
 */
class AutomationCondition
{
    protected $db;

    // Verfügbare Felder für Bedingungen
    public const AVAILABLE_FIELDS = [
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => ['Offen', 'Interessent', 'Kundin', 'Partnerin', 'Stillgelegt', 'Abgeschlossen']
        ],
        'phase' => [
            'label' => 'Phase',
            'type' => 'text',
            'placeholder' => 'z.B. Vorgemerkt, Geplant'
        ],
        'beziehung' => [
            'label' => 'Beziehung',
            'type' => 'select',
            'options' => ['Familie', 'Freundin', 'Bekannte', 'Arbeitskollegin', 'Kundin', 'Sonstige']
        ],
        'tag' => [
            'label' => 'Tag',
            'type' => 'text',
            'placeholder' => 'Tag-Name'
        ],
        'product' => [
            'label' => 'Produkt',
            'type' => 'text',
            'placeholder' => 'Produktname'
        ],
        'birthday_month' => [
            'label' => 'Geburtsmonat',
            'type' => 'select',
            'options' => [
                '1' => 'Januar',
                '2' => 'Februar',
                '3' => 'März',
                '4' => 'April',
                '5' => 'Mai',
                '6' => 'Juni',
                '7' => 'Juli',
                '8' => 'August',
                '9' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Dezember'
            ]
        ],
        'last_contacted_days' => [
            'label' => 'Tage seit letztem Kontakt',
            'type' => 'number',
            'placeholder' => 'Anzahl Tage'
        ],
        'created_days' => [
            'label' => 'Tage seit Erstellung',
            'type' => 'number',
            'placeholder' => 'Anzahl Tage'
        ]
    ];

    // Verfügbare Vergleichsoperatoren
    public const COMPARISONS = [
        'equals' => 'ist gleich',
        'not_equals' => 'ist nicht gleich',
        'contains' => 'enthält',
        'not_contains' => 'enthält nicht',
        'greater_than' => 'grösser als',
        'less_than' => 'kleiner als',
        'is_empty' => 'ist leer',
        'is_not_empty' => 'ist nicht leer'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Alle Bedingungen einer Regel abrufen
     */
    public function getByRuleId($ruleId)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM automation_conditions 
            WHERE rule_id = ? 
            ORDER BY sort_order ASC
        ');
        $stmt->execute([$ruleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Bedingung erstellen
     */
    public function create($data)
    {
        $stmt = $this->db->prepare('
            INSERT INTO automation_conditions (rule_id, group_id, operator, field, comparison, value, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['rule_id'],
            $data['group_id'] ?? 0,
            $data['operator'] ?? 'AND',
            $data['field'],
            $data['comparison'],
            $data['value'],
            $data['sort_order'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Bedingung aktualisieren
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach (['group_id', 'operator', 'field', 'comparison', 'value', 'sort_order'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $stmt = $this->db->prepare('UPDATE automation_conditions SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($values);
    }

    /**
     * Bedingung löschen
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare('DELETE FROM automation_conditions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Alle Bedingungen einer Regel löschen
     */
    public function deleteByRuleId($ruleId)
    {
        $stmt = $this->db->prepare('DELETE FROM automation_conditions WHERE rule_id = ?');
        return $stmt->execute([$ruleId]);
    }

    /**
     * Bedingungen für einen Kontakt evaluieren
     * 
     * @param array $conditions Array von Bedingungen
     * @param array $contact Kontakt-Daten
     * @return bool True wenn alle Bedingungen erfüllt sind
     */
    public function evaluate($conditions, $contact)
    {
        if (empty($conditions)) {
            return true; // Keine Bedingungen = immer wahr
        }

        $result = null;

        foreach ($conditions as $index => $condition) {
            $fieldValue = $this->getFieldValue($contact, $condition['field']);
            $conditionMet = $this->compareValues($fieldValue, $condition['comparison'], $condition['value']);

            if ($index === 0) {
                $result = $conditionMet;
            } else {
                // Operator bezieht sich auf die Verknüpfung zur VORHERIGEN Bedingung
                $prevOperator = $conditions[$index - 1]['operator'] ?? 'AND';

                if ($prevOperator === 'OR') {
                    $result = $result || $conditionMet;
                } else {
                    $result = $result && $conditionMet;
                }
            }
        }

        return $result;
    }

    /**
     * Feldwert aus Kontakt extrahieren
     */
    protected function getFieldValue($contact, $field)
    {
        switch ($field) {
            case 'status':
                return $contact['status'] ?? '';
            case 'phase':
                return $contact['phase'] ?? '';
            case 'beziehung':
                return $contact['beziehung'] ?? '';
            case 'tag':
                // Tags müssen separat geladen werden
                return $contact['tags'] ?? [];
            case 'product':
                // Produkte müssen separat geladen werden
                return $contact['products'] ?? [];
            case 'birthday_month':
                if (!empty($contact['birthday'])) {
                    return date('n', strtotime($contact['birthday']));
                }
                return null;
            case 'last_contacted_days':
                if (!empty($contact['last_contacted_at'])) {
                    $last = new DateTime($contact['last_contacted_at']);
                    $now = new DateTime();
                    return $now->diff($last)->days;
                }
                return null;
            case 'created_days':
                if (!empty($contact['created_at'])) {
                    $created = new DateTime($contact['created_at']);
                    $now = new DateTime();
                    return $now->diff($created)->days;
                }
                return null;
            default:
                return $contact[$field] ?? '';
        }
    }

    /**
     * Werte vergleichen basierend auf Operator
     */
    protected function compareValues($fieldValue, $comparison, $compareValue)
    {
        switch ($comparison) {
            case 'equals':
                if (is_array($fieldValue)) {
                    return in_array($compareValue, $fieldValue);
                }
                return strtolower(trim($fieldValue)) === strtolower(trim($compareValue));

            case 'not_equals':
                if (is_array($fieldValue)) {
                    return !in_array($compareValue, $fieldValue);
                }
                return strtolower(trim($fieldValue)) !== strtolower(trim($compareValue));

            case 'contains':
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $val) {
                        if (stripos($val, $compareValue) !== false) {
                            return true;
                        }
                    }
                    return false;
                }
                return stripos($fieldValue, $compareValue) !== false;

            case 'not_contains':
                if (is_array($fieldValue)) {
                    foreach ($fieldValue as $val) {
                        if (stripos($val, $compareValue) !== false) {
                            return false;
                        }
                    }
                    return true;
                }
                return stripos($fieldValue, $compareValue) === false;

            case 'greater_than':
                return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue > $compareValue;

            case 'less_than':
                return is_numeric($fieldValue) && is_numeric($compareValue) && $fieldValue < $compareValue;

            case 'is_empty':
                return empty($fieldValue);

            case 'is_not_empty':
                return !empty($fieldValue);

            default:
                return false;
        }
    }

    /**
     * Verfügbare Felder für UI abrufen
     */
    public static function getAvailableFields()
    {
        return self::AVAILABLE_FIELDS;
    }

    /**
     * Verfügbare Vergleichsoperatoren für UI abrufen
     */
    public static function getComparisons()
    {
        return self::COMPARISONS;
    }

    /**
     * Einzelne Bedingung gegen einen Kontakt auswerten (für externe Nutzung)
     * 
     * @param array $condition Einzelne Bedingung mit field, comparison, value
     * @param array $contact Kontakt-Daten
     * @return bool True wenn Bedingung erfüllt
     */
    public function evaluateSingle($condition, $contact)
    {
        $fieldValue = $this->getFieldValue($contact, $condition['field']);
        return $this->compareValues($fieldValue, $condition['comparison'], $condition['value']);
    }
}

