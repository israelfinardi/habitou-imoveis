// Minimapa da home (550x550, ao lado do formulário de busca no hero):
// mostra os mesmos imóveis de "Destaques da semana" como pinos de preço,
// reaproveitando o estilo .price-pin já usado no mapa da busca
// (includes/header.php). Só existe em telas xl+ (ver classe hidden xl:block
// no index.php) — não inicializa o Leaflet fora dessa faixa pra não gastar
// memória/rede à toa em celular.
(function () {
  var mapEl = document.getElementById('home-minimap');
  if (!mapEl || typeof L === 'undefined') return;
  if (window.innerWidth < 1280) return;

  var pins = window.__HOME_MINIMAP_PINS || [];
  var DEFAULT_CENTER = [-14.235004, -51.92528];
  var DEFAULT_ZOOM = 4;

  var map = L.map('home-minimap', {
    scrollWheelZoom: true,
    zoomControl: false,
    attributionControl: false,
  });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);

  function esc(s) {
    return (s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var bounds = [];
  pins.forEach(function (p) {
    var icon = L.divIcon({
      className: 'price-pin',
      html: '<div class="price-pin-label">' + esc(p.label) + '</div>',
      iconSize: [0, 0],
      iconAnchor: [0, 0],
      popupAnchor: [0, -34],
    });
    var marker = L.marker([p.lat, p.lng], { icon: icon }).addTo(map);
    var popupHtml = '<a href="' + esc(p.href) + '" class="block w-56 overflow-hidden rounded-xl">'
      + (p.image ? '<img src="' + esc(p.image) + '" alt="" class="h-32 w-full object-cover">' : '')
      + '<div class="p-2.5"><p class="truncate text-xs font-semibold text-brand-text">' + esc(p.title) + '</p>'
      + '<p class="mt-0.5 text-sm font-bold text-brand-text">' + esc(p.price) + '</p></div></a>';
    marker.bindPopup(popupHtml, { closeButton: true, className: 'map-pin-popup' });
    bounds.push([p.lat, p.lng]);
  });

  if (bounds.length === 1) {
    map.setView(bounds[0], 13);
  } else if (bounds.length > 1) {
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
  } else {
    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
  }
})();
