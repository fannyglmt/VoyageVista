<?php
// =============================================
// Dashboard Administrateur - VoyageVista
// =============================================
session_start();

// Vérification du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$total_users   = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_dest    = $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_admin.php" class="active">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
    <a href="gestion_notification.php">Notifications</a>
    <a href="statistiques.php">Statistiques</a>
  </nav>
  <div class="nav-icons">
    <a href="logout.php">Déconnexion</a>
  </div>
</header>

<main class="admin-dashboard">
  <h1>Dashboard Administrateur</h1>

  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-icon">👤</span>
      <h2><?php echo htmlspecialchars($total_users); ?></h2>
      <p>Utilisateurs inscrits</p>
    </div>
    <div class="stat-card">
      <span class="stat-icon">🗺️</span>
      <h2><?php echo htmlspecialchars($total_dest); ?></h2>
      <p>Destinations disponibles</p>
    </div>
    <div class="stat-card stat-card--alert">
      <span class="stat-icon">⚠️</span>
      <h2><?php echo htmlspecialchars($total_reports); ?></h2>
      <p>Signalements en attente</p>
    </div>
    <div class="stat-card">
      <span class="stat-icon">📅</span>
      <h2><?php echo htmlspecialchars($total_reservations); ?></h2>
      <p>Réservations totales</p>
    </div>
  </div>

  <div class="quick-links">
    <h2>Actions rapides</h2>
    <a href="gestion_utilisateur.php" class="btn">Gérer les utilisateurs</a>
    <a href="gestion_destination.php" class="btn">Gérer les destinations</a>
    <a href="gestion_signalement.php" class="btn btn--alert">Voir les signalements</a>
    <a href="statistiques.php" class="btn">Voir les statistiques</a>
  </div>
</main>

</body>
</html>