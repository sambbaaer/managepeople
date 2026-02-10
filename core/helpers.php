<?php

function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header("Location: " . $path);
    exit;
}

function asset($path)
{
    // Basic asset helper, can be improved to handle base URL
    return 'assets/' . ltrim($path, '/');
}

function url($path = '')
{
    // Should return absolute or relative URL based on config
    // For now, relative to root could work if index.php handles routing cleanly
    return '/' . ltrim($path, '/');
}

// Doodle Icon Helper (from Dummy)
function getDoodle($name, $class = "")
{
    $basePath = __DIR__ . "/../assets/icons/";
    $filePath = $basePath . $name . "_doodle.svg";

    if (file_exists($filePath)) {
        $svg = file_get_contents($filePath);
        $svg = preg_replace('/<\?xml.*\?>/i', '', $svg);
        if ($class) {
            $svg = str_replace('<svg ', '<svg class="' . $class . '" ', $svg);
        }
        return $svg;
    }
    return "<!-- Doodle $name nicht gefunden -->";
}

function getOffset($current, $total)
{
    $circumference = 226;
    if ($total <= 0)
        return $circumference;
    $percentage = $current / $total;
    return $circumference * (1 - $percentage);
}

function getSocialUrl($platform, $handle)
{
    $handle = trim($handle);
    if (empty($handle))
        return '';
    if (filter_var($handle, FILTER_VALIDATE_URL))
        return $handle;

    $handle = ltrim($handle, '@');
    switch ($platform) {
        case 'instagram':
            return "https://ig.me/m/$handle";
        case 'tiktok':
            return "https://www.tiktok.com/@$handle"; // No official DM link API known
        case 'facebook':
            return "https://m.me/$handle";
        case 'linkedin':
            return "https://www.linkedin.com/in/$handle";
        default:
            return $handle;
    }
}
