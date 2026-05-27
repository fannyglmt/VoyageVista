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

      <div class="badges">
        ${createBadges(dest.categories)}
      </div>

      <div class="destination-meta">
        <span>${dest.budget} • dès ${dest.price}€</span>
        <span>👥 ${dest.group}</span>
        <span>⭐ ${dest.rating}</span>
      </div>

      <a href="destination-detail.html?destination=${encodeURIComponent(dest.name)}" class="detail-link">
        Voir les détails
      </a>

    </div>
  `;

  swipeCounter.textContent =
    `${swipeIndex + 1} / ${destinations.length}`;
}

function nextSwipe(direction) {

  if (!swipeCard) return;

  swipeCard.classList.add(
    direction === "like"
      ? "swipe-right"
      : "swipe-left"
  );

  setTimeout(() => {

    swipeIndex++;

    swipeCard.classList.remove(
      "swipe-right",
      "swipe-left"
    );

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

    catalogueGrid.innerHTML = `
      <div class="empty-message">
        Aucune destination ne matche avec ces filtres 😭
      </div>
    `;

    return;
  }

  catalogueGrid.innerHTML = list.map(dest => `
    <article class="catalogue-card">

      <button class="fav-btn">♥</button>

      <img src="assets/images/${dest.image}" alt="${dest.name}">

      <div class="catalogue-content">

        <h3>${dest.name}</h3>

        <p>${dest.description}</p>

        <div class="badges">
          ${createBadges(dest.categories.slice(0, 3))}
        </div>

        <div class="destination-meta">
          <span>${dest.budget} • ${dest.price}€</span>
          <span>👥 ${dest.group}</span>
          <span>⭐ ${dest.rating}</span>
        </div>

        <a href="destination-detail.html?destination=${encodeURIComponent(dest.name)}" class="detail-link">
          Voir les détails
        </a>

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

  const search =
    document.getElementById("searchInput")?.value.toLowerCase() || "";

  const category =
    document.getElementById("categoryFilter")?.value || "all";

  const region =
    document.getElementById("regionFilter")?.value || "all";

  const budget =
    document.getElementById("budgetFilter")?.value || "all";

  const group =
    document.getElementById("groupFilter")?.value || "all";

  const sort =
    document.getElementById("sortFilter")?.value || "popular";

  let filtered = destinations.filter(dest => {

    const matchSearch =
      dest.name.toLowerCase().includes(search);

    const matchCategory =
      category === "all" ||
      dest.categories.includes(category);

    const matchRegion =
      region === "all" ||
      dest.region === region;

    const matchBudget =
      budget === "all" ||
      dest.budget === budget;

    const matchGroup =
      group === "all" ||
      dest.group === group;

    return (
      matchSearch &&
      matchCategory &&
      matchRegion &&
      matchBudget &&
      matchGroup
    );
  });

  if (sort === "priceAsc")
    filtered.sort((a, b) => a.price - b.price);

  if (sort === "priceDesc")
    filtered.sort((a, b) => b.price - a.price);

  if (sort === "rating")
    filtered.sort((a, b) => b.rating - a.rating);

  if (sort === "popular")
    filtered.sort((a, b) => b.popular - a.popular);

  if (sort === "trend")
    filtered.sort((a, b) => b.trend - a.trend);

  if (sort === "groups")
    filtered.sort((a, b) =>
      (b.group === "9+") - (a.group === "9+")
    );

  if (sort === "new")
    filtered.sort((a, b) => b.isNew - a.isNew);

  renderCatalogue(filtered);
}

[
  "searchInput",
  "categoryFilter",
  "regionFilter",
  "budgetFilter",
  "groupFilter",
  "sortFilter"
].forEach(id => {

  const element = document.getElementById(id);

  if (element) {
    element.addEventListener("input", applyFilters);
    element.addEventListener("change", applyFilters);
  }
});

renderSwipeCard();
renderCatalogue(destinations);

