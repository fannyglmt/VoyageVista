console.log("VoyageVista chargé 🌴");

window.addEventListener("load", () => {
  const splash = document.getElementById("splash-screen");

  if (splash) {
    setTimeout(() => {
      splash.classList.add("hidden");
    }, 7800);
  }
});

const destinations = [
  {
    name: "Bali",
    image: "bali.png",
    categories: ["Plage", "Nature", "Détente", "Aventure"],
    region: "Asie",
    budget: "€€",
    price: 780,
    rating: 4.8,
    group: "9+",
    popular: 98,
    trend: 95,
    isNew: false,
    description: "Plages turquoise, temples, surf et sunsets parfaits avec ta team."
  },
  {
    name: "Ibiza",
    image: "ibiza.png",
    categories: ["Nightlife", "Plage", "Détente"],
    region: "Europe",
    budget: "€€",
    price: 520,
    rating: 4.6,
    group: "5-8",
    popular: 96,
    trend: 99,
    isNew: false,
    description: "Le spot idéal pour alterner plage, musique et soirées entre potes."
  },
  {
    name: "Santorin",
    image: "santorin.png",
    categories: ["Culture", "Plage", "Gastronomie", "Détente"],
    region: "Europe",
    budget: "€€€",
    price: 640,
    rating: 4.9,
    group: "2-4",
    popular: 93,
    trend: 91,
    isNew: false,
    description: "Maisons blanches, vues incroyables et ambiance sunset premium."
  },
  {
    name: "Tokyo",
    image: "tokyo.png",
    categories: ["Culture", "Gastronomie", "Nightlife"],
    region: "Asie",
    budget: "€€€",
    price: 1100,
    rating: 4.9,
    group: "2-4",
    popular: 94,
    trend: 97,
    isNew: true,
    description: "Une ville ultra vivante entre food, néons, temples et quartiers iconiques."
  },
  {
    name: "Marrakech",
    image: "marrakech.png",
    categories: ["Culture", "Gastronomie", "Road Trip"],
    region: "Afrique",
    budget: "€",
    price: 390,
    rating: 4.5,
    group: "5-8",
    popular: 89,
    trend: 90,
    isNew: true,
    description: "Souks, riads, désert et vibes orientales pour un séjour dépaysant."
  },
  {
    name: "Costa Rica",
    image: "costarica.png",
    categories: ["Nature", "Aventure", "Sport"],
    region: "Amérique",
    budget: "€€",
    price: 890,
    rating: 4.7,
    group: "9+",
    popular: 90,
    trend: 92,
    isNew: false,
    description: "Jungle, volcans, surf et aventures nature pour un groupe motivé."
  },
  {
    name: "Barcelone",
    image: "barcelone.png",
    categories: ["Culture", "Nightlife", "Gastronomie", "Plage"],
    region: "Europe",
    budget: "€€",
    price: 430,
    rating: 4.6,
    group: "5-8",
    popular: 97,
    trend: 94,
    isNew: false,
    description: "Ville solaire, tapas, plage et soirées faciles à organiser."
  },
  {
    name: "Chamonix",
    image: "chamonix.png",
    categories: ["Sport", "Nature", "Aventure"],
    region: "Europe",
    budget: "€€",
    price: 560,
    rating: 4.4,
    group: "2-4",
    popular: 83,
    trend: 86,
    isNew: true,
    description: "Montagne, ski, randonnées et sensations fortes au grand air."
  },
  {
    name: "Road Trip Portugal",
    image: "portugal.png",
    categories: ["Road Trip", "Plage", "Gastronomie", "Détente"],
    region: "Europe",
    budget: "€",
    price: 350,
    rating: 4.5,
    group: "9+",
    popular: 88,
    trend: 93,
    isNew: true,
    description: "Un itinéraire chill entre Lisbonne, Algarve, plages et bons restos."
  }
];

const categoryIcons = {
  "Aventure": "🧭",
  "Nightlife": "🎉",
  "Plage": "🌊",
  "Gastronomie": "🍽️",
  "Culture": "🏛️",
  "Nature": "🌿",
  "Sport": "🏄",
  "Détente": "🧘",
  "Road Trip": "🚗"
};

let swipeIndex = 0;

const swipeCard = document.getElementById("swipeCard");
const swipeCounter = document.getElementById("swipeCounter");
const passBtn = document.getElementById("passBtn");
const likeBtn = document.getElementById("likeBtn");
const catalogueGrid = document.getElementById("catalogueGrid");

function createBadges(categories) {
  return categories.map(cat => {
    return `<span class="dest-badge">${categoryIcons[cat] || "✨"} ${cat}</span>`;
  }).join("");
}

