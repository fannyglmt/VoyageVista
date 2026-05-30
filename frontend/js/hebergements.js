// =============================================
// hebergements.js — VoyageVista
// Charge les hébergements depuis la BDD via l'API
// =============================================

console.log("Page Hébergements chargée ✨");

// ── CONFIGURATION ─────────────────────────────────────────
const API_URL = '../backend/api_hebergements.php';
const IMAGES_PATH = 'assets/images/';
const CARDS_PAR_PAGE = 6;

let tousLesHebergements = [];
let hebergementsAffiches = 0;

// ── INITIALISATION ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    chargerHebergements();
    ecouterFiltres();
    ecouterRecherche();
    ecouterShowMore();
});

// ── CHARGEMENT DEPUIS L'API ───────────────────────────────
async function chargerHebergements(filtres = {}) {
    const grid = document.querySelector('.hebergement-grid');
    if (!grid) return;

    // Afficher le loader
    grid.innerHTML = `
        <div class="loader-wrap" style="grid-column:1/-1;text-align:center;padding:60px 0">
            <div style="font-size:2rem;animation:spin 1s linear infinite;display:inline-block">✈</div>
            <p style="margin-top:12px;color:#466789;font-weight:600">Chargement des hébergements...</p>
        </div>
    `;

    try {
        const params = new URLSearchParams(filtres);
        const response = await fetch(`${API_URL}?${params}`);

        if (!response.ok) throw new Error(`Erreur serveur : ${response.status}`);

        const json = await response.json();

        if (!json.success) throw new Error(json.error || 'Erreur inconnue');

        tousLesHebergements = json.data;
        hebergementsAffiches = 0;

        afficherHebergements(grid);

    } catch (err) {
        console.error('Erreur API hébergements :', err);
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:60px 0;color:#e64b5d">
                <div style="font-size:2rem;margin-bottom:12px">⚠️</div>
                <p style="font-weight:700">Impossible de charger les hébergements.</p>
                <p style="font-size:14px;color:#466789;margin-top:6px">${err.message}</p>
            </div>
        `;
    }
}

// ── AFFICHAGE DES CARDS ───────────────────────────────────
function afficherHebergements(grid, append = false) {
    const slice = tousLesHebergements.slice(
        hebergementsAffiches,
        hebergementsAffiches + CARDS_PAR_PAGE
    );

    if (!append) grid.innerHTML = '';

    if (tousLesHebergements.length === 0) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:80px 0;color:#466789">
                <div style="font-size:3rem;margin-bottom:16px">🏖️</div>
                <p style="font-size:20px;font-weight:700">Aucun hébergement trouvé.</p>
                <p style="margin-top:8px;opacity:.7">Essayez d'autres filtres.</p>
            </div>
        `;
        cacherShowMore();
        return;
    }

    slice.forEach(h => {
        const card = creerCardHebergement(h);
        grid.appendChild(card);
        // Déclencher l'animation reveal
        setTimeout(() => card.classList.add('active'), 50);
    });

    hebergementsAffiches += slice.length;

    // Gérer le bouton "Voir plus"
    const btnMore = document.getElementById('showMoreBtn');
    if (btnMore) {
        btnMore.style.display = hebergementsAffiches < tousLesHebergements.length ? 'inline-block' : 'none';
    }
}

// ── CRÉATION D'UNE CARD ───────────────────────────────────
function creerCardHebergement(h) {
    const card = document.createElement('div');
    card.className = 'hebergement-card reveal';

    // Résoudre l'image
    const imgSrc = resolveImage(h.image_url, h.destination_nom);

    // Type lisible
    const typeLabels = {
        hotel: 'Hôtel',
        villa: 'Villa',
        appartement: 'Appartement',
        auberge: 'Auberge',
        camping: 'Camping',
        autre: 'Hébergement'
    };
    const typeLabel = typeLabels[h.type] || 'Hébergement';

    // Badge budget
    const budgetClass = h.budget === '€€€' ? 'luxury' : h.budget === '€€' ? 'medium' : 'budget';

    card.innerHTML = `
        <div class="card-image">
            <img 
                src="${imgSrc}" 
                alt="${escHtml(h.nom)}"
                onerror="this.src='${IMAGES_PATH}hebergement-bg.jpg'"
            >
            <span class="favorite" data-id="${h.id}" title="Ajouter aux favoris">♥</span>
            ${h.budget ? `<span class="budget-tag ${budgetClass}">${h.budget}</span>` : ''}
        </div>
        <div class="card-content">
            <div class="card-top">
                <span class="type">${escHtml(typeLabel)}</span>
                <span class="rating">★ ${h.note_moyenne > 0 ? h.note_moyenne.toFixed(1) : 'Nouveau'}</span>
            </div>
            <h3>${escHtml(h.nom)}</h3>
            <p>${escHtml(truncate(h.description, 80))}</p>
            <p class="card-location">📍 ${escHtml(h.destination_nom)}</p>
            <div class="card-bottom">
                <strong>${h.prix_nuit.toLocaleString('fr-FR')}€ / nuit</strong>
                <a href="detail-hebergement.html?id=${h.id}">Voir détail</a>
            </div>
        </div>
    `;

    // Favori
    card.querySelector('.favorite').addEventListener('click', function() {
        this.classList.toggle('favorited');
        toggleFavori(h.id, this.classList.contains('favorited'));
    });

    return card;
}

