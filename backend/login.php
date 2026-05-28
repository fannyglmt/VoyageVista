<?php
// =========================================
// BACKEND/LOGIN.PHP — VOYAGEVISTA
// =========================================
session_start();
require_once 'configuration.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin')
        header('Location: dashboard_admin.php');
    elseif ($_SESSION['role'] === 'prestataire')
        header('Location: dashboard_prestataire.php');
    else
        header('Location: ../frontend/index.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        header('Location: ../frontend/login.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/login.html?error=email_invalide');
        exit;
    }

    // ── Requête compatible avec la table utilisateurs ──
    $stmt = $pdo->prepare('
        SELECT id, username, email, password, role, est_actif 
        FROM utilisateurs 
        WHERE email = ? LIMIT 1
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['est_actif']) {
        header('Location: ../frontend/login.html?error=identifiants_incorrects');
        exit;
    }

    if ($user && password_verify($password, $user['password'])) {

        // ── Session compatible avec tous les dashboards ──
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];  // ← clé importante

        // Mise à jour dernière connexion
        $pdo->prepare("UPDATE utilisateurs SET derniere_connexion=NOW() WHERE id=?")
            ->execute([$user['id']]);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 60*60*24*30, '/', '', false, true);
        }

        // ── Redirection vers les vrais fichiers PHP ──
        switch ($user['role']) {
            case 'admin':
                header('Location: dashboard_admin.php');
                break;
            case 'prestataire':
                header('Location: dashboard_prestataire.php');
                break;
            default:
                header('Location: ../frontend/index.html');
        }
        exit;

    } else {
        header('Location: ../frontend/login.html?error=identifiants_incorrects');
        exit;
    }

} else {
    header('Location: ../frontend/login.html');
    exit;
}