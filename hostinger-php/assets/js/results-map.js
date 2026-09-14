// Mapa interativo lateral da página de listagem/filtros.
(function () {
  var pins = window.__RESULTS_MAP_PINS || [];
  var mapEl = document.getElementById('results-map');
  if (!mapEl || !window.L) return;

  // Centro padrão: Brasil inteiro, usado quando não há nenhum pin com
  // coordenadas (ex.: filtros sem resultados).
  var DEFAULT_CENTER = [-14.235004, -51.92528];
  var DEFAULT_ZOOM = 4;

  var map = L.map('results-map', { scrollWheelZoom: true, zoomControl: true });
  // Base neutra (cinza claro, sem vegetação/rodovias coloridas), via Esri —
  // gratuita e sem chave de API (a CARTO passou a exigir chave, o que
  // quebrava o mapa em produção com o aviso "API KEY REQUIRED").
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, maxNativeZoom: 16,
    attribution: '&copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
  }).addTo(map);
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Reference/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, maxNativeZoom: 16,
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
      '<div class="w-80 overflow-hidden rounded-[20px]">' +
      '<div class="js-carousel group relative">' +
      media + arrows +
      // Coração + fechar juntos no canto superior direito, com espaço entre
      // os dois (o botão de fechar do Leaflet já fica em top:10/right:10 —
      // o coração fica logo à esquerda dele) — mesmo agrupamento do popup
      // de imóvel da Airbnb, em vez de cada ícone num canto diferente.
      '<button type="button" class="js-favorite-btn js-favorite-overlay ' + (p.isFavorite ? 'is-favorite' : '') + ' absolute right-[46px] top-2.5 flex h-7 w-7 items-center justify-center" data-property-id="' + p.id + '" aria-label="Favoritar">' +
      '<svg width="20" height="20" viewBox="0 0 24 24" fill="' + (p.isFavorite ? '#C1502E' : 'rgba(0,0,0,.5)') + '" stroke="#fff" stroke-width="1.5" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,.3))"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>' +
      '</button>' +
      '</div>' +
      '<a href="' + esc(p.href) + '" class="block p-4">' +
      '<p class="truncate text-[15px] font-semibold leading-snug text-brand-text">' + esc(p.title) + '</p>' +
      (p.neighborhood ? '<p class="mt-0.5 truncate text-sm text-brand-text-secondary">' + esc(p.neighborhood) + '</p>' : '') +
      (p.meta ? '<p class="mt-0.5 truncate text-sm text-brand-text-secondary">' + esc(p.meta) + '</p>' : '') +
      '<p class="mt-2 text-base font-bold text-brand-text">' + esc(p.price) + '</p>' +
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

  // --- Celular: mapa fixo em tela cheia + lista em bandeja arrastável -------
  // (estilo Airbnb) por cima dele, com o texto "N imóveis nesta área"
  // atualizado conforme o usuário arrasta/dá zoom no mapa.
  var wrap = document.getElementById('results-map-wrap');
  var sheet = document.getElementById('results-list-col');
  var handleWrap = document.getElementById('sheet-handle-wrap');
  var countText = document.getElementById('sheet-count-text');
  var isMobileLayout = false;

  // Estados da bandeja: recolhida (só o cabeçalho + topo do 1º card),
  // metade da tela, e quase tela cheia (cobrindo o mapa).
  var SHEET_STATES = { collapsed: 132, half: null, full: null };

  function headerHeight() {
    var header = document.querySelector('header');
    return header ? header.offsetHeight : 0;
  }

  function computeStates() {
    var vh = window.innerHeight;
    SHEET_STATES.half = Math.round(vh * 0.5);
    SHEET_STATES.full = vh - headerHeight() - 12;
  }

  function applySheetHeight(px, animate) {
    if (!sheet) return;
    sheet.style.transition = animate ? 'height .22s ease' : 'none';
    sheet.style.height = px + 'px';
  }

  function setState(state) {
    applySheetHeight(SHEET_STATES[state], true);
  }

  function enableMobileLayout() {
    if (isMobileLayout || !wrap || !sheet) return;
    isMobileLayout = true;
    computeStates();

    wrap.style.position = 'fixed';
    wrap.style.top = headerHeight() + 'px';
    wrap.style.left = '0';
    wrap.style.right = '0';
    wrap.style.bottom = '0';
    wrap.style.zIndex = '10';
    wrap.style.borderRadius = '0';
    wrap.style.border = 'none';
    wrap.style.height = 'auto';

    sheet.style.position = 'fixed';
    sheet.style.left = '0';
    sheet.style.right = '0';
    sheet.style.bottom = '0';
    sheet.style.zIndex = '20';
    sheet.style.background = '#fff';
    sheet.style.borderTopLeftRadius = '20px';
    sheet.style.borderTopRightRadius = '20px';
    sheet.style.boxShadow = '0 -8px 30px rgba(0,0,0,.15)';
    sheet.style.overflowY = 'auto';
    sheet.style.overscrollBehavior = 'contain';
    sheet.style.padding = '0 16px 16px';

    document.body.style.overflow = 'hidden';
    // A barra inferior (includes/bottom_nav.php) some por completo aqui —
    // ver assets/js/bottom-nav.js — pra dar a tela toda pro mapa, igual ao
    // comportamento do Airbnb ao entrar no modo de mapa no celular.
    document.body.classList.add('hb-map-fullscreen');
    setState('half');
    setTimeout(function () { map.invalidateSize(); }, 60);
  }

  function disableMobileLayout() {
    if (!isMobileLayout || !wrap || !sheet) return;
    isMobileLayout = false;
    ['position', 'top', 'left', 'right', 'bottom', 'zIndex', 'borderRadius', 'border', 'height'].forEach(function (k) { wrap.style[k] = ''; });
    ['position', 'left', 'right', 'bottom', 'zIndex', 'background', 'borderTopLeftRadius', 'borderTopRightRadius', 'boxShadow', 'overflowY', 'overscrollBehavior', 'padding', 'transition', 'height'].forEach(function (k) { sheet.style[k] = ''; });
    grid && Array.prototype.forEach.call(grid.children, function (card) { card.style.display = ''; });
    document.body.style.overflow = '';
    document.body.classList.remove('hb-map-fullscreen');
    setTimeout(function () { map.invalidateSize(); }, 60);
  }

  // --- Arrastar a bandeja pelo cabeçalho (Pointer Events cobrem toque) ------
  var dragStartY = null;
  var dragStartHeight = 0;

  function onDragStart(e) {
    if (!isMobileLayout) return;
    dragStartY = e.clientY;
    dragStartHeight = sheet.getBoundingClientRect().height;
    sheet.style.transition = 'none';
    document.addEventListener('pointermove', onDragMove);
    document.addEventListener('pointerup', onDragEnd);
  }

  function onDragMove(e) {
    if (dragStartY === null) return;
    if (e.cancelable) e.preventDefault();
    var delta = dragStartY - e.clientY;
    var next = Math.min(SHEET_STATES.full, Math.max(80, dragStartHeight + delta));
    applySheetHeight(next, false);
  }

  function onDragEnd() {
    if (dragStartY === null) return;
    dragStartY = null;
    document.removeEventListener('pointermove', onDragMove);
    document.removeEventListener('pointerup', onDragEnd);

    var h = sheet.getBoundingClientRect().height;
    var candidates = [['collapsed', SHEET_STATES.collapsed], ['half', SHEET_STATES.half], ['full', SHEET_STATES.full]];
    candidates.sort(function (a, b) { return Math.abs(h - a[1]) - Math.abs(h - b[1]); });
    setState(candidates[0][0]);
  }

  if (handleWrap) {
    handleWrap.style.touchAction = 'none';
    handleWrap.addEventListener('pointerdown', onDragStart);
  }

  // --- Só mostra, na lista, os imóveis dentro da área visível do mapa ------
  // (tanto na bandeja do celular quanto na lista lateral do desktop — ao dar
  // zoom/arrastar o mapa, a lista acompanha a área visível na tela).
  function updateVisibleByBounds() {
    if (!grid) return;
    var b = map.getBounds();
    var visibleCount = 0;
    pins.forEach(function (p) {
      var card = grid.querySelector('[data-property-id="' + p.id + '"]');
      if (!card) return;
      var within = b.contains([p.lat, p.lng]);
      card.style.display = within ? '' : 'none';
      if (within) visibleCount++;
    });
    if (countText && isMobileLayout) {
      countText.textContent = visibleCount + (visibleCount === 1 ? ' imóvel nesta área' : ' imóveis nesta área');
    }
  }

  map.on('moveend', updateVisibleByBounds);

  function syncLayout() {
    if (window.innerWidth < 1024) {
      enableMobileLayout();
    } else {
      disableMobileLayout();
    }
    updateVisibleByBounds();
  }

  syncLayout();
  window.addEventListener('resize', syncLayout);
})();
