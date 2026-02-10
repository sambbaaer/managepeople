# Datenbank Struktur & Entwickler Dokumentation

Dieses Dokument beschreibt das Datenbankschema, welches für **ManagePeople V3** verwendet wird.
Die Datenbank ist eine **SQLite** Datei, standardmäßig abgelegt unter `data/managepeople.db`.

## Installation
Bei einer Neuinstallation wird die Datenbankstruktur automatisch durch `setup.php` angelegt, welches die `core/schema.sql` Datei einliest und ausführt.

## Tabellen Übersicht

### 1. `users`
Verwaltet die Login-Benutzer der Applikation.
- `id`: Primärschlüssel
- `name`: Anzeigename
- `email`: Login-Email (Eindeutig)
- `password_hash`: BCrypt Hash des Passworts
- `role`: Rolle (z.B. 'owner')
- `created_at`: Erstellungsdatum

### 2. `contacts`
Die zentrale Tabelle für alle Kontakte.
- `status`: Der Haupt-Status (Offen, Interessent, Kundin, Partnerin, Stillgelegt).
- `sub_status`: Verfeinerung des Status.
- `last_contacted_at`: Wird bei Interaktionen aktualisiert, um vernachlässigte Kontakte zu finden.
- `social_*`: Felder für Social Media Usernames (Instagram, TikTok, etc.).

### 3. `notes`
Enthält Notizen zu Kontakten. (1:n Beziehung zu Contacts).
- `content`: Inhalt der Notiz.

### 4. `activities`
Ein Audit-Log für Änderungen am Kontakt (Statuswechsel, Notizerstellung, etc.).
- `type`: Art der Änderung (z.B. `status_change`).
- `old_value` / `new_value`: Für Historie.

### 5. `tasks`
Aufgabenverwaltung. Kann einem Kontakt zugeordnet sein, muss aber nicht.
- `priority`: `normal`, `high`, `urgent`.
- `due_date`: Fälligkeitsdatum.

### 6. `products`
Produktkatalog (z.B. Ringana Produkte).
- `imported_id`: ID aus dem externen JSON Import.
- `image_url` / `product_url`: Links zu Bildern/Shop.

### 7. `contact_products`
Verknüpfungstabelle (n:m) für Lieblingsprodukte von Kontakten.
- Verknüpft `contact_id` und `product_id`.

### 8. `smart_lists`
Gespeicherte Filter für die Kontaktliste.
- `filter_criteria`: JSON String mit Filterlogik (z.B. `{"status":"Partnerin"}`).

### 9. `settings`
Key-Value Store für Anwendungseinstellungen.
- `backup_enabled`, `last_backup`, etc.

---

## Hinweise für Entwickler

- **Dateipfade**: Die SQLite DB liegt aus Sicherheitsgründen idealerweise ausserhalb des `public_html/` Ordners oder in einem geschützten Verzeichnis. In der Standardkonfig liegt sie in `../data/`.
- **Anpassungen**: Änderungen am Schema sollten in `core/schema.sql` nachgetragen werden. Für bestehende Installationen sind Migration-Scripts nötig.
- **ORM**: Es wird kein volles ORM verwendet, sondern einfache Model-Klassen (`core/models/`), welche `Database.php` nutzen.
