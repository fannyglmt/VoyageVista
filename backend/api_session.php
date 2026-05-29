<?php
// =============================================
// API_SESSION.PHP — VoyageVista
// Retourne l'état de connexion de l'utilisateur
// Appelé par le JS de la navbar
// =============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Credentials: true');

session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'connecte'  => true,
        'username'  => $_SESSION['username'] ?? '',
        'role'      => $_SESSION['role']     ?? 'utilisateur',
        'user_id'   => (int)$_SESSION['user_id'],
    ]);
} else {
    echo json_encode(['connecte' => false]);
}