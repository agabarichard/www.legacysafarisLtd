document.addEventListener('DOMContentLoaded', function() {
  // Load header
  fetch('partials/header.html')
    .then(response => response.text())
    .then(data => {
      document.getElementById('header-placeholder').innerHTML = data;
      // after header loads, re-attach hamburger event
      const hamburger = document.getElementById('hamburger');
      const navLinks = document.getElementById('navLinks');
      if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
          navLinks.classList.toggle('active');
        });
      }
      // highlight current page in nav
      const currentPage = window.location.pathname.split('/').pop();
      document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
          link.classList.add('active');
        }
      });
    })
    .catch(err => console.warn('Header load error:', err));

  // Load footer
  fetch('partials/footer.html')
    .then(response => response.text())
    .then(data => {
      document.getElementById('footer-placeholder').innerHTML = data;
      document.getElementById('currentYear').innerText = new Date().getFullYear();
    })
    .catch(err => console.warn('Footer load error:', err));
});

// ======================== PACKAGE DATABASE ========================
const packages = [
  {
    id: 1,
    name: "Great Migration Safari",
    price: "$2,450",
    priceNum: 2450,
    duration: 7,
    category: "premium",
    img: "../images/image10.jpg",
    shortDesc: "Witness the greatest wildlife spectacle on earth – wildebeest migration in Maasai Mara & Serengeti.",
    highlights: ["Hot air balloon safari", "River crossing viewing", "Luxury tented camps", "Maasai village visit"],
    itinerary: [
      "Day 1: Arrival in Nairobi – transfer to lodge",
      "Day 2: Fly to Maasai Mara – afternoon game drive",
      "Day 3: Full day Mara – river crossings (seasonal)",
      "Day 4: Balloon safari & bush breakfast",
      "Day 5: Drive to Serengeti (via Isebania)",
      "Day 6: Serengeti endless plains – big cats",
      "Day 7: Ngorongoro Crater tour – fly back"
    ],
    includes: ["All park fees", "Full board accommodation", "4x4 safari vehicle", "Professional guide", "Domestic flights"],
    excludes: ["International flights", "Visa fees", "Travel insurance", "Tips", "Drinks"],
    accommodation: "Mara Serena Lodge / Serengeti Kati Kati Tented Camp"
  },
  {
    id: 2,
    name: "Big Five Explorer",
    price: "$1,890",
    priceNum: 1890,
    duration: 5,
    category: "budget",
    img: "../images/image2.jpg",
    shortDesc: "Classic Kenya safari – Amboseli elephants, Lake Nakuru rhinos, and big cats.",
    highlights: ["Mount Kilimanjaro views", "Black rhino sanctuary", "Lion & leopard sightings", "Boat ride on Lake Naivasha"],
    itinerary: [
      "Day 1: Nairobi – Amboseli (elephant herds)",
      "Day 2: Amboseli full day – Kilimanjaro backdrop",
      "Day 3: Drive to Lake Nakuru – flamingos & rhinos",
      "Day 4: Lake Nakuru to Maasai Mara",
      "Day 5: Morning game drive – return to Nairobi"
    ],
    includes: ["Transport in safari van", "All meals (5 days)", "Park fees", "Driver guide"],
    excludes: ["Beverages", "Personal items", "Tips"],
    accommodation: "Amboseli Sopa Lodge / Nakuru Lion Hill Lodge"
  },
  {
    id: 3,
    name: "Gorilla & Golden Monkey",
    price: "$3,250",
    priceNum: 3250,
    duration: 4,
    category: "premium",
    img: "../images/image3.jpg",
    shortDesc: "Uganda’s Bwindi Impenetrable Forest – once‑in‑a‑lifetime gorilla trekking.",
    highlights: ["Mountain gorilla encounter", "Golden monkey trek", "Batwa cultural experience", "Scenic Kigezi highlands"],
    itinerary: [
      "Day 1: Fly to Kigali – drive to Bwindi",
      "Day 2: Gorilla trekking (permits included)",
      "Day 3: Golden monkey trek & community visit",
      "Day 4: Return to Kigali – optional city tour"
    ],
    includes: ["Gorilla permit ($700 value)", "Golden monkey permit", "Luxury lodge", "All transfers", "English guide"],
    excludes: ["Visa (Uganda/Rwanda)", "International flights", "Travel insurance"],
    accommodation: "Engagi Lodge / Bwindi Jungle Lodge"
  },
  {
    id: 4,
    name: "Selous Fly-in Safari",
    price: "$3,990",
    priceNum: 3990,
    duration: 6,
    category: "premium",
    img: "../images/image4.jpg",
    shortDesc: "Remote Tanzania – boat safaris, walking safaris, and exclusive camps.",
    highlights: ["Boat safari on Rufiji River", "Walking safari with rangers", "Wild dogs & elephants", "Fly-in convenience"],
    itinerary: [
      "Day 1: Flight from Dar es Salaam to Selous",
      "Day 2: Morning boat safari – afternoon game drive",
      "Day 3: Full day walking safari & bush picnic",
      "Day 4: Explore remote lakes by vehicle",
      "Day 5: Cultural visit to local village",
      "Day 6: Morning game drive – fly back"
    ],
    includes: ["Return flights from Dar", "All activities", "Full board", "Professional naturalist guide"],
    excludes: ["International flights", "Visas", "Alcoholic drinks", "Gratuities"],
    accommodation: "Selous Riverside Camp / Rufiji River Lodge"
  },
  {
    id: 5,
    name: "Kenya Family Safari",
    price: "$1,450",
    priceNum: 1450,
    duration: 4,
    category: "budget",
    img: "../images/image5.jpg",
    shortDesc: "Kid‑friendly adventure – short drives, educational activities, and fun.",
    highlights: ["Animal spotting games", "Visit to Giraffe Centre", "Bush school for kids", "Family tented camp"],
    itinerary: [
      "Day 1: Nairobi – Giraffe Centre & Elephant Orphanage",
      "Day 2: Drive to Amboseli – afternoon game drive",
      "Day 3: Full day Amboseli – junior ranger program",
      "Day 4: Morning drive – return to Nairobi"
    ],
    includes: ["Family tent", "All meals", "Park fees", "Child‑friendly guide"],
    excludes: ["International flights", "Travel insurance", "Souvenirs"],
    accommodation: "Kibo Safari Camp (family units)"
  }
];

