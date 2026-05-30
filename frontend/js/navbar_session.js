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
            const bellLink = navIcons.querySelector('a[href*="notification"]');

            // Charger le nombre de notifications non lues
            try {
                const resN  = await fetch(API_NOTIFS, { credentials: 'include' });
                const jsonN = await resN.json();
                const nb    = jsonN.success ? jsonN.non_lues : 0;

                if (bellLink) {
                    // Cloche déjà un lien → juste ajouter le badge
                    if (nb > 0 && !bellLink.querySelector('.notif-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'notif-badge';
                        badge.style.cssText = `
                            position:absolute;top:-4px;right:-6px;
                            background:#e64b5d;color:#fff;
                            font-size:9px;font-weight:800;
                            width:16px;height:16px;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            border:2px solid #fff;
                        `;
                        badge.textContent = nb > 9 ? '9+' : nb;
                        bellLink.style.position = 'relative';
                        bellLink.appendChild(badge);
                    }
                } else {
                    // Cloche = span simple → transformer en lien
                    const bellSpan = Array.from(navIcons.querySelectorAll('span'))
                        .find(s => s.textContent.trim() === '🔔');

                    if (bellSpan) {
                        const lien = document.createElement('a');
                        lien.href  = 'notifications.html';
                        lien.title = 'Mes notifications';
                        lien.style.cssText = 'text-decoration:none;position:relative';
                        lien.innerHTML = `<span>🔔</span>${nb > 0 ? `
                            <span class="notif-badge" style="
                                position:absolute;top:-4px;right:-6px;
                                background:#e64b5d;color:#fff;
                                font-size:9px;font-weight:800;
                                width:16px;height:16px;border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                border:2px solid #fff;
                            ">${nb > 9 ? '9+' : nb}</span>` : ''}`;
                        bellSpan.replaceWith(lien);
                    }
                }
            } catch(e) {
                // Si l'API notifs échoue, juste rendre la cloche cliquable si c'est un span
                const bellSpan = Array.from(navIcons.querySelectorAll('span'))
                    .find(s => s.textContent.trim() === '🔔');
                if (bellSpan && !bellLink) {
                    const lien    = document.createElement('a');
                    lien.href     = 'notifications.html';
                    lien.innerHTML = '<span>🔔</span>';
                    bellSpan.replaceWith(lien);
                }
            }

        } else {
    // Non connecté → 👤 vers login
    if (userLink) {
        userLink.href      = 'login.html';
        userLink.innerHTML = '<span>👤</span>';
    }

    // Cloche → toast + redirect login si pas connecté
    const bellSpan = Array.from(navIcons.querySelectorAll('span'))
        .find(s => s.textContent.trim() === '🔔');
    const bellLink = navIcons.querySelector('a[href*="notification"]');

    const bellEl = bellLink || bellSpan;
    if (bellEl) {
        bellEl.style.cursor = 'pointer';
        bellEl.addEventListener('click', (e) => {
            e.preventDefault();
            afficherToastNavbar('Connecte-toi pour accéder aux notifications 🔔');
            setTimeout(() => window.location.href = 'login.html', 1800);
        });
    }

    // Cœur ♥ → toast + redirect login si pas connecté
    const heartLink = navIcons.querySelector('a[href*="favoris"]');
    const heartSpan = navIcons.querySelector('.heart-icon');

    const heartEl = heartLink || heartSpan;
    if (heartEl) {
        heartEl.style.cursor = 'pointer';
        heartEl.addEventListener('click', (e) => {
            e.preventDefault();
            afficherToastNavbar('Connecte-toi pour accéder à tes favoris ❤️');
            setTimeout(() => window.location.href = 'login.html', 1800);
        });
        
        function afficherToastNavbar(message) {
            document.querySelector('.vv-toast-navbar')?.remove();
            
            const toast = document.createElement('div');
            toast.className = 'vv-toast-navbar';
            toast.style.cssText = `
                position:fixed;bottom:24px;right:24px;z-index:9999;
                padding:12px 20px;border-radius:16px;
                font-size:14px;font-weight:700;
                background:#fff8e1;color:#92400e;border:1.5px solid #fde68a;
                box-shadow:0 8px 24px rgba(0,0,0,.12);
                animation:slideInToast .3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }

    }
}

    } catch (err) {
        console.warn('Session check failed:', err);
    }
});