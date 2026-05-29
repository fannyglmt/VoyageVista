<?php
// =========================================
// BACKEND/REGISTER.PHP — VOYAGEVISTA
// Logique d'inscription
// =========================================

require_once 'configuration.php';
session_start();

// Si déjà connecté → redirection
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin')        header('Location: dashboard_admin.php');
    elseif ($_SESSION['role'] === 'prestataire') header('Location: dashboard_prestataire.php');
    else                                      header('Location: ../frontend/index.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';
    $role             = $_POST['role']                  ?? 'utilisateur';

    // ── Valider le rôle (sécurité : on n'accepte que ces deux valeurs) ──
    $roles_valides = ['utilisateur', 'prestataire'];
    if (!in_array($role, $roles_valides)) $role = 'utilisateur';

    // ── Validations ─────────────────────────────────────────
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../frontend/register.html?error=champs_vides');
        exit;
    }

    if (strlen($username) < 3) {
        header('Location: ../frontend/register.html?error=username_trop_court');
        exit;
    }

    if (strlen($username) > 50) {
        header('Location: ../frontend/register.html?error=username_trop_long');
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

    // ── Vérifier email déjà utilisé ─────────────────────────
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../frontend/register.html?error=email_deja_utilise');
        exit;
    }

    // ── Vérifier username déjà utilisé ──────────────────────
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: ../frontend/register.html?error=username_deja_utilise');
        exit;
    }

    // ── Insertion en BDD ────────────────────────────────────
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare('
        INSERT INTO utilisateurs (username, email, password, role, est_actif, date_inscription)
        VALUES (?, ?, ?, ?, 1, NOW())
    ');
    $stmt->execute([$username, $email, $password_hash, $role]);

    // ── Connexion automatique après inscription ──────────────
    $newId = $pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $newId;
    $_SESSION['username'] = $username;
    $_SESSION['role']     = $role;

    // Redirection selon le rôle
    if ($role === 'prestataire') {
        header('Location: dashboard_prestataire.php');
    } else {
        header('Location: ../frontend/index.html?success=compte_cree');
    }
    exit;

} else {
    header('Location: ../frontend/register.html');
    exit;
}