<?php
// =============================================
// Configuration BDD - VoyageVista
// =============================================

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "voyagevista";

try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En production : loguer l'erreur, ne jamais l'afficher
    error_log("Erreur BDD : " . $e->getMessage());
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}
?>
 