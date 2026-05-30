// =========================================
// FAVORIS_TABS.JS — VOYAGEVISTA
// Gestion des onglets Destinations / Hébergements / Activités
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  const tabBtns    = document.querySelectorAll('.tab-btn');
  const tabSections = document.querySelectorAll('.tab-section');

  // NAVIGATION ENTRE ONGLETS
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-tab');

      // Activer le bon bouton
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Afficher la bonne section
      tabSections.forEach(s => s.classList.remove('active'));
      document.getElementById('tab-' + target)?.classList.add('active');
    });
  });

  // FILTRES DESTINATIONS (dans l'onglet destinations uniquement)
  const filterBtns = document.querySelectorAll('.filter-btn');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');
      const cards  = document.querySelectorAll('#tab-destinations .favori-card');

      cards.forEach(card => {
        const cat = card.getAttribute('data-categorie');
        card.classList.toggle('hidden', filter !== 'all' && cat !== filter);
      });

      checkEmpty('destinations');
    });
  });

  // RETIRER UN FAVORI (pour tous les onglets)
  document.querySelectorAll('.btn-unfav').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const card = btn.closest('.favori-card');
      const id   = btn.getAttribute('data-fav-id');
      const tab  = btn.closest('.tab-section')?.id.replace('tab-', '');

      card.style.transition = '0.4s';
      card.style.opacity    = '0';
      card.style.transform  = 'scale(0.9)';

      setTimeout(() => {
        card.remove();
        updateCounts();
        if (tab) checkEmpty(tab);

        // Appel backend
        fetch('../backend/toggle_favori.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'destination_id=' + id + '&action=retirer'
        }).catch(() => {});
      }, 400);
    });
  });

  // MISE À JOUR DES COMPTEURS
  function updateCounts() {
    const nbDest  = document.querySelectorAll('#tab-destinations .favori-card').length;
    const nbHeb   = document.querySelectorAll('#tab-hebergements .favori-card').length;
    const nbAct   = document.querySelectorAll('#tab-activites .favori-card').length;

    document.getElementById('countDestinations').textContent = nbDest;
    document.getElementById('countHebergements').textContent = nbHeb;
    document.getElementById('countActivites').textContent    = nbAct;

    // Mise à jour des badges dans les onglets
    const tabCounts = document.querySelectorAll('.tab-count');
    if (tabCounts[0]) tabCounts[0].textContent = nbDest;
    if (tabCounts[1]) tabCounts[1].textContent = nbHeb;
    if (tabCounts[2]) tabCounts[2].textContent = nbAct;
  }

  // VÉRIFIER SI UN ONGLET EST VIDE
  function checkEmpty(tab) {
    const cards   = document.querySelectorAll(`#tab-${tab} .favori-card:not(.hidden)`);
    const emptyEl = document.getElementById(`empty${capitalize(tab)}`);
    const grid    = document.querySelector(`#tab-${tab} .favoris-grid`);

    if (cards.length === 0) {
      if (grid)    grid.style.display    = 'none';
      if (emptyEl) emptyEl.classList.remove('hidden');
    } else {
      if (grid)    grid.style.display    = 'grid';
      if (emptyEl) emptyEl.classList.add('hidden');
    }
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // Init compteurs
  updateCounts();
});