<?php
// =========================================
// BACKEND/REGISTER.PHP — VOYAGEVISTA
// Logique d'inscription uniquement (pas de HTML)
// =========================================

session_start();

require_once 'configuration.php';

// Si déjà connecté, on redirige
if (isset($_SESSION['user_id'])) {
    header('Location: ../frontend/index.html');
    exit;
}

// Uniquement si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $prenom           = trim($_POST['prenom']           ?? '');
    $nom              = trim($_POST['nom']              ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';

    // --- Validations ---
    if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../frontend/register.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/register.html?error=email_invalide');
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: ../frontend/register.html?error=mdp_trop_court');
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: ../frontend/register.html?error=mdp_non_identiques');
        exit;
    }

    // --- Vérification email déjà utilisé ---
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        header('Location: ../frontend/register.html?error=email_deja_utilise');
        exit;
    }

    // --- Insertion en BDD ---
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('INSERT INTO utilisateurs (prenom, nom, email, password, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$prenom, $nom, $email, $password_hash, 'client']);

    // Inscription réussie → on redirige vers le login avec message de succès
    header('Location: ../frontend/login.html?success=compte_cree');
    exit;

} else {
    // Accès direct sans POST → on renvoie vers le formulaire
    header('Location: ../frontend/register.html');
    exit;
}