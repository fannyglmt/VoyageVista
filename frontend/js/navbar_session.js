// =============================================
// navbar_session.js — VoyageVista
// Gère le bouton 👤 et 🔔 dynamiquement
// =============================================

const API_SESSION = '../backend/api_session.php';
const API_NOTIFS  = '../backend/api_notifications.php';

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res  = await fetch(API_SESSION, { credentials: 'include' });
        const json = await res.json();

        const navIcons = document.querySelector('.nav-icons');
        if (!navIcons) return;

        // ── Bouton 👤 ─────────────────────────────────────
        const userLink = navIcons.querySelector('a[href*="login"], a[href*="profil"]');

        if (json.connecte) {

            // Avatar avec initiale
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

            // Ajouter "Mon Profil" dans la nav
            const nav = document.querySelector('nav');
            if (nav && !nav.querySelector('a[href="profil.html"]')) {
                const profilLink       = document.createElement('a');
                profilLink.href        = 'profil.html';
                profilLink.textContent = 'Mon Profil';
                nav.appendChild(profilLink);
            }

            // ── Cloche 🔔 → notifications.html ───────────
            const bellSpan = navIcons.querySelector('span:not([style])');
            const bellLink = navIcons.querySelector('a[href*="notification"]');

            // Charger le nombre de notifications non lues
            try {
                const resN  = await fetch(API_NOTIFS, { credentials: 'include' });
                const jsonN = await resN.json();
                const nb    = jsonN.success ? jsonN.non_lues : 0;

                // Remplacer le span 🔔 par un lien cliquable
                const bell = navIcons.querySelector('span');
                if (bell && !bellLink) {
                    const lien    = document.createElement('a');
                    lien.href     = 'notifications.html';
                    lien.title    = 'Mes notifications';
                    lien.style.cssText = 'text-decoration:none;position:relative';
                    lien.innerHTML = `
                        <span>🔔</span>
                        ${nb > 0 ? `
                        <span style="
                            position:absolute;top:-4px;right:-6px;
                            background:#e64b5d;color:#fff;
                            font-size:9px;font-weight:800;
                            width:16px;height:16px;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            border:2px solid #fff;
                        ">${nb > 9 ? '9+' : nb}</span>` : ''}
                    `;
                    bell.replaceWith(lien);
                }
            } catch(e) {
                // Si l'API notifs échoue, juste rendre la cloche cliquable
                const bell = navIcons.querySelector('span');
                if (bell && !bellLink) {
                    const lien    = document.createElement('a');
                    lien.href     = 'notifications.html';
                    lien.innerHTML = '<span>🔔</span>';
                    bell.replaceWith(lien);
                }
            }

        } else {
            // Non connecté → 👤 vers login
            if (userLink) {
                userLink.href      = 'login.html';
                userLink.innerHTML = '<span>👤</span>';
            }
            // Cloche non cliquable si pas connecté
        }

    } catch (err) {
        console.warn('Session check failed:', err);
    }
});