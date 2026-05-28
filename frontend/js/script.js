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
        <a href="hebergements.html">🏨 Choisir l’hébergement</a>
        <a href="transports.html">✈ Choisir le transport</a>
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
    rating: 4.8,
reviews: 124,
date: "18 juin",
places: 6,
comment1: "Super activité à faire en groupe, l’ambiance était incroyable.",
comment2: "Simple à réserver et vraiment un des meilleurs moments du voyage.",
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
    rating: 4.7,
reviews: 126,
date: "20 juin",
places: 8,
comment1: "Le sunset sur le bateau était incroyable.",
comment2: "Activité parfaite pour chill avec le groupe.",
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
    rating: 4.6,
reviews: 93,
date: "21 juin",
places: 12,
comment1: "Super beau et hyper apaisant.",
comment2: "Ça change vraiment des activités classiques.",
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
    rating: 4.9,
reviews: 214,
date: "17 juin",
places: 5,
comment1: "On a trop mangé 😭 mais c’était incroyable.",
comment2: "Le meilleur moyen de découvrir Tokyo.",
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
    rating: 4.8,
reviews: 301,
date: "22 juin",
places: 18,
comment1: "Meilleure soirée du voyage clairement.",
comment2: "L’ambiance était folle du début à la fin.",
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
    rating: 4.5,
reviews: 87,
date: "19 juin",
places: 10,
comment1: "Les paysages étaient magnifiques.",
comment2: "Très bonne activité pour déconnecter un peu.",
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
    rating: 4.9,
reviews: 144,
date: "23 juin",
places: 4,
comment1: "On voulait plus repartir 😭",
comment2: "Le moment le plus relax du séjour.",
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
    rating: 4.4,
reviews: 68,
date: "18 juin",
places: 20,
comment1: "Très stylé pour les photos.",
comment2: "Petit musée mais expérience super sympa.",
    vibe: "🎨 Culture • photo • indoor",
    description: "Une activité simple, visuelle et sympa à faire entre deux sorties en ville."
  },
 
