// =============================================
// SCRIPT.JS — VoyageVista
// Synchronisé avec la BDD via les APIs PHP
// =============================================

console.log("VoyageVista chargé 🌴");

// ── SPLASH SCREEN ─────────────────────────────────────────
window.addEventListener("load", () => {
  const splash = document.getElementById("splash-screen");
  if (splash) {
    setTimeout(() => splash.classList.add("hidden"), 7800);
  }
});

// ── CONFIG ────────────────────────────────────────────────
const IMAGES_PATH   = 'assets/images/';
const API_BASE      = '../backend/';

const categoryIcons = {
  "Aventure":    "🧭",
  "Nightlife":   "🎉",
  "Plage":       "🌊",
  "Gastronomie": "🍽️",
  "Culture":     "🏛️",
  "Nature":      "🌿",
  "Sport":       "🏄",
  "Detente":     "🧘",
  "Road Trip":   "🚗",
  "Surf & Sports nautiques": "🏄",
  "Randonnée & Aventure":    "🧗",
  "Gastronomie & Food tour": "🍜",
  "Croisière & Bateau":      "🛥️",
  "Culture & Visite":        "🏛️",
  "Nightlife & Soirée":      "🎉",
  "Bien-être & Spa":         "🧖",
};

// Mapping image BDD → image locale
const destImagesMap = {
  'bali.png':              'bali.png',
  'algarve.png':           'algarve.png',
  'barcelone.png':         'barcelone.png',
  'chamonix.png':          'chamonix.png',
  'costarica.png':         'costarica.png',
  'ibiza.png':             'ibiza.png',
  'santorin.png':          'santorin.png',
  'diner-marocain.png':    'diner-marocain.png',
  'food-tour.png':         'food-tour.png',
  'boat.png':              'boat.png',
  'croisiere-sunset.png':  'croisiere-sunset.png',
  'hebergement-bg.jpg':    'hebergement-bg.jpg',
  'barcelonevilla.jpg':    'barcelonevista.jpg',
};

function resolveImg(imageUrl, fallback = 'hebergement-bg.jpg') {
  if (!imageUrl) return IMAGES_PATH + fallback;
  return IMAGES_PATH + (destImagesMap[imageUrl] || imageUrl);
}

// ── DONNÉES (chargées depuis la BDD) ─────────────────────
let destinations = [];
let activities   = [];

// ── INIT ─────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  await chargerDonnees();
  renderSwipeCard();
  renderCatalogue(destinations);
  renderActivitiesFeed();
  renderDestinationDetail();
  renderActivityDetail();
  renderHebergementDetail();
  setupFiltres();
  setupSearch();
});

// ── CHARGEMENT DONNÉES DEPUIS LA BDD ─────────────────────
async function chargerDonnees() {
  try {
    const [resD, resA] = await Promise.all([
      fetch(API_BASE + 'api_destinations.php?limit=50', { credentials: 'include' }),
      fetch(API_BASE + 'api_activites.php?limit=100',   { credentials: 'include' }),
    ]);

    const jsonD = await resD.json();
    const jsonA = await resA.json();

    if (jsonD.success && jsonD.data.length > 0) {
      // Convertir format BDD → format attendu par le reste du script
      destinations = jsonD.data.map(d => ({
        id:          d.id,
        name:        d.nom,
        image:       d.image_url || 'bali.png',
        categories:  d.categorie ? [d.categorie] : ['Voyage'],
        region:      d.region    || '',
        budget:      d.budget    || '€€',
        price:       d.prix_base || 0,
        rating:      d.note_moyenne || 4.5,
        group:       d.nb_voyageurs_max >= 9 ? '9+' : d.nb_voyageurs_max >= 5 ? '5-8' : '2-4',
        popular:     d.nb_reservations || 0,
        trend:       d.nb_reservations || 0,
        isNew:       false,
        description: d.description || '',
        pays:        d.pays || '',
      }));
    } else {
      // Fallback données hardcodées si BDD vide
      destinations = destinationsFallback;
    }

    if (jsonA.success && jsonA.data.length > 0) {
      // Activités depuis la BDD
      const activitesBDD = jsonA.data.map(a => ({
        id:           a.id,
        name:         a.nom,
        image:        a.image_url || 'boat.png',
        destination:  a.destination_nom || '',
        category:     a.categorie || '',
        price:        a.prix || 0,
        duration:     a.duree_heures ? a.duree_heures + 'h' : '2h',
        capacite_max: a.capacite_max || 10,
        rating:       a.note_moyenne || 4.5,
        reviews:      0,
        vibe:         (categoryIcons[a.categorie] || '✨') + ' ' + (a.categorie || 'Activité'),
        description:  a.description || '',
        comment1:     'Super activité à faire en groupe.',
        comment2:     'Une expérience mémorable avec toute la team.',
        date:         '',
        places:       a.capacite_max || 10,
      }));

      // Fusionner BDD + suggestions Sofia (éviter les doublons par nom)
      const nomsExistants = new Set(activitesBDD.map(a => a.name.toLowerCase()));
      const suggestionsUniques = activitesFallback.filter(
        a => !nomsExistants.has(a.name.toLowerCase())
      );

      activities = [...activitesBDD, ...suggestionsUniques];
    } else {
      activities = activitesFallback;
    }

  } catch (err) {
    console.warn('API non disponible, données locales utilisées :', err);
    destinations = destinationsFallback;
    activities   = activitesFallback;
  }
}