// ======================== RENDER CARDS ========================
function renderPackages(filter = "all") {
  const container = document.getElementById('tours-container');
  if (!container) return;

  let filtered = [...packages];
  if (filter === "budget") filtered = packages.filter(p => p.priceNum < 2000);
  if (filter === "premium") filtered = packages.filter(p => p.priceNum >= 2000);
  if (filter === "short") filtered = packages.filter(p => p.duration <= 5);
  if (filter === "long") filtered = packages.filter(p => p.duration > 5);

  container.innerHTML = filtered.map(pkg => `
    <div class="tour-card" data-id="${pkg.id}">
      <div class="tour-img"><img src="${pkg.img}" alt="${pkg.name}"></div>
      <div class="tour-info">
        <h3>${pkg.name}</h3>
        <div class="tour-price">from ${pkg.price}</div>
        <p>${pkg.shortDesc}</p>
        <p><i class="fas fa-calendar-alt"></i> ${pkg.duration} days &nbsp;|&nbsp; <i class="fas fa-paw"></i> ${pkg.highlights[0].slice(0, 30)}...</p>
        <div class="tour-actions">
          <button class="btn-outline book-now-modal" data-id="${pkg.id}">Request Booking</button>
          <button class="btn-small view-details" data-id="${pkg.id}">View Details</button>
        </div>
      </div>
    </div>
  `).join('');

  // Attach booking modal events
  document.querySelectorAll('.book-now-modal').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = parseInt(btn.getAttribute('data-id'));
      const pkg = packages.find(p => p.id === id);
      if (pkg) openBookingModal(pkg);
    });
  });

  // Attach detail modal events
  document.querySelectorAll('.view-details').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = parseInt(btn.getAttribute('data-id'));
      const pkg = packages.find(p => p.id === id);
      if (pkg) openDetailModal(pkg);
    });
  });
}

// ======================== QUICK BOOKING MODAL ========================
const bookingModal = document.getElementById('bookingModal');
const modalTourNameSpan = document.getElementById('modalTourName');
let currentPackage = null;

