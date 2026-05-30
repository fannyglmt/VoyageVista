// =============================================
// TRANSPORTS.JS — VoyageVista
// Charge les transports depuis la BDD
// =============================================

const API_TRANSPORTS = '../backend/api_transports.php';
const TRANSPORT_API_PANIER = '../backend/panier.php';
const TRANSPORT_IMAGES = 'assets/images/';

const typeIcons = {
    avion:    '✈',
    train:    '🚄',
    roadtrip: '🚗',
    ferry:    '🛥',
    velo:     '🚴',
    bus:      '🚌',
};

const typeLabels = {
    avion:    'Vol Premium',
    train:    'Train Premium',
    roadtrip: 'Van Road Trip',
    ferry:    'Ferry',
    velo:     'Vélo',
    bus:      'Bus',
};

let tousLesTransports = [];

// ── INIT ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await chargerTransports();
    setupFiltres();
    setupRecherche();
});

// ── CHARGEMENT DEPUIS LA BDD ──────────────────────────────
async function chargerTransports() {
    const grid = document.querySelector('.transport-grid');
    if (!grid) return;

    try {
        const res  = await fetch(API_TRANSPORTS, { credentials: 'include' });
        const json = await res.json();

        if (!json.success) throw new Error(json.error);

        tousLesTransports = json.data;
        afficherTransports(tousLesTransports);

    } catch (err) {
        console.warn('API transports indisponible, mode statique conservé');
        // Garder le HTML statique de Fanny si l'API échoue
        setupBoutonsStatiques();
    }
}