const destinationSearchInput =
  document.getElementById("searchInput");

const searchBtn =
  document.getElementById("searchBtn");

function searchDestination() {

  if (!destinationSearchInput) return;

  const searchValue =
    destinationSearchInput.value.trim().toLowerCase();

  const foundDestination =
  destinations.find((dest) =>
    dest.name.toLowerCase().includes(searchValue)
  );

  if (foundDestination) {

    window.location.href =
      `destination-detail.html?destination=${encodeURIComponent(foundDestination.name)}`;

  } else {

    window.location.href = "404.html";

  }
}

if (destinationSearchInput) {

  destinationSearchInput.addEventListener(
    "keydown",
    (event) => {

      if (event.key === "Enter") {
        searchDestination();
      }

    }
  );
}

if (searchBtn) {
  searchBtn.addEventListener(
    "click",
    searchDestination
  );
}

const detailExtras = {

  "Bali": {
    transport: "Vol Paris → Denpasar",
    hebergement: "Villa partagée avec piscine",
    mood: "Chill, plage, surf et sunsets",
    activities: [
      "Cours de surf",
      "Temple d’Uluwatu",
      "Sunset beach club"
    ]
  },

  "Ibiza": {
    transport: "Vol Paris → Ibiza",
    hebergement: "Appartement proche plage",
    mood: "Nightlife, mer et soirées",
    activities: [
      "Beach party",
      "Balade bateau",
      "Soirée club"
    ]
  },

  "Santorin": {
    transport: "Vol Paris → Santorin",
    hebergement: "Hôtel vue mer",
    mood: "Photos, sunset et détente",
    activities: [
      "Croisière sunset",
      "Visite d’Oia",
      "Dégustation locale"
    ]
  },

  "Tokyo": {
    transport: "Vol Paris → Tokyo",
    hebergement: "Hôtel central à Shibuya",
    mood: "Culture, food et néons",
    activities: [
      "Shibuya by night",
      "Temple Senso-ji",
      "Street food tour"
    ]
  },

  "Marrakech": {
    transport: "Vol Paris → Marrakech",
    hebergement: "Riad traditionnel",
    mood: "Culture, souks et désert",
    activities: [
      "Balade dans les souks",
      "Excursion désert",
      "Dîner marocain"
    ]
  },

  "Costa Rica": {
    transport: "Vol Paris → San José",
    hebergement: "Eco-lodge en pleine nature",
    mood: "Aventure, jungle et surf",
    activities: [
      "Surf",
      "Randonnée volcan",
      "Tyrolienne jungle"
    ]
  },

  "Barcelone": {
    transport: "Train ou vol Paris → Barcelone",
    hebergement: "Appartement de groupe",
    mood: "Tapas, plage et nightlife",
    activities: [
      "Tour tapas",
      "Plage Barceloneta",
      "Soirée rooftop"
    ]
  },

  "Chamonix": {
    transport: "Train Paris → Chamonix",
    hebergement: "Chalet de groupe",
    mood: "Montagne, sport et nature",
    activities: [
      "Randonnée",
      "Ski",
      "Spa montagne"
    ]
  },

  "Road Trip Portugal": {
    transport: "Van ou voiture de location",
    hebergement: "Auberges + logements étapes",
    mood: "Road trip, plage et liberté",
    activities: [
      "Lisbonne",
      "Algarve",
      "Food tour portugais"
    ]
  }
};

