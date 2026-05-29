// =========================================
// PANIER.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // -----------------------------------------------
  // DONNÉES DU PANIER
  // -----------------------------------------------
  let nbVoyageurs = 4;

  const prix = {
    transport:    320,   // par personne
    hebergement:  1800,  // total fixe (10 nuits)
    activites: [
      { id: 1, base: 45 },
      { id: 2, base: 30 },
      { id: 3, base: 60 },
    ]
  };

  let sections = {
    transport:   true,
    hebergement: true,
  };

  // -----------------------------------------------
  // 1. COMPTEUR VOYAGEURS
  // -----------------------------------------------
  const btnMoins = document.getElementById('btnMoins');
  const btnPlus  = document.getElementById('btnPlus');
  const nbEl     = document.getElementById('nbVoyageurs');

  if (btnMoins && btnPlus && nbEl) {
    btnMoins.addEventListener('click', () => {
      if (nbVoyageurs > 1) {
        nbVoyageurs--;
        nbEl.textContent = nbVoyageurs;
        recalculer();
      }
    });

    btnPlus.addEventListener('click', () => {
      nbVoyageurs++;
      nbEl.textContent = nbVoyageurs;
      recalculer();
    });
  }

  // -----------------------------------------------
  // 2. SUPPRIMER TRANSPORT / HÉBERGEMENT
  // -----------------------------------------------
  document.querySelectorAll('.btn-supprimer').forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.getAttribute('data-section');
      const card    = document.getElementById('card' + capitalize(section));

      if (card) {
        card.classList.add('removed');
        setTimeout(() => {
          card.remove();
          sections[section] = false;
          recalculer();
        }, 350);
      }
    });
  });

  // -----------------------------------------------
  // 3. SUPPRIMER UNE ACTIVITÉ
  // -----------------------------------------------
  document.querySelectorAll('.btn-suppr-activite').forEach(btn => {
    btn.addEventListener('click', () => {
      const id   = parseInt(btn.getAttribute('data-id'));
      const item = btn.closest('.activite-item');

      item.style.opacity   = '0';
      item.style.transform = 'translateX(30px)';
      item.style.transition = '0.3s';

      setTimeout(() => {
        item.remove();
        // Retire du tableau
        const idx = prix.activites.findIndex(a => a.id === id);
        if (idx !== -1) prix.activites.splice(idx, 1);

        updateActivitesCount();
        recalculer();
      }, 300);
    });
  });

  // -----------------------------------------------
  // 4. VIDER LE PANIER
  // -----------------------------------------------
  const btnVider = document.getElementById('btnVider');
  if (btnVider) {
    btnVider.addEventListener('click', () => {
      if (!confirm('Vider tout le panier ?')) return;

      ['cardTransport', 'cardHebergement', 'cardActivites'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
      });

      sections = { transport: false, hebergement: false };
      prix.activites = [];
      recalculer();
    });
  }

  // -----------------------------------------------
  // 5. RECALCUL TOTAL
  // -----------------------------------------------
  function recalculer() {
    let total = 0;

    // Transport
    const prixTransportEl     = document.getElementById('prixTransport');
    const recapTransportEl    = document.getElementById('recapTransport');
    const recapPrixTransport  = document.getElementById('recapPrixTransport');
    const recapNbPers         = document.getElementById('recapNbPers');

    if (sections.transport && prixTransportEl) {
      const t = prix.transport * nbVoyageurs;
      prixTransportEl.textContent    = formatPrix(t);
      if (recapPrixTransport) recapPrixTransport.textContent = formatPrix(t);
      if (recapNbPers)        recapNbPers.textContent        = `(×${nbVoyageurs})`;
      total += t;
    } else if (recapTransportEl) {
      recapTransportEl.style.display = 'none';
    }

    // Hébergement
    const recapHebergement   = document.getElementById('recapHebergement');
    const recapPrixHeberg    = document.getElementById('recapPrixHebergement');

    if (sections.hebergement) {
      if (recapPrixHeberg) recapPrixHeberg.textContent = formatPrix(prix.hebergement);
      total += prix.hebergement;
    } else if (recapHebergement) {
      recapHebergement.style.display = 'none';
    }

    // Activités
    let totalActivites = 0;
    prix.activites.forEach(a => {
      const t   = a.base * nbVoyageurs;
      totalActivites += t;
      const el  = document.getElementById('prixActiv' + a.id);
      if (el) el.textContent = formatPrix(t);
    });

    const recapPrixActivites  = document.getElementById('recapPrixActivites');
    const recapNbActivites    = document.getElementById('recapNbActivites');
    if (recapPrixActivites)   recapPrixActivites.textContent = formatPrix(totalActivites);
    if (recapNbActivites)     recapNbActivites.textContent   = `(×${prix.activites.length})`;
    total += totalActivites;

    // Total général
    const totalEl      = document.getElementById('totalGeneral');
    const parPersEl    = document.getElementById('totalParPers');

    if (totalEl) totalEl.textContent    = formatPrix(total);
    if (parPersEl && nbVoyageurs > 0)
      parPersEl.textContent = formatPrix(Math.round(total / nbVoyageurs));
  }

  // -----------------------------------------------
  // 6. COMPTEUR ACTIVITÉS
  // -----------------------------------------------
  function updateActivitesCount() {
    const countEl = document.getElementById('activitesCount');
    const vide    = document.getElementById('activitesVide');
    const list    = document.getElementById('activitesList');
    const n       = prix.activites.length;

    if (countEl) countEl.textContent = n;

    if (n === 0) {
      if (list) list.style.display = 'none';
      if (vide) vide.classList.remove('hidden');
    } else {
      if (list) list.style.display = 'flex';
      if (vide) vide.classList.add('hidden');
    }
  }

  // -----------------------------------------------
  // HELPERS
  // -----------------------------------------------
  function formatPrix(n) {
    return n.toLocaleString('fr-FR') + '€';
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // Init
  recalculer();

});