// ── AFFICHER LES CARDS ────────────────────────────────────
function afficherTransports(transports) {
    const grid = document.querySelector('.transport-grid');
    if (!grid) return;

    if (transports.length === 0) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:60px;color:#466789">
                <p style="font-size:20px;font-weight:700">Aucun transport trouvé.</p>
            </div>`;
        return;
    }

    grid.innerHTML = transports.map(t => {
        const icon  = typeIcons[t.type]  || '✈';
        const label = typeLabels[t.type] || t.type;
        const eco   = t.co2_reduit
            ? '<span>🌍 CO₂ réduit</span>'
            : t.type === 'train' || t.type === 'velo'
            ? '<span>🌱 Éco friendly</span>'
            : '<span>🌊 Adventure</span>';

        return `
        <article class="transport-card" data-type="${t.type}" data-id="${t.id}" data-service-id="${t.service_id || ''}">
            <img src="${TRANSPORT_IMAGES}${t.image_url}"
                 alt="${esc(t.nom)}"
                 onerror="this.src='${TRANSPORT_IMAGES}transport-avion.jpg'">
            <div class="transport-content">
                <span class="transport-type">${icon} ${label}</span>
                <h3>${esc(t.depart)} → ${esc(t.arrivee)}</h3>
                <p>${esc(t.description || '')}</p>
                <div class="transport-infos">
                    <span>⏱ ${t.duree || '—'}</span>
                    <span>⭐ ${t.note_moyenne}</span>
                    ${eco}
                </div>
                <div class="transport-bottom">
                    <strong>${t.prix.toLocaleString('fr-FR')}€</strong>
                    <button class="detail-link btn-choisir-transport"
                            data-id="${t.id}"
                            data-service-id="${t.service_id || ''}"
                            data-nom="${esc(t.nom)}"
                            data-prix="${t.prix}">
                        Choisir
                    </button>
                </div>
            </div>
        </article>`;
    }).join('');

    // Attacher les boutons "Choisir"
    document.querySelectorAll('.btn-choisir-transport').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            ajouterTransportAuPanier(btn);
        });
    });
}

// ── AJOUTER AU PANIER ─────────────────────────────────────
async function ajouterTransportAuPanier(btn) {
    const serviceId = btn.dataset.serviceId;
    const nom       = btn.dataset.nom;

    if (!serviceId) {
        afficherToast('Transport non disponible en BDD', 'error');
        return;
    }

    btn.textContent = '⏳ Ajout...';
    btn.disabled    = true;

    try {
        const body = new URLSearchParams({
            action:     'set_transport',
            service_id: serviceId,
        });

        const res  = await fetch(TRANSPORT_API_PANIER, {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        body.toString(),
        });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            afficherToast('Connecte-toi pour ajouter au panier !', 'warning');
            setTimeout(() => window.location.href = 'login.html', 1500);
            btn.textContent = 'Choisir';
            btn.disabled    = false;
            return;
        }

        if (json.success) {
            afficherToast(`✅ ${nom} ajouté au panier !`, 'success');
            btn.textContent      = '✅ Choisi';
            btn.style.background = '#16a34a';
            btn.style.color      = '#fff';
            btn.disabled         = false;
            setTimeout(() => window.location.href = 'panier.html', 1200);
        } else {
            afficherToast(json.error || 'Erreur', 'error');
            btn.textContent = 'Choisir';
            btn.disabled    = false;
        }

    } catch (err) {
        console.error('Erreur transport panier :', err);
        afficherToast('Erreur réseau', 'error');
        btn.textContent = 'Choisir';
        btn.disabled    = false;
    }
}

// ── FILTRES ───────────────────────────────────────────────
function setupFiltres() {
    document.querySelectorAll('.transport-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.transport-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const type = btn.dataset.type;

            if (tousLesTransports.length > 0) {
                // Filtrer depuis les données BDD
                const filtres = type === 'all'
                    ? tousLesTransports
                    : tousLesTransports.filter(t => t.type === type);
                afficherTransports(filtres);
            } else {
                // Fallback statique : filtrer les cards HTML
                document.querySelectorAll('.transport-card').forEach(card => {
                    const match = type === 'all' || card.dataset.type === type;
                    card.style.display = match ? '' : 'none';
                });
            }
        });
    });
}

// ── RECHERCHE ─────────────────────────────────────────────
function setupRecherche() {
    const input = document.getElementById('transportSearch');
    if (!input) return;

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();

        if (tousLesTransports.length > 0) {
            const filtres = tousLesTransports.filter(t =>
                t.nom.toLowerCase().includes(q)     ||
                t.depart.toLowerCase().includes(q)  ||
                t.arrivee.toLowerCase().includes(q) ||
                t.description?.toLowerCase().includes(q)
            );
            afficherTransports(filtres);
        } else {
            // Fallback statique
            document.querySelectorAll('.transport-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(q) ? '' : 'none';
            });
        }
    });
}

// ── BOUTON CTA "Ajouter au panier voyage" ─────────────────
document.addEventListener('click', e => {
    const cta = e.target.closest('.transport-cta a, .transport-cta button');
    if (!cta) return;
    e.preventDefault();
    window.location.href = 'panier.html';
});

// ── FALLBACK STATIQUE ─────────────────────────────────────
function setupBoutonsStatiques() {
    document.querySelectorAll('.transport-bottom a').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const card = link.closest('.transport-card');
            const nom  = card?.querySelector('h3')?.textContent || 'Transport';
            afficherToast(`✅ ${nom} ajouté au panier !`, 'success');
            setTimeout(() => window.location.href = 'panier.html', 1200);
        });
    });
}

// ── TOAST ─────────────────────────────────────────────────
function afficherToast(message, type = 'success') {
    document.querySelector('.vv-toast')?.remove();
    const colors = {
        success: { bg: '#eafff4', color: '#1e7e50', border: '#b7f0d4' },
        warning: { bg: '#fff8e1', color: '#92400e', border: '#fde68a' },
        error:   { bg: '#fff0f2', color: '#c0392b', border: '#f5c6cb' },
    };
    const c = colors[type] || colors.success;
    const toast = document.createElement('div');
    toast.className = 'vv-toast';
    toast.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:14px 22px;border-radius:16px;font-weight:700;font-size:15px;
        background:${c.bg};color:${c.color};border:1.5px solid ${c.border};
        box-shadow:0 8px 24px rgba(0,0,0,.12);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

// ── UTILITAIRES ───────────────────────────────────────────
function esc(s) {
    return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}