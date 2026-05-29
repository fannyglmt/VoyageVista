// =============================================
// PROFIL.JS — VoyageVista
// Version fusionnée : API + animations originales
// =============================================

const API_PROFIL  = '../backend/api_profil.php';
const IMAGES_PATH = 'assets/images/';

const destIcons = {
    'bali':'🌴','ibiza':'🎉','santorin':'🌊','barcelone':'🌇',
    'tokyo':'🍜','marrakech':'🕌','chamonix':'🏔️','costa rica':'🌋',
    'portugal':'🏖️','algarve':'🏖️',
};

// ── INIT ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await chargerProfil();
    gererAlertes();
    gererToggleMdp();
    gererFormulaire();
});

// ── CHARGEMENT PROFIL DEPUIS LA BDD ───────────────────────
async function chargerProfil() {
    try {
        const res  = await fetch(API_PROFIL, { credentials: 'include' });
        const json = await res.json();

        if (!json.success && json.error === 'non_connecte') {
            document.getElementById('profilMain').style.display  = 'none';
            document.getElementById('nonConnecte').style.display = 'block';
            const nav = document.getElementById('navProfil');
            if (nav) nav.href = 'login.html';
            return;
        }

        if (!json.success) throw new Error(json.error);

        document.getElementById('profilMain').style.display  = 'block';
        document.getElementById('nonConnecte').style.display = 'none';

        afficherInfosUtilisateur(json.user);
        afficherStats(json.stats);          // remplit les stats depuis la BDD
        afficherProchainVoyage(json.prochain_voyage);
        afficherFavoris(json.favoris);
        afficherHistorique(json.historique);
        preremplirFormulaire(json.user);

    } catch (err) {
        console.error('Erreur profil :', err);
        document.getElementById('profilMain').style.display  = 'none';
        document.getElementById('nonConnecte').style.display = 'block';
    }
}

// ── INFOS UTILISATEUR ─────────────────────────────────────
function afficherInfosUtilisateur(user) {
    document.getElementById('profilNom').textContent   = user.username;
    document.getElementById('profilEmail').textContent = user.email;
    document.getElementById('avatarEmoji').textContent = user.username.charAt(0).toUpperCase();

    if (user.date_inscription) {
        const d    = new Date(user.date_inscription);
        const mois = d.toLocaleString('fr-FR', { month:'long', year:'numeric' });
        document.getElementById('profilDepuis').textContent =
            mois.charAt(0).toUpperCase() + mois.slice(1);
    }
}

// ── STATS DEPUIS LA BDD + compteur animé (original) ───────
function afficherStats(stats) {
    // Swipes stockés localement côté client
    const swipes = JSON.parse(localStorage.getItem('vv_swipes') || '[]');

    const data = {
        swipes:       swipes.length,
        reservations: stats.reservations ?? 0,
        activites:    stats.activites    ?? 0,
        favoris:      stats.favoris      ?? 0,
    };

    // ── Badge dynamique (logique originale conservée) ──────
    const badge      = document.getElementById('badgeVoyageur');
    const badgeIcon  = badge?.querySelector('.badge-icon');
    const badgeLabel = document.getElementById('badgeLabel');

    if (badge && badgeIcon && badgeLabel) {
        const total = data.swipes + data.reservations * 5 + data.activites;
        if (total >= 60) {
            badgeIcon.textContent  = '🚀';
            badgeLabel.textContent = 'Globe-trotter';
        } else if (total >= 30) {
            badgeIcon.textContent  = '✈️';
            badgeLabel.textContent = 'Aventurier';
        } else {
            badgeIcon.textContent  = '🌍';
            badgeLabel.textContent = 'Explorateur';
        }
    }

    // ── Compteurs animés (logique originale conservée) ─────
    const statsGrid = document.querySelector('.stats-grid');
    if (!statsGrid) return;

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(document.getElementById('statSwipes'),      data.swipes);
                animateCounter(document.getElementById('statReservations'),data.reservations);
                animateCounter(document.getElementById('statActivites'),   data.activites);
                animateCounter(document.getElementById('statFavoris'),     data.favoris);
                statsObserver.disconnect();
            }
        });
    }, { threshold: 0.3 });

    statsObserver.observe(statsGrid);
}

// ── COMPTEUR ANIMÉ (fonction originale conservée) ─────────
function animateCounter(el, target, duration = 1200) {
    if (!el) return;
    let start    = 0;
    const step   = target / (duration / 16);
    const timer  = setInterval(() => {
        start += step;
        if (start >= target) {
            el.textContent = target;
            clearInterval(timer);
        } else {
            el.textContent = Math.floor(start);
        }
    }, 16);
}

