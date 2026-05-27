<?php
require_once 'configuration.php'; // Connexion BDD
$nom = ""; $description = ""; $prix = 0; $error = "";

if (isset($_POST["add_dest"])) {
    $nom = $_POST["nom"] ?? "";
    $prix = (float)($_POST["prix"] ?? 0);
    
    if (empty($nom)) $error .= "Le nom est requis.<br>";
    if ($prix <= 0) $error .= "Le prix doit être positif.<br>";

    if ($error == "") {
        $stmt = $pdo->prepare("INSERT INTO destinations (nom, prix) VALUES (?, ?)");
        $stmt->execute([$nom, $prix]);
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Gestion des Destinations</h1>
    <?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        Nom : <input type="text" name="nom"><br>
        Prix : <input type="number" name="prix"><br>
        <button type="submit" name="add_dest">Ajouter</button>
    </form>
</body>
</html>