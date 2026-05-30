// =============================================
// HISTORIQUE.JS — VoyageVista
// Charge l'historique depuis la BDD
// =============================================

const API_HISTORIQUE = '../backend/api_historique.php';
const IMAGES_PATH    = 'assets/images/';

const destIcons = {
    'bali':'🌴','ibiza':'🎉','santorin':'🌊','barcelone':'🌇',
    'tokyo':'🍜','marrakech':'🕌','chamonix':'🏔️','costa rica':'🌋',
    'algarve':'🏖️','maldives':'🐠','paris':'🗼','alpes':'⛷️',
};

const destGradients = {
    'bali':       'linear-gradient(135deg,#79a9df,#9bdff4)',
    'ibiza':      'linear-gradient(135deg,#f3b27d,#f39b5f)',
    'barcelone':  'linear-gradient(135deg,#f39b5f,#e64b5d)',
    'chamonix':   'linear-gradient(135deg,#9bdff4,#57c5b6)',
    'santorin':   'linear-gradient(135deg,#79a9df,#f3b27d)',
    'marrakech':  'linear-gradient(135deg,#f3b27d,#e64b5d)',
    'tokyo':      'linear-gradient(135deg,#4a68a6,#79a9df)',
    'costa rica': 'linear-gradient(135deg,#57c5b6,#9bdff4)',
    'algarve':    'linear-gradient(135deg,#79a9df,#57c5b6)',
    'maldives':   'linear-gradient(135deg,#9bdff4,#57c5b6)',
};

// ── INIT ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await chargerHistorique();
    setupFiltres();
    setupModaleAvis();
    gererAlertes();
});

// ── CHARGEMENT DEPUIS LA BDD ──────────────────────────────
async function chargerHistorique() {
    try {
        const res  = await fetch(API_HISTORIQUE, { credentials: 'include' });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            window.location.href = 'login.html'; return;
        }
        if (!json.success) throw new Error(json.error);

        // Mettre à jour les stats
        afficherStats(json.stats);

        if (json.data.length === 0) {
            afficherVide();
        } else {
            afficherReservations(json.data);
        }

    } catch (err) {
        console.error('Erreur historique :', err);
        // Fallback : garder le HTML statique de Marie-Zoé
        setupFiltresStatiques();
    }
}

// ── STATS ─────────────────────────────────────────────────
function afficherStats(stats) {
    const els = document.querySelectorAll('.histo-stat span');
    if (els[0]) els[0].textContent = stats.total        || 0;
    if (els[1]) els[1].textContent = stats.pays_visites  || 0;
    if (els[2]) els[2].textContent = stats.total_nuits   || 0;
}

// ── AFFICHAGE DES RÉSERVATIONS ────────────────────────────
function afficherReservations(reservations) {
    const list = document.getElementById('historiqueList');
    if (!list) return;

    list.innerHTML = reservations.map(r => {
        const destKey  = (r.destination_nom || '').toLowerCase();
        const icon     = Object.entries(destIcons).find(([k]) => destKey.includes(k))?.[1] || '✈️';
        const gradient = Object.entries(destGradients).find(([k]) => destKey.includes(k))?.[1]
                         || 'linear-gradient(135deg,#79a9df,#9bdff4)';

        const debut    = formaterDate(r.date_debut);
        const fin      = formaterDate(r.date_fin);
        const nuits    = r.date_debut && r.date_fin
            ? Math.round((new Date(r.date_fin) - new Date(r.date_debut)) / 86400000)
            : '—';

        const statutLabel = {
            'confirmee': '✈️ À venir',
            'en_attente':'⏳ En attente',
            'terminee':  '✅ Terminé',
            'annulee':   '❌ Annulé',
        }[r.statut] || r.statut;

        // Boutons selon statut
        let actions = '';
        if (r.statut === 'confirmee' || r.statut === 'en_attente') {
            actions = `
                <a href="panier.html" class="histo-btn-primary">Voir le séjour</a>
                <a href="destination-detail.html?id=${r.destination_id}"
                   class="histo-btn-secondary">Détails destination</a>`;
        } else if (r.statut === 'terminee') {
            if (r.a_deja_avis > 0) {
                actions = `
                    <span class="histo-btn-secondary" style="opacity:.6;cursor:default">
                        ✅ Avis déjà envoyé
                    </span>
                    <a href="destination-detail.html?id=${r.destination_id}"
                       class="histo-btn-secondary">Réserver à nouveau</a>`;
            } else {
                actions = `
                    <button class="histo-btn-primary btn-avis"
                            data-dest="${esc(r.destination_nom)}"
                            data-dest-id="${r.destination_id}">
                        Laisser un avis ⭐
                    </button>
                    <a href="destination-detail.html?id=${r.destination_id}"
                       class="histo-btn-secondary">Réserver à nouveau</a>`;
            }
        } else if (r.statut === 'annulee') {
            actions = `<a href="destination.html" class="histo-btn-secondary">
                Trouver un autre voyage</a>`;
        }

        return `
        <div class="histo-card" data-statut="${r.statut}">
            <div class="histo-card-img" style="background:${gradient}">
                <span>${icon}</span>
                <div class="histo-statut ${r.statut}">${statutLabel}</div>
            </div>
            <div class="histo-card-content">
                <div class="histo-card-header">
                    <div>
                        <h3>${esc(r.destination_nom)}${r.pays ? ', ' + esc(r.pays) : ''}</h3>
                        <p class="histo-dates">📅 ${debut} → ${fin} • ${nuits} nuit${nuits > 1 ? 's' : ''}</p>
                        <p class="histo-groupe">👥 ${r.nb_voyageurs} voyageur${r.nb_voyageurs > 1 ? 's' : ''}</p>
                    </div>
                    <div class="histo-prix ${r.statut === 'annulee' ? 'annule-prix' : ''}">
                        ${r.prix_total ? Number(r.prix_total).toLocaleString('fr-FR') + '€' : '—'}
                        ${r.statut === 'annulee' ? '<small>remboursé</small>' : ''}
                    </div>
                </div>
                <div class="histo-tags">
                    ${r.service_nom ? `<span>🏨 ${esc(r.service_nom)}</span>` : ''}
                    <span>📍 ${esc(r.region || r.pays || '')}</span>
                </div>
                <div class="histo-actions">${actions}</div>
            </div>
        </div>`;
    }).join('');

    // Attacher les boutons avis dynamiques
    document.querySelectorAll('.btn-avis').forEach(btn => {
        btn.addEventListener('click', () => ouvrirAvis(btn));
    });
}

