<?php
// Statistik laden
$statsPath = __DIR__ . '/daten/stats.json';
$stats = [
    'rezepte' => [],
    'gesamt_anzahl' => 0,
    'anzahl_menues' => 0
];
if (file_exists($statsPath)) {
    $json = json_decode(file_get_contents($statsPath), true);
    if (is_array($json)) {
        $stats = array_merge($stats, $json);
    }
}

// Top 10 am häufigsten gekochte Rezepte
$topRezepte = [];
if (!empty($stats['rezepte'])) {
    $alle = $stats['rezepte'];
    uasort($alle, function($a, $b) {
        return ($b['anzahl'] ?? 0) <=> ($a['anzahl'] ?? 0);
    });
    $topRezepte = array_slice($alle, 0, 10, true);
}

$anzahl_menues = max(1, (int)($stats['anzahl_menues'] ?? 0)); // Verhindert Division durch 0
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Statistik – Rezepte</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="assets/tokens.css">
       <link href="assets/style.css" rel="stylesheet">
    <style>
        body { padding-top: 80px; background-color: var(--color-bg); font-family: var(--font-family-base); }
        .material-symbols-outlined { font-size: var(--font-size-icon); vertical-align: middle; }
        .dashboard-kpi { background: var(--color-surface); border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 1.6em 1.2em; margin-bottom: 1.2em; display: flex; align-items: center; gap: 1.1em; }
        .dashboard-kpi .material-symbols-outlined { font-size: 38px; color: var(--color-accent-green);}
        .dashboard-kpi .kpi-label { color: var(--color-text-faint); font-size: 1.1em; font-weight: 500;}
        .dashboard-kpi .kpi-value { font-size: 2.2em; font-weight: bold; margin-bottom: .1em;}
        .top-rezepte-table th, .top-rezepte-table td { vertical-align: middle; }
        .top-rezepte-table th { font-size: 1.1em; }
        .top-rezepte-table td { font-size: 1.06em; }
        .dashboard-section-title { font-weight: 600; font-size: 1.22em; margin-top: 1.4em; margin-bottom:0.7em; color: #222; }
    </style>
</head>
<body>
<div class="sticky-header d-flex align-items-center">
    <a href="start.php" class="btn btn-outline-dark me-2" title="Zur Startseite">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
</div>

<div class="container py-4">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="dashboard-kpi">
                <span class="material-symbols-outlined">chef_hat</span>
                <div>
                    <div class="kpi-label">Rezepte</div>
                    <div class="kpi-value"><?= htmlspecialchars($stats['gesamt_anzahl'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-kpi">
                <span class="material-symbols-outlined">checklist</span>
                <div>
                    <div class="kpi-label">Gekochte Rezepte</div>
                    <div class="kpi-value"><?= htmlspecialchars($stats['anzahl_menues'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-kpi">
                <span class="material-symbols-outlined">favorite</span>
                <div>
                    <div class="kpi-label">Am häufigsten gekocht</div>
                    <div class="kpi-value">
                        <?php
                        $top1 = array_keys($topRezepte);
                        echo isset($top1[0]) ? htmlspecialchars($top1[0]) : "–";
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-title">Top 10</div>
    <div class="table-responsive">
        <table class="table table-bordered top-rezepte-table bg-white">
            <thead class="table-light">
                <tr>
                    <th style="width:3em;">#</th>
                    <th>Rezept</th>
                    <th style="width:8em;">Häufigkeit</th>
                    <th style="width:8em;">%</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $platz = 1;
                foreach ($topRezepte as $name => $r) {
                    $anzahl = (int)($r['anzahl'] ?? 0);
                    $prozent = $anzahl_menues > 0 ? (100 * $anzahl / $anzahl_menues) : 0;
                    echo '<tr>';
                    echo '<td>'. $platz . '</td>';
                    echo '<td>'. htmlspecialchars($name) .'</td>';
                    echo '<td>'. htmlspecialchars($anzahl) .'</td>';
                    echo '<td>'. number_format($prozent, 1, ',', '.') . '</td>';
                    echo '</tr>';
                    $platz++;
                }
                if ($platz == 1) {
                    echo '<tr><td colspan="4" class="text-center">Keine Daten vorhanden.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>