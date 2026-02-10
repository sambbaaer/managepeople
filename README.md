# ManagePeople

**Eine intuitive, selbst gehostete CRM-Lösung für die effiziente Kontaktpflege und Prozessautomatisierung.**

ManagePeople wurde entwickelt, um die Verwaltung von Kontakten, Aufgaben und Geschäftsprozessen so einfach und übersichtlich wie möglich zu gestalten. Die Applikation ist bewusst für das **Self-Hosting** optimiert, was volle Datensouveränität und Unabhängigkeit garantiert.

---

## 🚀 Kernfunktionen

### 👥 Kontaktmanagement der nächsten Generation
* **Detaillierte Profile**: Verwalten Sie nicht nur Namen, sondern auch Social-Media-Präsenzen (Instagram, TikTok, etc.) und spezifische Beziehungsstatus.
* **Interaktionsverlauf**: Ein automatisches Audit-Log (Activities) hält fest, wann was mit wem passiert ist.
* **Produkt-Verknüpfung**: Ordnen Sie Kontakten ihre Lieblingsprodukte zu – ideal für kundenorientierte Branchen.
* **Smart Lists**: Erstellen Sie dynamische Filter für Ihre Kontakte, um immer die richtige Zielgruppe im Blick zu haben.

### ⚙️ Automatisierung & Workflows
* **Prozess-Vorlagen**: Erstellen Sie komplexe, mehrstufige Workflows für wiederkehrende Abläufe.
* **Intelligente Automatisierung**: Definieren Sie Regeln, die Aktionen automatisch triggern, wenn bestimmte Bedingungen erfüllt sind.
* **Task-Management**: Integrierte Aufgabenverwaltung mit Prioritäten und direkter Kontaktverknüpfung.

### 📊 Übersicht & Analyse
* **Interaktives Dashboard**: Alle wichtigen Aktivitäten und anstehenden Aufgaben auf einen Blick.
* **Statistik-Modul**: Visualisieren Sie Ihr Wachstum und die Effektivität Ihrer Prozesse direkt in der App.
* **Kalender-Integration**: Synchronisieren Sie Ihre Termine via ICS-Feed mit externen Kalendern.

---

## 🛠 Technischer Stack

ManagePeople setzt auf bewährte, schlanke Technologien für maximale Performance und einfache Wartung:

* **Backend**: PHP (Vanilla MVC-Architektur)
* **Datenbank**: SQLite (Dateibasiert, keine komplexe DB-Einrichtung nötig)
* **Frontend**: Modernes HTML5, CSS3 und Vanilla JavaScript
* **PWA-Support**: Dank Service Worker und Manifest lässt sich die App wie eine native Anwendung auf dem Homescreen installieren.

---

## 📦 Installation (Self-Hosted)

Da ManagePeople auf SQLite basiert, ist die Installation extrem unkompliziert:

1. **Upload**: Laden Sie alle Dateien auf Ihren Webserver (PHP 8.0+ erforderlich) hoch.
2. **Berechtigungen**: Stellen Sie sicher, dass das Verzeichnis `data/` bzw. das App-Verzeichnis für den Webserver beschreibbar ist.
3. **Setup**: Rufen Sie `setup.php` in Ihrem Browser auf. Der geführte Prozess erstellt die Datenbankstruktur und den ersten Benutzeraccount.
4. **Schutz**: Aus Sicherheitsgründen wird empfohlen, die SQLite-Datenbank ausserhalb des `public_html`-Ordners zu lagern (konfigurierbar).

---

## 🔒 Sicherheit & Komfort

* **Datenschutz**: Ihre Daten verlassen nie Ihren Server. Keine Cloud-Zwänge, kein Tracking.
* **Backups**: Integriertes Backup-System für die SQLite-Datenbank.
* **Rollenkonzept**: Unterscheidung zwischen Besitzer (Owner) und Mentoren mit eingeschränkten Rechten.
* **Verschlüsselung**: Passwörter werden sicher mittels BCrypt gehasht.

---

## 📄 Lizenz

Dieses Projekt ist unter der **MIT Lizenz** lizenziert. Weitere Details finden Sie in der [LICENSE](LICENSE) Datei.