// ── SWIPE ─────────────────────────────────────────────────
let swipeIndex = 0;

const swipeCard    = document.getElementById("swipeCard");
const swipeCounter = document.getElementById("swipeCounter");
const passBtn      = document.getElementById("passBtn");
const likeBtn      = document.getElementById("likeBtn");

function createBadges(categories) {
  return categories.map(cat =>
    `<span class="dest-badge">${categoryIcons[cat] || "✨"} ${cat}</span>`
  ).join("");
}

function renderSwipeCard() {
  if (!swipeCard) return;

  if (swipeIndex >= destinations.length) {
    swipeCard.innerHTML = `
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:60px">
        <div style="max-width:700px;text-align:center">
          <h2 style="font-size:58px;color:#4a68a6;margin-bottom:25px;line-height:1.1">
            Tu as vu toutes les destinations ✨
          </h2>
          <p style="font-size:24px;color:#5b6f8f;margin-bottom:40px;line-height:1.5">
            Continue avec le catalogue pour filtrer, comparer et choisir le voyage parfait.
          </p>
          <a href="#catalogueGrid" class="detail-link" style="padding:18px 45px;font-size:20px">
            Voir le catalogue
          </a>
        </div>
      </div>`;
    swipeCounter.textContent = "Fin du swipe";
    return;
  }

  const dest = destinations[swipeIndex];
  swipeCard.innerHTML = `
    <img src="${resolveImg(dest.image)}" alt="${dest.name}" class="swipe-img">
    <div class="swipe-content">
      <h2>${dest.name}</h2>
      <p class="swipe-description">${dest.description}</p>
      <div class="badges">${createBadges(dest.categories)}</div>
      <div class="destination-meta">
        <span>${dest.budget} • dès ${dest.price}€</span>
        <span>👥 ${dest.group}</span>
        <span>⭐ ${dest.rating}</span>
      </div>
      <a href="destination-detail.html?id=${dest.id}" class="detail-link">
        Voir les détails
      </a>
    </div>`;

  swipeCounter.textContent = `${swipeIndex + 1} / ${destinations.length}`;

  // Sauvegarder le swipe localement pour les stats profil
  const swipes = JSON.parse(localStorage.getItem('vv_swipes') || '[]');
  if (!swipes.includes(dest.id)) {
    swipes.push(dest.id);
    localStorage.setItem('vv_swipes', JSON.stringify(swipes));
  }
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

if (passBtn) passBtn.addEventListener("click", () => nextSwipe("pass"));
if (likeBtn) likeBtn.addEventListener("click", () => nextSwipe("like"));

// ── CATALOGUE ─────────────────────────────────────────────
const catalogueGrid = document.getElementById("catalogueGrid");

function renderCatalogue(list) {
  if (!catalogueGrid) return;

  if (list.length === 0) {
    catalogueGrid.innerHTML = `
      <div class="empty-message">Aucune destination ne matche avec ces filtres 😭</div>`;
    return;
  }

  catalogueGrid.innerHTML = list.map(dest => `
    <article class="catalogue-card">
      <button class="fav-btn" data-fav-id="${dest.id}" title="Ajouter aux favoris">♥</button>
      <img src="${resolveImg(dest.image)}" alt="${dest.name}">
      <div class="catalogue-content">
        <h3>${dest.name}</h3>
        <p>${dest.description}</p>
        <div class="badges">${createBadges(dest.categories.slice(0, 3))}</div>
        <div class="destination-meta">
          <span>${dest.budget} • ${dest.price}€</span>
          <span>👥 ${dest.group}</span>
          <span>⭐ ${dest.rating}</span>
        </div>
        <a href="destination-detail.html?id=${dest.id}" class="detail-link">
          Voir les détails
        </a>
      </div>
    </article>`).join("");

  // Favoris gérés par favoris.js
}

// ── FILTRES ───────────────────────────────────────────────
function setupFiltres() {
  ["searchInput","categoryFilter","regionFilter",
   "budgetFilter","groupFilter","sortFilter"].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener("input",  applyFilters);
      el.addEventListener("change", applyFilters);
    }
  });
}

function applyFilters() {
  const search   = document.getElementById("searchInput")?.value.toLowerCase()  || "";
  const category = document.getElementById("categoryFilter")?.value              || "all";
  const region   = document.getElementById("regionFilter")?.value                || "all";
  const budget   = document.getElementById("budgetFilter")?.value                || "all";
  const group    = document.getElementById("groupFilter")?.value                 || "all";
  const sort     = document.getElementById("sortFilter")?.value                  || "popular";

  let filtered = destinations.filter(dest => {
    const matchSearch   = dest.name.toLowerCase().includes(search) ||
                          dest.description.toLowerCase().includes(search);
    const matchCategory = category === "all" || dest.categories.includes(category);
    const matchRegion   = region === "all"   || dest.region === region;
    const matchBudget   = budget === "all"   || dest.budget === budget;
    const matchGroup    = group === "all"    || dest.group === group;
    return matchSearch && matchCategory && matchRegion && matchBudget && matchGroup;
  });

  if (sort === "priceAsc")  filtered.sort((a,b) => a.price - b.price);
  if (sort === "priceDesc") filtered.sort((a,b) => b.price - a.price);
  if (sort === "rating")    filtered.sort((a,b) => b.rating - a.rating);
  if (sort === "popular")   filtered.sort((a,b) => b.popular - a.popular);
  if (sort === "trend")     filtered.sort((a,b) => b.trend - a.trend);
  if (sort === "groups")    filtered.sort((a,b) => (b.group==="9+")-(a.group==="9+"));
  if (sort === "new")       filtered.sort((a,b) => b.isNew - a.isNew);

  renderCatalogue(filtered);
}

