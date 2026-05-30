// =============================================
// VALIDATION.JS — VoyageVista
// Charge le récap depuis panier.php
// =============================================

document.addEventListener('DOMContentLoaded', async () => {
    await chargerDepuisSession();
    setupPaiement();
    setupFormatCarte();
    setupValidation();
});

// ── Charger session + panier ──────────────────────────────
async function chargerDepuisSession() {
    try {
        // Vérifier connexion
        const resS  = await fetch('../backend/api_session.php', { credentials: 'include' });
        const jsonS = await resS.json();

        if (!jsonS.connecte) {
            window.location.href = 'login.html'; return;
        }

        // Pré-remplir username
        const usernameEl = document.getElementById('inputUsername');
        if (usernameEl) usernameEl.value = jsonS.username || '';

        // Pré-remplir email depuis profil
        const resP  = await fetch('../backend/api_profil.php', { credentials: 'include' });
        const jsonP = await resP.json();
        const emailEl = document.getElementById('inputEmail');
        if (emailEl && jsonP.success) emailEl.value = jsonP.user.email || '';

        // Charger le panier
        const resK  = await fetch('../backend/panier.php?action=get', { credentials: 'include' });
        const jsonK = await resK.json();

        if (jsonK.success && jsonK.panier) {
            afficherRecap(jsonK.panier);
        }

    } catch (e) {
        console.warn('Erreur chargement session :', e);
    }
}

// ── Mettre à jour le récap avec les vraies données ────────
function afficherRecap(p) {
    const nb    = p.nb_voyageurs || 1;
    const total = p.total        || 0;

    // ── Destination (grand recap gauche) ──
    const destBanner = document.querySelector('.recap-dest-banner');
    if (destBanner && p.destination_nom) {
        destBanner.querySelector('h3').textContent = p.destination_nom;
        const infoEl = destBanner.querySelector('p');
        if (infoEl) {
            const debut = formaterDate(p.date_debut);
            const fin   = formaterDate(p.date_fin);
            const nuits = p.date_debut && p.date_fin
                ? Math.round((new Date(p.date_fin) - new Date(p.date_debut)) / 86400000) : '—';
            infoEl.textContent = `${debut} → ${fin} • ${nuits} nuit${nuits > 1 ? 's' : ''} • ${nb} voyageur${nb > 1 ? 's' : ''}`;
        }
    }

    // ── Lignes du recap gauche ──
    const recapItems = document.querySelector('.recap-items');
    if (recapItems) {
        const lignes = [];

        if (p.transport) {
            lignes.push({ icon: '✈️', nom: p.transport.nom || 'Transport', prix: p.transport.prix * nb });
        }
        if (p.hebergement) {
            const nuits = p.date_debut && p.date_fin
                ? Math.round((new Date(p.date_fin) - new Date(p.date_debut)) / 86400000) : 1;
            lignes.push({ icon: '🏨', nom: `${p.hebergement.nom} — ${nuits} nuit${nuits > 1 ? 's' : ''}`, prix: p.hebergement.prix_nuit * nuits });
        }
        (p.activites || []).forEach(a => {
            lignes.push({ icon: '🎯', nom: a.nom, prix: a.prix * nb });
        });

        if (lignes.length > 0) {
            recapItems.innerHTML = lignes.map(l => `
                <div class="recap-item-row">
                    <span>${l.icon} ${esc(l.nom)}</span>
                    <span>${formatPrix(l.prix)}</span>
                </div>`).join('');
        }
    }

    // ── Total gauche ──
    const totalPrixEl = document.querySelector('.recap-total-prix');
    if (totalPrixEl) totalPrixEl.textContent = formatPrix(total);

    const parPersEl = document.querySelector('.recap-par-pers-small strong');
    if (parPersEl) parPersEl.textContent = `${formatPrix(Math.round(total / nb))} / personne`;

    // ── Mini recap droite ──
    const miniDest = document.querySelector('.recap-mini-dest strong');
    if (miniDest && p.destination_nom) miniDest.textContent = p.destination_nom;

    const miniDestDate = document.querySelector('.recap-mini-dest p');
    if (miniDestDate && p.date_debut && p.date_fin) {
        miniDestDate.textContent = `${formaterDate(p.date_debut)} → ${formaterDate(p.date_fin)}`;
    }

    const miniLines = document.querySelector('.recap-mini-lines');
    if (miniLines) {
        const lignes = [];
        if (p.transport)   lignes.push({ icon: '✈️', nom: 'Transport',   prix: p.transport.prix * nb });
        if (p.hebergement) lignes.push({ icon: '🏨', nom: 'Hébergement', prix: p.hebergement.prix_nuit });
        if ((p.activites||[]).length > 0) {
            const totalActs = p.activites.reduce((s,a) => s + a.prix * nb, 0);
            lignes.push({ icon: '🎯', nom: `Activités (×${p.activites.length})`, prix: totalActs });
        }
        if (lignes.length > 0) {
            miniLines.innerHTML = lignes.map(l => `
                <div class="recap-mini-line">
                    <span>${l.icon} ${esc(l.nom)}</span>
                    <span>${formatPrix(l.prix)}</span>
                </div>`).join('');
        }
    }

    const miniTotal = document.querySelector('.recap-mini-total span:last-child');
    if (miniTotal) miniTotal.textContent = formatPrix(total);

    const miniPers = document.querySelector('.recap-mini-pers');
    if (miniPers) miniPers.textContent = `${formatPrix(Math.round(total / nb))} / personne`;
}

