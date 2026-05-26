console.log("VoyageVista chargé 🌴");

const cards = document.querySelectorAll(".trend-card");

cards.forEach((card) => {
  card.addEventListener("mouseenter", () => {
    card.style.cursor = "pointer";
  });
});