// ── PROCHAIN VOYAGE ───────────────────────────────────────
function afficherProchainVoyage(voyage) {
    const wrap = document.getElementById('prochainVoyageWrap');
    if (!wrap) return;

    if (!voyage) {
        wrap.innerHTML = `
            <div class="no-voyage" id="noVoyage">
                <p class="no-voyage-emoji">🗺️</p>
                <p>Aucun voyage prévu pour l'instant.</p>
                <a href="destination.html" class="btn-primary"
                   style="margin-top:16px;display:inline-block">
                    Commencer à swiper
                </a>
            </div>`;
        return;
    }

    const dest  = voyage.destination_nom || '—';
    const key   = dest.toLowerCase();
    const icon  = Object.entries(destIcons).find(([k]) => key.includes(k))?.[1] || '✈️';
    const debut = voyage.date_debut ? formaterDate(voyage.date_debut) : '—';
    const fin   = voyage.date_fin   ? formaterDate(voyage.date_fin)   : '—';

    wrap.innerHTML = `
        <div class="prochain-voyage">
            <div class="voyage-card">
                <div class="voyage-dest-icon">${icon}</div>
                <div class="voyage-info">
                    <h3>${esc(voyage.nom_service || dest)}</h3>
                    <p class="voyage-date">📅 ${debut} → ${fin}</p>
                    <p class="voyage-groupe">💰 ${voyage.prix_total ? voyage.prix_total + '€' : '—'}</p>
                    <div class="voyage-tags">
                        <span>📍 ${esc(dest)}</span>
                        <span>${voyage.statut === 'confirmee' ? '✅ Confirmé' : '⏳ En attente'}</span>
                    </div>
                </div>
            </div>
            <a href="panier.html" class="btn-voir-voyage">Voir le séjour →</a>
        </div>`;
}

// ── FAVORIS ───────────────────────────────────────────────
function afficherFavoris(favoris) {
    const grid    = document.getElementById('favorisGrid');
    const noFavEl = document.getElementById('noFavoris');
    if (!grid) return;

    if (!favoris || favoris.length === 0) {
        grid.innerHTML = '';
        noFavEl?.classList.remove('hidden');
        return;
    }

    noFavEl?.classList.add('hidden');

    const gradients = [
        'linear-gradient(135deg,#79a9df,#9bdff4)',
        'linear-gradient(135deg,#f3b27d,#f39b5f)',
        'linear-gradient(135deg,#9bdff4,#57c5b6)',
        'linear-gradient(135deg,#f39b5f,#e64b5d)',
    ];

    grid.innerHTML = favoris.map((f, i) => {
        const key  = (f.nom || '').toLowerCase();
        const icon = Object.entries(destIcons).find(([k]) => key.includes(k))?.[1] || '🌍';
        return `
            <div class="favori-card">
                <div class="favori-img" style="background:${gradients[i % gradients.length]}">
                    <span>${icon}</span>
                </div>
                <div class="favori-info">
                    <h4>${esc(f.nom)}</h4>
                    <p>dès ${f.prix_base ? f.prix_base + '€' : f.budget || '—'}</p>
                </div>
            </div>`;
    }).join('');
}

// ── HISTORIQUE ────────────────────────────────────────────
function afficherHistorique(historique) {
    const list    = document.getElementById('historiqueList');
    const noHisto = document.getElementById('noHistorique');
    if (!list) return;

    if (!historique || historique.length === 0) {
        list.innerHTML = '';
        noHisto?.classList.remove('hidden');
        return;
    }

    noHisto?.classList.add('hidden');

    const gradients = [
        'linear-gradient(135deg,#79a9df,#9bdff4)',
        'linear-gradient(135deg,#f3b27d,#f39b5f)',
        'linear-gradient(135deg,#9bdff4,#57c5b6)',
    ];

    list.innerHTML = historique.map((h, i) => {
        const dest = h.destination_nom || '—';
        const key  = dest.toLowerCase();
        const icon = Object.entries(destIcons).find(([k]) => key.includes(k))?.[1] || '✈️';
        return `
            <div class="historique-item">
                <div class="historique-icon"
                     style="background:${gradients[i % gradients.length]}">${icon}</div>
                <div class="historique-info">
                    <h4>${esc(h.nom_service || dest)}</h4>
                    <p>🗓 ${formaterDate(h.date_debut)} • 📍 ${esc(dest)}</p>
                </div>
                <span class="historique-badge termine">Terminé</span>
            </div>`;
    }).join('');
}

