// =============================================
// ADD_TO_CART.JS — VoyageVista
// Gère les boutons "Ajouter au panier voyage"
// sur destination-detail, detail-hebergement,
// activite-detail
// À inclure dans toutes les pages de détail
// =============================================

const API_PANIER = '../backend/panier.php';

// ── Délégation d'événement — fonctionne même si les boutons
// sont créés dynamiquement par script.js après le chargement
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-cart-btn');
    if (!btn) return;

    e.preventDefault();

    const params = new URLSearchParams(window.location.search);
    const page   = window.location.pathname;

    // ── Sur destination-detail.html ───────────────────────
    if (page.includes('destination-detail')) {
        const destId = params.get('id');
        if (!destId) { afficherToast('Destination introuvable', 'error'); return; }

        btn.textContent = '⏳ Ajout en cours...';
        btn.disabled    = true;

        const json = await callPanier('set_destination', { destination_id: destId });

        if (!json.success && json.error === 'non_connecte') {
            afficherToast('Connecte-toi pour ajouter au panier !', 'warning');
            setTimeout(() => window.location.href = 'login.html', 1500);
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
            return;
        }
        if (json.success) {
            afficherToast('✅ Destination ajoutée au panier !', 'success');
            btn.textContent      = '✅ Dans le panier';
            btn.style.background = '#16a34a';
            btn.disabled         = false;
            setTimeout(() => window.location.href = 'panier.html', 1200);
        } else {
            afficherToast(json.error || 'Erreur', 'error');
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
        }
    }

    // ── Sur detail-hebergement.html ───────────────────────
    else if (page.includes('detail-hebergement')) {
        // Priorité 1 : ?id= dans l'URL
        let hebId = params.get('id');

        // Priorité 2 : ID stocké par script.js dans la page
        if (!hebId) {
            const pageEl = document.getElementById('hebergementDetailPage');
            if (pageEl && pageEl.dataset.hebId) {
                hebId = pageEl.dataset.hebId;
            }
        }

        // Priorité 3 : recherche par nom (?hebergement=)
        if (!hebId) {
            const nom = params.get('hebergement');
            if (nom) {
                try {
                    const res  = await fetch(`../backend/api_hebergements.php?search=${encodeURIComponent(nom)}&limit=1`, { credentials: 'include' });
                    const list = await res.json();
                    if (list.success && list.data.length > 0) {
                        hebId = list.data[0].id;
                    }
                } catch(e) {}
            }
        }

        if (!hebId) { afficherToast('Hébergement introuvable — essaie depuis la liste des hébergements', 'error'); return; }

        btn.textContent = '⏳ Ajout en cours...';
        btn.disabled    = true;

        const json = await callPanier('set_hebergement', { hebergement_id: hebId });

        if (!json.success && json.error === 'non_connecte') {
            afficherToast('Connecte-toi pour ajouter au panier !', 'warning');
            setTimeout(() => window.location.href = 'login.html', 1500);
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
            return;
        }
        if (json.success) {
            afficherToast('✅ Hébergement ajouté au panier !', 'success');
            btn.textContent      = '✅ Dans le panier';
            btn.style.background = '#16a34a';
            btn.disabled         = false;
            setTimeout(() => window.location.href = 'panier.html', 1200);
        } else {
            afficherToast(json.error || 'Erreur', 'error');
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
        }
    }

    // ── Sur activite-detail.html ──────────────────────────
    else if (page.includes('activite-detail')) {
        const actId = params.get('id');
        if (!actId) { afficherToast('Activité introuvable', 'error'); return; }

        btn.textContent = '⏳ Ajout en cours...';
        btn.disabled    = true;

        const json = await callPanier('add_activite', { activite_id: actId });

        if (!json.success && json.error === 'non_connecte') {
            afficherToast('Connecte-toi pour ajouter au panier !', 'warning');
            setTimeout(() => window.location.href = 'login.html', 1500);
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
            return;
        }
        if (json.success) {
            afficherToast(`✅ ${json.message || 'Activité ajoutée au panier !'}`, 'success');
            btn.textContent      = '✅ Dans le panier';
            btn.style.background = '#16a34a';
            btn.disabled         = false;
        } else {
            afficherToast(json.error || 'Erreur', 'error');
            btn.textContent = 'Ajouter au panier voyage';
            btn.disabled    = false;
        }
    }
});

// ── APPEL API ─────────────────────────────────────────────
async function callPanier(action, data = {}) {
    try {
        const body = new URLSearchParams({ action, ...data });
        const res  = await fetch(API_PANIER, {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        body.toString(),
        });
        return await res.json();
    } catch (e) {
        console.error('Erreur panier :', e);
        return { success: false, error: 'Erreur réseau' };
    }
}

// ── TOAST ─────────────────────────────────────────────────
function afficherToast(message, type = 'success') {
    document.querySelector('.vv-toast')?.remove();
    const colors = {
        success: { bg:'#eafff4', color:'#1e7e50', border:'#b7f0d4' },
        warning: { bg:'#fff8e1', color:'#92400e', border:'#fde68a' },
        error:   { bg:'#fff0f2', color:'#c0392b', border:'#f5c6cb' },
    };
    const c = colors[type] || colors.success;
    const toast = document.createElement('div');
    toast.className = 'vv-toast';
    toast.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:14px 22px;border-radius:16px;font-weight:700;font-size:15px;
        background:${c.bg};color:${c.color};border:1.5px solid ${c.border};
        box-shadow:0 8px 24px rgba(0,0,0,.12);animation:fadeIn .3s ease;
    `;
    toast.textContent = message;
    if (!document.querySelector('#vv-toast-css')) {
        const s = document.createElement('style');
        s.id = 'vv-toast-css';
        s.textContent = '@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(s);
    }
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}