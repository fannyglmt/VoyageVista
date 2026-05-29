// =========================================
// CONFIRMATION.JS — VOYAGEVISTA
// =========================================

document.addEventListener('DOMContentLoaded', () => {
  // Confettis légers au chargement
  const emojis = ['🎉', '✈️', '🌴', '🌊', '⭐'];
  for (let i = 0; i < 18; i++) {
    setTimeout(() => {
      const el = document.createElement('div');
      el.className = 'confetti-piece';
      el.textContent = emojis[Math.floor(Math.random() * emojis.length)];
      el.style.cssText = `
        position: fixed;
        left: ${Math.random() * 100}vw;
        top: -40px;
        font-size: ${14 + Math.random() * 18}px;
        animation: confettiFall ${2 + Math.random() * 2}s ease forwards;
        z-index: 9999;
        pointer-events: none;
      `;
      document.body.appendChild(el);
      setTimeout(() => el.remove(), 4000);
    }, i * 120);
  }

  // Style animation confetti
  if (!document.getElementById('confettiStyle')) {
    const style = document.createElement('style');
    style.id = 'confettiStyle';
    style.textContent = `
      @keyframes confettiFall {
        to { top: 110vh; opacity: 0; transform: rotate(${Math.random() * 720}deg); }
      }
    `;
    document.head.appendChild(style);
  }
});