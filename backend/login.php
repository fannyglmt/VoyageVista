<?php
// =============================================
// Login - VoyageVista (partie Marie-Zoé)
// =============================================

// Déjà connecté → redirection
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin')        header("Location: dashboard_admin.php");
    elseif ($_SESSION['role'] === 'prestataire') header("Location: dashboard_prestataire.php");
    else                                      header("Location: ../frontend/index.html");
    exit;
}

require_once 'configuration.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND est_actif = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Mise à jour dernière connexion
            $pdo->prepare("UPDATE utilisateurs SET derniere_connexion=NOW() WHERE id=?")->execute([$user['id']]);

            if ($user['role'] === 'admin')             header("Location: dashboard_admin.php");
            elseif ($user['role'] === 'prestataire')   header("Location: dashboard_prestataire.php");
            else                                       header("Location: ../frontend/index.html");
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VoyageVista — Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin_style.css">
  <style>
    body{display:flex;flex-direction:column}
    .auth-main{display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:calc(100vh - 64px)}
    @media(max-width:768px){.auth-main{grid-template-columns:1fr}}
    .auth-left{position:relative;background:linear-gradient(135deg,#faf5ff,#fdf2f8,#f0fdfa);display:flex;align-items:center;justify-content:center;padding:3rem;overflow:hidden}
    .auth-left::before{content:'';position:absolute;top:-100px;left:-100px;width:400px;height:400px;background:radial-gradient(circle,rgba(124,92,252,.15) 0%,transparent 65%);pointer-events:none}
    .auth-left::after{content:'';position:absolute;bottom:-80px;right:-80px;width:300px;height:300px;background:radial-gradient(circle,rgba(242,92,162,.12) 0%,transparent 65%);pointer-events:none}
    @media(max-width:768px){.auth-left{display:none}}
    .left-content{position:relative;z-index:1;max-width:380px}
    .auth-tag{font-family:'Syne',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--purple);background:rgba(124,92,252,.1);border:1px solid rgba(124,92,252,.2);padding:.3rem .8rem;border-radius:20px;display:inline-block;margin-bottom:1.5rem}
    .left-content h2{font-family:'Syne',sans-serif;font-size:2.4rem;font-weight:800;line-height:1.2;margin-bottom:1rem;color:var(--text)}
    .left-content h2 span{background:linear-gradient(90deg,var(--purple),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    .left-sub{color:var(--muted);font-size:.95rem;line-height:1.6;margin-bottom:2rem}
    .bubbles{display:flex;flex-wrap:wrap;gap:.6rem}
    .bubble{background:#fff;border:1px solid var(--border);padding:.4rem .9rem;border-radius:20px;font-size:.8rem;color:var(--muted);box-shadow:0 2px 6px rgba(0,0,0,.05)}
    .plane-deco{position:absolute;bottom:2rem;right:2rem;font-size:3rem;opacity:.08;transform:rotate(45deg)}
    .auth-right{display:flex;align-items:center;justify-content:center;padding:3rem 2rem;background:#fff}
    .auth-form-wrap{width:100%;max-width:400px}
    .auth-form-header{margin-bottom:2rem}
    .auth-form-header h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:.4rem;color:var(--text)}
    .auth-form-header p{color:var(--muted);font-size:.85rem}
    .auth-form-header a{color:var(--purple);text-decoration:none;font-weight:500}
    .alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:.85rem 1rem;border-radius:10px;font-size:.85rem;margin-bottom:1.5rem}
    .form-group{margin-bottom:1.2rem}
    .form-group label{display:block;font-size:.8rem;font-weight:500;color:var(--muted);margin-bottom:.5rem}
    .input-wrap{position:relative}
    .input-icon{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);font-size:.9rem;pointer-events:none}
    .input-wrap input{width:100%;background:#fff;border:1px solid var(--border);color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;padding:.75rem 1rem .75rem 2.6rem;border-radius:10px;outline:none;transition:border-color .2s;box-shadow:0 1px 4px rgba(0,0,0,.04)}
    .input-wrap input:focus{border-color:rgba(124,92,252,.5)}
    .input-wrap input::placeholder{color:var(--muted)}
    .toggle-pw{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:.9rem;color:var(--muted)}
    .form-options{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem}
    .checkbox-label{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--muted);cursor:pointer}
    .checkbox-label input{accent-color:var(--purple)}
    .forgot-link{font-size:.82rem;color:var(--purple);text-decoration:none}
    .btn-auth{width:100%;background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;border:none;padding:.85rem;border-radius:12px;font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:opacity .2s;display:flex;align-items:center;justify-content:center;gap:.6rem;box-shadow:0 4px 15px rgba(124,92,252,.3)}
    .btn-auth:hover{opacity:.88}
    .auth-divider{display:flex;align-items:center;gap:1rem;margin:1.5rem 0;color:var(--muted);font-size:.8rem}
    .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--border)}
    .auth-footer{text-align:center}
    .auth-footer p{font-size:.75rem;color:var(--muted);line-height:1.6}
    .auth-footer a{color:var(--purple);text-decoration:none}
    .register-link{display:block;text-align:center;margin-top:1.5rem;font-size:.85rem;color:var(--muted)}
    .register-link a{color:#0d9488;text-decoration:none;font-weight:500}
  </style>
</head>
<body>

<header class="navbar">
  <div class="brand">
    <a href="../frontend/index.html"><img src="../frontend/assets/images/logo-voyagevista.png" alt="VoyageVista"></a>
  </div>
  <nav>
    <a href="../frontend/index.html">Accueil</a>
    <a href="../frontend/destination.html">Destinations</a>
    <a href="../frontend/activites.html">Activités</a>
  </nav>
</header>

<main class="auth-main">

  <!-- PANNEAU GAUCHE -->
  <div class="auth-left">
    <div class="left-content">
      <p class="auth-tag">EXPLORE • SWIPE • TRAVEL</p>
      <h2>Bienvenue<br>de retour <span>✈️</span></h2>
      <p class="left-sub">Reprends là où tu t'es arrêté(e). Ton prochain voyage de groupe t'attend.</p>
      <div class="bubbles">
        <span class="bubble">🌊 Destinations</span>
        <span class="bubble">🌴 Activités</span>
        <span class="bubble">👥 Groupes</span>
        <span class="bubble">💼 Panier</span>
      </div>
    </div>
    <div class="plane-deco">✈</div>
  </div>

  <!-- PANNEAU DROIT -->
  <div class="auth-right">
    <div class="auth-form-wrap">

      <div class="auth-form-header">
        <h1>Connexion</h1>
        <p>Pas encore de compte ? <a href="../frontend/register.html">Créer un compte</a></p>
      </div>

      <?php if($error):?>
      <div class="alert-error">⚠️ <?php echo htmlspecialchars($error);?></div>
      <?php endif;?>

      <form method="POST" id="loginForm">

        <div class="form-group">
          <label for="email">Adresse email</label>
          <div class="input-wrap">
            <span class="input-icon">✉️</span>
            <input type="email" id="email" name="email" placeholder="ton@email.com" required autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="toggle-pw" id="togglePw" aria-label="Afficher">👁</button>
          </div>
        </div>

        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" name="remember" id="remember">
            Se souvenir de moi
          </label>
          <a href="#" class="forgot-link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" name="login" class="btn-auth">
          Se connecter <span>✈</span>
        </button>

      </form>

      <div class="auth-divider"><span>ou</span></div>

      <div class="auth-footer">
        <p>En continuant, tu acceptes les <a href="#">Conditions d'utilisation</a> et la <a href="#">Politique de confidentialité</a> de VoyageVista.</p>
      </div>

      <p class="register-link">Nouveau sur VoyageVista ? <a href="../frontend/register.html">Créer un compte →</a></p>

    </div>
  </div>

</main>

<script>
document.getElementById('togglePw').addEventListener('click', function(){
  const pw = document.getElementById('password');
  pw.type = pw.type === 'password' ? 'text' : 'password';
  this.textContent = pw.type === 'password' ? '👁' : '🙈';
});
</script>
</body>
</html>