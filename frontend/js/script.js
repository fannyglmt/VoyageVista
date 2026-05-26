console.log("VoyageVista chargé 🌴");

window.addEventListener("load", () => {
  const splash = document.getElementById("splash-screen");

  setTimeout(() => {
    splash.classList.add("hidden");
  }, 7500);
});

const cards = document.querySelectorAll(".trend-card");

cards.forEach((card) => {
  card.addEventListener("mouseenter", () => {
    card.style.cursor = "pointer";
  });
});