// ── PRÉ-REMPLIR LE FORMULAIRE ─────────────────────────────
function preremplirFormulaire(user) {
    const inputUsername = document.getElementById('username');
    const inputEmail    = document.getElementById('email');
    if (inputUsername) inputUsername.value = user.username || '';
    if (inputEmail)    inputEmail.value    = user.email    || '';
}

// ── VALIDATION FORMULAIRE (corrigée : username au lieu de prenom/nom) ──
function gererFormulaire() {
    const profilForm = document.getElementById('profilForm');
    const alertBox   = document.getElementById('alertModif');
    if (!profilForm) return;

    profilForm.addEventListener('submit', e => {
        e.preventDefault();

        // ── CORRECTION : username au lieu de prenom + nom ──
        const username   = document.getElementById('username')?.value.trim() || '';
        const email      = document.getElementById('email')?.value.trim()    || '';
        const ancienMdp  = document.getElementById('ancien_mdp')?.value      || '';
        const nouveauMdp = document.getElementById('nouveau_mdp')?.value     || '';
        const confirmMdp = document.getElementById('confirm_mdp')?.value     || '';

        // Validations
        if (!username || !email) {
            showAlert('error', '⚠️ Nom d\'utilisateur et email sont obligatoires.');
            return;
        }
        if (username.length < 3) {
            showAlert('error', '⚠️ Le nom d\'utilisateur doit faire au moins 3 caractères.');
            return;
        }
        if (!isValidEmail(email)) {
            showAlert('error', '⚠️ Adresse email invalide.');
            return;
        }
        if (nouveauMdp || confirmMdp || ancienMdp) {
            if (!ancienMdp) {
                showAlert('error', '⚠️ Saisis ton ancien mot de passe pour le modifier.');
                return;
            }
            if (nouveauMdp.length < 8) {
                showAlert('error', '⚠️ Le nouveau mot de passe doit contenir au moins 8 caractères.');
                return;
            }
            if (nouveauMdp !== confirmMdp) {
                showAlert('error', '⚠️ Les nouveaux mots de passe ne correspondent pas.');
                return;
            }
        }

        // Animation bouton (originale conservée)
        const btn = document.getElementById('btnSave');
        if (btn) {
            btn.textContent         = 'Sauvegarde en cours…';
            btn.style.opacity       = '0.7';
            btn.style.pointerEvents = 'none';
        }

        profilForm.submit();
    });

    function showAlert(type, message) {
        if (!alertBox) return;
        alertBox.innerHTML = `<div class="profil-alert profil-alert-${type}">${message}</div>`;
        alertBox.scrollIntoView({ behavior:'smooth', block:'center' });
    }
}

// ── ALERTES URL (originale étendue avec tous les codes) ───
function gererAlertes() {
    const params  = new URLSearchParams(window.location.search);
    const error   = params.get('error');
    const success = params.get('success');
    const alertBox = document.getElementById('alertModif');
    if (!alertBox) return;

    const messages = {
        champs_vides:          'Veuillez remplir tous les champs obligatoires.',
        email_invalide:        'L\'adresse email n\'est pas valide.',
        email_deja_utilise:    'Cette adresse email est déjà utilisée.',
        username_trop_court:   'Le nom d\'utilisateur doit faire au moins 3 caractères.',
        username_deja_utilise: 'Ce nom d\'utilisateur est déjà pris.',
        ancien_mdp_incorrect:  'Ancien mot de passe incorrect.',
        mdp_trop_court:        'Le nouveau mot de passe doit faire au moins 8 caractères.',
        mdp_non_identiques:    'Les mots de passe ne correspondent pas.',
    };

    if (error && messages[error]) {
        alertBox.innerHTML = `
            <div class="profil-alert profil-alert-error">⚠️ ${messages[error]}</div>`;
        alertBox.scrollIntoView({ behavior:'smooth', block:'center' });
    } else if (success === 'profil_modifie') {
        alertBox.innerHTML = `
            <div class="profil-alert profil-alert-success">✅ Profil mis à jour avec succès !</div>`;
        window.history.replaceState({}, '', 'profil.html');
    }
}

// ── TOGGLE MOT DE PASSE (original conservé) ───────────────
function gererToggleMdp() {
    document.querySelectorAll('.toggle-pw-profil').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input    = document.getElementById(targetId);
            if (!input) return;
            const hidden     = input.type === 'password';
            input.type       = hidden ? 'text' : 'password';
            btn.textContent  = hidden ? '🙈' : '👁';
        });
    });
}

// ── UTILITAIRES ───────────────────────────────────────────
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function esc(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function formaterDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' });
}