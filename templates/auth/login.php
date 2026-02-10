<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ManagePeople</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/images/favicon.png">
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
    <div
        class="bg-white/90 backdrop-blur-md p-8 rounded-2xl shadow-xl border border-white/50 w-full max-w-sm text-center">
        <div class="mb-8">
            <h1 class="font-display text-3xl font-bold text-gray-800 mb-2">Login</h1>
            <p class="text-gray-500">Willkommen zurück</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-sm">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div class="text-left">
                <input type="email" name="email" required placeholder="E-Mail Adresse"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                    value="<?= h($_POST['email'] ?? '') ?>">
            </div>

            <div class="text-left">
                <input type="password" name="password" required placeholder="Passwort"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
            </div>

            <button type="submit"
                class="w-full bg-primary hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 mt-2">
                Anmelden
            </button>
        </form>

        <div class="mt-4">
            <a href="index.php?page=forgot_password" class="text-sm text-gray-400 hover:text-primary transition-colors">
                Passwort vergessen?
            </a>
        </div>
    </div>
</body>

</html>