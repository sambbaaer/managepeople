<?php
// helpers.php loaded previously
global $page; // Current page for active state defaults
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>
    <?= isset($pageTitle) ? htmlspecialchars($pageTitle . ' - ManagePeople') : 'ManagePeople' ?>
</title>
<meta name="theme-color" content="#FF6B6B">
<link rel="icon" type="image/png" href="assets/images/favicon.png">
<link rel="manifest" href="manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="assets/images/favicon.png">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">

<!-- Tailwind (CDN for Dev, Build for Prod - using CDN for simplicity as per instructions) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#FF6B6B',
                    secondary: '#4ECDC4',
                    accent: '#9B59B6',
                    highlight: '#FFD93D',
                    bg: '#F8F9FA',
                    text: '#2C3E50',
                    'text-light': '#95a5a6'
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                    display: ['Poppins', 'sans-serif']
                }
            }
        }
    }
</script>

<!-- Custom Styles -->
<link rel="stylesheet" href="assets/css/variables.css?v=<?= time() ?>">
<link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>