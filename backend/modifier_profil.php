<?php
// =============================================
// MODIFIER_PROFIL.PHP — VoyageVista
// =============================================
require_once 'configuration.php';
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../frontend/login.html'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/profil.html'); exit;
}

$user_id     = (int)$_SESSION['user_id'];
// ── CORRECTION 1 : username au lieu de prenom/nom ────────
$username    = trim($_POST['username']    ?? '');
$email       = trim($_POST['email']       ?? '');
$ancien_mdp  = $_POST['ancien_mdp']       ?? '';
$nouveau_mdp = $_POST['nouveau_mdp']      ?? '';
$confirm_mdp = $_POST['confirm_mdp']      ?? '';

// ── Validations ──────────────────────────────────────────
if (empty($username) || empty($email)) {
    header('Location: ../frontend/profil.html?error=champs_vides'); exit;
}

if (strlen($username) < 3) {
    header('Location: ../frontend/profil.html?error=username_trop_court'); exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../frontend/profil.html?error=email_invalide'); exit;
}

// Vérifier email unique (sauf si c'est le sien)
$stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ? LIMIT 1');
$stmt->execute([$email, $user_id]);
if ($stmt->fetch()) {
    header('Location: ../frontend/profil.html?error=email_deja_utilise'); exit;
}

// Vérifier username unique (sauf si c'est le sien)
$stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE username = ? AND id != ? LIMIT 1');
$stmt->execute([$username, $user_id]);
if ($stmt->fetch()) {
    header('Location: ../frontend/profil.html?error=username_deja_utilise'); exit;
}

// ── CORRECTION 2 : UPDATE username/email ─────────────────
$stmt = $pdo->prepare('UPDATE utilisateurs SET username = ?, email = ?, derniere_connexion = NOW() WHERE id = ?');
$stmt->execute([$username, $email, $user_id]);

// ── CORRECTION 3 : session cohérente avec login.php ──────
$_SESSION['username'] = $username;

// ── Changement mot de passe (optionnel) ──────────────────
if (!empty($ancien_mdp) || !empty($nouveau_mdp) || !empty($confirm_mdp)) {

    $stmt = $pdo->prepare('SELECT password FROM utilisateurs WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($ancien_mdp, $user['password'])) {
        header('Location: ../frontend/profil.html?error=ancien_mdp_incorrect'); exit;
    }

    if (strlen($nouveau_mdp) < 8) {
        header('Location: ../frontend/profil.html?error=mdp_trop_court'); exit;
    }

    if ($nouveau_mdp !== $confirm_mdp) {
        header('Location: ../frontend/profil.html?error=mdp_non_identiques'); exit;
    }

    $pdo->prepare('UPDATE utilisateurs SET password = ? WHERE id = ?')
        ->execute([password_hash($nouveau_mdp, PASSWORD_BCRYPT), $user_id]);
}

header('Location: ../frontend/profil.html?success=profil_modifie'); exit;