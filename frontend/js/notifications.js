// =============================================
// NOTIFICATIONS.JS — VoyageVista
// Charge les notifications depuis la BDD
// =============================================

const API_NOTIFS = '../backend/api_notifications.php';

// ── Types de notifications → icône + couleur ─────────────
const notifStyles = {
    'reservation': { icon: '✈️', gradient: 'linear-gradient(135deg,#79a9df,#9bdff4)', label: 'reservation' },
    'alerte':      { icon: '⚠️', gradient: 'linear-gradient(135deg,#e64b5d,#f39b5f)', label: 'rappel'      },
    'promotion':   { icon: '🏷️', gradient: 'linear-gradient(135deg,#f39b5f,#f3b27d)', label: 'offre'       },
    'info':        { icon: '💬', gradient: 'linear-gradient(135deg,#4a68a6,#79a9df)', label: 'destination' },
};

document.addEventListener('DOMContentLoaded', async () => {
    await chargerNotifications();
    setupFiltres();
    setupBtnToutLire();
});

// ── CHARGEMENT DEPUIS LA BDD ──────────────────────────────
async function chargerNotifications() {
    try {
        const res  = await fetch(API_NOTIFS, { credentials: 'include' });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            // Non connecté → rediriger vers login
            window.location.href = 'login.html';
            return;
        }

        if (!json.success) throw new Error(json.error);

        afficherNotifications(json.data);
        majCompteur(json.non_lues);

    } catch (err) {
        console.error('Erreur notifications :', err);
        // Fallback : afficher les notifications statiques du HTML
        majCompteur(document.querySelectorAll('.notif-item.unread').length);
        setupInteractions();
    }
}

// ── AFFICHAGE DES NOTIFICATIONS ───────────────────────────
function afficherNotifications(notifs) {
    const list = document.getElementById('notifList');
    if (!list) return;

    if (!notifs || notifs.length === 0) {
        list.style.display = 'none';
        document.getElementById('notifEmpty')?.classList.remove('hidden');
        return;
    }

    list.innerHTML = notifs.map(n => {
        const style  = notifStyles[n.type] || notifStyles['info'];
        const isRead = n.lu == 1;
        const date   = formaterDate(n.date_envoi);

        return `
        <div class="notif-item ${isRead ? 'read' : 'unread'}"
             data-type="${style.label}"
             data-id="${n.id}">
            <div class="notif-icon-wrap"
                 style="background:${style.gradient}">
                ${style.icon}
            </div>
            <div class="notif-content">
                <div class="notif-header">
                    <h3>${titreNotif(n.type)}</h3>
                    <span class="notif-time">${date}</span>
                </div>
                <p>${escHtml(n.message)}</p>
            </div>
            <button class="notif-close" data-id="${n.id}">✕</button>
        </div>`;
    }).join('');

    setupInteractions();
}

// ── INTERACTIONS (clic, fermer, marquer lu) ───────────────
function setupInteractions() {

    // Marquer comme lu au clic
    document.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            if (e.target.closest('.notif-close') || e.target.closest('a')) return;

            if (item.classList.contains('unread')) {
                item.classList.remove('unread');
                item.classList.add('read');
                majCompteur(document.querySelectorAll('.notif-item.unread').length);

                // Sauvegarder en BDD
                const id = item.dataset.id;
                if (id) {
                    await fetch(API_NOTIFS, {
                        method:      'POST',
                        credentials: 'include',
                        headers:     { 'Content-Type': 'application/json' },
                        body:         JSON.stringify({ action: 'lire', id: parseInt(id) }),
                    });
                }
            }
        });
    });

    // Supprimer une notification
    document.querySelectorAll('.notif-close').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const item = btn.closest('.notif-item');
            const id   = btn.dataset.id;

            item.classList.add('removing');

            // Supprimer en BDD si vrai ID
            if (id && parseInt(id) > 0) {
                await fetch(API_NOTIFS, {
                    method:      'POST',
                    credentials: 'include',
                    headers:     { 'Content-Type': 'application/json' },
                    body:         JSON.stringify({ action: 'supprimer', id: parseInt(id) }),
                });
            }

            setTimeout(() => {
                item.remove();
                majCompteur(document.querySelectorAll('.notif-item.unread').length);
                checkEmpty();
            }, 350);
        });
    });
}

// ── FILTRES ───────────────────────────────────────────────
function setupFiltres() {
    const filterBtns = document.querySelectorAll('.filter-btn');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');

            document.querySelectorAll('.notif-item').forEach(item => {
                const type = item.getAttribute('data-type');
                if (filter === 'all' || type === filter) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });

            checkEmpty();
        });
    });
}

// ── TOUT MARQUER COMME LU ─────────────────────────────────
function setupBtnToutLire() {
    const btn = document.getElementById('btnToutLire');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        document.querySelectorAll('.notif-item.unread').forEach(item => {
            item.classList.remove('unread');
            item.classList.add('read');
        });

        majCompteur(0);

        // Sauvegarder en BDD
        await fetch(API_NOTIFS, {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json' },
            body:         JSON.stringify({ action: 'tout_lire' }),
        });

        // Feedback visuel (logique originale conservée)
        btn.textContent         = '✓ Tout marqué comme lu';
        btn.style.background    = '#4a68a6';
        btn.style.color         = 'white';
        setTimeout(() => {
            btn.textContent      = 'Tout marquer comme lu';
            btn.style.background = '';
            btn.style.color      = '';
        }, 2000);
    });
}

// ── COMPTEUR ──────────────────────────────────────────────
function majCompteur(nb) {
    const countLabel = document.getElementById('countNonLues');
    if (countLabel) countLabel.textContent = nb;

    const navCount = document.getElementById('navNotifCount');
    if (navCount) {
        navCount.textContent    = nb;
        navCount.style.display  = nb > 0 ? 'flex' : 'none';
    }
}

// ── VÉRIFIER SI LISTE VIDE ────────────────────────────────
function checkEmpty() {
    const visibles = document.querySelectorAll('.notif-item:not(.hidden)');
    const empty    = document.getElementById('notifEmpty');
    const list     = document.getElementById('notifList');
    if (!empty || !list) return;

    if (visibles.length === 0) {
        list.style.display = 'none';
        empty.classList.remove('hidden');
    } else {
        list.style.display = 'flex';
        empty.classList.add('hidden');
    }
}

// ── UTILITAIRES ───────────────────────────────────────────
function titreNotif(type) {
    const titres = {
        'reservation': 'Réservation',
        'alerte':      'Alerte',
        'promotion':   'Offre spéciale',
        'info':        'Information',
    };
    return titres[type] || 'Notification';
}

function formaterDate(str) {
    if (!str) return '—';
    const d    = new Date(str);
    const now  = new Date();
    const diff = Math.floor((now - d) / 1000);

    if (diff < 60)         return 'À l\'instant';
    if (diff < 3600)       return `Il y a ${Math.floor(diff/60)} min`;
    if (diff < 86400)      return `Il y a ${Math.floor(diff/3600)}h`;
    if (diff < 172800)     return 'Hier';
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short' });
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}