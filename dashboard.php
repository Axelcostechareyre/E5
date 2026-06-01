<?php
/**
 * dashboard.php
 * -----------------------------------------------------------
 * Page de supervision de la salle serveur — version SANS JavaScript.
 *
 * - Le PHP se connecte à MySQL et lit les mesures.
 * - L'affichage est généré directement par PHP (boucles foreach).
 * - Le rafraichissement "temps réel" est assuré par la balise
 *   <meta http-equiv="refresh" content="5"> : le navigateur
 *   recharge la page tout seul toutes les 5 secondes.
 * -----------------------------------------------------------
 */

session_start();

// --- Protection d'accès : à décommenter quand le login est en place ---
// if (!isset($_SESSION['user'])) {
//     header('Location: login.php');
//     exit;
// }

require __DIR__ . "/db.php";

// -------------------------------------------------------------
// 1) Derniere mesure de chaque type (pour les cartes)
// -------------------------------------------------------------
$sqlDernieres = "
    SELECT m.type, m.valeur, m.unite, m.date_mesure
    FROM mesures m
    INNER JOIN (
        SELECT type, MAX(date_mesure) AS maxd
        FROM mesures
        GROUP BY type
    ) AS d ON d.type = m.type AND d.maxd = m.date_mesure
    ORDER BY m.type
";
$dernieres = $pdo->query($sqlDernieres)->fetchAll();

// -------------------------------------------------------------
// 2) Historique des 30 dernieres mesures (pour le tableau)
// -------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT type, valeur, unite, date_mesure
    FROM mesures
    ORDER BY date_mesure DESC
    LIMIT 30
");
$stmt->execute();
$historique = $stmt->fetchAll();

/**
 * Petite fonction PHP qui choisit une couleur selon le type
 * et la valeur (seuils d'alerte). Renvoie un nom de classe CSS.
 */
function classeAlerte($type, $valeur) {
    $v = (float) $valeur;
    if ($type === 'temperature') {
        if ($v > 27) return 'danger';
        if ($v > 24) return 'warn';
        return 'ok';
    }
    if ($type === 'humidite') {
        return ($v > 70 || $v < 30) ? 'warn' : 'ok';
    }
    if ($type === 'co2') {
        if ($v > 1000) return 'danger';
        if ($v > 800)  return 'warn';
        return 'ok';
    }
    return 'neutre';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- C'EST CETTE LIGNE qui recharge la page toutes les 5 secondes -->
    <meta http-equiv="refresh" content="5">
    <title>Supervision - Salle serveur</title>
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a2129;
            --border: #2a333d;
            --text: #e6edf3;
            --text-dim: #8b949e;
            --ok: #3fb950;
            --warn: #d29922;
            --danger: #f85149;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", system-ui, sans-serif;
            background: var(--bg); color: var(--text); padding: 24px;
        }
        header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }
        h1 { font-size: 22px; font-weight: 600; }
        .statut { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-dim); }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--ok); }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 18px 20px;
        }
        .card .label { font-size: 13px; color: var(--text-dim); text-transform: uppercase; letter-spacing: .5px; }
        .card .value { font-size: 34px; font-weight: 600; margin: 6px 0 2px; }
        .card .unit { font-size: 18px; color: var(--text-dim); font-weight: 400; }
        .card .time { font-size: 12px; color: var(--text-dim); }
        .value.ok     { color: var(--ok); }
        .value.warn   { color: var(--warn); }
        .value.danger { color: var(--danger); }
        .value.neutre { color: var(--text); }

        table {
            width: 100%; border-collapse: collapse; background: var(--surface);
            border: 1px solid var(--border); border-radius: 12px; overflow: hidden;
        }
        th, td { padding: 11px 16px; text-align: left; font-size: 14px; }
        thead th { background: #161c23; color: var(--text-dim); font-weight: 500; }
        tbody tr { border-top: 1px solid var(--border); }
        h2 { font-size: 16px; font-weight: 500; margin-bottom: 12px; color: var(--text-dim); }
    </style>
</head>
<body>

    <header>
        <h1>Supervision - Salle serveur</h1>
        <div class="statut">
            <span class="dot"></span>
            <!-- Heure du dernier rafraichissement (cote serveur) -->
            <span>Derniere mise a jour : <?= date('H:i:s') ?></span>
        </div>
    </header>

    <!-- ===== Cartes : derniere valeur de chaque capteur ===== -->
    <section class="cards">
        <?php if (empty($dernieres)): ?>
            <div class="card"><div class="label">Aucune donnee disponible</div></div>
        <?php else: ?>
            <?php foreach ($dernieres as $m): ?>
                <div class="card">
                    <div class="label"><?= htmlspecialchars($m['type']) ?></div>
                    <div class="value <?= classeAlerte($m['type'], $m['valeur']) ?>">
                        <?= htmlspecialchars($m['valeur']) ?><span class="unit"> <?= htmlspecialchars($m['unite']) ?></span>
                    </div>
                    <div class="time"><?= htmlspecialchars($m['date_mesure']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- ===== Tableau : historique des dernieres mesures ===== -->
    <h2>Dernieres mesures</h2>
    <table>
        <thead>
            <tr><th>Type</th><th>Valeur</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php if (empty($historique)): ?>
                <tr><td colspan="3">Aucune mesure enregistree.</td></tr>
            <?php else: ?>
                <?php foreach ($historique as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['type']) ?></td>
                        <td><?= htmlspecialchars($m['valeur']) ?> <?= htmlspecialchars($m['unite']) ?></td>
                        <td><?= htmlspecialchars($m['date_mesure']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