function renderDestinationDetail() {

  const page =
    document.getElementById("destinationDetailPage");

  if (!page) return;

  const params =
    new URLSearchParams(window.location.search);

  const name =
    params.get("destination") || "Bali";

  const dest =
    destinations.find(
      d => d.name.toLowerCase() === name.toLowerCase()
    );

  if (!dest) {
    window.location.href = "404.html";
    return;
  }

  const extra = detailExtras[dest.name];

  page.innerHTML = `
    <section class="detail-hero">

      <img src="assets/images/${dest.image}" alt="${dest.name}">

      <div class="detail-overlay">

        <p class="tag">DESTINATION MATCHÉE</p>

        <h1>${dest.name}</h1>

        <p>${dest.description}</p>

        <div class="badges">
          ${createBadges(dest.categories)}
        </div>

        <div class="detail-meta">
          <span>${dest.budget} • dès ${dest.price}€</span>
          <span>👥 ${dest.group}</span>
          <span>⭐ ${dest.rating}</span>
        </div>

      </div>

    </section>

    <section class="detail-content">

      <div class="detail-main-card">

        <h2>Pourquoi ça matche avec ta team ?</h2>

        <p>${extra.mood}</p>

        <div class="detail-options">

          <div>
            <h3>✈ Transport conseillé</h3>
            <p>${extra.transport}</p>
          </div>

          <div>
            <h3>🏨 Hébergement conseillé</h3>
            <p>${extra.hebergement}</p>
          </div>

          <div>
            <h3>💸 Budget estimé</h3>
            <p>À partir de ${dest.price}€ par personne</p>
          </div>

        </div>

        <button class="add-cart-btn">
          Ajouter au panier voyage
        </button>

      </div>

      <div class="detail-side-card">

        <h2>Activités à ne pas rater</h2>

        ${extra.activities.map(activity => `
          <div class="activity-line">
            <span>✨</span>
            <p>${activity}</p>
          </div>
        `).join("")}

      </div>

    </section>

    <section class="next-step-section">

      <h2>
        Ok… le voyage prend forme ✈️🌴
      </h2>

      <div class="next-step-grid">
        <a href="activites.html">🎉 Choisir les activités</a>
        <a href="#">🏨 Choisir l’hébergement</a>
        <a href="#">✈ Choisir le transport</a>
      </div>

      <p class="back-catalogue-text">
        Finalement c’était pas le bon mood ? 👀
        <a href="destination.html">
          Retourner swiper d’autres destinations
        </a>
      </p>

    </section>
  `;
}

renderDestinationDetail();
const activities = [
  {
    name: "Cours de surf",
    image: "surf.png",
    destination: "Bali",
    category: "Sport",
    price: 45,
    duration: "2h",
    vibe: "🌊 Sport • fun • plage",
    description: "Apprends à surfer avec ta team sur une plage incroyable. Parfait pour commencer le voyage avec de l’énergie."
  },
  {
    name: "Balade en bateau",
    image: "boat.png",
    destination: "Ibiza",
    category: "Détente",
    price: 65,
    duration: "3h",
    vibe: "🛥️ Mer • chill • sunset",
    description: "Une sortie en bateau pour profiter de la mer, du soleil et des meilleurs spots photo."
  },
  {
    name: "Visite de temple",
    image: "temple.png",
    destination: "Bali",
    category: "Culture",
    price: 25,
    duration: "1h30",
    vibe: "🏛️ Culture • découverte",
    description: "Découvre un lieu iconique, calme et magnifique pour ajouter une vraie touche culturelle au séjour."
  },
  {
    name: "Food tour",
    image: "food-tour.png",
    destination: "Tokyo",
    category: "Gastronomie",
    price: 55,
    duration: "2h",
    vibe: "🍜 Food • ville • découverte",
    description: "Teste les meilleurs spots food locaux et découvre la ville à travers ses saveurs."
  },
  {
    name: "Beach party",
    image: "beach-party.png",
    destination: "Ibiza",
    category: "Nightlife",
    price: 70,
    duration: "Soirée",
    vibe: "🎉 Nightlife • plage • musique",
    description: "Ambiance festive, musique et coucher de soleil : l’activité parfaite pour une team qui veut kiffer."
  },
  {
    name: "Randonnée nature",
    image: "hiking.png",
    destination: "Chamonix",
    category: "Nature",
    price: 30,
    duration: "4h",
    vibe: "🥾 Nature • aventure",
    description: "Un moment en pleine nature pour respirer, marcher et profiter de paysages incroyables."
  },
  {
    name: "Spa chill",
    image: "spa.png",
    destination: "Santorin",
    category: "Détente",
    price: 80,
    duration: "2h",
    vibe: "🧘 Détente • bien-être",
    description: "Pause détente obligatoire : spa, calme et recharge totale avant de repartir explorer."
  },
  {
    name: "Musée immersif",
    image: "museum.png",
    destination: "Barcelone",
    category: "Culture",
    price: 20,
    duration: "1h",
    vibe: "🎨 Culture • photo • indoor",
    description: "Une activité simple, visuelle et sympa à faire entre deux sorties en ville."
  },
  {
    name: "Rooftop sunset",
    image: "rooftop.png",
    destination: "Marrakech",
    category: "Détente",
    price: 35,
    duration: "Soirée",
    vibe: "🌅 Sunset • chill • photos",
    description: "Un rooftop stylé pour profiter du coucher de soleil et finir la journée en beauté."
  }
];