{
  name: "Visite d’Oia",
  image: "oia.png",
  destination: "Santorin",
  category: "Culture",
  price: 30,
  duration: "2h",
  rating: 4.9,
reviews: 246,
date: "21 juin",
places: 7,
comment1: "On comprend pourquoi tout le monde en parle.",
comment2: "Les photos là-bas sont incroyables.",
  vibe: "🏛️ Culture • sunset • photos",
  description: "Découvre les ruelles blanches d’Oia, les vues iconiques et les spots parfaits pour les photos."
},
 {
    name: "Rooftop sunset",
    image: "rooftop.png",
    destination: "Marrakech",
    category: "Détente",
    price: 35,
    duration: "Soirée",
    rating: 4.8,
reviews: 173,
date: "24 juin",
places: 9,
comment1: "La vue au coucher du soleil était dingue.",
comment2: "Hyper bonne vibe pour finir la journée.",
    vibe: "🌅 Sunset • chill • photos",
    description: "Un rooftop stylé pour profiter du coucher de soleil et finir la journée en beauté."
  },
{
  name: "Dégustation locale",
  image: "degustation-locale.png",
  destination: "Santorin",
  category: "Gastronomie",
  price: 45,
  duration: "1h30",
  rating: 4.6,
reviews: 91,
date: "20 juin",
places: 11,
comment1: "Très bonne surprise franchement.",
comment2: "On a découvert plein de spécialités.",
  vibe: "🍽️ Food • local • chill",
  description: "Une pause gourmande pour goûter les spécialités locales et profiter d’un moment simple avec ta team."
},
{
  name: "Croisière sunset",
  image: "croisiere-sunset.png",
  destination: "Santorin",
  category: "Détente",
  price: 80,
  duration: "3h",
  rating: 5.0,
reviews: 318,
date: "22 juin",
places: 3,
comment1: "Le coucher de soleil était irréel 😭",
comment2: "Moment préféré du voyage.",
  vibe: "🌅 Sunset • mer • premium",
  description: "Une croisière au coucher du soleil pour finir la journée avec une vraie vibe carte postale."
},
{
  name: "Temple Senso-ji",
  image: "sensoji.png",
  destination: "Tokyo",
  category: "Culture",
  price: 20,
  duration: "1h30",
  rating: 4.7,
reviews: 141,
date: "19 juin",
places: 15,
comment1: "Très beau temple et super ambiance.",
comment2: "À faire absolument à Tokyo.",
  vibe: "🏮 Culture • Japon • découverte",
  description: "Explore un temple iconique de Tokyo et plonge dans une ambiance traditionnelle au cœur de la ville."
},
{
  name: "Ski",
  image: "ski.png",
  destination: "Chamonix",
  category: "Sport",
  price: 75,
  duration: "Journée",
  rating: 4.8,
reviews: 224,
date: "17 juin",
places: 12,
comment1: "Les pistes étaient parfaites.",
comment2: "On a passé une journée incroyable.",
  vibe: "⛷️ Sport • neige • montagne",
  description: "Une journée ski pour profiter des pistes, de la montagne et d’un bon mood sportif avec ta team."
},
{
  name: "Shibuya by night",
  image: "shibuya-night.png",
  destination: "Tokyo",
  category: "Nightlife",
  price: 35,
  duration: "2h",
  rating: 4.9,
reviews: 278,
date: "18 juin",
places: 9,
comment1: "Tokyo la nuit c’est une folie.",
comment2: "On avait l’impression d’être dans un film.",
  vibe: "🌃 Néons • ville • nightlife",
  description: "Découvre Shibuya de nuit, ses lumières, son énergie et ses spots parfaits pour sortir entre amis."
},
{
  name: "Excursion dans les souks",
  image: "souk.png",
  destination: "Marrakech",
  category: "Culture",
  price: 25,
  duration: "2h",
  rating: 4.5,
reviews: 117,
date: "20 juin",
places: 14,
comment1: "Trop de choses à voir partout.",
comment2: "Les couleurs et l’ambiance sont incroyables.",
  vibe: "🧺 Souks • culture • couleurs",
  description: "Balade dans les souks, entre artisanat, épices, ruelles vivantes et ambiance marocaine."
},
{
  name: "Tyrolienne jungle",
  image: "tyrolienne-jungle.png",
  destination: "Costa Rica",
  category: "Aventure",
  price: 70,
  duration: "2h",
  rating: 4.9,
reviews: 167,
date: "19 juin",
places: 6,
comment1: "Adrénaline maximale 😭",
comment2: "La jungle vue d’en haut c’était fou.",
  vibe: "🌿 Jungle • adrénaline • fun",
  description: "Traverse la jungle en tyrolienne pour une activité pleine de sensations et parfaite pour les groupes."
},
{
  name: "Dîner marocain",
  image: "diner-marocain.png",
  destination: "Marrakech",
  category: "Gastronomie",
  price: 40,
  duration: "Soirée",
  rating: 4.7,
reviews: 102,
date: "22 juin",
places: 10,
comment1: "Le repas était incroyable.",
comment2: "Très bonne ambiance avec musique et déco.",
  vibe: "🍽️ Food • ambiance • partage",
  description: "Un dîner marocain convivial avec plats traditionnels, ambiance chaleureuse et vraie vibe locale."
},
{
  name: "Algarve",
  image: "algarve.png",
  destination: "Road Trip Portugal",
  category: "Plage",
  price: 35,
  duration: "Journée",rating: 4.9,
reviews: 242,
date: "21 juin",
places: 7,
comment1: "Les plages étaient incroyables.",
comment2: "Clairement un highlight du road trip.",
  vibe: "🌊 Plage • road trip • soleil",
  description: "Cap sur l’Algarve pour profiter des plages, falaises et spots parfaits pendant le road trip."
},
{
  name: "Randonnée volcan",
  image: "randonnee-volcan.png",
  destination: "Costa Rica",
  category: "Nature",
  price: 45,
  duration: "4h",
  rating: 4.6,
reviews: 95,
date: "18 juin",
places: 8,
comment1: "Paysages incroyables tout le long.",
comment2: "Bonne activité si on aime marcher.",
  vibe: "🌋 Nature • marche • aventure",
  description: "Une randonnée autour d’un volcan pour profiter de paysages impressionnants et d’une vraie pause nature."
},

{
  name: "Balade désert",
  image: "desert.png",
  destination: "Marrakech",
  category: "Aventure",
  price: 65,
  duration: "Demi-journée",
  rating: 4.8,
reviews: 189,
date: "21 juin",
places: 5,
comment1: "Le coucher de soleil dans le désert 😭",
comment2: "Expérience vraiment mémorable.",
  vibe: "🐪 Désert • aventure • golden hour",
  description: "Une sortie dans le désert pour vivre un moment dépaysant, entre paysages dorés et souvenirs de groupe."
},
{
  name: "Spa montagne",
  image: "spa-montagne.png",
  destination: "Chamonix",
  category: "Détente",
  price: 60,
  duration: "2h",
  rating: 4.9,
reviews: 133,
date: "18 juin",
places: 4,
comment1: "Le spa avec vue montagne 😭",
comment2: "Ultra relaxant après le ski.",
  vibe: "🧖 Détente • montagne • chill",
  description: "Après l’effort, place au chill : spa, calme et vue montagne pour recharger tout le groupe."
},
{
  name: "Lisbonne",
  image: "lisbonne.png",
  destination: "Road Trip Portugal",
  category: "Culture",
  price: 30,
  duration: "Journée",
  rating: 4.7,
reviews: 158,
date: "20 juin",
places: 13,
comment1: "Ville trop agréable à visiter.",
comment2: "Les points de vue sont magnifiques.",
  vibe: "🚋 Ville • culture • food",
  description: "Découvre Lisbonne entre ruelles, tramways, points de vue et pauses gourmandes."
},
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

