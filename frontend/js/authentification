// =========================================
// AUTH.JS — VOYAGEVISTA
// JS côté client : login.html / register.html
// =========================================

document.addEventListener('DOMContentLoaded', () => {

  // -----------------------------------------------
  // 1. AFFICHAGE DES ERREURS DEPUIS L'URL (?error=)
  // Le backend redirige avec ?error=code, on affiche
  // le bon message ici sans PHP
  // -----------------------------------------------
  const errorMessages = {
    champs_vides:           'Merci de remplir tous les champs.',
    email_invalide:         'Adresse email invalide.',
    identifiants_incorrects:'Email ou mot de passe incorrect.',
    email_deja_utilise:     'Cette adresse email est déjà utilisée.',
    mdp_trop_court:         'Le mot de passe doit contenir au moins 8 caractères.',
    mdp_non_identiques:     'Les mots de passe ne correspondent pas.',
  };

  const successMessages = {
    compte_cree: 'Compte créé avec succès ! Tu peux maintenant te connecter.',
  };

  const params = new URLSearchParams(window.location.search);
  const errorCode   = params.get('error');
  const successCode = params.get('success');
  const container   = document.getElementById('auth-alert-container');

  if (container) {
    if (errorCode && errorMessages[errorCode]) {
      container.innerHTML = `
        <div class="auth-alert auth-alert-error">
          ⚠️ ${errorMessages[errorCode]}
        </div>`;
    }
    if (successCode && successMessages[successCode]) {
      container.innerHTML = `
        <div class="auth-alert auth-alert-success">
          ✅ ${successMessages[successCode]}
        </div>`;
    }
  }

  // -----------------------------------------------
  // 2. TOGGLE AFFICHAGE MOT DE PASSE
  // -----------------------------------------------
  function setupToggle(btnId, inputId) {
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    if (!btn || !input) return;
    btn.addEventListener('click', () => {
      const hidden = input.type === 'password';
      input.type   = hidden ? 'text' : 'password';
      btn.textContent = hidden ? '🙈' : '👁';
    });
  }

  setupToggle('togglePw',  'password');
  setupToggle('togglePw2', 'confirm_password');

  // -----------------------------------------------
  // 3. INDICATEUR DE FORCE DU MOT DE PASSE (register)
  // -----------------------------------------------
  const passwordInput  = document.getElementById('password');
  const strengthFill   = document.querySelector('.strength-fill');
  const strengthText   = document.querySelector('.strength-text');

  if (passwordInput && strengthFill && strengthText) {
    passwordInput.addEventListener('input', () => {
      const val = passwordInput.value;
      let score = 0;
      if (val.length >= 8)           score++;
      if (/[A-Z]/.test(val))         score++;
      if (/[0-9]/.test(val))         score++;
      if (/[^A-Za-z0-9]/.test(val))  score++;

      const levels = [
        { pct: '0%',   label: '',             color: '' },
        { pct: '25%',  label: 'Trop court',   color: '#e64b5d' },
        { pct: '50%',  label: 'Moyen',        color: '#f39b5f' },
        { pct: '75%',  label: 'Bien',         color: '#57c5b6' },
        { pct: '100%', label: 'Excellent 💪', color: '#4a68a6' },
      ];

      strengthFill.style.width      = levels[score].pct;
      strengthFill.style.background = levels[score].color;
      strengthText.textContent      = levels[score].label;
    });
  }

  // -----------------------------------------------
  // 4. VALIDATION CÔTÉ CLIENT — LOGIN
  // -----------------------------------------------
  const loginForm = document.getElementById('loginForm');

  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      const email    = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();

      if (!email || !password) {
        e.preventDefault();
        showClientError('Merci de remplir tous les champs.');
        return;
      }
      if (!isValidEmail(email)) {
        e.preventDefault();
        showClientError('Adresse email invalide.');
        return;
      }

      // Animation bouton
      animateBtn(loginForm);
    });
  }

  // -----------------------------------------------
  // 5. VALIDATION CÔTÉ CLIENT — REGISTER
  // -----------------------------------------------
  const registerForm = document.getElementById('registerForm');

  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      const prenom = document.getElementById('prenom')?.value.trim();
      const nom    = document.getElementById('nom')?.value.trim();
      const email  = document.getElementById('email')?.value.trim();
      const pw     = document.getElementById('password')?.value;
      const pw2    = document.getElementById('confirm_password')?.value;

      if (!prenom || !nom || !email || !pw || !pw2) {
        e.preventDefault();
        showClientError('Merci de remplir tous les champs.');
        return;
      }
      if (!isValidEmail(email)) {
        e.preventDefault();
        showClientError('Adresse email invalide.');
        return;
      }
      if (pw.length < 8) {
        e.preventDefault();
        showClientError('Le mot de passe doit contenir au moins 8 caractères.');
        return;
      }
      if (pw !== pw2) {
        e.preventDefault();
        showClientError('Les mots de passe ne correspondent pas.');
        return;
      }

      animateBtn(registerForm);
    });
  }

  // -----------------------------------------------
  // HELPERS
  // -----------------------------------------------

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function showClientError(message) {
    const old = document.querySelector('.auth-alert-client');
    if (old) old.remove();

    const alert = document.createElement('div');
    alert.className = 'auth-alert auth-alert-error auth-alert-client';
    alert.textContent = '⚠️ ' + message;

    const target = document.querySelector('.auth-form');
    if (target) target.parentNode.insertBefore(alert, target);
    alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function animateBtn(form) {
    const btn = form.querySelector('.btn-auth');
    if (!btn) return;
    btn.style.opacity = '0.7';
    btn.style.pointerEvents = 'none';
    const txt = btn.querySelector('.btn-text');
    if (txt) txt.textContent = 'Chargement…';
  }

});