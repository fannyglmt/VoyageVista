// =========================================
// PROFIL.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // -----------------------------------------------
  // 1. COMPTEUR ANIMÉ POUR LES STATS
  // -----------------------------------------------
  function animateCounter(el, target, duration = 1200) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(() => {
      start += step;
      if (start >= target) {
        el.textContent = target;
        clearInterval(timer);
      } else {
        el.textContent = Math.floor(start);
      }
    }, 16);
  }

  // Données fictives — à remplacer par les vraies données PHP en session
  const stats = {
    swipes:       24,
    reservations: 3,
    activites:    12,
    favoris:      8,
  };

  // Lance les compteurs quand la section est visible
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(document.getElementById('statSwipes'),       stats.swipes);
        animateCounter(document.getElementById('statReservations'),  stats.reservations);
        animateCounter(document.getElementById('statActivites'),     stats.activites);
        animateCounter(document.getElementById('statFavoris'),       stats.favoris);
        statsObserver.disconnect();
      }
    });
  }, { threshold: 0.3 });

  const statsGrid = document.querySelector('.stats-grid');
  if (statsGrid) statsObserver.observe(statsGrid);

  // -----------------------------------------------
  // 2. BADGE VOYAGEUR selon les stats
  // -----------------------------------------------
  const badge     = document.getElementById('badgeVoyageur');
  const badgeIcon  = badge?.querySelector('.badge-icon');
  const badgeLabel = badge?.querySelector('.badge-label');

  if (badge && badgeIcon && badgeLabel) {
    const total = stats.swipes + stats.reservations * 5 + stats.activites;

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

  // -----------------------------------------------
  // 3. TOGGLE MOT DE PASSE
  // -----------------------------------------------
  document.querySelectorAll('.toggle-pw-profil').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input    = document.getElementById(targetId);
      if (!input) return;
      const hidden = input.type === 'password';
      input.type       = hidden ? 'text' : 'password';
      btn.textContent  = hidden ? '🙈' : '👁';
    });
  });

  // -----------------------------------------------
  // 4. VALIDATION + SOUMISSION FORMULAIRE PROFIL
  // -----------------------------------------------
  const profilForm = document.getElementById('profilForm');
  const alertBox   = document.getElementById('alertModif');

  if (profilForm) {
    profilForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const prenom      = document.getElementById('prenom').value.trim();
      const nom         = document.getElementById('nom').value.trim();
      const email       = document.getElementById('email').value.trim();
      const ancienMdp   = document.getElementById('ancien_mdp').value;
      const nouveauMdp  = document.getElementById('nouveau_mdp').value;
      const confirmMdp  = document.getElementById('confirm_mdp').value;

      // Validation de base
      if (!prenom || !nom || !email) {
        showAlert('error', '⚠️ Prénom, nom et email sont obligatoires.');
        return;
      }

      if (!isValidEmail(email)) {
        showAlert('error', '⚠️ Adresse email invalide.');
        return;
      }

      // Validation mot de passe seulement si rempli
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

      // Animation bouton
      const btn = document.getElementById('btnSave');
      btn.textContent    = 'Sauvegarde en cours…';
      btn.style.opacity  = '0.7';
      btn.style.pointerEvents = 'none';

      // Soumission réelle vers le backend
      profilForm.submit();
    });
  }

  // -----------------------------------------------
  // 5. MESSAGE DE SUCCÈS / ERREUR DEPUIS L'URL
  // -----------------------------------------------
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === 'profil_modifie') {
    showAlert('success', '✅ Profil mis à jour avec succès !');
  }
  if (params.get('error') === 'ancien_mdp_incorrect') {
    showAlert('error', '⚠️ Ancien mot de passe incorrect.');
  }
  if (params.get('error') === 'email_deja_utilise') {
    showAlert('error', '⚠️ Cette adresse email est déjà utilisée.');
  }

  // -----------------------------------------------
  // HELPERS
  // -----------------------------------------------
  function showAlert(type, message) {
    if (!alertBox) return;
    alertBox.innerHTML = `<div class="profil-alert profil-alert-${type}">${message}</div>`;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

});