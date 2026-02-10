<?php
// Statistics Template
$kpis = $data['kpis'];
$statusStats = $data['status_stats'];
$growthStats = $data['growth_stats']; // keys: month, count
$topProducts = $data['top_products'];
$activityDist = $data['activity_dist']; // keys: recent, quarter, old, never
$totalContacts = $kpis['total_contacts'];
$monthStats = $data['month_stats'] ?? []; // Current month activities
$relationshipStats = $data['relationship_stats'] ?? []; // Relationship distribution

// --- Helper Functions for SVG Charts ---

function generateAreaPath($stats, $width, $height)
{
    if (empty($stats))
        return "";

    $max = 0;
    foreach ($stats as $s)
        $max = max($max, $s['count']);
    if ($max == 0)
        $max = 10; // Avoid division by zero

    $points = [];
    $stepX = $width / (count($stats) - 1);

    foreach ($stats as $i => $s) {
        $x = $i * $stepX;
        $y = $height - (($s['count'] / $max) * ($height * 0.8)); // Leave 20% top padding
        $points[] = "$x,$y";
    }

    return implode(" ", $points);
}

// Donut Chart Segments
$totalStatus = 0;
foreach ($statusStats as $s)
    $totalStatus += $s['count'];
$donutSegments = [];
$currentAngle = 0;
$colors = ['#FF6B6B', '#4ECDC4', '#ffe66d', '#1a535c', '#ff9f1c', '#2ec4b6'];

foreach ($statusStats as $i => $s) {
    if ($totalStatus == 0)
        break;
    $percentage = $s['count'] / $totalStatus;
    $dashArray = 2 * M_PI * 40; // r=40
    $dashOffset = $dashArray - ($percentage * $dashArray);

    $donutSegments[] = [
        'color' => $colors[$i % count($colors)],
        'offset' => -$currentAngle, // SVG rotates clockwise, stroke-dashoffset needs negative
        'value' => $percentage * 100, // For display
        'label' => $s['status'], // For display
        'count' => $s['count']
    ];
    // This logic for CSS donut is tricky with stroke-dasharray.
    // Simpler approach: conic-gradient in CSS! much easier.
}

// Conic Gradient String Construction
$conicGradient = "conic-gradient(";
$deg = 0;
foreach ($statusStats as $i => $s) {
    if ($totalStatus == 0)
        break;
    $pct = ($s['count'] / $totalStatus) * 100;
    $endDeg = $deg + ($pct * 3.6); // 360 / 100
    $color = $colors[$i % count($colors)];
    $conicGradient .= "$color {$deg}deg {$endDeg}deg, ";
    $deg = $endDeg;
}
$conicGradient = rtrim($conicGradient, ", ") . ")";

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <?php include __DIR__ . '/layout/head.php'; ?>
    <style>
        .stat-card:hover {
            transform: translateY(-3px);
        }

        .chart-tooltip {
            visibility: hidden;
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 100;
        }

        .chart-point:hover+.chart-tooltip {
            visibility: visible;
        }
    </style>
</head>

