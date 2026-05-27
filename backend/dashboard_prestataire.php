<?php
require_once 'configuration.php';
// Supposons que l'ID du prestataire soit en session
$pid = $_SESSION['user_id']; 
$services = $pdo->prepare("SELECT * FROM services WHERE prestataire_id = ?");
$services->execute([$pid]);
$data = $services->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Mon Dashboard Prestataire</h1>
    <p>Liste de mes services :</p>
    <ul>
        <?php foreach ($data as $s) { echo "<li>" . htmlspecialchars($s['nom']) . "</li>"; } ?>
    </ul>
</body>
</html>