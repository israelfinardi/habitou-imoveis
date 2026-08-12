// Mapa interativo lateral da página de listagem/filtros.
(function () {
  var pins = window.__RESULTS_MAP_PINS || [];
  var mapEl = document.getElementById('results-map');
  if (!mapEl || !window.L || !pins.length) return;

  var map = L.map('results-map', { scrollWheelZoom: true, zoomControl: true });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(map);

  var markers = {};
  var bounds = [];

  function setActive(id, active) {
    var marker = markers[id];
    if (!marker) return;
    var el = marker.getElement();
    if (!el) return;
    var label = el.querySelector('.price-pin-label');
    if (label) label.classList.toggle('active', active);
  }

  pins.forEach(function (p) {
    var icon = L.divIcon({
      className: 'price-pin',
      html: '<div class="price-pin-label">' + p.label + '</div>',
      iconSize: [0, 0],
      iconAnchor: [0, 0],
    });
    var marker = L.marker([p.lat, p.lng], { icon: icon, riseOnHover: true });
    var img = p.image
      ? '<img src="' + p.image + '" alt="" class="h-24 w-full object-cover">'
      : '<div class="flex h-24 w-full items-center justify-center bg-brand-bg-subtle text-xs text-brand-text-secondary">Sem foto</div>';
    var popupHtml =
      '<a href="' + p.href + '" class="block w-48 overflow-hidden rounded-lg">' +
      img +
      '<div class="p-2">' +
      '<p class="text-sm font-bold text-brand-text">' + p.label + '</p>' +
      '<p class="truncate text-xs text-brand-text">' + p.title + '</p>' +
      '<p class="truncate text-[11px] text-brand-text-secondary">' + p.meta + '</p>' +
      '</div></a>';
    marker.bindPopup(popupHtml, { closeButton: true, minWidth: 190, maxWidth: 200, className: 'map-pin-popup' });
    marker.on('popupopen', function () { setActive(p.id, true); });
    marker.on('popupclose', function () { setActive(p.id, false); });
    marker.addTo(map);
    markers[p.id] = marker;
    bounds.push([p.lat, p.lng]);
  });

  if (bounds.length === 1) {
    map.setView(bounds[0], 14);
  } else {
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
  }

  var grid = document.getElementById('results-grid');
  if (grid) {
    grid.addEventListener('mouseover', function (e) {
      var card = e.target.closest('[data-property-id]');
      if (card) setActive(parseInt(card.dataset.propertyId, 10), true);
    });
    grid.addEventListener('mouseout', function (e) {
      var card = e.target.closest('[data-property-id]');
      if (card) setActive(parseInt(card.dataset.propertyId, 10), false);
    });
  }

  // --- Alternar mapa em tela cheia no celular -------------------------------
  var wrap = document.getElementById('results-map-wrap');
  var toggleBtn = document.getElementById('map-toggle-btn');
  var closeBtn = document.getElementById('map-close-btn');

  function openMobileMap() {
    if (!wrap) return;
    wrap.classList.remove('hidden');
    wrap.style.position = 'fixed';
    wrap.style.inset = '0';
    wrap.style.zIndex = '50';
    wrap.style.borderRadius = '0';
    wrap.style.height = '100%';
    if (closeBtn) closeBtn.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(function () { map.invalidateSize(); }, 50);
  }

  function closeMobileMap() {
    if (!wrap) return;
    wrap.classList.add('hidden');
    wrap.style.position = '';
    wrap.style.inset = '';
    wrap.style.zIndex = '';
    wrap.style.borderRadius = '';
    wrap.style.height = '70vh';
    if (closeBtn) closeBtn.classList.add('hidden');
    document.body.style.overflow = '';
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openMobileMap);
  if (closeBtn) closeBtn.addEventListener('click', closeMobileMap);

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024 && wrap && wrap.style.position === 'fixed') {
      closeMobileMap();
    }
  });
})();
