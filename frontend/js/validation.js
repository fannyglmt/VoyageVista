// =========================================
// VALIDATION.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // -----------------------------------------------
  // 1. OPTIONS DE PAIEMENT
  // -----------------------------------------------
  const options      = document.querySelectorAll('.paiement-option');
  const carteFields  = document.getElementById('carteFields');

  options.forEach(opt => {
    opt.addEventListener('click', () => {
      options.forEach(o => o.classList.remove('active'));
      opt.classList.add('active');

      const val = opt.querySelector('input').value;
      if (carteFields) {
        carteFields.classList.toggle('hidden', val !== 'carte');
      }
    });
  });

  // -----------------------------------------------
  // 2. FORMAT NUMÉRO DE CARTE (XXXX XXXX XXXX XXXX)
  // -----------------------------------------------
  const numCarte = document.getElementById('numCarte');
  if (numCarte) {
    numCarte.addEventListener('input', () => {
      let val = numCarte.value.replace(/\D/g, '').substring(0, 16);
      numCarte.value = val.match(/.{1,4}/g)?.join(' ') || val;
    });
  }

  // -----------------------------------------------
  // 3. VALIDATION + SOUMISSION
  // -----------------------------------------------
  const validForm   = document.getElementById('validForm');
  const alertBox    = document.getElementById('validAlert');
  const btnConfirmer = document.getElementById('btnConfirmer');

  if (validForm) {
    validForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const prenom  = validForm.querySelector('[name="prenom"]').value.trim();
      const nom     = validForm.querySelector('[name="nom"]').value.trim();
      const email   = validForm.querySelector('[name="email"]').value.trim();
      const cgu     = document.getElementById('cguCheck').checked;
      const paiement = validForm.querySelector('[name="paiement"]:checked')?.value;

      if (!prenom || !nom || !email) {
        showAlert('error', '⚠️ Merci de remplir tous les champs obligatoires.');
        return;
      }

      if (!isValidEmail(email)) {
        showAlert('error', '⚠️ Adresse email invalide.');
        return;
      }

      if (!cgu) {
        showAlert('error', '⚠️ Tu dois accepter les conditions générales de vente.');
        return;
      }

      // Validation carte si paiement par carte
      if (paiement === 'carte') {
        const num  = numCarte?.value.replace(/\s/g, '');
        const exp  = validForm.querySelector('[name="expiration"]')?.value;
        const cvv  = validForm.querySelector('[name="cvv"]')?.value;

        if (!num || num.length !== 16) {
          showAlert('error', '⚠️ Numéro de carte invalide (16 chiffres requis).');
          return;
        }
        if (!exp || !/^\d{2}\/\d{2}$/.test(exp)) {
          showAlert('error', '⚠️ Date d\'expiration invalide (format MM/AA).');
          return;
        }
        if (!cvv || cvv.length !== 3) {
          showAlert('error', '⚠️ CVV invalide (3 chiffres requis).');
          return;
        }
      }

      // Animation bouton
      if (btnConfirmer) {
        btnConfirmer.textContent    = '⏳ Confirmation en cours…';
        btnConfirmer.style.opacity  = '0.7';
        btnConfirmer.style.pointerEvents = 'none';
      }

      // Soumission vers le backend
      validForm.submit();
    });
  }

  // -----------------------------------------------
  // HELPERS
  // -----------------------------------------------
  function showAlert(type, message) {
    if (!alertBox) return;
    alertBox.innerHTML = `<div class="valid-alert valid-alert-${type}">${message}</div>`;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

});