function renderSwipeCard() {
  if (!swipeCard) return;

  if (swipeIndex >= destinations.length) {
   swipeCard.innerHTML = `
  <div style="
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:60px;
  ">

    <div style="
      max-width:700px;
      text-align:center;
    ">

      <h2 style="
        font-size:58px;
        color:#4a68a6;
        margin-bottom:25px;
        line-height:1.1;
      ">
        Tu as vu toutes les destinations ✨
      </h2>

      <p style="
        font-size:24px;
        color:#5b6f8f;
        margin-bottom:40px;
        line-height:1.5;
      ">
        Continue avec le catalogue pour filtrer, comparer et choisir le voyage parfait.
      </p>

      <a href="#catalogueGrid" class="detail-link" style="
        padding:18px 45px;
        font-size:20px;
      ">
        Voir le catalogue
      </a>

    </div>

  </div>
`;

    swipeCounter.textContent = "Fin du swipe";
    return;
  }

  const dest = destinations[swipeIndex];

  swipeCard.innerHTML = `
    <img src="assets/images/${dest.image}" alt="${dest.name}" class="swipe-img">
    <div class="swipe-content">
      <h2>${dest.name}</h2>
      <p class="swipe-description">${dest.description}</p>
      <div class="badges">${createBadges(dest.categories)}</div>
      <div class="destination-meta">
        <span>${dest.budget} • dès ${dest.price}€</span>
        <span>👥 ${dest.group}</span>
        <span>⭐ ${dest.rating}</span>
      </div>
      <a href="détail-de-destination.html" class="detail-link">Voir les détails</a>
    </div>
  `;

  swipeCounter.textContent = `${swipeIndex + 1} / ${destinations.length}`;
}

function nextSwipe(direction) {
  if (!swipeCard) return;

  swipeCard.classList.add(direction === "like" ? "swipe-right" : "swipe-left");

  setTimeout(() => {
    swipeIndex++;
    swipeCard.classList.remove("swipe-right", "swipe-left");
    renderSwipeCard();
  }, 430);
}

if (passBtn) {
  passBtn.addEventListener("click", () => nextSwipe("pass"));
}

if (likeBtn) {
  likeBtn.addEventListener("click", () => nextSwipe("like"));
}

function renderCatalogue(list) {
  if (!catalogueGrid) return;

  if (list.length === 0) {
    catalogueGrid.innerHTML = `<div class="empty-message">Aucune destination ne matche avec ces filtres 😭</div>`;
    return;
  }

  catalogueGrid.innerHTML = list.map(dest => `
    <article class="catalogue-card">
      <button class="fav-btn">♥</button>
      <img src="assets/images/${dest.image}" alt="${dest.name}">
      <div class="catalogue-content">
        <h3>${dest.name}</h3>
        <p>${dest.description}</p>
        <div class="badges">${createBadges(dest.categories.slice(0, 3))}</div>
        <div class="destination-meta">
         <span>${dest.budget} • ${dest.price}€</span>
<span>👥 ${dest.group}</span>
<span>⭐ ${dest.rating}</span>
        </div>
        <a href="détail-de-destination.html" class="detail-link">Voir les détails</a>
      </div>
    </article>
  `).join("");

  document.querySelectorAll(".fav-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.classList.toggle("active");
    });
  });
}

function applyFilters() {
  const search = document.getElementById("searchInput")?.value.toLowerCase() || "";
  const category = document.getElementById("categoryFilter")?.value || "all";
  const region = document.getElementById("regionFilter")?.value || "all";
  const budget = document.getElementById("budgetFilter")?.value || "all";
  const group = document.getElementById("groupFilter")?.value || "all";
  const sort = document.getElementById("sortFilter")?.value || "popular";

  let filtered = destinations.filter(dest => {
    const matchSearch = dest.name.toLowerCase().includes(search);
    const matchCategory = category === "all" || dest.categories.includes(category);
    const matchRegion = region === "all" || dest.region === region;
    const matchBudget = budget === "all" || dest.budget === budget;
    const matchGroup = group === "all" || dest.group === group;

    return matchSearch && matchCategory && matchRegion && matchBudget && matchGroup;
  });

  if (sort === "priceAsc") filtered.sort((a, b) => a.price - b.price);
  if (sort === "priceDesc") filtered.sort((a, b) => b.price - a.price);
  if (sort === "rating") filtered.sort((a, b) => b.rating - a.rating);
  if (sort === "popular") filtered.sort((a, b) => b.popular - a.popular);
  if (sort === "trend") filtered.sort((a, b) => b.trend - a.trend);
  if (sort === "groups") filtered.sort((a, b) => (b.group === "9+") - (a.group === "9+"));
  if (sort === "new") filtered.sort((a, b) => b.isNew - a.isNew);

  renderCatalogue(filtered);
}

["searchInput", "categoryFilter", "regionFilter", "budgetFilter", "groupFilter", "sortFilter"].forEach(id => {
  const element = document.getElementById(id);
  if (element) {
    element.addEventListener("input", applyFilters);
    element.addEventListener("change", applyFilters);
  }
});

renderSwipeCard();
renderCatalogue(destinations);
const searchInput = document.getElementById("searchInput");

if (searchInput) {
  searchInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      const value = searchInput.value.trim().toLowerCase();

      const foundDestination = destinations.find(dest =>
        dest.name.toLowerCase() === value
      );

      if (foundDestination) {
        window.location.href = "détail-de-destination.html";
      } else {
        window.location.href = "404.html";
      }
    }
  });
}