// =============================================
// navbar_session.js — VoyageVista
// Gère le bouton 👤 dans la navbar :
// - Non connecté → pointe vers login.html
// - Connecté → affiche "Mon Profil" + avatar
// À inclure dans TOUTES les pages HTML frontend
// =============================================

document.addEventListener('DOMContentLoaded', async () => {

    try {
        const res  = await fetch('../backend/api_session.php', { credentials: 'include' });
        const json = await res.json();

        const navIcons = document.querySelector('.nav-icons');
        if (!navIcons) return;

        const userLink = navIcons.querySelector('a[href*="login"], a[href*="profil"]');

        if (json.connecte) {

            // ── Connecté : remplacer 👤 par avatar + "Mon Profil" ──
            const initiale = json.username.charAt(0).toUpperCase();

            if (userLink) {
                userLink.href  = 'profil.html';
                userLink.title = `Mon Profil — ${json.username}`;
                userLink.innerHTML = `
                    <span style="
                        display:inline-flex;align-items:center;justify-content:center;
                        width:32px;height:32px;border-radius:50%;
                        background:linear-gradient(135deg,#79a9df,#f3b27d);
                        color:#fff;font-size:13px;font-weight:800;
                        vertical-align:middle;
                    ">${initiale}</span>`;
            }

            // Ajouter lien "Mon Profil" dans la nav si pas déjà là
            const nav = document.querySelector('nav');
            if (nav && !nav.querySelector('a[href="profil.html"]')) {
                const profilLink = document.createElement('a');
                profilLink.href      = 'profil.html';
                profilLink.textContent = 'Mon Profil';
                nav.appendChild(profilLink);
            }

        } else {

            // ── Non connecté : 👤 pointe vers login.html ──
            if (userLink) {
                userLink.href = 'login.html';
                userLink.innerHTML = '<span>👤</span>';
            }
        }

    } catch (err) {
        // Si l'API échoue, comportement par défaut → login.html
        console.warn('Session check failed:', err);
    }

});