<?php
// =============================================
// Gestion des Destinations - VoyageVista
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

// ── AJOUT ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $nom         = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix        = (float)($_POST['prix'] ?? 0);
        $region      = trim($_POST['region'] ?? '');
        $categorie   = trim($_POST['categorie'] ?? '');
        $budget      = trim($_POST['budget'] ?? '');

        if ($nom === '')   $error .= "Le nom est requis.<br>";
        if ($prix <= 0)    $error .= "Le prix doit être positif.<br>";
        if ($region === '') $error .= "La région est requise.<br>";

        if ($error === '') {
            $stmt = $pdo->prepare("
                INSERT INTO destinations (nom, description, prix, region, categorie, budget) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nom, $description, $prix, $region, $categorie, $budget]);
            $message = "Destination « $nom » ajoutée avec succès.";
        }
    }
}

// ── MODIFICATION ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $id          = (int)$_POST['id'];
        $nom         = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix        = (float)($_POST['prix'] ?? 0);
        $region      = trim($_POST['region'] ?? '');
        $categorie   = trim($_POST['categorie'] ?? '');
        $budget      = trim($_POST['budget'] ?? '');

        if ($nom === '')   $error .= "Le nom est requis.<br>";
        if ($prix <= 0)    $error .= "Le prix doit être positif.<br>";

        if ($error === '') {
            $stmt = $pdo->prepare("
                UPDATE destinations 
                SET nom=?, description=?, prix=?, region=?, categorie=?, budget=? 
                WHERE id=?
            ");
            $stmt->execute([$nom, $description, $prix, $region, $categorie, $budget, $id]);
            $message = "Destination mise à jour.";
        }
    }
}

// ── SUPPRESSION ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dest'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Action non autorisée.";
    } else {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM destinations WHERE id = ?")->execute([$id]);
        $message = "Destination supprimée.";
    }
}

// Récupération de toutes les destinations
$destinations = $pdo->query("SELECT * FROM destinations ORDER BY nom ASC")->fetchAll();

// Pré-remplissage pour édition
$edit_dest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_dest = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion Destinations - VoyageVista</title>
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
    <a href="gestion_destination.php" class="active">Destinations</a>
    <a href="gestion_signalement.php">Signalements</a>
  </nav>
</header>

<main class="admin-page">
  <h1>Gestion des Destinations</h1>

  <?php if ($message): ?>
    <p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="alert alert--error"><?php echo $error; ?></p>
  <?php endif; ?>

  <!-- Formulaire ajout / modification -->
  <section class="form-section">
    <h2><?php echo $edit_dest ? 'Modifier la destination' : 'Ajouter une destination'; ?></h2>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      <?php if ($edit_dest): ?>
        <input type="hidden" name="id" value="<?php echo (int)$edit_dest['id']; ?>">
      <?php endif; ?>

      <label>Nom *
        <input type="text" name="nom" required 
               value="<?php echo htmlspecialchars($edit_dest['nom'] ?? ''); ?>">
      </label>

      <label>Description
        <textarea name="description"><?php echo htmlspecialchars($edit_dest['description'] ?? ''); ?></textarea>
      </label>

      <label>Prix (€) *
        <input type="number" name="prix" min="1" step="0.01" required
               value="<?php echo htmlspecialchars($edit_dest['prix'] ?? ''); ?>">
      </label>

      <label>Région *
        <select name="region" required>
          <option value="">-- Choisir --</option>
          <?php foreach (['Europe','Asie','Afrique','Amérique'] as $r): ?>
            <option value="<?php echo $r; ?>" 
              <?php echo ($edit_dest['region'] ?? '') === $r ? 'selected' : ''; ?>>
              <?php echo $r; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Catégorie
        <select name="categorie">
          <option value="">-- Choisir --</option>
          <?php foreach (['Aventure','Nightlife','Plage','Gastronomie','Culture','Nature','Sport','Détente','Road Trip'] as $c): ?>
            <option value="<?php echo $c; ?>"
              <?php echo ($edit_dest['categorie'] ?? '') === $c ? 'selected' : ''; ?>>
              <?php echo $c; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Budget
        <select name="budget">
          <option value="">-- Choisir --</option>
          <?php foreach (['€','€€','€€€'] as $b): ?>
            <option value="<?php echo $b; ?>"
              <?php echo ($edit_dest['budget'] ?? '') === $b ? 'selected' : ''; ?>>
              <?php echo $b; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <button type="submit" name="<?php echo $edit_dest ? 'edit_dest' : 'add_dest'; ?>" class="btn">
        <?php echo $edit_dest ? 'Enregistrer' : 'Ajouter'; ?>
      </button>
      <?php if ($edit_dest): ?>
        <a href="gestion_destination.php" class="btn btn--secondary">Annuler</a>
      <?php endif; ?>
    </form>
  </section>

  <!-- Liste des destinations -->
  <section>
    <h2>Liste des destinations (<?php echo count($destinations); ?>)</h2>
    <table class="data-table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Région</th>
          <th>Catégorie</th>
          <th>Budget</th>
          <th>Prix</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($destinations as $d): ?>
        <tr>
          <td><?php echo htmlspecialchars($d['nom']); ?></td>
          <td><?php echo htmlspecialchars($d['region'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($d['categorie'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars($d['budget'] ?? '-'); ?></td>
          <td><?php echo number_format((float)($d['prix'] ?? 0), 2, ',', ' '); ?> €</td>
          <td>
            <a href="?edit=<?php echo (int)$d['id']; ?>" class="btn btn--secondary btn--sm">Modifier</a>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Supprimer cette destination ?');">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
              <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
              <button type="submit" name="delete_dest" class="btn btn--danger btn--sm">Supprimer</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

</body>
</html>