function openBookingModal(pkg) {
  currentPackage = pkg;
  modalTourNameSpan.innerText = `${pkg.name} – ${pkg.price} per person`;
  bookingModal.classList.add('active');
}

function closeBookingModal() {
  bookingModal.classList.remove('active');
  document.getElementById('modalBookingForm').reset();
  document.getElementById('modalFeedback').innerHTML = '';
}

// Close booking modal via X or background click
document.querySelector('#bookingModal .close-modal')?.addEventListener('click', closeBookingModal);
bookingModal?.addEventListener('click', (e) => { if (e.target === bookingModal) closeBookingModal(); });

// Booking form submission
const modalForm = document.getElementById('modalBookingForm');
if (modalForm) {
  modalForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('modalName').value.trim();
    const email = document.getElementById('modalEmail').value.trim();
    const travelDate = document.getElementById('modalTravelDate').value.trim();
    const feedbackDiv = document.getElementById('modalFeedback');

    if (!name || !email || !travelDate) {
      feedbackDiv.innerHTML = '<span style="color:#E76F51;">Please fill all required fields.</span>';
      return;
    }
    if (!/^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/.test(email)) {
      feedbackDiv.innerHTML = '<span style="color:#E76F51;">Enter a valid email.</span>';
      return;
    }
    feedbackDiv.innerHTML = `<span style="color:#2F6B3E;">✅ Thank you ${name}! We’ll contact you about ${currentPackage.name} within 24h.</span>`;
    modalForm.reset();
    setTimeout(closeBookingModal, 2500);
  });
}

// ======================== DETAILED PACKAGE MODAL ========================
const detailModal = document.getElementById('detailModal');
const detailContent = document.getElementById('detailContent');
const closeDetailBtn = document.getElementById('closeDetailModal');

function openDetailModal(pkg) {
  detailContent.innerHTML = `
    <h2 style="color: var(--acacia-green);">${pkg.name}</h2>
    <p><strong>Price:</strong> ${pkg.price} per person (sharing)</p>
    <p><strong>Duration:</strong> ${pkg.duration} days</p>
    <img src="${pkg.img}" alt="${pkg.name}" style="width:100%; border-radius: 20px; margin: 15px 0;">
    
    <h3><i class="fas fa-star"></i> Highlights</h3>
    <ul>${pkg.highlights.map(h => `<li>${h}</li>`).join('')}</ul>
    
    <h3><i class="fas fa-map-signs"></i> Itinerary</h3>
    <ul>${pkg.itinerary.map(day => `<li>${day}</li>`).join('')}</ul>
    
    <h3><i class="fas fa-check-circle"></i> Inclusions</h3>
    <ul>${pkg.includes.map(i => `<li>✓ ${i}</li>`).join('')}</ul>
    
    <h3><i class="fas fa-times-circle"></i> Exclusions</h3>
    <ul>${pkg.excludes.map(e => `<li>✗ ${e}</li>`).join('')}</ul>
    
    <h3><i class="fas fa-bed"></i> Accommodation</h3>
    <p>${pkg.accommodation}</p>
    
    <button class="btn-primary book-from-detail" style="margin-top: 1rem;">Book This Package</button>
  `;
  detailModal.classList.add('active');

  // Attach book button inside detail modal
  const bookBtn = detailContent.querySelector('.book-from-detail');
  if (bookBtn) {
    bookBtn.addEventListener('click', () => {
      detailModal.classList.remove('active');
      openBookingModal(pkg);
    });
  }
}

function closeDetailModal() {
  detailModal.classList.remove('active');
  detailContent.innerHTML = '';
}

closeDetailBtn?.addEventListener('click', closeDetailModal);
detailModal?.addEventListener('click', (e) => { if (e.target === detailModal) closeDetailModal(); });

// ======================== FILTER BUTTONS ========================
function initFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filterValue = btn.getAttribute('data-filter');
      renderPackages(filterValue);
    });
  });
}

// ======================== INITIAL PAGE LOAD ========================
function initPage() {
  renderPackages('all');
  initFilters();
  // (Optional: keep any existing gallery/contact form logic if present elsewhere)
}

// Wait for header & footer includes to be loaded (include.js ensures DOMContentLoaded)
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => setTimeout(initPage, 100));
} else {
  setTimeout(initPage, 100);
}