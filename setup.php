<?php
/**
 * ManagePeople V3 - Setup Wizard
 */

require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';

if (file_exists(__DIR__ . '/config.php')) {
    die("Setup bereits abgeschlossen. Bitte löschen Sie config.php, um das Setup erneut auszuführen.");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Bitte alle Felder ausfüllen.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwörter stimmen nicht überein.";
    } elseif (!Auth::validatePassword($password)) {
        $error = "Passwort unsicher: Min. 8 Zeichen, 1 Grossbuchstabe, 1 Zahl, 1 Sonderzeichen.";
    } else {
        try {
            // 1. Singleton initialisieren (erstellt DB Datei falls nötig)
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            // 2. Schema laden und ausführen
            $schemaPath = __DIR__ . '/core/schema.sql';
            if (!file_exists($schemaPath)) {
                throw new Exception("Schema Datei fehlt: $schemaPath");
            }
            $schemaSql = file_get_contents($schemaPath);

            // Split SQL by commands to handle potential multiple statements better if needed, 
            // but pdo->exec usually handles batches if the driver supports it. 
            // SQLite usually supports multiple statements in exec().
            $pdo->exec($schemaSql);

            // 3. Admin User anlegen
            Auth::register($name, $email, $password, 'owner');

            // 4. Vordefinierte Automatisierungsregeln initialisieren
            require_once __DIR__ . '/core/models/AutomationRule.php';
            require_once __DIR__ . '/core/models/Settings.php';
            $automationRuleModel = new AutomationRule();
            $automationRuleModel->seedPredefinedRules();

            // 5. Config schreiben
            $configContent = "<?php\n\n// ManagePeople V3 Configuration\ndefine('APP_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['PHP_SELF']));\ndefine('APP_START', time());\n";
            file_put_contents(__DIR__ . '/config.php', $configContent);

            // Weiterleitung
            redirect('index.php');

        } catch (Exception $e) {
            $error = "Fehler beim Setup: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManagePeople Setup</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/images/favicon.png">
    <!-- Fonts & Tailwind for generic stylish look -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#FF6B6B',
                        secondary: '#4ECDC4',
                        accent: '#9B59B6',
                        bg: '#F8F9FA',
                        text: '#2C3E50'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #F8F9FA;
            background-image: radial-gradient(#4ECDC4 1px, transparent 0);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white/90 backdrop-blur-md p-8 rounded-2xl shadow-xl border border-white/50 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="font-display text-3xl font-bold text-gray-800 mb-2">ManagePeople</h1>
            <p class="text-gray-500">Installation & Setup</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 text-sm flex items-center">
                <span class="font-bold mr-2">Fehler:</span>
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dein Name</label>
                <input type="text" name="name" required placeholder="z.B. Sämi"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                    value="<?= h($_POST['name'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail Adresse</label>
                <input type="email" name="email" required placeholder="name@example.com"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                    value="<?= h($_POST['email'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passwort</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                <p class="text-xs text-gray-400 mt-1">Min. 8 Zeichen, Großbuchstabe, Zahl, Sonderzeichen</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passwort wiederholen</label>
                <input type="password" name="password_confirm" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
            </div>

            <button type="submit"
                class="w-full bg-primary hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 mt-4">
                Installation starten
            </button>
        </form>
    </div>
</body>

</html>