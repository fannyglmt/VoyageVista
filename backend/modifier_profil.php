<?php
// =========================================
// BACKEND/MODIFIER_PROFIL.PHP — VOYAGEVISTA
// Modification des infos du profil utilisateur
// =========================================

session_start();
require_once 'configuration.php';

// Vérification connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id      = $_SESSION['user_id'];
    $prenom       = trim($_POST['prenom']       ?? '');
    $nom          = trim($_POST['nom']          ?? '');
    $email        = trim($_POST['email']        ?? '');
    $ancien_mdp   = $_POST['ancien_mdp']        ?? '';
    $nouveau_mdp  = $_POST['nouveau_mdp']       ?? '';
    $confirm_mdp  = $_POST['confirm_mdp']       ?? '';

    // --- Validations de base ---
    if (empty($prenom) || empty($nom) || empty($email)) {
        header('Location: ../frontend/profil.html?error=champs_vides');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../frontend/profil.html?error=email_invalide');
        exit;
    }

    // --- Vérification email unique (sauf si c'est le sien) ---
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ? LIMIT 1');
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        header('Location: ../frontend/profil.html?error=email_deja_utilise');
        exit;
    }

    // --- Mise à jour infos de base ---
    $stmt = $pdo->prepare('UPDATE utilisateurs SET prenom = ?, nom = ?, email = ? WHERE id = ?');
    $stmt->execute([$prenom, $nom, $email, $user_id]);

    // Mise à jour de la session
    $_SESSION['user_prenom'] = $prenom;
    $_SESSION['user_nom']    = $nom;
    $_SESSION['user_email']  = $email;

    // --- Changement de mot de passe (optionnel) ---
    if (!empty($ancien_mdp) || !empty($nouveau_mdp) || !empty($confirm_mdp)) {

        // Récupère le hash actuel
        $stmt = $pdo->prepare('SELECT password FROM utilisateurs WHERE id = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($ancien_mdp, $user['password'])) {
            header('Location: ../frontend/profil.html?error=ancien_mdp_incorrect');
            exit;
        }

        if (strlen($nouveau_mdp) < 8) {
            header('Location: ../frontend/profil.html?error=mdp_trop_court');
            exit;
        }

        if ($nouveau_mdp !== $confirm_mdp) {
            header('Location: ../frontend/profil.html?error=mdp_non_identiques');
            exit;
        }

        $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE utilisateurs SET password = ? WHERE id = ?');
        $stmt->execute([$nouveau_hash, $user_id]);
    }

    header('Location: ../frontend/profil.html?success=profil_modifie');
    exit;

} else {
    header('Location: ../frontend/profil.html');
    exit;
}