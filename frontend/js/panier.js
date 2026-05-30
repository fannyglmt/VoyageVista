// =========================================
// PANIER.JS — VOYAGEVISTA
// Connecté à panier.php via session
// =========================================

const API_PANIER = '../backend/panier.php';

document.addEventListener('DOMContentLoaded', async () => {
    await chargerPanier();
    setupCompteurVoyageurs();
    setupSupprimerSections();
    setupSupprimerActivites();
    setupViderPanier();
});

// ── CHARGER LE PANIER ─────────────────────────────────────
async function chargerPanier() {
    try {
        const res  = await fetch(API_PANIER + '?action=get', { credentials: 'include' });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            window.location.href = 'login.html'; return;
        }

        if (json.success) afficherPanier(json.panier);

    } catch (err) {
        console.warn('API panier indisponible, mode statique');
        recalculerStatic(); // fallback statique original
    }
}

// ── AFFICHER LE PANIER DEPUIS LA SESSION ──────────────────
function afficherPanier(panier) {
    // ── Destination ──
    const destEl = document.querySelector('.groupe-value');
    if (destEl && panier.destination_nom) {
        destEl.textContent = '🌍 ' + panier.destination_nom;
    }

    // ── Nombre de voyageurs ──
    const nbEl = document.getElementById('nbVoyageurs');
    if (nbEl) nbEl.textContent = panier.nb_voyageurs || 1;

    // ── Dates ──
    if (panier.date_debut && panier.date_fin) {
        const datesEls = document.querySelectorAll('.groupe-value');
        if (datesEls[2]) datesEls[2].textContent =
            `📅 ${formaterDate(panier.date_debut)} → ${formaterDate(panier.date_fin)}`;
        if (datesEls[3]) {
            const nuits = Math.round((new Date(panier.date_fin) - new Date(panier.date_debut)) / 86400000);
            datesEls[3].textContent = `⏱ ${nuits} nuit${nuits > 1 ? 's' : ''}`;
        }
    }

    // ── Hébergement ──
    const cardHeb = document.getElementById('cardHebergement');
    if (cardHeb) {
        if (!panier.hebergement) {
            // Pas d'hébergement → afficher un placeholder
            const content = cardHeb.querySelector('.panier-item-content');
            if (content) content.innerHTML = `
                <div style="text-align:center;padding:20px;color:#8aabb8">
                    <p>Aucun hébergement sélectionné.</p>
                    <a href="hebergements.html"
                       style="color:#4a68a6;font-weight:700;margin-top:8px;display:inline-block">
                        Choisir un hébergement →
                    </a>
                </div>`;
        } else {
            const h = panier.hebergement;
            const nomEl = cardHeb.querySelector('h3');
            if (nomEl) nomEl.textContent = h.nom;
            const nuits = panier.date_debut && panier.date_fin
                ? Math.round((new Date(panier.date_fin) - new Date(panier.date_debut)) / 86400000) : 1;
            const unitEl = cardHeb.querySelector('.prix-unit');
            if (unitEl) unitEl.innerHTML = `${formatPrix(h.prix_nuit)} <small>/nuit</small>`;
            const totEl = cardHeb.querySelector('.prix-total');
            if (totEl) totEl.textContent = formatPrix(h.prix_nuit * nuits);
        }
    }

    // ── Transport ──
    const cardTrans = document.getElementById('cardTransport');
    if (cardTrans) {
        const content = cardTrans.querySelector('.panier-item-content');
        if (!panier.transport) {
            if (content) content.innerHTML = `
                <div style="text-align:center;padding:20px;color:#8aabb8">
                    <p>Aucun transport sélectionné.</p>
                    <a href="transports.html"
                       style="color:#4a68a6;font-weight:700;margin-top:8px;display:inline-block">
                        Choisir un transport →
                    </a>
                </div>`;
        } else {
            const t    = panier.transport;
            const nom  = t.nom || 'Transport';
            const dep  = t.depart || '';
            const arr  = t.arrivee || '';
            const dur  = t.duree || '—';
            const prix = (parseFloat(t.prix) || 0) * nb;

            if (content) content.innerHTML = `
                <div class="item-main">
                    <div class="item-emoji">✈️</div>
                    <div class="item-info">
                        <h3>${esc(nom)}</h3>
                        <p>${dep ? `📍 ${esc(dep)} → ${esc(arr)}` : ''}</p>
                        <p>⏱ ${dur} • 👥 ${nb} pers.</p>
                    </div>
                </div>
                <div class="item-prix">
                    <span class="prix-unit">${formatPrix(parseFloat(t.prix))} <small>/pers.</small></span>
                    <span class="prix-total">${formatPrix(prix)}</span>
                </div>`;
        }
    }

    // ── Activités ──
    const list  = document.getElementById('activitesList');
    const vide  = document.getElementById('activitesVide');
    const count = document.getElementById('activitesCount');
    const acts  = panier.activites || [];

    if (count) count.textContent = acts.length;

    if (list && acts.length > 0) {
        list.style.display = 'flex';
        vide?.classList.add('hidden');
        list.innerHTML = acts.map(a => `
            <div class="activite-item" data-id="${a.id}">
                <div class="activite-emoji">🎯</div>
                <div class="activite-info">
                    <h4>${esc(a.nom)}</h4>
                    <p>📍 ${esc(a.dest || '')} • ⏱ ${a.duree_heures ? a.duree_heures + 'h' : '—'}</p>
                </div>
                <div class="activite-prix">
                    <span class="prix-unit-sm">${formatPrix(a.prix)}/pers.</span>
                    <span class="activite-prix-total">${formatPrix(a.prix * (panier.nb_voyageurs || 1))}</span>
                </div>
                <button class="btn-suppr-activite" data-id="${a.id}">✕</button>
            </div>`).join('');

        // Réattacher événements
        list.querySelectorAll('.btn-suppr-activite').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id   = parseInt(btn.dataset.id);
                const item = btn.closest('.activite-item');
                item.style.opacity = '0'; item.style.transition = '.3s';
                setTimeout(async () => {
                    const json = await callPanier('remove_activite', { activite_id: id });
                    if (json.success) afficherPanier(json.panier);
                }, 300);
            });
        });

    } else if (list) {
        list.style.display = 'none';
        vide?.classList.remove('hidden');
    }

    // ── Récapitulatif ──
    mettreAJourRecap(panier);
}

