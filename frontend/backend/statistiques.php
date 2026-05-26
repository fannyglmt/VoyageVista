<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Exemple de requête pour les réservations par mois
$stmt = $pdo->query("SELECT DATE_FORMAT(date_reservation, '%Y-%m') as mois, COUNT(*) as nb 
                     FROM reservations GROUP BY mois");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>