// ── RECHERCHE ─────────────────────────────────────────────
function setupSearch() {
  const searchInput = document.getElementById("searchInput");
  const searchBtn   = document.getElementById("searchBtn");

  function doSearch() {
    if (!searchInput) return;
    const val = searchInput.value.trim().toLowerCase();
    const found = destinations.find(d => d.name.toLowerCase().includes(val));
    if (found) {
      window.location.href = `destination-detail.html?id=${found.id}`;
    } else {
      window.location.href = "404.html";
    }
  }

  if (searchBtn) searchBtn.addEventListener("click", doSearch);
  if (searchInput) {
    searchInput.addEventListener("keydown", e => {
      if (e.key === "Enter") doSearch();
    });
  }
}

// ── DESTINATION DETAIL ────────────────────────────────────
async function renderDestinationDetail() {
  const page = document.getElementById("destinationDetailPage");
  if (!page) return;

  const params = new URLSearchParams(window.location.search);
  const id     = params.get("id");
  const name   = params.get("destination"); // fallback ancien lien

  // Loader
  page.innerHTML = `<div style="text-align:center;padding:120px;font-size:3rem">✈️</div>`;

  try {
    let dest = null;

    if (id) {
      const res  = await fetch(`${API_BASE}api_destination_detail.php?id=${id}`, { credentials: 'include' });
      const json = await res.json();
      if (json.success) dest = json.data;
    }

    // Fallback par nom (ancien système)
    if (!dest && name) {
      dest = destinations.find(d => d.name.toLowerCase() === name.toLowerCase());
      if (dest) {
        const res  = await fetch(`${API_BASE}api_destination_detail.php?id=${dest.id}`, { credentials: 'include' });
        const json = await res.json();
        if (json.success) dest = json.data;
      }
    }

    if (!dest) { window.location.href = "404.html"; return; }

    const hebs  = dest.hebergements || [];
    const acts  = dest.activites    || [];
    const cats  = dest.categorie    ? [dest.categorie] : [];
    const img   = resolveImg(dest.image_url);

    // Stocker l'ID dans la page pour add_to_cart.js
    page.dataset.destId = dest.id;

    page.innerHTML = `
      <section class="detail-hero">
        <img src="${img}" alt="${dest.nom}">
        <div class="detail-overlay">
          <p class="tag">DESTINATION MATCHÉE</p>
          <h1>${dest.nom}</h1>
          <p>${dest.description || ''}</p>
          <div class="badges">${createBadges(cats)}</div>
          <div class="detail-meta">
            <span>${dest.budget || ''} • dès ${dest.prix_base || 0}€</span>
            <span>⭐ ${dest.note_moyenne || ''}</span>
            <span>📍 ${dest.pays || ''}</span>
          </div>
        </div>
      </section>

      <section class="detail-content">
        <div class="detail-main-card">
          <h2>Pourquoi ça matche avec ta team ?</h2>
          <p>${dest.description || ''}</p>

          <div class="detail-options">
            <div>
              <h3>🏨 Hébergements disponibles</h3>
              ${hebs.length > 0
                ? hebs.map(h => `
                    <div class="activity-line">
                      <span>✨</span>
                      <p>
                        <a href="detail-hebergement.html?id=${h.id}" style="color:#4a68a6;font-weight:600">${h.nom}</a>
                        — ${h.prix_nuit}€/nuit
                        ${h.nb_disponibilites > 0
                          ? `<span style="color:#16a34a;font-size:12px;margin-left:8px">✅ Disponible</span>`
                          : `<span style="color:#e64b5d;font-size:12px;margin-left:8px">❌ Indisponible</span>`
                        }
                      </p>
                    </div>`).join('')
                : '<p>Aucun hébergement disponible pour cette destination.</p>'}
            </div>
            <div>
              <h3>💸 Budget estimé</h3>
              <p>À partir de ${dest.prix_base || 0}€ par personne</p>
            </div>
            <div>
              <h3>🌍 Région</h3>
              <p>${dest.region || ''} — ${dest.pays || ''}</p>
            </div>
          </div>

          <button class="add-cart-btn">Ajouter au panier voyage</button>
        </div>

        <div class="detail-side-card">
          <h2>Activités à ne pas rater</h2>
          ${acts.length > 0
            ? acts.map(a => `
                <a class="activity-line clickable-activity"
                   href="activite-detail.html?id=${a.id}">
                  <span>✨</span>
                  <p>${a.nom} — ${a.prix}€</p>
                </a>`).join('')
            : '<p>Découvrez nos activités sur la page dédiée.</p>'}
        </div>
      </section>

      <section class="next-step-section">
        <h2>Ok… le voyage prend forme ✈️🌴</h2>
        <div class="next-step-grid">
          <a href="activites.html">🎉 Choisir les activités</a>
          <a href="hebergements.html">🏨 Choisir l'hébergement</a>
          <a href="transports.html">✈ Choisir le transport</a>
        </div>
        <p class="back-catalogue-text">
          Finalement c'était pas le bon mood ? 👀
          <a href="destination.html">Retourner swiper d'autres destinations</a>
        </p>
      </section>`;

  } catch(err) {
    console.error('Erreur destination detail :', err);
    page.innerHTML = `<section class="error-page"><h1>Destination introuvable 😢</h1></section>`;
  }
}

