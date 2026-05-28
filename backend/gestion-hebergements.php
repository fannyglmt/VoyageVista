<?php
session_start();

require_once "configuration.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $deleteId = intval($_POST['delete_id']);

    $stmt = $pdo->prepare("
        DELETE FROM hebergements
        WHERE id = ?
    ");

    $stmt->execute([$deleteId]);

    header('Location: gestion-hebergements.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hebergement'])) {
  $type = $_POST['type'];
$capacite = $_POST['capacite'];

    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $prix = $_POST['prix_nuit'];
    $image = $_POST['image_url'];
    $destinationId = $_POST['destination_id'];

    $stmt = $pdo->prepare("
    INSERT INTO hebergements (
        nom,
        description,
        type,
        prix_nuit,
        capacite,
        image_url,
        destination_id,
        prestataire_id,
        est_actif
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
");

    $stmt->execute([
    $nom,
    $description,
    $type,
    $prix,
    $capacite,
    $image,
    $destinationId,
    $_SESSION['user_id']
]);

    header("Location: gestion-hebergements.php");
    exit;
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
SELECT
    h.*,
    d.nom AS destination_nom
FROM hebergements h
JOIN destinations d
ON h.destination_id = d.id
WHERE h.prestataire_id = ?
ORDER BY h.date_creation DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$hebergements = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalHebergements = count($hebergements);

$hebergementsActifs = 0;
$totalPrix = 0;
$totalNotes = 0;

foreach ($hebergements as $h) {

    if ($h['est_actif']) {
        $hebergementsActifs++;
    }

    $totalPrix += $h['prix_nuit'];
    $totalNotes += $h['note_moyenne'];
}

$prixMoyen = $totalHebergements > 0
    ? round($totalPrix / $totalHebergements)
    : 0;

$noteMoyenne = $totalHebergements > 0
    ? round($totalNotes / $totalHebergements, 1)
    : 0;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Gestion Hébergements - VoyageVista</title>

  <link rel="stylesheet" href="css/gestion-hebergements.css">
</head>

<body>

  <!-- NAVBAR -->

  <header class="navbar">

    <div class="brand">
      <img src="assets/images/logo-voyagevista.png" alt="Logo VoyageVista">
    </div>

    <nav>
      <a href="index.html">Accueil</a>
      <a href="destination.html">Destinations</a>
      <a href="hebergements.html">Hébergements</a>
      <a href="#" class="active-link">
        Dashboard
      </a>
    </nav>

    <div class="nav-icons">
      <span class="heart-icon">♥</span>
      <span>🔔</span>
      <a href="../backend/login.php">👤</a>
    </div>

  </header>

  <!-- HERO -->

  <section class="dashboard-hero">

    <div class="dashboard-hero-content">

      <p class="tag">
        HOST • MANAGE • EARN
      </p>

      <h1>
        Gestion de vos hébergements
      </h1>

      <p class="hero-subtitle">
        Gérez vos logements, vos disponibilités,
        vos réservations et vos revenus
        depuis un seul espace.
      </p>

    </div>

  </section>

  <!-- STATS -->

  <section class="stats-section">

    <div class="stats-grid">

    
        <div class="stat-card">
    <h2><?= $totalHebergements ?></h2>
    <p>🏨 Hébergements</p>
</div>
        

      <div class="stat-card">
    <h2><?= $hebergementsActifs ?></h2>
    <p>✅ Actifs</p>
</div>

      <div class="stat-card">
    <h2><?= $noteMoyenne ?></h2>
    <p>⭐ Note moyenne</p>
</div>

      <div class="stat-card">
    <h2><?= $prixMoyen ?>€</h2>
    <p>💰 Prix moyen / nuit</p>
</div>

    </div>

  </section>

  <!-- ACTION -->

  <section class="action-section">

    <button
  class="add-btn"
  onclick="toggleForm()"
>
  + Ajouter un hébergement
</button>

  </section>

  <div id="addFormContainer" style="display:none; margin-top:20px;">

<form method="POST" class="add-hebergement-form">

    <input
      type="text"
      name="nom"
      placeholder="Nom de l’hébergement"
      required
    >

    <textarea
      name="description"
      placeholder="Description"
      required
    ></textarea>

    <input
      type="number"
      name="prix_nuit"
      placeholder="Prix / nuit"
      required
    >

    <input
      type="text"
      name="image_url"
      placeholder="Nom image (ex: bali-villa.jpg)"
      required
    >

    <input
      type="number"
      name="destination_id"
      placeholder="ID destination"
      required
    >

    <select name="type" required>
    <option value="hotel">Hôtel</option>
    <option value="villa">Villa</option>
    <option value="appartement">Appartement</option>
    <option value="auberge">Auberge</option>
    <option value="camping">Camping</option>
</select>

<input
  type="number"
  name="capacite"
  placeholder="Capacité"
  required
>if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hebergement']))
    <button type="submit" name="add_hebergement">
        Enregistrer
    </button>

</form>

</div>

  <!-- HEBERGEMENTS -->

  <section class="gestion-section">

    <div class="section-title">
      <h2>Vos hébergements</h2>
    </div>

    <div class="gestion-grid">

      <div class="gestion-grid">

<?php foreach($hebergements as $hebergement): ?>

<div class="gestion-card">

    <img
      src="assets/images/<?=
      htmlspecialchars($hebergement['image_url'])
      ?>"
      alt="<?= htmlspecialchars($hebergement['nom']) ?>"
    >

    <div class="gestion-content">

        <span class="status available">

            <?= $hebergement['est_actif']
                ? "Disponible"
                : "Inactif"
            ?>

        </span>

        <h3>
            <?= htmlspecialchars($hebergement['nom']) ?>
        </h3>

        <p>
            <?= htmlspecialchars($hebergement['description']) ?>
        </p>

        <p>
            📍 <?= htmlspecialchars($hebergement['destination_nom']) ?>
        </p>

        <div class="gestion-info">

            <span>
                <?= $hebergement['prix_nuit'] ?>€/nuit
            </span>

            <span>
                ⭐ <?= $hebergement['note_moyenne'] ?>
            </span>

        </div>

        <div class="gestion-buttons">

    <a
      href="detail-hebergement.html?id=<?= $hebergement['id'] ?>"
      class="view-btn"
    >
      Voir détail
    </a>

    <a
      href="modifier-hebergement.php?id=<?= $hebergement['id'] ?>"
      class="edit-btn"
    >
      Modifier
    </a>

    <form
      method="POST"
      onsubmit="return confirm('Supprimer cet hébergement ?');"
      style="display:inline;"
    >
        <input
          type="hidden"
          name="delete_id"
          value="<?= $hebergement['id'] ?>"
        >

        <button
          type="submit"
          class="delete-btn"
        >
          Supprimer
        </button>
    </form>

</div>

    </div>

</div>

<?php endforeach; ?>

</div>
  </section>

  <!-- FOOTER -->

  <footer>
    © 2026 VoyageVista — Host smarter 🌴
  </footer>
<script>
function toggleForm() {
    const form = document.getElementById("addFormContainer");

    if (form.style.display === "none") {
        form.style.display = "block";
    } else {
        form.style.display = "none";
    }
}
</script>
</body>

</html>