// ── FILTRES (données BDD) ─────────────────────────────────
function setupFiltres() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filtre = btn.getAttribute('data-filter');

            // Si données dynamiques — refiltrer localement
            const cards = document.querySelectorAll('.histo-card');
            if (cards.length > 0) {
                let visible = 0;
                cards.forEach(card => {
                    const statut = card.getAttribute('data-statut');
                    const show   = filtre === 'all' || statut === filtre;
                    card.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                const empty = document.getElementById('histoEmpty');
                if (empty) empty.classList.toggle('hidden', visible > 0);
            }
        });
    });
}

// ── FILTRES STATIQUES (fallback HTML Marie-Zoé) ───────────
function setupFiltresStatiques() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards      = document.querySelectorAll('.histo-card');
    const empty      = document.getElementById('histoEmpty');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.getAttribute('data-filter');
            cards.forEach(card => {
                const statut = card.getAttribute('data-statut');
                card.classList.toggle('hidden', filter !== 'all' && statut !== filter);
            });
            const visibles = document.querySelectorAll('.histo-card:not(.hidden)');
            empty?.classList.toggle('hidden', visibles.length > 0);
        });
    });
}

// ── ÉTAT VIDE ─────────────────────────────────────────────
function afficherVide() {
    const list = document.getElementById('historiqueList');
    if (list) list.innerHTML = '';
    document.getElementById('histoEmpty')?.classList.remove('hidden');
}

// ── MODALE AVIS ───────────────────────────────────────────
function setupModaleAvis() {
    const modal    = document.getElementById('avisModal');
    const fermer   = document.getElementById('fermerAvis');
    const etoiles  = document.querySelectorAll('.etoile');
    const noteInput= document.getElementById('noteInput');
    let noteSelectionnee = 0;

    // Ouvrir depuis les boutons statiques (HTML Marie-Zoé)
    window.ouvrirAvis = function(btn) {
        const dest   = btn.getAttribute('data-dest');
        const destId = btn.getAttribute('data-dest-id') || '';

        const destNomEl  = document.getElementById('avisDestNom');
        const destInput  = document.getElementById('destNomInput');
        const destIdInput= document.getElementById('destIdInput');

        if (destNomEl)  destNomEl.textContent = dest;
        if (destInput)  destInput.value       = dest;
        if (destIdInput)destIdInput.value     = destId;

        modal?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    // Fermer
    fermer?.addEventListener('click', fermerModal);
    modal?.addEventListener('click', e => { if (e.target === modal) fermerModal(); });

    function fermerModal() {
        modal?.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Étoiles interactives (logique originale conservée)
    etoiles.forEach(e => {
        e.addEventListener('mouseover', () => {
            const val = parseInt(e.getAttribute('data-val'));
            etoiles.forEach((s, i) => s.classList.toggle('active', i < val));
        });
        e.addEventListener('mouseout', () => {
            etoiles.forEach((s, i) => s.classList.toggle('active', i < noteSelectionnee));
        });
        e.addEventListener('click', () => {
            noteSelectionnee = parseInt(e.getAttribute('data-val'));
            if (noteInput) noteInput.value = noteSelectionnee;
            etoiles.forEach((s, i) => s.classList.toggle('active', i < noteSelectionnee));
        });
    });
}

// ── ALERTES URL ───────────────────────────────────────────
function gererAlertes() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('success') === 'avis_envoye') {
        afficherToast('✅ Avis envoyé avec succès, merci !', 'success');
        window.history.replaceState({}, '', 'historique.html');
    }
    if (params.get('error')) {
        afficherToast('⚠️ Une erreur est survenue. Réessaie.', 'error');
    }
}

// ── TOAST ─────────────────────────────────────────────────
function afficherToast(message, type = 'success') {
    document.querySelector('.vv-toast')?.remove();
    const c = type === 'success'
        ? { bg:'#eafff4', color:'#1e7e50', border:'#b7f0d4' }
        : { bg:'#fff0f2', color:'#c0392b', border:'#f5c6cb' };
    const toast = document.createElement('div');
    toast.className = 'vv-toast';
    toast.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        padding:12px 20px;border-radius:16px;font-weight:700;font-size:14px;
        background:${c.bg};color:${c.color};border:1.5px solid ${c.border};
        box-shadow:0 8px 24px rgba(0,0,0,.12);`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ── UTILITAIRES ───────────────────────────────────────────
function formaterDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}