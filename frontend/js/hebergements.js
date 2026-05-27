console.log("Page Hébergements chargée ✨");

const reveals = document.querySelectorAll(".reveal");

window.addEventListener("scroll", () => {

  reveals.forEach((card) => {

    const windowHeight = window.innerHeight;
    const revealTop = card.getBoundingClientRect().top;

    if (revealTop < windowHeight - 100) {
      card.classList.add("active");
    }

  });

});