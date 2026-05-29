<?php
// =============================================
// LOGIN.PHP — VoyageVista
// Logique uniquement — pas de HTML
// Le formulaire est dans frontend/login.html
// =============================================

require_once 'configuration.php';
session_start();

// ── Déjà connecté → redirection selon le rôle ────────────
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: dashboard_admin.php'); break;
        case 'prestataire':
            header('Location: dashboard_prestataire.php'); break;
        default:
            header('Location: ../frontend/index.html');
    }
    exit;
}

// ── Traitement du formulaire POST ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $remember = isset($_POST['remember']);

    // Validations
    if (empty($email) || empty($password)) {
        header('Location: ../frontend/login.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/login.html?error=email_invalide');
        exit;
    }

    // Recherche en BDD
    $stmt = $pdo->prepare('
        SELECT id, username, email, password, role, est_actif
        FROM utilisateurs
        WHERE email = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Vérification compte actif
    if (!$user || !$user['est_actif']) {
        header('Location: ../frontend/login.html?error=identifiants_incorrects');
        exit;
    }

    // Vérification mot de passe
    if (!password_verify($password, $user['password'])) {
        header('Location: ../frontend/login.html?error=identifiants_incorrects');
        exit;
    }

    // ── Connexion réussie ──────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];

    // Mise à jour dernière connexion
    $pdo->prepare('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    // Cookie "Se souvenir de moi" (30 jours)
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + 60*60*24*30, '/', '', false, true);
    }

    // Redirection selon le rôle
    switch ($user['role']) {
        case 'admin':
            header('Location: dashboard_admin.php'); break;
        case 'prestataire':
            header('Location: dashboard_prestataire.php'); break;
        default:
            header('Location: ../frontend/index.html');
    }
    exit;

} else {
    // Accès direct sans POST → renvoyer vers le formulaire
    header('Location: ../frontend/login.html');
    exit;
}