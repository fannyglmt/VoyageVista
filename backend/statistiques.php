<?php
// =============================================
// Statistiques Plateforme - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

// ── PÉRIODE (filtre) ───────────────────────────────────
$periode = $_GET['periode'] ?? '12'; // nb de mois à afficher
$periode = in_array($periode, ['3','6','12','24']) ? (int)$periode : 12;

// ── CHIFFRES CLÉS ─────────────────────────────────────
$total_users        = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nouveaux_ce_mois   = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription) = MONTH(NOW()) AND YEAR(date_inscription) = YEAR(NOW())")->fetchColumn();
$total_destinations = $pdo->query("SELECT COUNT(*) FROM destinations WHERE est_active = 1")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$reservations_mois  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE MONTH(date_reservation) = MONTH(NOW()) AND YEAR(date_reservation) = YEAR(NOW())")->fetchColumn();
$chiffre_affaires   = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut != 'annulee'")->fetchColumn();
$ca_mois            = $pdo->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut != 'annulee' AND MONTH(date_reservation) = MONTH(NOW()) AND YEAR(date_reservation) = YEAR(NOW())")->fetchColumn();
$signalements_ouverts = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn();
$taux_annulation    = $pdo->query("SELECT ROUND(COUNT(*) * 100.0 / NULLIF((SELECT COUNT(*) FROM reservations),0), 1) FROM reservations WHERE statut = 'annulee'")->fetchColumn();

// ── GRAPHIQUE 1 : Réservations par mois ───────────────
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_reservation, '%Y-%m') AS mois,
        COUNT(*) AS nb_reservations,
        COALESCE(SUM(prix_total), 0) AS ca
    FROM reservations
    WHERE date_reservation >= DATE_SUB(NOW(), INTERVAL ? MONTH)
    GROUP BY mois
    ORDER BY mois ASC
");
$stmt->execute([$periode]);
$stats_reservations = $stmt->fetchAll();

$labels_reservations = array_column($stats_reservations, 'mois');
$data_reservations   = array_column($stats_reservations, 'nb_reservations');
$data_ca             = array_column($stats_reservations, 'ca');

// ── GRAPHIQUE 2 : Inscriptions par mois ───────────────
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(date_inscription, '%Y-%m') AS mois,
        COUNT(*) AS nb_inscrits
    FROM utilisateurs
    WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL ? MONTH)
    GROUP BY mois
    ORDER BY mois ASC
");
$stmt->execute([$periode]);
$stats_users = $stmt->fetchAll();

$labels_users = array_column($stats_users, 'mois');
$data_users   = array_column($stats_users, 'nb_inscrits');

