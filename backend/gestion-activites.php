<?php
session_start();

require_once "configuration.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];





/* =========================
   AJOUT ACTIVITÉ
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_activite'])) {

    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $categorie = $_POST['categorie'];
    $prix = $_POST['prix'];
    $duree = $_POST['duree_heures'];
    $image = $_POST['image_url'];
    $destinationId = $_POST['destination_id'];

    $stmt = $pdo->prepare("
        INSERT INTO activites (
            nom,
            description,
            categorie,
            prix,
            duree_heures,
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
        $categorie,
        $prix,
        $duree,
        $image,
        $destinationId,
        $user_id
    ]);

    header("Location: gestion-activites.php");
    exit;
}





/* =========================
   SUPPRESSION
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_id'])) {

    $deleteId = intval($_POST['delete_id']);

    $stmt = $pdo->prepare("
        DELETE FROM activites
        WHERE id = ?
    ");

    $stmt->execute([$deleteId]);

    header("Location: gestion-activites.php");
    exit;
}





/* =========================
   RECUP ACTIVITÉS
========================= */

$sql = "
SELECT
    a.*,
    d.nom AS destination_nom
FROM activites a
JOIN destinations d
ON a.destination_id = d.id
WHERE a.prestataire_id = ?
ORDER BY a.date_creation DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$activites = $stmt->fetchAll(PDO::FETCH_ASSOC);





/* =========================
   STATS
========================= */

$totalActivites = count($activites);

$actives = 0;
$totalNotes = 0;

foreach ($activites as $a) {

    if ($a['est_actif']) {
        $actives++;
    }

    $totalNotes += $a['note_moyenne'];
}

$noteMoyenne = $totalActivites > 0
    ? round($totalNotes / $totalActivites, 1)
    : 0;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion Activités</title>

<link rel="stylesheet" href="css/gestion-activites.css">
</head>

<body>

<header class="navbar">

  <div class="brand">
    <img src="assets/images/logo-voyagevista.png">
  </div>

  <nav>
    <a href="index.html">Accueil</a>
    <a href="gestion-hebergements.php">
      Hébergements
    </a>
    <a href="#" class="active-link">
      Activités
    </a>
  </nav>

</header>





<section class="dashboard-hero">

  <p class="tag">
    PROVIDER • ACTIVITIES • MANAGEMENT
  </p>

  <h1>
    Gestion des activités
  </h1>

  <p>
    Gérez vos expériences et excursions.
  </p>

</section>





<section class="stats-section">

  <div class="stat-card">
    <h2><?= $totalActivites ?></h2>
    <p>Activités</p>
  </div>

  <div class="stat-card">
    <h2><?= $actives ?></h2>
    <p>Actives</p>
  </div>

  <div class="stat-card">
    <h2><?= $noteMoyenne ?></h2>
    <p>⭐ Note moyenne</p>
  </div>

</section>





<section class="table-section">

<div class="table-header">

<h2>Liste des activités</h2>

<button
  class="add-btn"
  onclick="toggleForm()"
>
  + Ajouter une activité
</button>

</div>





<div
  id="addFormContainer"
  style="display:none;"
>

<form method="POST" class="add-form">

<input
  type="text"
  name="nom"
  placeholder="Nom activité"
  required
>

<textarea
  name="description"
  placeholder="Description"
  required
></textarea>

<input
  type="text"
  name="categorie"
  placeholder="Catégorie"
  required
>

<input
  type="number"
  name="prix"
  placeholder="Prix"
  required
>

<input
  type="number"
  step="0.1"
  name="duree_heures"
  placeholder="Durée (heures)"
  required
>

<input
  type="text"
  name="image_url"
  placeholder="Image"
  required
>

<input
  type="number"
  name="destination_id"
  placeholder="ID destination"
  required
>

<button
  type="submit"
  name="add_activite"
>
  Enregistrer
</button>

</form>

</div>





<table>

<thead>
<tr>
<th>Activité</th>
<th>Lieu</th>
<th>Prix</th>
<th>Durée</th>
<th>Statut</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($activites as $activite): ?>

<tr>

<td>
<?= htmlspecialchars($activite['nom']) ?>
</td>

<td>
<?= htmlspecialchars($activite['destination_nom']) ?>
</td>

<td>
<?= $activite['prix'] ?>€
</td>

<td>
<?= $activite['duree_heures'] ?>h
</td>

<td>

<span class="status active">

<?= $activite['est_actif']
? "Disponible"
: "Inactif"
?>

</span>

</td>

<td>

<a
  href="modifier-activite.php?id=<?= $activite['id'] ?>"
  class="edit-btn"
>
  Modifier
</a>

<form
  method="POST"
  style="display:inline;"
  onsubmit="return confirm('Supprimer cette activité ?');"
>

<input
  type="hidden"
  name="delete_id"
  value="<?= $activite['id'] ?>"
>

<button
  type="submit"
  class="delete-btn"
>
  Supprimer
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</section>





<footer>
© 2026 VoyageVista
</footer>

<script>
function toggleForm() {

    const form =
    document.getElementById(
        "addFormContainer"
    );

    if (form.style.display === "none") {
        form.style.display = "block";
    } else {
        form.style.display = "none";
    }
}
</script>

</body>
</html>