function renderActivitiesFeed() {
  const feed = document.getElementById("activityFeed");
  if (!feed) return;

  feed.innerHTML = activities.map(activity => `
    <article class="activity-post">
      <img src="assets/images/${activity.image}" alt="${activity.name}">

      <div class="activity-post-content">
        <p class="tag">${activity.vibe}</p>
        <h2>${activity.name}</h2>
        <p>${activity.description}</p>

        <div class="activity-info">
          <span>📍 ${activity.destination}</span>
          <span>⏱️ ${activity.duration}</span>
          <span>💸 ${activity.price}€</span>
        </div>

        <div class="activity-actions">
          <a href="activite-detail.html?activite=${encodeURIComponent(activity.name)}">
            Voir l’activité
          </a>
          <button>♥</button>
        </div>
      </div>
    </article>
  `).join("");
}

function renderActivityDetail() {
  const page = document.getElementById("activityDetailPage");
  if (!page) return;

  const params = new URLSearchParams(window.location.search);
  const name = params.get("activite") || "Cours de surf";

  const activity = activities.find(a =>
    a.name.toLowerCase() === name.toLowerCase()
  );

  if (!activity) {
    window.location.href = "404.html";
    return;
  }

  page.innerHTML = `
    <section class="activity-detail-hero">
      <img src="assets/images/${activity.image}" alt="${activity.name}">

      <div class="activity-detail-content">
        <p class="tag">${activity.vibe}</p>
        <h1>${activity.name}</h1>
        <p>${activity.description}</p>

        <div class="activity-info">
          <span>📍 ${activity.destination}</span>
          <span>⏱️ ${activity.duration}</span>
          <span>💸 ${activity.price}€</span>
        </div>

        <button class="add-cart-btn">Ajouter au panier voyage</button>
      </div>
    </section>

    <section class="activity-detail-boxes">
      <div class="activity-detail-box">
        <h3>Pourquoi on valide ?</h3>
        <p>Parce que c’est simple à réserver, visuel, fun et parfait pour créer des souvenirs de groupe.</p>
      </div>

      <div class="activity-detail-box">
        <h3>Pour qui ?</h3>
        <p>Idéal pour les groupes qui veulent ajouter une vraie vibe au voyage sans se prendre la tête.</p>
      </div>

      <div class="activity-detail-box">
        <h3>À prévoir</h3>
        <p>Réserve à l’avance, prépare ton téléphone pour les photos et viens avec une bonne énergie.</p>
      </div>
    </section>

    <section class="activity-detail-cta">
      <h2>Ok… cette activité part dans le moodboard du voyage ✨</h2>
      <a href="activites.html" class="detail-link">Retour aux activités</a>
      <a href="destination.html" class="detail-link">Voir les destinations</a>
    </section>
  `;
}

renderActivitiesFeed();
renderActivityDetail();