// ── ACTIVITÉS FEED ────────────────────────────────────────
function renderActivitiesFeed() {
  const feed = document.getElementById("activityFeed");
  if (!feed) return;

  if (activities.length === 0) {
    feed.innerHTML = `<div class="empty-message">Aucune activité disponible pour le moment.</div>`;
    return;
  }

  feed.innerHTML = activities.map(a => `
    <article class="activity-post">
      <img src="${resolveImg(a.image, 'boat.png')}" alt="${a.name}">
      <div class="activity-post-content">
        <p class="tag">${a.vibe}</p>
        <h2>${a.name}</h2>
        <p>${a.description}</p>
        <div class="activity-info">
          <span>📍 ${a.destination}</span>
          <span>⏱️ ${a.duration}</span>
          <span>💸 ${a.price}€</span>
          <span>👥 Max ${a.capacite_max || a.places || 10} pers.</span>
        </div>
        <div class="activity-actions">
          ${a.id > 0
            ? `<a href="activite-detail.html?id=${a.id}">Voir l'activité</a>`
            : `<a href="activites.html">Voir l'activité</a>`
          }
          <button>♥</button>
        </div>
      </div>
    </article>`).join("");
}

// ── ACTIVITÉ DETAIL ───────────────────────────────────────
async function renderActivityDetail() {
  const page = document.getElementById("activityDetailPage");
  if (!page) return;

  const params = new URLSearchParams(window.location.search);
  const id     = params.get("id");
  const name   = params.get("activite"); // fallback

  page.innerHTML = `<div style="text-align:center;padding:120px;font-size:3rem">✈️</div>`;

  try {
    let activity = null;

    if (id) {
      const res  = await fetch(`${API_BASE}api_activites.php?id=${id}`, { credentials: 'include' });
      const json = await res.json();
      if (json.success && json.data.length > 0) activity = json.data[0];
    }

    // Fallback par nom
    if (!activity && name) {
      activity = activities.find(a => a.name.toLowerCase() === name.toLowerCase());
    }

    if (!activity) { window.location.href = "404.html"; return; }

    const a   = activity;
    const img = resolveImg(a.image || a.image_url, 'boat.png');

    // Récupérer le nb de voyageurs du panier pour vérifier la capacité
    let nbVoyageurs = 1;
    try {
      const resP  = await fetch('../backend/panier.php?action=get', { credentials: 'include' });
      const jsonP = await resP.json();
      if (jsonP.success) nbVoyageurs = jsonP.panier.nb_voyageurs || 1;
    } catch(e) {}

    const capaciteMax  = a.capacite_max || a.places || 10;
    const complet      = nbVoyageurs > capaciteMax;
    const btnPanier    = complet
      ? `<button class="add-cart-btn" disabled style="background:#ccc;cursor:not-allowed;opacity:.6">
           ⛔ Complet pour ${nbVoyageurs} personnes (max ${capaciteMax})
         </button>`
      : `<button class="add-cart-btn">Ajouter au panier voyage</button>`;

    // Stocker l'ID dans la page pour add_to_cart.js
    page.dataset.actId = a.id;

    page.innerHTML = `
      <section class="activity-detail-hero">
        <img src="${img}" alt="${a.name || a.nom}">
        <div class="activity-detail-content">
          <p class="tag">${a.vibe || (categoryIcons[a.categorie] || '✨') + ' ' + (a.categorie || '')}</p>
          <h1>${a.name || a.nom}</h1>
          <p>${a.description || ''}</p>
          <div class="activity-info">
            <span>📍 ${a.destination || a.destination_nom || ''}</span>
            <span>⏱️ ${a.duration || (a.duree_heures ? a.duree_heures + 'h' : '—')}</span>
            <span>💸 ${a.price || a.prix || 0}€</span>
            <span>👥 Max ${capaciteMax} pers.</span>
          </div>
          ${btnPanier}
        </div>
      </section>

      <section class="activity-detail-boxes">
        <div class="activity-detail-box">
          <h3>Pourquoi on valide ?</h3>
          <p>Simple à réserver, visuel, fun et parfait pour créer des souvenirs de groupe.</p>
        </div>
        <div class="activity-detail-box">
          <h3>Avis voyageurs</h3>
          <p>⭐ ${a.rating || a.note_moyenne || 4.5}/5</p>
          <p>"${a.comment1 || 'Super activité à faire en groupe.'}"</p>
          <p>"${a.comment2 || 'Une expérience mémorable avec toute la team.'}"</p>
        </div>
        <div class="activity-detail-box">
          <h3>Infos pratiques</h3>
          <p>📍 ${a.destination || a.destination_nom || '—'}</p>
          <p>⏱️ Durée : ${a.duration || (a.duree_heures ? a.duree_heures + 'h' : '—')}</p>
          <p>💸 Prix : ${a.price || a.prix || 0}€ / personne</p>
        </div>
      </section>

      <section class="next-step-section">
        <h2>Cette activité te parle ? ✈️</h2>
        <div class="next-step-grid">
          <a href="activites.html">🎉 Voir toutes les activités</a>
          <a href="destination.html">🌍 Explorer les destinations</a>
          <a href="hebergements.html">🏨 Choisir un hébergement</a>
        </div>
      </section>`;

  } catch(err) {
    console.error('Erreur activité detail :', err);
    page.innerHTML = `<section class="error-page"><h1>Activité introuvable 😢</h1></section>`;
  }
}

// ── HÉBERGEMENT DETAIL ────────────────────────────────────
async function renderHebergementDetail() {
  const page = document.getElementById("hebergementDetailPage");
  if (!page) return;

  const params = new URLSearchParams(window.location.search);
  const id     = params.get("id");
  const nom    = params.get("hebergement"); // fallback ancien lien

  if (!id && !nom) {
    page.innerHTML = `<section class="error-page"><h1>Hébergement introuvable 😢</h1></section>`;
    return;
  }

  page.innerHTML = `<div style="text-align:center;padding:120px;font-size:3rem">✈️</div>`;

  try {
    let json = null;

    if (id) {
      // Nouveau système : ?id=1
      const res = await fetch(`${API_BASE}api_hebergement_detail.php?id=${id}`, { credentials: 'include' });
      json = await res.json();
    } else if (nom) {
      // Ancien système : ?hebergement=NomVilla → chercher par nom
      const res  = await fetch(`${API_BASE}api_hebergements.php?search=${encodeURIComponent(nom)}&limit=1`, { credentials: 'include' });
      const list = await res.json();
      if (list.success && list.data.length > 0) {
        const hId  = list.data[0].id;
        const res2 = await fetch(`${API_BASE}api_hebergement_detail.php?id=${hId}`, { credentials: 'include' });
        json = await res2.json();
      }
    }

    if (!json || !json.success) {
      window.location.href = "404.html"; return;
    }

    const h   = json.data;
    const img = resolveImg(h.image_url, 'hebergement-bg.jpg');

    // Stocker l'ID dans la page pour add_to_cart.js
    page.dataset.hebId = h.id;

    page.innerHTML = `
      <section class="hebergement-detail-hero">
        <img src="${img}" alt="${h.nom}" onerror="this.src='${IMAGES_PATH}hebergement-bg.jpg'">
        <div class="hebergement-detail-overlay">
          <p class="tag">${h.type || 'Hébergement'}</p>
          <h1>${h.nom}</h1>
          <p>${h.description || ''}</p>
          <div class="detail-meta">
            <span>⭐ ${h.note_moyenne}</span>
            <span>📍 ${h.destination_nom}</span>
            <span>💸 ${h.prix_nuit}€ / nuit</span>
            <span>👥 ${h.capacite} pers. max</span>
          </div>
        </div>
      </section>

      <section class="hebergement-detail-content">
        <div class="detail-main-card">
          <h2>Pourquoi choisir cet hébergement ? ✨</h2>
          <p>${h.description || ''}</p>

          ${h.disponibilites && h.disponibilites.length > 0 ? `
          <h3 style="margin-top:30px;color:#4a68a6">📅 Choisir vos dates</h3>

          <!-- Sélecteur de dates -->
          <div id="datePicker" style="
            background:#f7fbff;border-radius:18px;padding:20px;
            margin-top:12px;border:1.5px solid rgba(121,169,223,.2)
          ">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
              <div>
                <label style="font-size:13px;font-weight:700;color:#466789;display:block;margin-bottom:6px">
                  📅 Arrivée
                </label>
                <input type="date" id="dateDebut"
                  min="${new Date().toISOString().split('T')[0]}"
                  style="width:100%;padding:10px 14px;border:1.5px solid rgba(121,169,223,.3);
                  border-radius:12px;font-size:14px;color:#17375e;background:#fff;outline:none">
              </div>
              <div>
                <label style="font-size:13px;font-weight:700;color:#466789;display:block;margin-bottom:6px">
                  📅 Départ
                </label>
                <input type="date" id="dateFin"
                  min="${new Date().toISOString().split('T')[0]}"
                  style="width:100%;padding:10px 14px;border:1.5px solid rgba(121,169,223,.3);
                  border-radius:12px;font-size:14px;color:#17375e;background:#fff;outline:none">
              </div>
            </div>
            <button onclick="verifierDisponibilite(${h.id})" style="
              width:100%;padding:12px;border:none;border-radius:14px;
              background:linear-gradient(135deg,#79a9df,#f3b27d);
              color:#fff;font-size:14px;font-weight:700;cursor:pointer
            ">🔍 Vérifier la disponibilité</button>
            <div id="dispoResult" style="margin-top:12px"></div>
          </div>

          <!-- Créneaux disponibles -->
          <h3 style="margin-top:24px;color:#4a68a6">🗓️ Créneaux disponibles</h3>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;margin-top:12px">
          ${h.disponibilites.map(d => {
            const debut  = new Date(d.date_debut).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'});
            const fin    = new Date(d.date_fin).toLocaleDateString('fr-FR', {day:'2-digit',month:'short',year:'numeric'});
            const nuits  = Math.round((new Date(d.date_fin) - new Date(d.date_debut)) / 86400000);
            const dispo  = parseInt(d.places_dispo);
            const couleur = dispo <= 2 ? '#e64b5d' : dispo <= 5 ? '#f39b5f' : '#16a34a';
            return `
              <div style="
                background:#fff;border-radius:14px;padding:14px 16px;
                border-left:4px solid ${couleur};
                box-shadow:0 2px 8px rgba(0,0,0,.06);cursor:pointer
              " onclick="remplirDates('${d.date_debut}','${d.date_fin}')">
                <div style="font-weight:700;color:#4a68a6;font-size:14px">
                  📅 ${debut} → ${fin}
                </div>
                <div style="font-size:13px;color:#466789;margin-top:4px">
                  ⏱ ${nuits} nuit${nuits > 1 ? 's' : ''}
                  &nbsp;•&nbsp;
                  <span style="color:${couleur};font-weight:700">
                    ${dispo} place${dispo > 1 ? 's' : ''} disponible${dispo > 1 ? 's' : ''}
                  </span>
                </div>
                <div style="font-size:12px;color:#8aabb8;margin-top:4px">
                  Cliquer pour sélectionner ce créneau
                </div>
              </div>`;
          }).join('')}
          </div>
          ` : `
          <div style="margin-top:20px;padding:16px;background:#fff8e1;border-radius:14px;border-left:4px solid #f39b5f">
            <p style="color:#92400e;font-weight:600">⚠️ Aucune disponibilité pour le moment.</p>
            <p style="color:#92400e;font-size:13px;margin-top:4px">Contactez le prestataire pour plus d'informations.</p>
          </div>`}

          <button class="add-cart-btn">Ajouter au panier voyage</button>
        </div>

        <div class="detail-side-card">
          <h2>Infos pratiques ✨</h2>
          <div class="activity-line"><span>✔</span><p>Type : ${h.type || '—'}</p></div>
          <div class="activity-line"><span>✔</span><p>Capacité : ${h.capacite} personnes</p></div>
          <div class="activity-line"><span>✔</span><p>Région : ${h.region || '—'}</p></div>
          <div class="activity-line"><span>✔</span><p>Destination : ${h.destination_nom}</p></div>
          <div class="activity-line"><span>✔</span><p>Prix : ${h.prix_nuit}€ / nuit</p></div>
          <div class="activity-line"><span>✔</span><p>Note : ⭐ ${h.note_moyenne}/5</p></div>
          <div class="activity-line"><span>✔</span><p>Prestataire : ${h.prestataire_nom}</p></div>
          <button data-signaler="hebergement" data-id="${h.id}"
                  style="margin-top:20px;background:none;border:1.5px solid #ffc5cb;
                         color:#e64b5d;padding:10px 18px;border-radius:20px;
                         cursor:pointer;font-weight:700;font-size:13px;width:100%">
            🚩 Signaler cet hébergement
          </button>
        </div>
      </section>

      <section class="next-step-section">
        <h2>Votre hébergement est trouvé 🏨</h2>
        <div class="next-step-grid">
          <a href="transports.html">✈ Choisir le transport</a>
          <a href="hebergements.html">🏨 Retour aux hébergements</a>
          <a href="activites.html">🎉 Voir les activités</a>
        </div>
      </section>`;

  } catch(err) {
    console.error('Erreur hébergement detail :', err);
    page.innerHTML = `<section class="error-page"><h1>Hébergement introuvable 😢</h1></section>`;
  }
}

// ── DONNÉES FALLBACK (si API non disponible) ──────────────
const destinationsFallback = [
  { id:0, name:"Bali",          image:"bali.png",       categories:["Plage","Nature"],      region:"Asie",    budget:"€€",  price:780,  rating:4.8, group:"9+", popular:98, trend:95, isNew:false, description:"Plages turquoise, temples, surf et sunsets parfaits avec ta team." },
  { id:0, name:"Ibiza",         image:"ibiza.png",       categories:["Nightlife","Plage"],   region:"Europe",  budget:"€€",  price:520,  rating:4.6, group:"5-8",popular:96, trend:99, isNew:false, description:"Le spot idéal pour alterner plage, musique et soirées entre potes." },
  { id:0, name:"Barcelone",     image:"barcelone.png",   categories:["Nightlife","Culture"], region:"Europe",  budget:"€€",  price:430,  rating:4.6, group:"5-8",popular:97, trend:94, isNew:false, description:"Ville solaire, tapas, plage et soirées faciles à organiser." },
  { id:0, name:"Chamonix",      image:"chamonix.png",    categories:["Aventure","Sport"],    region:"Europe",  budget:"€€",  price:560,  rating:4.4, group:"2-4",popular:83, trend:86, isNew:true,  description:"Montagne, ski, randonnées et sensations fortes au grand air." },
  { id:0, name:"Costa Rica",    image:"costarica.png",   categories:["Aventure","Nature"],   region:"Amerique",budget:"€€",  price:890,  rating:4.7, group:"9+", popular:90, trend:92, isNew:false, description:"Jungle, volcans, surf et aventures nature pour un groupe motivé." },
  { id:0, name:"Marrakech",     image:"diner-marocain.png",categories:["Culture","Gastronomie"],region:"Afrique",budget:"€", price:390,  rating:4.5, group:"5-8",popular:89, trend:90, isNew:true,  description:"Souks, riads, désert et vibes orientales pour un séjour dépaysant." },
];

const activitesFallback = [
  { id:0, name:"Cours de surf",     image:"surf.png",        destination:"Bali",       category:"Sport",       price:45, duration:"2h",    rating:4.8, reviews:124, vibe:"🌊 Sport • fun • plage",           description:"Apprends à surfer avec ta team sur une plage incroyable. Parfait pour commencer le voyage avec de l'énergie.",       comment1:"Super activité à faire en groupe, l'ambiance était incroyable.", comment2:"Simple à réserver et vraiment un des meilleurs moments du voyage." },
  { id:0, name:"Balade en bateau",  image:"boat.png",        destination:"Ibiza",      category:"Détente",     price:65, duration:"3h",    rating:4.7, reviews:126, vibe:"🛥️ Mer • chill • sunset",          description:"Une sortie en bateau pour profiter de la mer, du soleil et des meilleurs spots photo.",                               comment1:"Le sunset sur le bateau était incroyable.",                      comment2:"Activité parfaite pour chill avec le groupe." },
  { id:0, name:"Visite de temple",  image:"temple.png",      destination:"Bali",       category:"Culture",     price:25, duration:"1h30",  rating:4.6, reviews:93,  vibe:"🏛️ Culture • découverte",          description:"Découvre un lieu iconique, calme et magnifique pour ajouter une vraie touche culturelle au séjour.",                 comment1:"Super beau et hyper apaisant.",                                   comment2:"Ça change vraiment des activités classiques." },
  { id:0, name:"Food tour",         image:"food-tour.png",   destination:"Tokyo",      category:"Gastronomie", price:55, duration:"2h",    rating:4.9, reviews:214, vibe:"🍜 Food • ville • découverte",      description:"Teste les meilleurs spots food locaux et découvre la ville à travers ses saveurs.",                                   comment1:"On a trop mangé 😭 mais c'était incroyable.",                    comment2:"Le meilleur moyen de découvrir Tokyo." },
  { id:0, name:"Beach party",       image:"beach-party.png", destination:"Ibiza",      category:"Nightlife",   price:70, duration:"Soirée",rating:4.8, reviews:301, vibe:"🎉 Nightlife • plage • musique",    description:"Ambiance festive, musique et coucher de soleil : l'activité parfaite pour une team qui veut kiffer.",               comment1:"Meilleure soirée du voyage clairement.",                          comment2:"L'ambiance était folle du début à la fin." },
  { id:0, name:"Randonnée nature",  image:"hiking.png",      destination:"Chamonix",   category:"Nature",      price:30, duration:"4h",    rating:4.5, reviews:87,  vibe:"🥾 Nature • aventure",             description:"Un moment en pleine nature pour respirer, marcher et profiter de paysages incroyables.",                             comment1:"Les paysages étaient magnifiques.",                               comment2:"Très bonne activité pour déconnecter un peu." },
  { id:0, name:"Spa chill",         image:"spa.png",         destination:"Santorin",   category:"Détente",     price:80, duration:"2h",    rating:4.9, reviews:144, vibe:"🧘 Détente • bien-être",           description:"Pause détente obligatoire : spa, calme et recharge totale avant de repartir explorer.",                              comment1:"On voulait plus repartir 😭",                                     comment2:"Le moment le plus relax du séjour." },
  { id:0, name:"Musée immersif",    image:"museum.png",      destination:"Barcelone",  category:"Culture",     price:20, duration:"1h",    rating:4.4, reviews:68,  vibe:"🎨 Culture • photo • indoor",      description:"Une activité simple, visuelle et sympa à faire entre deux sorties en ville.",                                         comment1:"Très stylé pour les photos.",                                     comment2:"Petit musée mais expérience super sympa." },
  { id:0, name:"Visite d'Oia",      image:"oia.png",         destination:"Santorin",   category:"Culture",     price:30, duration:"2h",    rating:4.9, reviews:246, vibe:"🏛️ Culture • sunset • photos",    description:"Découvre les ruelles blanches d'Oia, les vues iconiques et les spots parfaits pour les photos.",                      comment1:"On comprend pourquoi tout le monde en parle.",                    comment2:"Les photos là-bas sont incroyables." },
  { id:0, name:"Rooftop sunset",    image:"rooftop.png",     destination:"Marrakech",  category:"Détente",     price:35, duration:"Soirée",rating:4.8, reviews:173, vibe:"🌅 Sunset • chill • photos",       description:"Un rooftop stylé pour profiter du coucher de soleil et finir la journée en beauté.",                                  comment1:"La vue au coucher du soleil était dingue.",                       comment2:"Hyper bonne vibe pour finir la journée." },
  { id:0, name:"Dîner marocain",    image:"diner-marocain.png",destination:"Marrakech",category:"Gastronomie", price:40, duration:"Soirée",rating:4.7, reviews:102, vibe:"🍽️ Food • ambiance • partage",    description:"Un dîner marocain convivial avec plats traditionnels, ambiance chaleureuse.",                                         comment1:"Le repas était incroyable.",                                      comment2:"Très bonne ambiance avec musique et déco." },
  { id:0, name:"Croisière sunset",  image:"croisiere-sunset.png",destination:"Ibiza",  category:"Détente",     price:80, duration:"3h",    rating:5.0, reviews:318, vibe:"🌅 Sunset • mer • premium",        description:"Une croisière au coucher du soleil pour finir la journée avec une vraie vibe carte postale.",                         comment1:"Le coucher de soleil était irréel.",                              comment2:"Moment préféré du voyage." },
];

// ── GESTION DES DATES ET DISPONIBILITÉS ──────────────────

// Remplir les inputs de date depuis un créneau cliqué
function remplirDates(dateDebut, dateFin) {
  const inputDebut = document.getElementById('dateDebut');
  const inputFin   = document.getElementById('dateFin');
  if (inputDebut) inputDebut.value = dateDebut;
  if (inputFin)   inputFin.value   = dateFin;

  // Déclencher la vérification automatiquement
  const hebId = document.getElementById('hebergementDetailPage')?.dataset.hebId;
  if (hebId) verifierDisponibilite(parseInt(hebId));
}

// Vérifier la disponibilité pour les dates saisies
async function verifierDisponibilite(hebId) {
  const dateDebut = document.getElementById('dateDebut')?.value;
  const dateFin   = document.getElementById('dateFin')?.value;
  const result    = document.getElementById('dispoResult');

  if (!result) return;

  if (!dateDebut || !dateFin) {
    result.innerHTML = `
      <div style="padding:12px 16px;background:#fff8e1;border-radius:12px;color:#92400e;font-size:13px;font-weight:700">
        ⚠️ Sélectionnez une date d'arrivée et de départ.
      </div>`;
    return;
  }

  if (dateFin <= dateDebut) {
    result.innerHTML = `
      <div style="padding:12px 16px;background:#fff0f2;border-radius:12px;color:#c0392b;font-size:13px;font-weight:700">
        ❌ La date de départ doit être après la date d'arrivée.
      </div>`;
    return;
  }

  result.innerHTML = `<div style="padding:12px;color:#466789;font-size:13px">⏳ Vérification en cours...</div>`;

  try {
    const res  = await fetch(
      `../backend/api_check_dispo.php?heb_id=${hebId}&date_debut=${dateDebut}&date_fin=${dateFin}`,
      { credentials: 'include' }
    );
    const json = await res.json();

    if (!json.success) {
      result.innerHTML = `
        <div style="padding:12px 16px;background:#fff0f2;border-radius:12px;color:#c0392b;font-size:13px;font-weight:700">
          ❌ ${json.error}
        </div>`;
      return;
    }

    if (json.disponible) {
      result.innerHTML = `
        <div style="padding:14px 16px;background:#eafff4;border-radius:12px;border:1.5px solid #b7f0d4">
          <p style="color:#1e7e50;font-weight:700;margin-bottom:6px">✅ Disponible !</p>
          <p style="color:#466789;font-size:13px">
            📅 ${json.nuits} nuit${json.nuits > 1 ? 's' : ''}
            &nbsp;•&nbsp; 💸 ${json.prix_nuit}€/nuit
            &nbsp;•&nbsp; 💰 Total : <strong>${json.prix_total.toLocaleString('fr-FR')}€</strong>
          </p>
          <p style="color:#466789;font-size:13px;margin-top:4px">
            👥 ${json.places_dispo} place${json.places_dispo > 1 ? 's' : ''} disponible${json.places_dispo > 1 ? 's' : ''}
          </p>
          <button onclick="ajouterAuPanierAvecDates(${hebId}, '${dateDebut}', '${dateFin}', ${json.prix_total})"
            style="
              margin-top:12px;width:100%;padding:12px;border:none;border-radius:12px;
              background:linear-gradient(135deg,#79a9df,#f3b27d);
              color:#fff;font-size:14px;font-weight:700;cursor:pointer
            ">
            🛒 Ajouter au panier — ${json.prix_total.toLocaleString('fr-FR')}€
          </button>
        </div>`;
    } else {
      result.innerHTML = `
        <div style="padding:14px 16px;background:#fff0f2;border-radius:12px;border:1.5px solid #f5c6cb">
          <p style="color:#c0392b;font-weight:700">❌ Pas disponible pour ces dates</p>
          <p style="color:#466789;font-size:13px;margin-top:4px">
            Choisissez un créneau dans la liste ci-dessous ou modifiez vos dates.
          </p>
        </div>`;
    }

  } catch(err) {
    result.innerHTML = `
      <div style="padding:12px 16px;background:#fff0f2;border-radius:12px;color:#c0392b;font-size:13px">
        ❌ Erreur réseau. Réessayez.
      </div>`;
  }
}

// Ajouter au panier avec les dates sélectionnées
async function ajouterAuPanierAvecDates(hebId, dateDebut, dateFin, prixTotal) {
  try {
    // 1. Ajouter l'hébergement
    const bodyHeb = new URLSearchParams({ action: 'set_hebergement', hebergement_id: hebId });
    await fetch('../backend/panier.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: bodyHeb.toString()
    });

    // 2. Définir les dates
    const bodyDates = new URLSearchParams({
      action: 'set_dates', date_debut: dateDebut, date_fin: dateFin
    });
    await fetch('../backend/panier.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: bodyDates.toString()
    });

    // 3. Toast + redirection
    const toast = document.createElement('div');
    toast.style.cssText = `
      position:fixed;bottom:24px;right:24px;z-index:9999;
      padding:14px 22px;border-radius:16px;font-weight:700;font-size:15px;
      background:#eafff4;color:#1e7e50;border:1.5px solid #b7f0d4;
      box-shadow:0 8px 24px rgba(0,0,0,.12);
    `;
    toast.textContent = `✅ Hébergement + dates ajoutés au panier !`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.remove(); window.location.href = 'panier.html'; }, 1200);

  } catch(err) {
    alert('Erreur lors de l\'ajout au panier. Réessayez.');
  }
}