// ── FILTRES ───────────────────────────────────────────────
function ecouterFiltres() {
    const selects = document.querySelectorAll('.filters-grid select');
    selects.forEach(select => {
        select.addEventListener('change', appliquerFiltres);
    });
}

function appliquerFiltres() {
    const filtres = {};

    const typeSelect = document.querySelector('select[data-filter="type"]');
    const budgetSelect = document.querySelector('select[data-filter="budget"]');

    if (typeSelect && typeSelect.value !== 'all') filtres.type = typeSelect.value;
    if (budgetSelect && budgetSelect.value !== 'all') filtres.budget = budgetSelect.value;

    // Recherche en cours
    const searchInput = document.querySelector('.hebergement-search input');
    if (searchInput && searchInput.value.trim()) {
        filtres.search = searchInput.value.trim();
    }

    chargerHebergements(filtres);
}

// ── RECHERCHE ─────────────────────────────────────────────
function ecouterRecherche() {
    const searchBtn = document.querySelector('.hebergement-search button');
    const searchInput = document.querySelector('.hebergement-search input');

    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const q = searchInput?.value.trim();
            if (q) chargerHebergements({ search: q });
            else chargerHebergements();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const q = searchInput.value.trim();
                if (q) chargerHebergements({ search: q });
                else chargerHebergements();
            }
        });
    }
}

// ── SHOW MORE ─────────────────────────────────────────────
function ecouterShowMore() {
    const btn = document.getElementById('showMoreBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const grid = document.querySelector('.hebergement-grid');
        if (grid) afficherHebergements(grid, true);
    });
}

function cacherShowMore() {
    const btn = document.getElementById('showMoreBtn');
    if (btn) btn.style.display = 'none';
}

// ── FAVORIS (stockage local) ──────────────────────────────
function toggleFavori(id, actif) {
    const favoris = JSON.parse(localStorage.getItem('vv_favoris_heb') || '[]');
    if (actif) {
        if (!favoris.includes(id)) favoris.push(id);
    } else {
        const idx = favoris.indexOf(id);
        if (idx > -1) favoris.splice(idx, 1);
    }
    localStorage.setItem('vv_favoris_heb', JSON.stringify(favoris));
}

// ── SCROLL REVEAL (logique originale conservée) ───────────
window.addEventListener('scroll', () => {
    const reveals = document.querySelectorAll('.reveal:not(.active)');
    reveals.forEach(card => {
        const windowHeight = window.innerHeight;
        const revealTop = card.getBoundingClientRect().top;
        if (revealTop < windowHeight - 100) {
            card.classList.add('active');
        }
    });
});

// ── UTILITAIRES ───────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.slice(0, len) + '...' : str;
}

// Associe un nom de destination à une image locale
function resolveImage(imageUrl, destinationNom) {
    if (imageUrl) return IMAGES_PATH + imageUrl;

    const map = {
        'bali':       'bali.png',
        'algarve':    'algarve.png',
        'barcelone':  'barcelone.png',
        'chamonix':   'chamonix.png',
        'costa rica': 'costarica.png',
        'ibiza':      'ibiza.png',
        'santorin':   'santorin.png',
        'tokyo':      'food-tour.png',
        'maroc':      'diner-marocain.png',
        'maldives':   'boat.png',
    };

    if (destinationNom) {
        const dest = destinationNom.toLowerCase();
        for (const [key, img] of Object.entries(map)) {
            if (dest.includes(key)) return IMAGES_PATH + img;
        }
    }

    return IMAGES_PATH + 'hebergement-bg.jpg';
}
