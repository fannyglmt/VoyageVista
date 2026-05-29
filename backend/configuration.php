<?php
// =============================================
// CONFIGURATION.PHP — VoyageVista
// BDD + Session partagée frontend/backend
// =============================================

// ── SESSION : configuration AVANT session_start() ────────
if (session_status() === PHP_SESSION_NONE) {

    // Nom unique pour éviter les conflits avec d'autres apps MAMP
    session_name('VOYAGEVISTA_SESSION');

    // Cookie valide pour tout le site (frontend ET backend)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ── BASE DE DONNÉES ───────────────────────────────────────
$servername = "localhost";
$username   = "root";
$password   = "root";
$dbname     = "voyagevista";

try {
    $pdo = new PDO(
        "mysql:host=$servername;port=8889;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur BDD : " . $e->getMessage());
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}
?>
 