<?php
// =============================================
// Gestion des Utilisateurs - VoyageVista
// =============================================
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'configuration.php';

$message = "";
$error   = "";

// Génération du token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Suppression via POST + CSRF (jamais via GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $id_to_delete = (int) $_POST['user_id'];
        // Empêcher la suppression de son propre compte
        if ($id_to_delete === (int) $_SESSION['user_id']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $message = "Utilisateur supprimé avec succès.";
        }
    }
}

// Changement de rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['role'], (int)$_POST['user_id']]);
        $message = "Rôle mis à jour.";
    }
}

// Recherche/filtre
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';

$query = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role_filter !== 'all') {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}
$query .= " ORDER BY date_inscription DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Utilisateurs - VoyageVista</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="navbar">
  <div class="brand">
    <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
  </div>
  <nav>
    <a href="dashboard_admin.php">Dashboard</a>
    <a href="gestion_utilisateur.php" class="active">Utilisateurs</a>
    <a href="gestion_destination.php">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
  </nav>
</header>

<main class="admin-page">
  <h1>Gestion des Utilisateurs</h1>

  <?php if ($message): ?>
    <p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <!-- Filtres -->
  <form method="GET" class="filter-bar">
    <input 
      type="text" 
      name="search" 
      placeholder="Rechercher par nom ou email..." 
      value="<?php echo htmlspecialchars($search); ?>"
    >
    <select name="role">
      <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>Tous les rôles</option>
      <option value="utilisateur" <?php echo $role_filter === 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
      <option value="prestataire" <?php echo $role_filter === 'prestataire' ? 'selected' : ''; ?>>Prestataire</option>
      <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
    </select>
    <button type="submit" class="btn">Filtrer</button>
  </form>

  <p><?php echo count($users); ?> utilisateur(s) trouvé(s)</p>

  <table class="data-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom d'utilisateur</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Date d'inscription</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo (int)$u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
        <td>
          <!-- Changement de rôle inline -->
          <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
            <select name="role" onchange="this.form.submit()">
              <option value="utilisateur" <?php echo $u['role'] === 'utilisateur' ? 'selected' : ''; ?>>Utilisateur</option>
              <option value="prestataire" <?php echo $u['role'] === 'prestataire' ? 'selected' : ''; ?>>Prestataire</option>
              <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
            <button type="submit" name="change_role" style="display:none;">OK</button>
          </form>
        </td>
        <td><?php echo htmlspecialchars($u['date_inscription'] ?? '-'); ?></td>
        <td>
          <!-- Suppression via POST + confirmation JS -->
          <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
          <form 
            method="POST" 
            onsubmit="return confirm('Supprimer cet utilisateur ? Cette action est irréversible.');"
            style="display:inline;"
          >
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
            <button type="submit" name="delete_user" class="btn btn--danger">Supprimer</button>
          </form>
          <?php else: ?>
            <span class="text-muted">(vous)</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

</body>
</html>