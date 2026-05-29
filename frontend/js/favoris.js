// =============================================
// favoris.js — VoyageVista
// Gère les boutons ♥ sur toutes les pages
// À inclure dans toutes les pages HTML frontend
// =============================================

const API_FAVORIS = '../backend/api_favoris.php';

// IDs des favoris de l'utilisateur (chargés au démarrage)
let mesFavorisIds = [];

// ── INIT ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await chargerMesFavoris();
    activerBoutonsFavoris();
});

// ── CHARGER LES FAVORIS EXISTANTS ─────────────────────────
async function chargerMesFavoris() {
    try {
        const res  = await fetch(API_FAVORIS, { credentials: 'include' });
        const json = await res.json();

        if (json.success) {
            mesFavorisIds = json.data.map(f => f.destination_id);
            // Mettre à jour l'état visuel des boutons déjà présents
            majEtatBoutons();
        }
    } catch (err) {
        // Non connecté ou API indisponible → favoris localStorage seulement
        console.warn('Favoris API non disponible');
    }
}

// ── METTRE À JOUR L'ÉTAT VISUEL DES BOUTONS ──────────────
function majEtatBoutons() {
    document.querySelectorAll('[data-fav-id]').forEach(btn => {
        const id = parseInt(btn.dataset.favId);
        if (mesFavorisIds.includes(id)) {
            btn.classList.add('active');
            btn.title = 'Retirer des favoris';
        } else {
            btn.classList.remove('active');
            btn.title = 'Ajouter aux favoris';
        }
    });
}

// ── ACTIVER LES BOUTONS ♥ ─────────────────────────────────
// Détecte automatiquement tous les boutons avec data-fav-id
function activerBoutonsFavoris() {
    // Délégation d'événement pour les cards générées dynamiquement
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-fav-id]');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const destId = parseInt(btn.dataset.favId);
        if (!destId) return;

        await toggleFavori(destId, btn);
    });
}

// ── TOGGLE FAVORI ─────────────────────────────────────────
async function toggleFavori(destinationId, btn) {
    // Animation immédiate
    btn.style.transform = 'scale(1.3)';
    setTimeout(() => btn.style.transform = 'scale(1)', 200);

    try {
        const res  = await fetch(API_FAVORIS, {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json' },
            body:         JSON.stringify({ destination_id: destinationId, action: 'toggle' }),
        });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            // Pas connecté → rediriger vers login
            afficherToast('Connecte-toi pour ajouter des favoris ❤️', 'warning');
            setTimeout(() => window.location.href = 'login.html', 1500);
            return;
        }

        if (json.success) {
            if (json.action === 'added') {
                mesFavorisIds.push(destinationId);
                btn.classList.add('active');
                btn.title = 'Retirer des favoris';
                afficherToast('Ajouté aux favoris ❤️', 'success');
            } else if (json.action === 'removed') {
                mesFavorisIds = mesFavorisIds.filter(id => id !== destinationId);
                btn.classList.remove('active');
                btn.title = 'Ajouter aux favoris';
                afficherToast('Retiré des favoris', 'info');
            }
        }

    } catch (err) {
        console.error('Erreur favori :', err);
        afficherToast('Erreur, réessaie.', 'error');
    }
}

// ── TOAST DE NOTIFICATION ─────────────────────────────────
function afficherToast(message, type = 'success') {
    // Supprimer l'ancien toast
    document.querySelector('.vv-toast')?.remove();

    const colors = {
        success: { bg: '#eafff4', color: '#1e7e50', border: '#b7f0d4' },
        warning: { bg: '#fff8e1', color: '#92400e', border: '#fde68a' },
        info:    { bg: '#eaf4ff', color: '#4a68a6', border: '#c5defa' },
        error:   { bg: '#fff0f2', color: '#c0392b', border: '#f5c6cb' },
    };
    const c = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.className = 'vv-toast';
    toast.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:12px 20px;border-radius:16px;font-family:'DM Sans',sans-serif;
        font-size:14px;font-weight:700;
        background:${c.bg};color:${c.color};border:1.5px solid ${c.border};
        box-shadow:0 8px 24px rgba(0,0,0,.12);
        animation:slideInToast .3s ease;
    `;
    toast.textContent = message;

    // Ajouter l'animation CSS si pas déjà présente
    if (!document.querySelector('#vv-toast-style')) {
        const style = document.createElement('style');
        style.id = 'vv-toast-style';
        style.textContent = `
            @keyframes slideInToast {
                from { opacity:0; transform:translateY(20px) }
                to   { opacity:1; transform:translateY(0) }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}