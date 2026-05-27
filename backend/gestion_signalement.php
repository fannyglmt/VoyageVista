<?php
// =============================================
// Gestion des Signalements - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$message = "";
$error   = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── MISE À JOUR du statut ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $statuts_valides = ['ouvert', 'en_cours', 'resolu', 'rejete'];
        $statut = $_POST['statut'] ?? '';
        $id     = (int)$_POST['id'];

        if (!in_array($statut, $statuts_valides)) {
            $error = "Statut invalide.";
        } else {
            $stmt = $pdo->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
            $stmt->execute([$statut, $id]);
            $message = "Signalement #$id mis à jour.";
        }
    }
}

// ── SUPPRESSION ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $pdo->prepare("DELETE FROM signalements WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = "Signalement supprimé.";
    }
}

// Filtre par statut
$filtre = $_GET['statut'] ?? 'all';
$query  = "SELECT s.*, u.username FROM signalements s LEFT JOIN utilisateurs u ON s.user_id = u.id";
$params = [];

if ($filtre !== 'all') {
    $query .= " WHERE s.statut = ?";
    $params[] = $filtre;
}
$query .= " ORDER BY s.date_signalement DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Compteurs par statut
$counts = $pdo->query("
    SELECT statut, COUNT(*) as nb FROM signalements GROUP BY statut
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Signalements - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php">Utilisateurs</a>
    <a href="gestion_signalement.php" class="active">Signalements</a>
    <a href="gestion_notification.php">Notifications</a>
  </nav>
</header>

<main class="admin-page">
  <h1>Gestion des Signalements</h1>

  <?php if ($message): ?>
    <p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <!-- Filtres par statut -->
  <div class="filter-tabs">
    <a href="?statut=all" class="<?php echo $filtre === 'all' ? 'active' : ''; ?>">
      Tous (<?php echo array_sum($counts); ?>)
    </a>
    <a href="?statut=ouvert" class="<?php echo $filtre === 'ouvert' ? 'active' : ''; ?>">
      Ouverts (<?php echo $counts['ouvert'] ?? 0; ?>)
    </a>
    <a href="?statut=en_cours" class="<?php echo $filtre === 'en_cours' ? 'active' : ''; ?>">
      En cours (<?php echo $counts['en_cours'] ?? 0; ?>)
    </a>
    <a href="?statut=resolu" class="<?php echo $filtre === 'resolu' ? 'active' : ''; ?>">
      Résolus (<?php echo $counts['resolu'] ?? 0; ?>)
    </a>
    <a href="?statut=rejete" class="<?php echo $filtre === 'rejete' ? 'active' : ''; ?>">
      Rejetés (<?php echo $counts['rejete'] ?? 0; ?>)
    </a>
  </div>

  <?php if (empty($reports)): ?>
    <p class="empty-state">Aucun signalement trouvé.</p>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Utilisateur</th>
          <th>Raison</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr class="report-row report-row--<?php echo htmlspecialchars($r['statut']); ?>">
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['username'] ?? 'Inconnu'); ?></td>
          <td><?php echo htmlspecialchars($r['raison']); ?></td>
          <td><?php echo htmlspecialchars($r['date_signalement'] ?? '-'); ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <select name="statut" onchange="this.form.submit()">
                <?php foreach (['ouvert'=>'Ouvert','en_cours'=>'En cours','resolu'=>'Résolu','rejete'=>'Rejeté'] as $val=>$label): ?>
                  <option value="<?php echo $val; ?>" <?php echo $r['statut'] === $val ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="update_statut" style="display:none;"></button>
            </form>
          </td>
          <td>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Supprimer ce signalement ?');">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button type="submit" name="delete_report" class="btn btn--danger btn--sm">Supprimer</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>

</body>
</html>