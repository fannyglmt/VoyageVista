<?php
require_once 'configuration.php';
$total_users = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$total_dest = $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Dashboard Administrateur</h1>
    <p>Utilisateurs inscrits : <?php echo $total_users; ?></p>
    <p>Destinations disponibles : <?php echo $total_dest; ?></p>
    <p>Signalements en attente : <?php echo $total_reports; ?></p>
</body>
</html>