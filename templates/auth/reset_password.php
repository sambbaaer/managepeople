<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen - ManagePeople</title>
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

        <?php if (!empty($success)): ?>
            <!-- Success State -->
            <div class="mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="check-circle-2" class="w-8 h-8 text-green-600"></i>
                </div>
                <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Passwort geändert!</h1>
                <p class="text-gray-500 text-sm">Dein Passwort wurde erfolgreich zurückgesetzt. Du kannst dich jetzt anmelden.</p>
            </div>
            <a href="index.php?page=login"
                class="block w-full bg-primary hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 no-underline">
                Zum Login
            </a>

        <?php elseif (!empty($invalid)): ?>
            <!-- Invalid/Expired Token -->
            <div class="mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="x-circle" class="w-8 h-8 text-red-500"></i>
                </div>
                <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Link ungültig</h1>
                <p class="text-gray-500 text-sm">Dieser Link ist abgelaufen oder ungültig. Bitte fordere einen neuen an.</p>
            </div>
            <a href="index.php?page=forgot_password"
                class="block w-full bg-primary hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 no-underline">
                Neuen Link anfordern
            </a>

        <?php else: ?>
            <!-- Reset Form -->
            <div class="mb-6">
                <div class="w-16 h-16 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="lock-keyhole" class="w-8 h-8 text-secondary"></i>
                </div>
                <h1 class="font-display text-2xl font-bold text-gray-800 mb-2">Neues Passwort</h1>
                <p class="text-gray-500 text-sm">Wähle ein sicheres neues Passwort.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-4 h-4 mr-2 shrink-0"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4 text-left">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Neues Passwort</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary focus:border-transparent outline-none transition-all">
                    <p class="text-[10px] text-gray-400 mt-1">Min. 8 Zeichen, Grossbuchstabe, Zahl, Sonderzeichen</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Passwort wiederholen</label>
                    <input type="password" name="password_confirm" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-secondary focus:border-transparent outline-none transition-all">
                </div>

                <button type="submit"
                    class="w-full bg-secondary hover:bg-teal-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                    Passwort speichern
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