const typeFilter = document.getElementById("typeFilter");
const budgetFilter = document.getElementById("budgetFilter");
const ambianceFilter = document.getElementById("ambianceFilter");
const voyageurFilter = document.getElementById("voyageurFilter");
const hebergementSearch = document.getElementById("hebergementSearch");

const cards = document.querySelectorAll(".hebergement-card");

function filterHebergements() {

  const typeValue =
    typeFilter?.value.toLowerCase() || "";

  const budgetValue =
    budgetFilter?.value.toLowerCase() || "";

  const ambianceValue =
    ambianceFilter?.value.toLowerCase() || "";

  const voyageurValue =
    voyageurFilter?.value.toLowerCase() || "";

  const searchValue =
    hebergementSearch?.value.toLowerCase() || "";

  cards.forEach(card => {

    const type =
      card.querySelector(".type")
      ?.textContent.toLowerCase() || "";

    const title =
      card.querySelector("h3")
      ?.textContent.toLowerCase() || "";

    const text =
      card.textContent.toLowerCase();

    const matchType =
      typeValue.includes("tous") ||
      type.includes(typeValue);

    const matchBudget =
      budgetValue.includes("tous") ||
      text.includes(budgetValue);

    const matchAmbiance =
      ambianceValue.includes("ambiance") ||
      text.includes(ambianceValue);

    const matchVoyageur =
      voyageurValue.includes("voyageurs") ||
      text.includes(voyageurValue);

    const matchSearch =
      title.includes(searchValue);

    if (
      matchType &&
      matchBudget &&
      matchAmbiance &&
      matchVoyageur &&
      matchSearch
    ) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }

  });

}

typeFilter?.addEventListener("change", filterHebergements);
budgetFilter?.addEventListener("change", filterHebergements);
ambianceFilter?.addEventListener("change", filterHebergements);
voyageurFilter?.addEventListener("change", filterHebergements);
hebergementSearch?.addEventListener("input", filterHebergements);

const transportFilters =
  document.querySelectorAll(".transport-filter");

const transportCards =
  document.querySelectorAll(".transport-card");

const transportSearch =
  document.getElementById("transportSearch");

let currentTransportFilter = "all";

function filterTransports() {

  const searchValue =
    transportSearch?.value.toLowerCase() || "";

  transportCards.forEach(card => {

    const type =
      card.dataset.type;

    const text =
      card.textContent.toLowerCase();

    const matchFilter =
      currentTransportFilter === "all" ||
      type === currentTransportFilter;

    const matchSearch =
      text.includes(searchValue);

    if (matchFilter && matchSearch) {

      card.style.display = "block";

    } else {

      card.style.display = "none";

    }

  });

}

transportFilters.forEach(button => {

  button.addEventListener("click", () => {

    transportFilters.forEach(btn =>
      btn.classList.remove("active")
    );

    button.classList.add("active");

    currentTransportFilter =
      button.dataset.type;

    filterTransports();

  });

});

transportSearch?.addEventListener(
  "input",
  filterTransports
);