// ── GRAPHIQUE 3 : Répartition par statut réservation ──
$stats_statuts = $pdo->query("
    SELECT statut, COUNT(*) AS nb
    FROM reservations
    GROUP BY statut
")->fetchAll();

$labels_statuts = array_column($stats_statuts, 'statut');
$data_statuts   = array_column($stats_statuts, 'nb');

// ── GRAPHIQUE 4 : Top destinations ────────────────────
$top_destinations = $pdo->query("
    SELECT d.nom, COUNT(r.id) AS nb_reservations
    FROM destinations d
    LEFT JOIN reservations r ON r.destination_id = d.id
    GROUP BY d.id, d.nom
    ORDER BY nb_reservations DESC
    LIMIT 8
")->fetchAll();

$labels_dest = array_column($top_destinations, 'nom');
$data_dest   = array_column($top_destinations, 'nb_reservations');

// ── GRAPHIQUE 5 : Répartition par région ──────────────
$stats_regions = $pdo->query("
    SELECT region, COUNT(*) AS nb
    FROM destinations
    WHERE region IS NOT NULL AND est_active = 1
    GROUP BY region
")->fetchAll();

$labels_regions = array_column($stats_regions, 'region');
$data_regions   = array_column($stats_regions, 'nb');

// ── GRAPHIQUE 6 : Répartition rôles utilisateurs ──────
$stats_roles = $pdo->query("
    SELECT role, COUNT(*) AS nb FROM utilisateurs GROUP BY role
")->fetchAll();

$labels_roles = array_column($stats_roles, 'role');
$data_roles   = array_column($stats_roles, 'nb');

// ── TABLEAU : Dernières réservations ──────────────────
$dernieres_resa = $pdo->query("
    SELECT 
        r.id,
        u.username,
        d.nom AS destination,
        r.date_debut,
        r.date_fin,
        r.nb_voyageurs,
        r.prix_total,
        r.statut,
        r.date_reservation
    FROM reservations r
    JOIN utilisateurs u  ON r.user_id = u.id
    JOIN destinations d  ON r.destination_id = d.id
    ORDER BY r.date_reservation DESC
    LIMIT 10
")->fetchAll();

// ── TABLEAU : Prestataires les plus actifs ────────────
$top_prestataires = $pdo->query("
    SELECT 
        u.username,
        COUNT(s.id) AS nb_services,
        COUNT(r.id) AS nb_reservations,
        COALESCE(SUM(r.prix_total),0) AS ca_total
    FROM utilisateurs u
    LEFT JOIN services s     ON s.prestataire_id = u.id
    LEFT JOIN reservations r ON r.service_id = s.id AND r.statut != 'annulee'
    WHERE u.role = 'prestataire'
    GROUP BY u.id, u.username
    ORDER BY ca_total DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiques - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    /* ── Styles spécifiques statistiques ── */
    .stats-page { padding: 2rem; max-width: 1400px; margin: 0 auto; }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .page-header h1 { margin: 0; font-size: 1.8rem; }

    .periode-filter { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .periode-filter a {
      padding: 0.4rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 20px;
      text-decoration: none;
      color: #555;
      font-weight: 500;
      transition: all 0.2s;
    }
    .periode-filter a.active,
    .periode-filter a:hover {
      background: #4f46e5;
      border-color: #4f46e5;
      color: white;
    }

    /* KPI cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2.5rem;
    }
    .kpi-card {
      background: white;
      border-radius: 12px;
      padding: 1.4rem 1.6rem;
      box-shadow: 0 2px 8px rgba(0,0,0,.07);
      border-left: 4px solid #4f46e5;
    }
    .kpi-card.green  { border-color: #10b981; }
    .kpi-card.orange { border-color: #f59e0b; }
    .kpi-card.red    { border-color: #ef4444; }
    .kpi-card.purple { border-color: #8b5cf6; }

    .kpi-label { font-size: .8rem; color: #888; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem; }
    .kpi-value { font-size: 2rem; font-weight: 700; color: #1a1a2e; line-height: 1; }
    .kpi-sub   { font-size: .78rem; color: #10b981; margin-top: .3rem; }
    .kpi-sub.bad { color: #ef4444; }

    /* Charts grid */
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(560px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2.5rem;
    }
    .chart-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,.07);
    }
    .chart-card h3 { margin: 0 0 1.2rem; font-size: 1rem; color: #333; }
    .chart-card canvas { max-height: 280px; }
    .chart-card.full { grid-column: 1 / -1; }

    /* Tables */
    .table-section { margin-bottom: 2rem; }
    .table-section h2 { font-size: 1.1rem; margin-bottom: 1rem; color: #333; }
    .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
    .data-table th { background: #f8fafc; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; padding: .9rem 1rem; text-align: left; color: #555; border-bottom: 2px solid #e2e8f0; }
    .data-table td { padding: .8rem 1rem; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
    .data-table tr:last-child td { border: none; }
    .data-table tr:hover td { background: #fafafa; }

    .badge {
      display: inline-block;
      padding: .2rem .6rem;
      border-radius: 20px;
      font-size: .75rem;
      font-weight: 600;
    }
    .badge-confirmee { background: #d1fae5; color: #065f46; }
    .badge-en_attente { background: #fef3c7; color: #92400e; }
    .badge-annulee    { background: #fee2e2; color: #991b1b; }
    .badge-terminee   { background: #e0e7ff; color: #3730a3; }

    @media (max-width: 768px) {
      .charts-grid { grid-template-columns: 1fr; }
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
    <a href="statistiques.php" class="active">Statistiques</a>
  </nav>
  <div class="nav-icons">
    <a href="logout.php">Déconnexion</a>
  </div>
</header>

<main class="stats-page">

  <div class="page-header">
    <h1>📊 Statistiques de la plateforme</h1>
    <div class="periode-filter">
      <span style="color:#888;font-size:.85rem;align-self:center;">Période :</span>
      <?php foreach (['3'=>'3 mois','6'=>'6 mois','12'=>'12 mois','24'=>'2 ans'] as $val => $label): ?>
        <a href="?periode=<?php echo $val; ?>" 
           class="<?php echo $periode == $val ? 'active' : ''; ?>">
          <?php echo $label; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── KPI ── -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-label">Utilisateurs</div>
      <div class="kpi-value"><?php echo number_format($total_users); ?></div>
      <div class="kpi-sub">+<?php echo $nouveaux_ce_mois; ?> ce mois</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-label">Destinations actives</div>
      <div class="kpi-value"><?php echo number_format($total_destinations); ?></div>
    </div>
    <div class="kpi-card purple">
      <div class="kpi-label">Réservations totales</div>
      <div class="kpi-value"><?php echo number_format($total_reservations); ?></div>
      <div class="kpi-sub">+<?php echo $reservations_mois; ?> ce mois</div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-label">Chiffre d'affaires</div>
      <div class="kpi-value"><?php echo number_format((float)$chiffre_affaires, 0, ',', ' '); ?> €</div>
      <div class="kpi-sub">+<?php echo number_format((float)$ca_mois, 0, ',', ' '); ?> € ce mois</div>
    </div>
    <div class="kpi-card orange">
      <div class="kpi-label">Signalements ouverts</div>
      <div class="kpi-value"><?php echo number_format($signalements_ouverts); ?></div>
      <div class="kpi-sub <?php echo $signalements_ouverts > 10 ? 'bad' : ''; ?>">
        <?php echo $signalements_ouverts > 10 ? 'À traiter' : 'Sous contrôle'; ?>
      </div>
    </div>
    <div class="kpi-card <?php echo $taux_annulation > 20 ? 'red' : 'green'; ?>">
      <div class="kpi-label">Taux d'annulation</div>
      <div class="kpi-value"><?php echo $taux_annulation ?? 0; ?> %</div>
    </div>
  </div>

  <!-- ── GRAPHIQUES ── -->
  <div class="charts-grid">

    <!-- Réservations par mois -->
    <div class="chart-card full">
      <h3>📅 Réservations & Chiffre d'affaires (<?php echo $periode; ?> derniers mois)</h3>
      <canvas id="chartReservations"></canvas>
    </div>

    <!-- Inscriptions par mois -->
    <div class="chart-card">
      <h3>👤 Nouvelles inscriptions</h3>
      <canvas id="chartUsers"></canvas>
    </div>

    <!-- Statuts réservations -->
    <div class="chart-card">
      <h3>📋 Réservations par statut</h3>
      <canvas id="chartStatuts"></canvas>
    </div>

    <!-- Top destinations -->
    <div class="chart-card">
      <h3>🌍 Top destinations les plus réservées</h3>
      <canvas id="chartDestinations"></canvas>
    </div>

    <!-- Régions -->
    <div class="chart-card">
      <h3>🗺️ Destinations par région</h3>
      <canvas id="chartRegions"></canvas>
    </div>

    <!-- Rôles utilisateurs -->
    <div class="chart-card">
      <h3>👥 Répartition des rôles</h3>
      <canvas id="chartRoles"></canvas>
    </div>

  </div>

  <!-- ── TABLEAU : Dernières réservations ── -->
  <div class="table-section">
    <h2>🕐 10 dernières réservations</h2>
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Utilisateur</th>
          <th>Destination</th>
          <th>Dates</th>
          <th>Voyageurs</th>
          <th>Prix</th>
          <th>Statut</th>
          <th>Réservé le</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($dernieres_resa)): ?>
          <tr><td colspan="8" style="text-align:center;color:#aaa;padding:2rem;">Aucune réservation.</td></tr>
        <?php else: ?>
          <?php foreach ($dernieres_resa as $r): ?>
          <tr>
            <td>#<?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['username']); ?></td>
            <td><?php echo htmlspecialchars($r['destination']); ?></td>
            <td><?php echo htmlspecialchars($r['date_debut']); ?> → <?php echo htmlspecialchars($r['date_fin']); ?></td>
            <td><?php echo (int)$r['nb_voyageurs']; ?></td>
            <td><?php echo number_format((float)$r['prix_total'], 2, ',', ' '); ?> €</td>
            <td><span class="badge badge-<?php echo htmlspecialchars($r['statut']); ?>"><?php echo htmlspecialchars($r['statut']); ?></span></td>
            <td><?php echo htmlspecialchars(substr($r['date_reservation'], 0, 10)); ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── TABLEAU : Top prestataires ── -->
  <div class="table-section">
    <h2>🏆 Top prestataires</h2>
    <table class="data-table">
      <thead>
        <tr>
          <th>Prestataire</th>
          <th>Services publiés</th>
          <th>Réservations</th>
          <th>CA généré</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($top_prestataires)): ?>
          <tr><td colspan="4" style="text-align:center;color:#aaa;padding:2rem;">Aucun prestataire.</td></tr>
        <?php else: ?>
          <?php foreach ($top_prestataires as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['username']); ?></td>
            <td><?php echo (int)$p['nb_services']; ?></td>
            <td><?php echo (int)$p['nb_reservations']; ?></td>
            <td><?php echo number_format((float)$p['ca_total'], 2, ',', ' '); ?> €</td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<!-- ── CHART.JS SCRIPTS ── -->
<script>
const palette = ['#4f46e5','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];

// Graphique 1 : Réservations + CA
new Chart(document.getElementById('chartReservations'), {
  data: {
    labels: <?php echo json_encode($labels_reservations); ?>,
    datasets: [
      {
        type: 'bar',
        label: 'Nb réservations',
        data: <?php echo json_encode($data_reservations); ?>,
        backgroundColor: 'rgba(79,70,229,0.25)',
        borderColor: '#4f46e5',
        borderWidth: 2,
        yAxisID: 'y'
      },
      {
        type: 'line',
        label: 'CA (€)',
        data: <?php echo json_encode($data_ca); ?>,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.1)',
        tension: 0.4,
        pointRadius: 4,
        fill: true,
        yAxisID: 'y1'
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    scales: {
      y:  { beginAtZero: true, title: { display: true, text: 'Réservations' } },
      y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'CA (€)' }, grid: { drawOnChartArea: false } }
    }
  }
});

// Graphique 2 : Inscriptions
new Chart(document.getElementById('chartUsers'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels_users); ?>,
    datasets: [{
      label: 'Inscriptions',
      data: <?php echo json_encode($data_users); ?>,
      borderColor: '#8b5cf6',
      backgroundColor: 'rgba(139,92,246,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 4
    }]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

// Graphique 3 : Statuts (donut)
new Chart(document.getElementById('chartStatuts'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($labels_statuts); ?>,
    datasets: [{ data: <?php echo json_encode($data_statuts); ?>, backgroundColor: palette }]
  },
  options: { responsive: true, plugins: { legend: { position: 'right' } } }
});

// Graphique 4 : Top destinations (bar horizontal)
new Chart(document.getElementById('chartDestinations'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels_dest); ?>,
    datasets: [{
      label: 'Réservations',
      data: <?php echo json_encode($data_dest); ?>,
      backgroundColor: palette
    }]
  },
  options: {
    responsive: true,
    indexAxis: 'y',
    scales: { x: { beginAtZero: true } },
    plugins: { legend: { display: false } }
  }
});

// Graphique 5 : Régions (pie)
new Chart(document.getElementById('chartRegions'), {
  type: 'pie',
  data: {
    labels: <?php echo json_encode($labels_regions); ?>,
    datasets: [{ data: <?php echo json_encode($data_regions); ?>, backgroundColor: palette }]
  },
  options: { responsive: true, plugins: { legend: { position: 'right' } } }
});

// Graphique 6 : Rôles (doughnut)
new Chart(document.getElementById('chartRoles'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($labels_roles); ?>,
    datasets: [{ data: <?php echo json_encode($data_roles); ?>, backgroundColor: ['#4f46e5','#10b981','#f59e0b'] }]
  },
  options: { responsive: true, plugins: { legend: { position: 'right' } } }
});
</script>

</body>
</html>