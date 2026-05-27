<?php
// =========================================
// BACKEND/LOGIN.PHP — VOYAGEVISTA
// Logique de connexion uniquement (pas de HTML)
// =========================================

session_start();

require_once 'configuration.php';

// Si déjà connecté, on redirige
if (isset($_SESSION['user_id'])) {
    header('Location: ../frontend/index.html');
    exit;
}

// Uniquement si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    // --- Validation ---
    if (empty($email) || empty($password)) {
        header('Location: ../frontend/login.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/login.html?error=email_invalide');
        exit;
    }

    // --- Recherche en BDD ---
    $stmt = $pdo->prepare('SELECT id, prenom, nom, email, password, role FROM utilisateurs WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Connexion réussie — on remplit la session
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom']    = $user['nom'];
        $_SESSION['user_role']   = $user['role'];

        // Option "Se souvenir de moi" : cookie 30 jours
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, true);
        }

        // Redirection selon le rôle
        switch ($user['role']) {
            case 'admin':
                header('Location: ../frontend/dashboard-admin.html');
                break;
            case 'prestataire':
                header('Location: ../frontend/dashboard-prestataire.html');
                break;
            default:
                header('Location: ../frontend/index.html');
        }
        exit;

    } else {
        // Mauvais identifiants
        header('Location: ../frontend/login.html?error=identifiants_incorrects');
        exit;
    }

} else {
    // Accès direct au fichier PHP sans POST → on renvoie vers le formulaire
    header('Location: ../frontend/login.html');
    exit;
}