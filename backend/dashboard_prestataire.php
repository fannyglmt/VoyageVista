<?php
// =============================================
// Dashboard Prestataire - VoyageVista
// =============================================
session_start();

// Vérification du rôle prestataire
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$pid = (int) $_SESSION['user_id'];

// Récupération des services du prestataire
$stmt = $pdo->prepare("SELECT * FROM services WHERE prestataire_id = ? ORDER BY nom ASC");
$stmt->execute([$pid]);
$services = $stmt->fetchAll();

// Statistiques du prestataire
$stmt_reservations = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reservations r 
    JOIN services s ON r.service_id = s.id 
    WHERE s.prestataire_id = ?
");
$stmt_reservations->execute([$pid]);
$total_reservations = $stmt_reservations->fetchColumn();

$stmt_notifs = $pdo->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = ? AND lu = 0
");
$stmt_notifs->execute([$pid]);
$notifs_non_lues = $stmt_notifs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Dashboard - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_prestataire.php" class="active">Mon Dashboard</a>
    <a href="gestion-hebergements.html">Mes Hébergements</a>
    <a href="gestion-activites.html">Mes Activités</a>
    <a href="gestion-disponibilites.html">Disponibilités</a>
  </nav>
  <div class="nav-icons">
    <?php if ($notifs_non_lues > 0): ?>
      <span class="notif-badge"><?php echo $notifs_non_lues; ?></span>
    <?php endif; ?>
    <a href="logout.php">Déconnexion</a>
  </div>
</header>

<main class="prestataire-dashboard">
  <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Prestataire'); ?></h1>

  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-icon">🏨</span>
      <h2><?php echo count($services); ?></h2>
      <p>Services publiés</p>
    </div>
    <div class="stat-card">
      <span class="stat-icon">📅</span>
      <h2><?php echo htmlspecialchars($total_reservations); ?></h2>
      <p>Réservations reçues</p>
    </div>
    <div class="stat-card">
      <span class="stat-icon">🔔</span>
      <h2><?php echo htmlspecialchars($notifs_non_lues); ?></h2>
      <p>Notifications non lues</p>
    </div>
  </div>

  <section class="services-section">
    <div class="section-header">
      <h2>Mes services</h2>
      <a href="gestion-hebergements.html" class="btn">+ Ajouter un service</a>
    </div>

    <?php if (empty($services)): ?>
      <p class="empty-state">Vous n'avez pas encore publié de service.</p>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Prix/nuit</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($services as $s): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['nom']); ?></td>
            <td><?php echo htmlspecialchars($s['type'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($s['prix'] ?? '-'); ?> €</td>
            <td><?php echo htmlspecialchars($s['statut'] ?? 'actif'); ?></td>
            <td>
              <a href="gestion-hebergements.html?id=<?php echo (int)$s['id']; ?>">Modifier</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</main>

</body>
</html>