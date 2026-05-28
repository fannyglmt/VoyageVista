<?php
session_start();

require_once "configuration.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];



/* =========================================
   AJOUT DISPONIBILITÉ
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dispo'])) {

    $service_id = $_POST['service_id'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $places = $_POST['places_dispo'];
    $est_bloque = isset($_POST['est_bloque']) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO disponibilites (
            service_id,
            date_debut,
            date_fin,
            places_dispo,
            est_bloque
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $service_id,
        $date_debut,
        $date_fin,
        $places,
        $est_bloque
    ]);

    header("Location: gestion-disponibilites.php");
    exit;
}



/* =========================================
   SUPPRESSION
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dispo'])) {

    $deleteId = intval($_POST['delete_dispo']);

    $stmt = $pdo->prepare("
        DELETE FROM disponibilites
        WHERE id = ?
    ");

    $stmt->execute([$deleteId]);

    header("Location: gestion-disponibilites.php");
    exit;
}



/* =========================================
   RÉCUPÉRATION SERVICES PRESTATAIRE
========================================= */

$sqlServices = "
SELECT *
FROM services
WHERE prestataire_id = ?
ORDER BY nom ASC
";

$stmtServices = $pdo->prepare($sqlServices);
$stmtServices->execute([$user_id]);

$services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);



/* =========================================
   RÉCUPÉRATION DISPONIBILITÉS
========================================= */

$sql = "
SELECT
    d.*,
    s.nom AS service_nom
FROM disponibilites d
JOIN services s
ON d.service_id = s.id
WHERE s.prestataire_id = ?
ORDER BY d.date_debut ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);



/* =========================================
   STATS
========================================= */

$totalDispos = count($disponibilites);

$bloquees = 0;
$totalPlaces = 0;

foreach ($disponibilites as $d) {

    if ($d['est_bloque']) {
        $bloquees++;
    }

    $totalPlaces += $d['places_dispo'];
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestion Disponibilités - VoyageVista</title>

<link rel="stylesheet" href="css/gestion-disponibilites.css">
</head>

<body>

<header class="navbar">

    <div class="brand">
        <img src="assets/images/logo-voyagevista.png" alt="VoyageVista">
    </div>

    <nav>
        <a href="index.html">Accueil</a>
        <a href="hebergements.html">Hébergements</a>
        <a href="activites.html">Activités</a>
        <a href="#" class="active-link">Disponibilités</a>
    </nav>

</header>



<!-- HERO -->

<section class="dashboard-hero">

    <h1>Gestion des disponibilités</h1>

    <p>
        Gérez les périodes disponibles,
        les réservations et les dates bloquées.
    </p>

</section>



<!-- STATS -->

<section class="stats-section">

    <div class="stats-grid">

        <div class="stat-card">
            <h2><?= $totalDispos ?></h2>
            <p>📅 Disponibilités</p>
        </div>

        <div class="stat-card">
            <h2><?= $bloquees ?></h2>
            <p>🚫 Dates bloquées</p>
        </div>

        <div class="stat-card">
            <h2><?= $totalPlaces ?></h2>
            <p>👥 Places disponibles</p>
        </div>

    </div>

</section>



<!-- AJOUT -->

<section class="add-section">

    <button
      class="add-btn"
      onclick="toggleForm()"
    >
      + Ajouter une disponibilité
    </button>

</section>



<div
  id="formContainer"
  style="display:none;"
>

<form method="POST" class="dispo-form">

    <select name="service_id" required>

        <option value="">
            Choisir un service
        </option>

        <?php foreach($services as $service): ?>

        <option value="<?= $service['id'] ?>">

            <?= htmlspecialchars($service['nom']) ?>

        </option>

        <?php endforeach; ?>

    </select>

    <input
      type="date"
      name="date_debut"
      required
    >

    <input
      type="date"
      name="date_fin"
      required
    >

    <input
      type="number"
      name="places_dispo"
      placeholder="Nombre de places"
      required
    >

    <label>

        <input
          type="checkbox"
          name="est_bloque"
        >

        Bloquer cette période

    </label>

    <button
      type="submit"
      name="add_dispo"
    >
      Enregistrer
    </button>

</form>

</div>



<!-- TABLEAU -->

<section class="table-section">

<table>

    <thead>

        <tr>
            <th>Service</th>
            <th>Début</th>
            <th>Fin</th>
            <th>Places</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>

    </thead>

    <tbody>

    <?php foreach($disponibilites as $dispo): ?>

    <tr>

        <td>
            <?= htmlspecialchars($dispo['service_nom']) ?>
        </td>

        <td>
            <?= $dispo['date_debut'] ?>
        </td>

        <td>
            <?= $dispo['date_fin'] ?>
        </td>

        <td>
            <?= $dispo['places_dispo'] ?>
        </td>

        <td>

            <?php if($dispo['est_bloque']): ?>

                <span class="status blocked">
                    Bloqué
                </span>

            <?php else: ?>

                <span class="status active">
                    Disponible
                </span>

            <?php endif; ?>

        </td>

        <td>

            <form
              method="POST"
              onsubmit="return confirm('Supprimer cette disponibilité ?');"
            >

                <input
                  type="hidden"
                  name="delete_dispo"
                  value="<?= $dispo['id'] ?>"
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



<!-- FOOTER -->

<footer>
    © 2026 VoyageVista — Dashboard Prestataire
</footer>



<script>

function toggleForm() {

    const form =
    document.getElementById("formContainer");

    if (form.style.display === "none") {

        form.style.display = "block";

    } else {

        form.style.display = "none";
    }
}

</script>

</body>
</html>