// ── Sélection paiement (original conservé) ────────────────
function setupPaiement() {
    document.querySelectorAll('.paiement-option').forEach(label => {
        label.addEventListener('click', () => {
            document.querySelectorAll('.paiement-option').forEach(l => l.classList.remove('active'));
            label.classList.add('active');
            const val = label.querySelector('input')?.value;
            const carteFields = document.getElementById('carteFields');
            if (carteFields) carteFields.style.display = val === 'carte' ? 'block' : 'none';
        });
    });
}

// ── Format carte (original conservé) ─────────────────────
function setupFormatCarte() {
    document.getElementById('numCarte')?.addEventListener('input', function() {
        let val    = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = val.replace(/(.{4})/g, '$1 ').trim();
    });
}

// ── Validation formulaire ─────────────────────────────────
function setupValidation() {
    const form     = document.getElementById('validForm');
    const alertBox = document.getElementById('validAlert');
    const params   = new URLSearchParams(window.location.search);

    if (params.get('error') === 'email_invalide' && alertBox) {
        alertBox.innerHTML = `<div style="background:#fff0f2;color:#c0392b;padding:12px 16px;border-radius:12px;font-weight:700;margin-bottom:16px">⚠️ Email invalide.</div>`;
    }

    form?.addEventListener('submit', e => {
        const email = document.getElementById('inputEmail')?.value.trim();
        const cgu   = document.getElementById('cguCheck')?.checked;

        if (!email || !email.includes('@')) {
            e.preventDefault();
            if (alertBox) alertBox.innerHTML = `<div style="background:#fff0f2;color:#c0392b;padding:12px 16px;border-radius:12px;font-weight:700;margin-bottom:16px">⚠️ Email invalide.</div>`;
            return;
        }
        if (!cgu) {
            e.preventDefault();
            if (alertBox) alertBox.innerHTML = `<div style="background:#fff0f2;color:#c0392b;padding:12px 16px;border-radius:12px;font-weight:700;margin-bottom:16px">⚠️ Accepte les CGV pour continuer.</div>`;
            return;
        }

        const btn = document.getElementById('btnConfirmer');
        if (btn) {
            btn.textContent         = 'Confirmation en cours...';
            btn.style.opacity       = '0.7';
            btn.style.pointerEvents = 'none';
        }
    });
}

// ── Utilitaires ───────────────────────────────────────────
function formatPrix(n) { return Number(n||0).toLocaleString('fr-FR') + '€'; }
function formaterDate(s) {
    if (!s) return '—';
    return new Date(s).toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
}
function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }