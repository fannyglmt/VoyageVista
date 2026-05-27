<?php
require_once 'configuration.php';
if (isset($_POST['update_statut'])) {
    $stmt = $pdo->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
    $stmt->execute([$_POST['statut'], $_POST['id']]);
}
$reports = $pdo->query("SELECT * FROM signalements")->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Gestion des Signalements</h1>
    <?php foreach ($reports as $r) { ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            Signalement: <?php echo htmlspecialchars($r['raison']); ?> 
            <select name="statut">
                <option value="ouvert">Ouvert</option>
                <option value="resolu">Résolu</option>
            </select>
            <button type="submit" name="update_statut">Mettre à jour</button>
        </form>
    <?php } ?>
</body>
</html>