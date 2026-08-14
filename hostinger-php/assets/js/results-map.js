// Mapa interativo lateral da página de listagem/filtros.
(function () {
  var pins = window.__RESULTS_MAP_PINS || [];
  var mapEl = document.getElementById('results-map');
  if (!mapEl || !window.L) return;

  // Centro padrão: Santa Catarina, usado quando não há nenhum pin com
  // coordenadas (ex.: filtros sem resultados).
  var DEFAULT_CENTER = [-27.24, -50.4];
  var DEFAULT_ZOOM = 7;

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

  function esc(s) {
    return (s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // Card do popup no mesmo estilo do card da lista: carrossel de fotos
  // (setas no hover + deslize por toque) com favoritar sobreposto, e só o
  // resto do card (fora dos botões) navega para o imóvel — antes o popup
  // inteiro era um único <a>, o que "roubava" o clique de qualquer
  // interação dentro da foto.
  function buildPopupHtml(p) {
    var images = p.images || [];
    var media = images.length
      ? '<div class="js-carousel-track scrollbar-none flex h-44 w-full snap-x snap-mandatory overflow-x-auto scroll-smooth">' +
        images.map(function (src) { return '<img src="' + esc(src) + '" alt="" class="h-44 w-full shrink-0 snap-center object-cover">'; }).join('') +
        '</div>'
      : '<div class="flex h-44 w-full items-center justify-center bg-brand-bg-subtle text-xs text-brand-text-secondary">Sem foto</div>';
    var arrows = images.length > 1
      ? '<button type="button" class="js-carousel-prev absolute left-1.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Foto anterior"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>' +
        '<button type="button" class="js-carousel-next absolute right-1.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Próxima foto"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>' +
        '<div class="js-carousel-dots pointer-events-none absolute bottom-1.5 left-1/2 flex -translate-x-1/2 gap-1">' +
        images.map(function (_, i) { return '<span class="h-1.5 w-1.5 rounded-full ' + (i === 0 ? 'bg-white' : 'bg-white/50') + '"></span>'; }).join('') +
        '</div>'
      : '';
    return (
      '<div class="w-80 overflow-hidden rounded-xl">' +
      '<div class="js-carousel group relative">' +
      media + arrows +
      '<button type="button" class="js-favorite-btn js-favorite-overlay ' + (p.isFavorite ? 'is-favorite' : '') + ' absolute left-2 top-2 flex h-7 w-7 items-center justify-center" data-property-id="' + p.id + '" aria-label="Favoritar">' +
      '<svg width="20" height="20" viewBox="0 0 24 24" fill="' + (p.isFavorite ? '#C1502E' : 'rgba(0,0,0,.5)') + '" stroke="#fff" stroke-width="1.5" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,.3))"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>' +
      '</button>' +
      '</div>' +
      '<a href="' + esc(p.href) + '" class="block p-2.5">' +
      '<p class="truncate text-sm font-semibold text-brand-text">' + esc(p.title) + '</p>' +
      (p.neighborhood ? '<p class="truncate text-xs text-brand-text-secondary">' + esc(p.neighborhood) + '</p>' : '') +
      (p.meta ? '<p class="truncate text-xs text-brand-text-secondary">' + esc(p.meta) + '</p>' : '') +
      '<p class="mt-1 text-sm font-bold text-brand-text">' + esc(p.price) + '</p>' +
      '</a></div>'
    );
  }

  pins.forEach(function (p) {
    var icon = L.divIcon({
      className: 'price-pin',
      html: '<div class="price-pin-label">' + p.label + '</div>',
      iconSize: [0, 0],
      // O rótulo é posicionado por CSS (translate(-50%,-100%)), então o
      // ponto [0,0] do ícone cai exatamente na base do balão (o "pino" no
      // mapa). O popup precisa de um popupAnchor deslocado para cima dessa
      // altura + uma folga, senão o card do popup nasce por cima do próprio
      // pino em vez de flutuar acima dele.
      iconAnchor: [0, 0],
      popupAnchor: [0, -34],
    });
    var marker = L.marker([p.lat, p.lng], { icon: icon, riseOnHover: true });
    marker.bindPopup(buildPopupHtml(p), { closeButton: true, minWidth: 320, maxWidth: 320, className: 'map-pin-popup', autoPanPadding: [40, 40] });
    marker.on('popupopen', function () { setActive(p.id, true); });
    marker.on('popupclose', function () { setActive(p.id, false); });
    marker.addTo(map);
    markers[p.id] = marker;
    bounds.push([p.lat, p.lng]);
  });

  if (bounds.length === 1) {
    map.setView(bounds[0], 14);
  } else if (bounds.length > 1) {
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
  } else {
    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
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