const hebergementsData = {

  "Bali Paradise Resort": {
    image: "hotel1.jpg",
    type: "Resort",
    price: "320€ / nuit",
    rating: "4.9",
    location: "Bali",
    description:
      "Resort tropical avec piscine privée, jungle luxuriante et sunset incroyable.",

    services: [
      "Piscine privée",
      "Spa & massages",
      "Petit-déjeuner inclus",
      "Vue jungle"
    ]
  },

  "Maldives Escape": {
    image: "hotel2.jpg",
    type: "Hotel",
    price: "540€ / nuit",
    rating: "5.0",
    location: "Maldives",
    description:
      "Villa premium au-dessus de l’eau turquoise avec expérience VIP.",

    services: [
      "Villa sur l’eau",
      "Accès plage privée",
      "Restaurant gastronomique",
      "Service VIP"
    ]
  },

  "Santorini Skyline": {
    image: "hotel3.jpg",
    type: "Hotel",
    price: "280€ / nuit",
    rating: "4.8",
    location: "Santorin",
    description:
      "Architecture minimaliste avec vue magique sur le coucher de soleil.",

    services: [
      "Vue mer",
      "Piscine rooftop",
      "Suite romantique",
      "Petit-déjeuner inclus"
    ]
  },

  "Jungle Villa Bali": {
    image: "villachill.jpg",
    type: "Villa",
    price: "420€ / nuit",
    rating: "4.9",
    location: "Bali",
    description:
      "Villa luxueuse au cœur de la jungle avec piscine privée.",

    services: [
      "Piscine privée",
      "Vue jungle",
      "Cuisine équipée",
      "Terrasse tropicale"
    ]
  },

  "Ocean Villa Maldives": {
    image: "maldivevilla.jpg",
    type: "Villa",
    price: "350€ / nuit",
    rating: "4.7",
    location: "Maldives",
    description:
      "Villa premium avec accès direct à la mer turquoise.",

    services: [
      "Accès mer",
      "Suite premium",
      "Terrasse privée",
      "Sunset view"
    ]
  },

  "Maison Cyclades": {
    image: "maisongrecquetypique.jpg",
    type: "Maison Grecque",
    price: "400€ / nuit",
    rating: "5.0",
    location: "Santorin",
    description:
      "Maison typique des Cyclades avec vue panoramique sur la mer.",

    services: [
      "Vue mer",
      "Maison traditionnelle",
      "Terrasse sunset",
      "Cuisine équipée"
    ]
  }

};

function renderHebergementDetail() {

  const page =
    document.getElementById("hebergementDetailPage");

  if (!page) return;

  const params =
    new URLSearchParams(window.location.search);

  const name =
    params.get("hebergement");

  const hebergement =
    hebergementsData[name];

  if (!hebergement) {

    page.innerHTML = `
      <section class="error-page">
        <h1>Hébergement introuvable 😢</h1>
      </section>
    `;

    return;
  }

  page.innerHTML = `

<section class="hebergement-detail-hero">

  <img
    src="assets/images/${hebergement.image}"
    alt="${name}"
  >

  <div class="hebergement-detail-overlay">

    <p class="tag">
      ${hebergement.type}
    </p>

    <h1>
      ${name}
    </h1>

    <p>
      ${hebergement.description}
    </p>

    <div class="detail-meta">

      <span>
        ⭐ ${hebergement.rating}
      </span>

      <span>
        📍 ${hebergement.location}
      </span>

      <span>
        💸 ${hebergement.price}
      </span>

    </div>

  </div>

</section>

<section class="hebergement-detail-content">

  <div class="detail-main-card">

    <h2>
      Pourquoi choisir cet hébergement ? ✨
    </h2>

    <p>
      Cet hébergement est parfait pour profiter
      d’un séjour premium avec une ambiance
      immersive et relaxante.
    </p>

    <div class="detail-options">

      <div>
        <h3>🏝 Ambiance</h3>
        <p>
          Luxe, détente et expérience instagrammable.
        </p>
      </div>

      <div>
        <h3>🍽 Restauration</h3>
        <p>
          Restaurants premium et petit-déjeuner inclus.
        </p>
      </div>

      <div>
        <h3>🛏 Confort</h3>
        <p>
          Chambres modernes avec équipements haut de gamme.
        </p>
      </div>

    </div>

  </div>

  <div class="detail-side-card">

    <h2>
      Services inclus ✨
    </h2>

    ${hebergement.services.map(service => `
      <div class="activity-line">
        <span>✔</span>
        <p>${service}</p>
      </div>
    `).join("")}

  </div>

</section>

<section class="next-step-section">

  <h2>
    Votre hébergement est trouvé 🏨
  </h2>

  <div class="next-step-grid">

    <a href="transports.html">
      ✈ Choisir le transport
    </a>

    <a href="hebergements.html">
      🏨 Retour aux hébergements
    </a>

    <a href="activites.html">
      🎉 Voir les activités
    </a>

  </div>

  <button class="add-cart-btn">
    Ajouter au panier voyage
  </button>

</section>

`;
}

renderHebergementDetail();

const showMoreBtn =
  document.getElementById("showMoreBtn");

const hiddenCards =
  document.querySelectorAll(".hidden-card");

let isExpanded = false;

if (showMoreBtn) {

  showMoreBtn.addEventListener("click", () => {

    isExpanded = !isExpanded;

    hiddenCards.forEach(card => {

      if (isExpanded) {

        card.style.display = "block";

      } else {

        card.style.display = "none";

      }

    });

    showMoreBtn.textContent = isExpanded
      ? "Voir moins ✨"
      : "Voir plus ✨";

  });

}