<body class="min-h-screen flex bg-gray-50">

    <?php include __DIR__ . '/layout/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto mb-16 md:mb-0">

        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold font-display text-gray-800">Statistik</h1>
                <p class="text-gray-500 mt-1">Deine Geschäftszahlen im Überblick</p>
            </div>

            <!-- Desktop Only Badge -->
            <div
                class="hidden md:flex bg-secondary/10 text-secondary px-3 py-1 rounded-full text-xs font-semibold items-center">
                <i data-lucide="monitor" class="w-3 h-3 mr-2"></i> Desktop View
            </div>
        </header>

        <!-- KPI ROW -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Totals -->
            <div class="glass-card p-6 stat-card transition-all duration-300 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Kontakte Gesamt</p>
                    <h2 class="text-4xl font-bold text-gray-800 font-display">
                        <?= $kpis['total_contacts'] ?>
                    </h2>
                    <div class="mt-2 text-xs text-secondary font-medium flex items-center">
                        <i data-lucide="users" class="w-3 h-3 mr-1"></i> Aktive Datenbank
                    </div>
                </div>
                <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-24 h-24 -right-6 -bottom-6 opacity-20 rotate-12'); ?>
            </div>

            <!-- Conversion -->
            <div class="glass-card p-6 stat-card transition-all duration-300 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Conversion Rate</p>
                    <h2 class="text-4xl font-bold text-gray-800 font-display">
                        <?= $kpis['conversion_rate'] ?>%
                    </h2>
                    <div class="mt-2 text-xs text-green-500 font-medium flex items-center">
                        <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Kunden & Partner
                    </div>
                </div>
                <?php echo getDoodle('Blume1', 'doodle doodle-teal w-20 h-20 -right-4 -top-2 opacity-10 rotate-12'); ?>
            </div>

            <!-- Tasks -->
            <div class="glass-card p-6 stat-card transition-all duration-300 relative overflow-hidden">
                <div class="relative z-10">
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">Offene Aufgaben</p>
                    <h2 class="text-4xl font-bold text-gray-800 font-display">
                        <?= $kpis['open_tasks'] ?>
                    </h2>
                    <div class="mt-2 text-xs text-orange-500 font-medium flex items-center">
                        <i data-lucide="list-todo" class="w-3 h-3 mr-1"></i> Zu erledigen
                    </div>
                </div>
                <?php echo getDoodle('Aufgaben', 'doodle doodle-coral w-20 h-20 -right-2 -bottom-2 opacity-10 -rotate-12'); ?>
            </div>
        </div>

        <!-- CHARTS ROW 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Growth Chart (Area) - takes 2 cols -->
            <div class="glass-card p-6 lg:col-span-2 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="bar-chart-2" class="w-5 h-5 mr-2 text-primary"></i> Wachstum
                </h3>

                <div class="relative h-64 w-full">
                    <!-- SVG Chart -->
                    <svg viewBox="0 0 1000 300" class="w-full h-full" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid Lines -->
                        <line x1="0" y1="0" x2="1000" y2="0" stroke="#f3f4f6" stroke-width="2" />
                        <line x1="0" y1="75" x2="1000" y2="75" stroke="#f3f4f6" stroke-width="2" />
                        <line x1="0" y1="150" x2="1000" y2="150" stroke="#f3f4f6" stroke-width="2" />
                        <line x1="0" y1="225" x2="1000" y2="225" stroke="#f3f4f6" stroke-width="2" />
                        <line x1="0" y1="300" x2="1000" y2="300" stroke="#e5e7eb" stroke-width="2" />

                        <!-- Area Path -->
                        <?php
                        $pathData = "";
                        $points = [];
                        $maxVal = 0;
                        foreach ($growthStats as $g)
                            $maxVal = max($maxVal, $g['count']);
                        if ($maxVal == 0)
                            $maxVal = 5;

                        $width = 1000;
                        $height = 300;
                        $step = $width / (count($growthStats) - 1);

                        foreach ($growthStats as $i => $g) {
                            $x = $i * $step;
                            $y = $height - (($g['count'] / $maxVal) * ($height * 0.8)); // 80% height usage
                            $points[] = "$x,$y";
                        }
                        $polyline = implode(" ", $points);

                        // Close the path for fill
                        if (!empty($points)) {
                            $fillPath = "0,$height " . $polyline . " $width,$height";
                        } else {
                            $fillPath = "";
                        }
                        ?>

                        <polygon points="<?= $fillPath ?>" fill="rgba(78, 205, 196, 0.1)" />
                        <polyline points="<?= $polyline ?>" fill="none" stroke="#4ECDC4" stroke-width="4"
                            stroke-linecap="round" stroke-linejoin="round" />

                        <!-- Dots -->
                        <?php foreach ($growthStats as $i => $g):
                            $x = $i * $step;
                            $y = $height - (($g['count'] / $maxVal) * ($height * 0.8));
                            ?>
                            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="6" fill="white" stroke="#4ECDC4" stroke-width="3"
                                class="chart-point transition-all hover:r-8 cursor-pointer" />
                            <foreignObject x="<?= $x - 20 ?>" y="<?= $y - 40 ?>" width="60" height="30"
                                class="chart-tooltip">
                                <div class="text-center font-bold">
                                    <?= $g['count'] ?>
                                </div>
                            </foreignObject>
                        <?php endforeach; ?>
                    </svg>

                    <!-- X-Axis Labels -->
                    <div class="relative mt-2 text-xs text-gray-400 font-medium uppercase tracking-wider"
                        style="height: 20px;">
                        <?php
                        $labelWidth = 1000;
                        $labelStep = $labelWidth / (count($growthStats) - 1);
                        foreach ($growthStats as $i => $g):
                            $labelX = $i * $labelStep;
                            // Convert SVG coordinate to percentage
                            $labelPercent = ($labelX / $labelWidth) * 100;
                            ?>
                            <div class="absolute text-center"
                                style="left: <?= $labelPercent ?>%; transform: translateX(-50%);">
                                <?= $g['month'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Status Distribution (Donut) -->
            <div class="glass-card p-6 relative overflow-hidden flex flex-col items-center justify-center">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display self-start flex items-center">
                    <i data-lucide="pie-chart" class="w-5 h-5 mr-2 text-secondary"></i> Status
                </h3>

                <div class="relative w-48 h-48 rounded-full mb-6 shadow-inner"
                    style="background: <?= $conicGradient ?>">
                    <!-- Inner Circle for Donut -->
                    <div
                        class="absolute inset-4 bg-white rounded-full shadow-sm flex items-center justify-center flex-col">
                        <span class="text-3xl font-bold text-gray-700 font-display">
                            <?= $totalStatus ?>
                        </span>
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Kontakte</span>
                    </div>
                </div>

                <div class="w-full space-y-2">
                    <?php foreach ($statusStats as $i => $s):
                        $pct = round(($s['count'] / $totalStatus) * 100);
                        $color = $colors[$i % count($colors)];
                        ?>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $color ?>"></div>
                                <span class="text-gray-600 font-medium">
                                    <?= htmlspecialchars($s['status']) ?>
                                </span>
                            </div>
                            <span class="font-bold text-gray-700">
                                <?= $pct ?>%
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ROW 2 - Additional Stats-->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Current Month Activity -->
            <div class="glass-card p-6 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="calendar-check" class="w-5 h-5 mr-2 text-blue-500"></i> Dieser Monat
                </h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Neue Kontakte</span>
                        <span class="text-2xl font-bold text-blue-600 font-display">
                            <?= $monthStats['new_contacts'] ?? 0 ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Abgeschlossene Aufgaben</span>
                        <span class="text-2xl font-bold text-green-600 font-display">
                            <?= $monthStats['completed_tasks'] ?? 0 ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Interaktionen</span>
                        <span class="text-2xl font-bold text-purple-600 font-display">
                            <?= $monthStats['interactions'] ?? 0 ?>
                        </span>
                    </div>
                </div>
                <?php echo getDoodle('Kalender', 'doodle doodle-blue w-20 h-20 -right-4 -bottom-4 opacity-15'); ?>
            </div>

            <!-- Relationship Distribution -->
            <div class="glass-card p-6 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="heart" class="w-5 h-5 mr-2 text-pink-500"></i> Beziehungen
                </h3>

                <?php if (!empty($relationshipStats)): ?>
                    <div class="space-y-3">
                        <?php
                        $relColors = [
                            'Partnerin' => 'text-pink-600 bg-pink-50',
                            'Kundin' => 'text-purple-600 bg-purple-50',
                            'Interessentin' => 'text-blue-600 bg-blue-50',
                            'Bekannte' => 'text-teal-600 bg-teal-50',
                            'Familie' => 'text-red-600 bg-red-50'
                        ];
                        foreach ($relationshipStats as $rel):
                            $colorClass = $relColors[$rel['relationship']] ?? 'text-gray-600 bg-gray-50';
                            ?>
                            <div class="flex justify-between items-center p-2 rounded-lg <?= explode(' ', $colorClass)[1] ?>">
                                <span class="text-sm font-medium <?= explode(' ', $colorClass)[0] ?>">
                                    <?= htmlspecialchars($rel['relationship']) ?>
                                </span>
                                <span class="text-lg font-bold <?= explode(' ', $colorClass)[0] ?> font-display">
                                    <?= $rel['count'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400 text-sm italic py-4">Noch keine Daten verfügbar.</p>
                <?php endif; ?>
                <?php echo getDoodle('Herz1', 'doodle doodle-coral w-20 h-20 -right-4 -bottom-4 opacity-10'); ?>
            </div>

            <!-- Average Response Time -->
            <div class="glass-card p-6 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="clock" class="w-5 h-5 mr-2 text-amber-500"></i> Durchschnitt
                </h3>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Kontakte pro Monat</p>
                        <p class="text-3xl font-bold text-gray-800 font-display">
                            <?php
                            $avgPerMonth = count($growthStats) > 0
                                ? round(array_sum(array_column($growthStats, 'count')) / count($growthStats), 1)
                                : 0;
                            echo $avgPerMonth;
                            ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Notizen pro Kontakt</p>
                        <p class="text-3xl font-bold text-gray-800 font-display">
                            <?= $kpis['avg_notes_per_contact'] ?? '0' ?>
                        </p>
                    </div>
                </div>
                <?php echo getDoodle('Sterne', 'doodle doodle-yellow w-20 h-20 -right-4 -top-4 opacity-15 rotate-12'); ?>
            </div>
        </div>

        <!-- ROW 3 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Activity / Interaction Age -->
            <div class="glass-card p-6 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="activity" class="w-5 h-5 mr-2 text-orange-500"></i> Kontakt Gesundheit
                </h3>

                <div class="space-y-6">
                    <!-- Item -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-semibold text-gray-700">Frische Kontakte (< 30 Tage)</span>
                                    <span class="text-sm font-bold text-green-500">
                                        <?= $activityDist['recent'] ?>
                                    </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full"
                                style="width: <?= ($activityDist['recent'] / $totalContacts) * 100 ?>%"></div>
                        </div>
                    </div>

                    <!-- Item -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-semibold text-gray-700">Aktiv (1-3 Monate)</span>
                            <span class="text-sm font-bold text-secondary">
                                <?= $activityDist['quarter'] ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-secondary h-2.5 rounded-full"
                                style="width: <?= ($activityDist['quarter'] / $totalContacts) * 100 ?>%"></div>
                        </div>
                    </div>

                    <!-- Item -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-semibold text-gray-700">Schlafend (> 3 Monate)</span>
                            <span class="text-sm font-bold text-orange-400">
                                <?= $activityDist['old'] ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-orange-400 h-2.5 rounded-full"
                                style="width: <?= ($activityDist['old'] / $totalContacts) * 100 ?>%"></div>
                        </div>
                    </div>

                    <!-- Item -->
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-semibold text-gray-500">Nie kontaktiert</span>
                            <span class="text-sm font-bold text-gray-400">
                                <?= $activityDist['never'] ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-gray-400 h-2.5 rounded-full"
                                style="width: <?= ($activityDist['never'] / $totalContacts) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="glass-card p-6 relative overflow-hidden">
                <h3 class="text-lg font-bold text-gray-800 mb-6 font-display flex items-center">
                    <i data-lucide="package" class="w-5 h-5 mr-2 text-purple-500"></i> Top Produkte
                </h3>

                <?php if (empty($topProducts)): ?>
                    <p class="text-gray-400 text-sm italic py-4">Noch keine Produkte zugeordnet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($topProducts as $i => $p): ?>
                            <div
                                class="flex items-center justify-between p-3 bg-white/50 rounded-lg hover:bg-white transition-colors border border-transparent hover:border-gray-100">
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm mr-3">
                                        <?= $i + 1 ?>
                                    </div>
                                    <span class="font-semibold text-gray-700">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </span>
                                </div>
                                <span class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold">
                                    <?= $p['count'] ?> x
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <?php include __DIR__ . '/layout/footer.php'; ?>
</body>

</html>