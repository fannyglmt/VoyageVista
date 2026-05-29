<?php
// =============================================
// CONFIGURATION.PHP — VoyageVista
// BDD + Session partagée frontend/backend
// =============================================

// ── SESSION COOKIE : chemin global ───────────────────────
// Fix MAMP : le cookie de session doit être valide pour
// tout le site (frontend ET backend), pas juste /backend/
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',          // ← clé du fix : accessible depuis tout le site
        'domain'   => '',
        'secure'   => false,        // true si HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// ── BASE DE DONNÉES ───────────────────────────────────────
$servername = "localhost";
$username   = "root";
$password   = "root";             // MAMP : "root" par défaut
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
    die(json_encode([
        'success' => false,
        'error'   => 'Erreur de connexion à la base de données.'
    ]));
}
?>
 