// ── RÉCAPITULATIF ─────────────────────────────────────────
function mettreAJourRecap(panier) {
    const nb = panier.nb_voyageurs || 1;

    // ── Recalcul total côté JS (ne pas dépendre du total PHP) ──
    let total = 0;

    // Transport
    if (panier.transport) {
        const tPrix = (panier.transport.prix || 0) * nb;
        total += tPrix;
        const el   = document.getElementById('recapPrixTransport');
        const nbEl = document.getElementById('recapNbPers');
        if (el)   el.textContent   = formatPrix(tPrix);
        if (nbEl) nbEl.textContent = `(×${nb})`;
    } else {
        const rt = document.getElementById('recapTransport');
        if (rt) rt.style.opacity = '.4';
    }

    // Hébergement
    if (panier.hebergement) {
        const nuits  = panier.date_debut && panier.date_fin
            ? Math.round((new Date(panier.date_fin) - new Date(panier.date_debut)) / 86400000)
            : 1;
        const hPrix  = (panier.hebergement.prix_nuit || 0) * Math.max(1, nuits);
        total += hPrix;
        const el = document.getElementById('recapPrixHebergement');
        if (el) el.textContent = formatPrix(hPrix);
    }

    // Activités
    const acts      = panier.activites || [];
    const totalActs = acts.reduce((s, a) => s + (a.prix || 0) * nb, 0);
    total += totalActs;
    const elA = document.getElementById('recapPrixActivites');
    if (elA) elA.textContent = formatPrix(totalActs);
    const elN = document.getElementById('recapNbActivites');
    if (elN) elN.textContent = `(×${acts.length})`;

    // Destination dans recap
    const recapDestEl = document.querySelector('.recap-destination strong');
    if (recapDestEl && panier.destination_nom) recapDestEl.textContent = panier.destination_nom;

    // Total général
    const totalEl   = document.getElementById('totalGeneral');
    const parPersEl = document.getElementById('totalParPers');
    if (totalEl)   totalEl.textContent   = formatPrix(total);
    if (parPersEl) parPersEl.textContent = formatPrix(nb > 0 ? Math.round(total / nb) : 0);
}

