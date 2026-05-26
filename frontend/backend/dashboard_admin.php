<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Vue d'ensemble du système
$data = [
    'utilisateurs' => $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'destinations' => $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn(),
    'signalements_ouverts' => $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'ouvert'")->fetchColumn()
];

echo json_encode(['success' => true, 'dashboard_data' => $data]);
?>