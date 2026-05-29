<?php
// =============================================
// RESET_SESSION.PHP — VoyageVista
// Nettoie tous les anciens cookies de session
// À utiliser UNE SEULE FOIS puis supprimer
// =============================================

// Détruire tous les noms de session possibles
$session_names = ['PHPSESSID', 'VOYAGEVISTA_SESSION'];

foreach ($session_names as $name) {
    session_name($name);
    if (isset($_COOKIE[$name])) {
        session_start();
        session_unset();
        session_destroy();
        // Supprimer le cookie sur tous les chemins possibles
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/voyagevista/');
        setcookie($name, '', time() - 3600, '/voyagevista/backend/');
        setcookie($name, '', time() - 3600, '/voyagevista/frontend/');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Reset Session - VoyageVista</title>
  <style>
    body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f7fbff;flex-direction:column;gap:16px}
    .box{background:#fff;border-radius:16px;padding:32px 40px;box-shadow:0 8px 24px rgba(0,0,0,.1);text-align:center;max-width:400px}
    h2{color:#4a68a6;margin-bottom:8px}
    p{color:#466789;margin-bottom:20px;font-size:14px}
    a{background:linear-gradient(135deg,#79a9df,#f3b27d);color:#fff;padding:12px 28px;border-radius:24px;text-decoration:none;font-weight:700}
  </style>
</head>
<body>
  <div class="box">
    <h2>✅ Session réinitialisée</h2>
    <p>Tous les anciens cookies ont été supprimés.<br>Tu peux maintenant te connecter normalement.</p>
    <a href="../frontend/login.html">Se connecter →</a>
  </div>
  <p style="font-size:12px;color:#8aabb8">Supprime ce fichier après utilisation.</p>
</body>
</html>