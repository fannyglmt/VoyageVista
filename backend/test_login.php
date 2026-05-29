<?php
// =============================================
// TEST_LOGIN.PHP — Diagnostic connexion
// À SUPPRIMER après utilisation !
// =============================================
session_name('VOYAGEVISTA_SESSION');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
session_start();

require_once 'configuration.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Test Login - VoyageVista</title>
  <style>
    body{font-family:sans-serif;padding:30px;background:#f7fbff;color:#17375e}
    .box{background:#fff;border-radius:16px;padding:24px;margin-bottom:16px;box-shadow:0 4px 14px rgba(0,0,0,.08)}
    .ok{color:#16a34a;font-weight:700} .err{color:#dc2626;font-weight:700}
    .warn{color:#d97706;font-weight:700}
    h2{color:#4a68a6;margin-bottom:12px;font-size:18px}
    table{width:100%;border-collapse:collapse}
    td,th{padding:8px 12px;border:1px solid #e5e7eb;font-size:13px;text-align:left}
    th{background:#f7fbff;font-weight:700}
    .btn{background:linear-gradient(135deg,#79a9df,#f3b27d);color:#fff;border:none;
         padding:10px 20px;border-radius:20px;cursor:pointer;font-weight:700;font-size:14px}
    input{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;
          font-size:14px;margin-bottom:8px}
    label{font-size:13px;font-weight:600;color:#466789;display:block;margin-bottom:4px}
  </style>
</head>
<body>
<h1 style="color:#4a68a6;margin-bottom:20px">🔍 Diagnostic Connexion — VoyageVista</h1>

<?php
// ── TEST 1 : Connexion BDD ────────────────────────────────
echo '<div class="box"><h2>1. Connexion Base de Données</h2>';
try {
    $count = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    echo "<p class='ok'>✅ Connexion BDD OK — $count utilisateur(s) en base</p>";
} catch(Exception $e) {
    echo "<p class='err'>❌ Erreur BDD : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ── TEST 2 : Liste des utilisateurs ──────────────────────
echo '<div class="box"><h2>2. Utilisateurs en base</h2>';
try {
    $users = $pdo->query("SELECT id, username, email, role, est_actif, LEFT(password,20) as hash_debut FROM utilisateurs")->fetchAll();
    if ($users) {
        echo '<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Rôle</th><th>Actif</th><th>Hash (début)</th></tr>';
        foreach ($users as $u) {
            $actif = $u['est_actif'] ? "<span class='ok'>✅ Oui</span>" : "<span class='err'>❌ Non</span>";
            echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['email']}</td><td>{$u['role']}</td><td>$actif</td><td>{$u['hash_debut']}...</td></tr>";
        }
        echo '</table>';
    } else {
        echo "<p class='err'>❌ Aucun utilisateur trouvé ! Insère les comptes test.</p>";
        echo "<p>Exécute ce SQL dans phpMyAdmin :</p>
        <pre style='background:#f7fbff;padding:12px;border-radius:8px;font-size:12px'>
INSERT INTO utilisateurs (username, email, password, role, est_actif)
VALUES ('admin', 'admin@voyagevista.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'admin', 1),
       ('prestataire', 'prestataire@voyagevista.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'prestataire', 1),
       ('voyageur', 'voyageur@voyagevista.com', '" . password_hash('password', PASSWORD_BCRYPT) . "', 'utilisateur', 1);
        </pre>";
    }
} catch(Exception $e) {
    echo "<p class='err'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ── TEST 3 : Vérification mot de passe ───────────────────
echo '<div class="box"><h2>3. Test mot de passe</h2>';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = trim($_POST['test_email']);
    $testPw    = $_POST['test_password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "<p class='err'>❌ Aucun compte avec cet email.</p>";
    } else {
        echo "<p class='ok'>✅ Compte trouvé : {$user['username']} (rôle: {$user['role']})</p>";
        echo "<p>Actif : " . ($user['est_actif'] ? "<span class='ok'>Oui</span>" : "<span class='err'>Non — bloque la connexion !</span>") . "</p>";

        if (password_verify($testPw, $user['password'])) {
            echo "<p class='ok'>✅ Mot de passe CORRECT !</p>";
            echo "<p style='margin-top:8px'>→ La connexion devrait fonctionner. Problème probable : <strong>session ou cookie</strong>.</p>";
        } else {
            echo "<p class='err'>❌ Mot de passe INCORRECT — le hash ne correspond pas.</p>";
            echo "<p class='warn'>Nouveau hash pour 'password' : <code>" . password_hash('password', PASSWORD_BCRYPT) . "</code></p>";
            echo "<p>Mets à jour avec ce SQL :</p>
            <pre style='background:#f7fbff;padding:10px;border-radius:8px;font-size:12px'>
UPDATE utilisateurs SET password = '" . password_hash($testPw, PASSWORD_BCRYPT) . "' WHERE email = '" . htmlspecialchars($testEmail) . "';</pre>";
        }
    }
}
?>
<form method="POST">
  <label>Email à tester</label>
  <input type="email" name="test_email" placeholder="admin@voyagevista.com" value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
  <label>Mot de passe à tester</label>
  <input type="password" name="test_password" placeholder="password">
  <button type="submit" class="btn">Tester la connexion</button>
</form>
</div>

<?php
// ── TEST 4 : Session courante ─────────────────────────────
echo '<div class="box"><h2>4. Session courante</h2>';
if (isset($_SESSION['user_id'])) {
    echo "<p class='ok'>✅ Session active !</p>";
    echo "<table><tr><th>Clé</th><th>Valeur</th></tr>";
    foreach ($_SESSION as $k => $v) {
        echo "<tr><td>$k</td><td>" . htmlspecialchars((string)$v) . "</td></tr>";
    }
    echo "</table>";
    echo "<p style='margin-top:12px'><a href='dashboard_admin.php' style='color:#4a68a6;font-weight:700'>→ Aller au dashboard admin</a></p>";
} else {
    echo "<p class='warn'>⚠️ Aucune session active (normal si pas encore connecté)</p>";
    echo "<p>Nom de session utilisé : <strong>" . session_name() . "</strong></p>";
    echo "<p>Session ID : <strong>" . session_id() . "</strong></p>";
}
echo '</div>';
?>

<div class="box" style="background:#fff8e1;border:1px solid #fde68a">
  <p style="font-size:13px;color:#92400e">⚠️ <strong>Supprime ce fichier après diagnostic !</strong> Il expose des informations sensibles.</p>
</div>

</body>
</html>