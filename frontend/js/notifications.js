// =========================================
// NOTIFICATIONS.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // -----------------------------------------------
  // 1. FILTRES
  // -----------------------------------------------
  const filterBtns = document.querySelectorAll('.filter-btn');
  const notifItems = document.querySelectorAll('.notif-item');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      // Activer le bon bouton
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');

      notifItems.forEach(item => {
        const type = item.getAttribute('data-type');
        if (filter === 'all' || type === filter) {
          item.classList.remove('hidden');
        } else {
          item.classList.add('hidden');
        }
      });

      checkEmpty();
    });
  });

  // -----------------------------------------------
  // 2. SUPPRIMER UNE NOTIFICATION
  // -----------------------------------------------
  document.querySelectorAll('.notif-close').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.notif-item');
      item.classList.add('removing');

      setTimeout(() => {
        item.remove();
        updateCount();
        checkEmpty();
      }, 350);
    });
  });

  // -----------------------------------------------
  // 3. MARQUER COMME LU AU CLIC SUR L'ITEM
  // -----------------------------------------------
  notifItems.forEach(item => {
    item.addEventListener('click', (e) => {
      // Ne pas déclencher si on clique sur un bouton ou lien
      if (e.target.closest('.notif-close') || e.target.closest('a')) return;

      if (item.classList.contains('unread')) {
        item.classList.remove('unread');
        item.classList.add('read');
        updateCount();
      }
    });
  });

  // -----------------------------------------------
  // 4. TOUT MARQUER COMME LU
  // -----------------------------------------------
  const btnToutLire = document.getElementById('btnToutLire');

  if (btnToutLire) {
    btnToutLire.addEventListener('click', () => {
      document.querySelectorAll('.notif-item.unread').forEach(item => {
        item.classList.remove('unread');
        item.classList.add('read');
      });
      updateCount();

      // Feedback visuel
      btnToutLire.textContent = '✓ Tout marqué comme lu';
      btnToutLire.style.background = '#4a68a6';
      btnToutLire.style.color = 'white';
      setTimeout(() => {
        btnToutLire.textContent = 'Tout marquer comme lu';
        btnToutLire.style.background = '';
        btnToutLire.style.color = '';
      }, 2000);
    });
  }

  // -----------------------------------------------
  // 5. MISE À JOUR DU COMPTEUR
  // -----------------------------------------------
  function updateCount() {
    const nonLues = document.querySelectorAll('.notif-item.unread').length;

    // Compteur dans le hero
    const countLabel = document.getElementById('countNonLues');
    if (countLabel) countLabel.textContent = nonLues;

    // Badge dans la navbar
    const navCount = document.getElementById('navNotifCount');
    if (navCount) {
      navCount.textContent = nonLues;
      navCount.style.display = nonLues > 0 ? 'flex' : 'none';
    }
  }

  // -----------------------------------------------
  // 6. VÉRIFIER SI LISTE VIDE
  // -----------------------------------------------
  function checkEmpty() {
    const visibles = document.querySelectorAll('.notif-item:not(.hidden)');
    const empty    = document.getElementById('notifEmpty');
    const list     = document.getElementById('notifList');

    if (!empty || !list) return;

    if (visibles.length === 0) {
      list.style.display = 'none';
      empty.classList.remove('hidden');
    } else {
      list.style.display = 'flex';
      empty.classList.add('hidden');
    }
  }

  // Init
  updateCount();
  checkEmpty();

});