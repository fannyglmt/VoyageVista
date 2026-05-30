// =============================================
// VALIDATION.JS — VoyageVista
// Page validation-reservation.html
// =============================================

document.addEventListener('DOMContentLoaded', async () => {
    await preremplirDepuisSession();
    setupPaiement();
    setupFormatCarte();
    setupValidation();
});

// ── Pré-remplir username + email depuis la session ────────
async function preremplirDepuisSession() {
    try {
        const res  = await fetch('../backend/api_session.php', { credentials: 'include' });
        const json = await res.json();

        if (!json.connecte) {
            window.location.href = 'login.html'; return;
        }

        const usernameEl = document.getElementById('inputUsername');
        const emailEl    = document.getElementById('inputEmail');
        if (usernameEl) usernameEl.value = json.username || '';

        // Charger l'email depuis api_profil
        const resP  = await fetch('../backend/api_profil.php', { credentials: 'include' });
        const jsonP = await resP.json();
        if (jsonP.success && emailEl) emailEl.value = jsonP.user.email || '';

        // Charger le recap du panier
        const resK  = await fetch('../backend/panier.php?action=get', { credentials: 'include' });
        const jsonK = await resK.json();
        if (jsonK.success) afficherRecapPanier(jsonK.panier);

    } catch (e) {
        console.warn('Pré-remplissage échoué:', e);
    }
}

// ── Afficher le récap depuis le panier ────────────────────
function afficherRecapPanier(panier) {
    if (!panier) return;
    const nb    = panier.nb_voyageurs || 1;
    const total = panier.total || 0;

    // Destination
    const destEl = document.querySelector('.recap-dest-banner h3, .recap-mini-dest strong');
    if (destEl && panier.destination_nom) destEl.textContent = panier.destination_nom;

    // Total
    document.querySelectorAll('.recap-total-prix, .recap-mini-total span:last-child').forEach(el => {
        el.textContent = formatPrix(total);
    });
    document.querySelectorAll('.recap-par-pers-small strong, .recap-mini-pers').forEach(el => {
        el.textContent = formatPrix(Math.round(total / nb)) + ' / personne';
    });
}

// ── Sélection mode de paiement (original conservé) ────────
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

// ── Format numéro de carte (original conservé) ────────────
function setupFormatCarte() {
    const numCarte = document.getElementById('numCarte');
    if (numCarte) {
        numCarte.addEventListener('input', () => {
            let val = numCarte.value.replace(/\D/g, '').slice(0, 16);
            numCarte.value = val.replace(/(.{4})/g, '$1 ').trim();
        });
    }
}

// ── Validation formulaire ─────────────────────────────────
function setupValidation() {
    const form      = document.getElementById('validForm');
    const alertBox  = document.getElementById('validAlert');
    const params    = new URLSearchParams(window.location.search);

    // Afficher erreur depuis URL
    if (params.get('error') === 'email_invalide' && alertBox) {
        alertBox.innerHTML = `<div class="valid-alert-error">⚠️ Email invalide. Vérifie ton adresse.</div>`;
    }

    if (!form) return;

    form.addEventListener('submit', e => {
        const email = document.getElementById('inputEmail')?.value.trim();
        const cgu   = document.getElementById('cguCheck')?.checked;

        if (!email || !email.includes('@')) {
            e.preventDefault();
            if (alertBox) alertBox.innerHTML = `<div class="valid-alert-error">⚠️ Email invalide.</div>`;
            return;
        }
        if (!cgu) {
            e.preventDefault();
            if (alertBox) alertBox.innerHTML = `<div class="valid-alert-error">⚠️ Accepte les CGV pour continuer.</div>`;
            return;
        }

        // Animation bouton
        const btn = document.getElementById('btnConfirmer');
        if (btn) {
            btn.textContent      = 'Confirmation en cours...';
            btn.style.opacity    = '0.7';
            btn.style.pointerEvents = 'none';
        }
    });
}

function formatPrix(n) { return Number(n||0).toLocaleString('fr-FR') + '€'; }