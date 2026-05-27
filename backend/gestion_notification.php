<?php
require_once 'configuration.php';
$message_status = "";
if (isset($_POST['send'])) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$_POST['user_id'], $_POST['message']]);
    $message_status = "Notification envoyée !";
}
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Envoyer une Notification</h1>
    <p style="color:green;"><?php echo $message_status; ?></p>
    <form method="POST">
        ID Utilisateur : <input type="number" name="user_id" required><br>
        Message : <textarea name="message" required></textarea><br>
        <button type="submit" name="send">Envoyer</button>
    </form>
</body>
</html>