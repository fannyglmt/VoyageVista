// =========================================
// HISTORIQUE.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // FILTRES
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
      empty.classList.toggle('hidden', visibles.length > 0);
    });
  });

  // MODALE AVIS
  const modal    = document.getElementById('avisModal');
  const fermer   = document.getElementById('fermerAvis');
  const destNom  = document.getElementById('avisDestNom');
  const destInput= document.getElementById('destNomInput');
  const etoiles  = document.querySelectorAll('.etoile');
  const noteInput= document.getElementById('noteInput');

  window.ouvrirAvis = function(btn) {
    const dest = btn.getAttribute('data-dest');
    if (destNom)  destNom.textContent  = dest;
    if (destInput) destInput.value     = dest;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  };

  if (fermer) {
    fermer.addEventListener('click', () => {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    });
  }

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }
  });

  // Étoiles interactives
  let noteSelectionnee = 0;

  etoiles.forEach(e => {
    e.addEventListener('mouseover', () => {
      const val = parseInt(e.getAttribute('data-val'));
      etoiles.forEach((s, i) => s.classList.toggle('active', i < val));
    });

    e.addEventListener('mouseout', () => {
      etoiles.forEach((s, i) => s.classList.toggle('active', i < noteSelectionnee));
    });

    e.addEventListener('click', () => {
      noteSelectionnee     = parseInt(e.getAttribute('data-val'));
      noteInput.value      = noteSelectionnee;
      etoiles.forEach((s, i) => s.classList.toggle('active', i < noteSelectionnee));
    });
  });

});