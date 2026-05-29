<?php
// =============================================
// LOGIN.PHP — VoyageVista — Logique pure
// =============================================

// Session configurée DIRECTEMENT ici, sans dépendre de configuration.php
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once 'configuration.php';

// ── Traitement du formulaire POST uniquement ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        header('Location: ../frontend/login.html?error=champs_vides'); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/login.html?error=email_invalide'); exit;
    }

    $stmt = $pdo->prepare('SELECT id, username, email, password, role, est_actif FROM utilisateurs WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['est_actif'] || !password_verify($password, $user['password'])) {
        header('Location: ../frontend/login.html?error=identifiants_incorrects'); exit;
    }

    // Connexion réussie
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];

    $pdo->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    if ($remember) {
        setcookie('remember_token', bin2hex(random_bytes(32)), time() + 60*60*24*30, '/', '', false, true);
    }

    switch ($user['role']) {
        case 'admin':        header('Location: dashboard_admin.php');       break;
        case 'prestataire':  header('Location: dashboard_prestataire.php'); break;
        default:             header('Location: ../frontend/index.html');
    }
    exit;

} else {
    // Accès direct sans POST → formulaire
    header('Location: ../frontend/login.html'); exit;
}