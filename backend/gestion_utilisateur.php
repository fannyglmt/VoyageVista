<?php
require_once 'configuration.php';
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}
$users = $pdo->query("SELECT * FROM utilisateurs")->fetchAll();
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Gestion des Utilisateurs</h1>
    <table border="1">
        <?php foreach ($users as $u) { ?>
        <tr>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><a href="?delete=<?php echo $u['id']; ?>">Supprimer</a></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>