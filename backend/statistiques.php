<?php
require_once 'configuration.php';
$stats = $pdo->query("SELECT DATE_FORMAT(date_creation, '%Y-%m') as mois, COUNT(*) as nb FROM reservations GROUP BY mois")->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Statistiques Plateforme</h1>
    <ul>
        <?php foreach ($stats as $s) { ?>
            <li>Mois : <?php echo $s['mois']; ?> - Réservations : <?php echo $s['nb']; ?></li>
        <?php } ?>
    </ul>
</body>
</html>