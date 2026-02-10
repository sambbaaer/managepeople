<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen - ManagePeople</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/images/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
    <div class="bg-white/90 backdrop-blur-md p-8 rounded-2xl shadow-xl border border-white/50 w-full max-w-sm text-center">
        <div class="mb-6">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="key-round" class="w-8 h-8 text-primary"></i>
            </div>
            <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Passwort vergessen?</h1>
            <p class="text-gray-500 text-sm">Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm flex items-center justify-center">
                <i data-lucide="alert-circle" class="w-4 h-4 mr-2 shrink-0"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-lg mb-4 text-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 mx-auto mb-2"></i>
                <p class="font-medium">E-Mail gesendet!</p>
                <p class="text-xs text-green-500 mt-1">Falls die Adresse registriert ist, erhältst du in Kürze einen Link zum Zurücksetzen. Prüfe auch den Spam-Ordner.</p>
            </div>
        <?php else: ?>
            <form method="post" class="space-y-4 text-left">
                <div>
                    <input type="email" name="email" required placeholder="Deine E-Mail-Adresse"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <button type="submit"
                    class="w-full bg-primary hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                    Link senden
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-6">
            <a href="index.php?page=login" class="text-sm text-secondary hover:underline font-medium">
                Zurück zum Login
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>

</html>