// ── COMPTEUR VOYAGEURS (original conservé + API) ──────────
function setupCompteurVoyageurs() {
    const btnMoins = document.getElementById('btnMoins');
    const btnPlus  = document.getElementById('btnPlus');
    const nbEl     = document.getElementById('nbVoyageurs');

    btnMoins?.addEventListener('click', async () => {
        const nb   = Math.max(1, parseInt(nbEl?.textContent || 1) - 1);
        if (nbEl) nbEl.textContent = nb;
        const json = await callPanier('set_voyageurs', { nb });
        if (json.success) mettreAJourRecap(json.panier);
    });

    btnPlus?.addEventListener('click', async () => {
        const nb   = parseInt(nbEl?.textContent || 1) + 1;
        if (nbEl) nbEl.textContent = nb;
        const json = await callPanier('set_voyageurs', { nb });
        if (json.success) mettreAJourRecap(json.panier);
    });
}

// ── SUPPRIMER SECTION (original conservé + API) ───────────
function setupSupprimerSections() {
    document.querySelectorAll('.btn-supprimer').forEach(btn => {
        btn.addEventListener('click', async () => {
            const section = btn.dataset.section;
            const card    = document.getElementById('card' + capitalize(section));
            const action  = section === 'transport' ? 'remove_transport' : 'remove_hebergement';

            if (card) { card.style.opacity = '0'; card.style.transition = '.35s'; }
            const json = await callPanier(action);
            if (json.success) {
                setTimeout(() => afficherPanier(json.panier), 350);
            }
        });
    });
}

// ── SUPPRIMER ACTIVITÉ ────────────────────────────────────
function setupSupprimerActivites() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-suppr-activite');
        if (!btn) return;
        const id   = parseInt(btn.dataset.id);
        const item = btn.closest('.activite-item');
        if (item) { item.style.opacity = '0'; item.style.transition = '.3s'; }
        setTimeout(async () => {
            const json = await callPanier('remove_activite', { activite_id: id });
            if (json.success) afficherPanier(json.panier);
        }, 300);
    });
}

// ── VIDER (original conservé + API) ──────────────────────
function setupViderPanier() {
    document.getElementById('btnVider')?.addEventListener('click', async () => {
        if (!confirm('Vider tout le panier ?')) return;
        const json = await callPanier('vider');
        if (json.success) afficherPanier(json.panier);
    });
}

// ── FALLBACK STATIQUE (si API indisponible) ───────────────
function recalculerStatic() {
    let nb   = 4;
    const p  = { transport: 320, hebergement: 1800, activites: [{id:1,base:45},{id:2,base:30},{id:3,base:60}] };

    function calc() {
        let tot = p.transport * nb + p.hebergement;
        p.activites.forEach(a => tot += a.base * nb);
        const el = document.getElementById('totalGeneral');
        if (el) el.textContent = formatPrix(tot);
        const pp = document.getElementById('totalParPers');
        if (pp) pp.textContent = formatPrix(Math.round(tot / nb));
        const pT = document.getElementById('prixTransport');
        if (pT) pT.textContent = formatPrix(p.transport * nb);
    }

    document.getElementById('btnMoins')?.addEventListener('click', () => {
        if (nb > 1) { nb--; document.getElementById('nbVoyageurs').textContent = nb; calc(); }
    });
    document.getElementById('btnPlus')?.addEventListener('click', () => {
        nb++; document.getElementById('nbVoyageurs').textContent = nb; calc();
    });
    document.querySelectorAll('.btn-suppr-activite').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = parseInt(btn.dataset.id);
            const idx = p.activites.findIndex(a => a.id === id);
            if (idx > -1) p.activites.splice(idx, 1);
            btn.closest('.activite-item')?.remove();
            calc();
        });
    });
    document.querySelectorAll('.btn-supprimer').forEach(btn => {
        btn.addEventListener('click', () => {
            const s = btn.dataset.section;
            document.getElementById('card' + capitalize(s))?.remove();
            if (s === 'transport')   p.transport   = 0;
            if (s === 'hebergement') p.hebergement = 0;
            calc();
        });
    });
    document.getElementById('btnVider')?.addEventListener('click', () => {
        if (!confirm('Vider tout le panier ?')) return;
        ['cardTransport','cardHebergement'].forEach(id => document.getElementById(id)?.remove());
        p.activites = []; p.transport = 0; p.hebergement = 0;
        calc();
    });
    calc();
}

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
    } catch (e) { return { success: false }; }
}

// ── UTILITAIRES ───────────────────────────────────────────
function formatPrix(n) { return Number(n||0).toLocaleString('fr-FR') + '€'; }
function formaterDate(s) {
    if (!s) return '—';
    return new Date(s).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'});
}
function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }