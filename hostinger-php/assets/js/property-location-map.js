// Mapa interativo do formulário de cadastro/edição de imóvel: clicar (ou
// arrastar o pino) localiza o ponto e usa geocodificação reversa (Nominatim/
// OpenStreetMap) para preencher automaticamente cidade, bairro, rua e CEP.
(function () {
  var mapEl = document.getElementById('property-map');
  if (!mapEl || typeof L === 'undefined') return;

  var latInput = document.getElementById('js-lat-input');
  var lngInput = document.getElementById('js-lng-input');
  var cidadeInput = document.getElementById('js-cidade-input');
  var bairroInput = document.getElementById('js-bairro-input');
  var streetInput = document.getElementById('js-street-input');
  var zipInput = document.getElementById('js-zip-input');
  var statusEl = document.getElementById('property-map-status');
  var searchInput = document.getElementById('property-map-search');
  var searchBtn = document.getElementById('property-map-search-btn');

  var STATE_NAME_TO_UF = {
    'Acre': 'AC', 'Alagoas': 'AL', 'Amapá': 'AP', 'Amazonas': 'AM', 'Bahia': 'BA', 'Ceará': 'CE',
    'Distrito Federal': 'DF', 'Espírito Santo': 'ES', 'Goiás': 'GO', 'Maranhão': 'MA', 'Mato Grosso': 'MT',
    'Mato Grosso do Sul': 'MS', 'Minas Gerais': 'MG', 'Pará': 'PA', 'Paraíba': 'PB', 'Paraná': 'PR',
    'Pernambuco': 'PE', 'Piauí': 'PI', 'Rio de Janeiro': 'RJ', 'Rio Grande do Norte': 'RN', 'Rio Grande do Sul': 'RS',
    'Rondônia': 'RO', 'Roraima': 'RR', 'Santa Catarina': 'SC', 'São Paulo': 'SP', 'Sergipe': 'SE', 'Tocantins': 'TO',
  };

  var hasInitial = !!(latInput.value && lngInput.value);
  var initialLat = hasInitial ? parseFloat(latInput.value) : -14.235004;
  var initialLng = hasInitial ? parseFloat(lngInput.value) : -51.92528;

  var map = L.map('property-map').setView([initialLat, initialLng], hasInitial ? 16 : 4);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  var marker = null;
  function placeMarker(lat, lng) {
    if (marker) {
      marker.setLatLng([lat, lng]);
    } else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function () {
        var pos = marker.getLatLng();
        handlePin(pos.lat, pos.lng, false);
      });
    }
  }
  if (hasInitial) placeMarker(initialLat, initialLng);

  function resolveUf(address) {
    var iso = address['ISO3166-2-lvl4'];
    if (iso && iso.indexOf('BR-') === 0) return iso.split('-')[1];
    return STATE_NAME_TO_UF[address.state] || null;
  }

  function handlePin(lat, lng, recenter) {
    placeMarker(lat, lng);
    if (recenter) map.setView([lat, lng], 16);
    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);
    if (statusEl) statusEl.textContent = 'Buscando endereço...';

    fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&addressdetails=1&accept-language=pt-BR')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var address = data.address || {};
        var cityName = address.city || address.town || address.village || address.municipality || address.county;
        var uf = resolveUf(address);
        if (cityName && uf && cidadeInput) {
          cidadeInput.value = cityName + ' (' + uf + ')';
        }
        var bairro = address.suburb || address.neighbourhood || address.quarter || address.city_district;
        if (bairro && bairroInput) bairroInput.value = bairro;
        if (address.road && streetInput) streetInput.value = address.road;
        if (address.postcode && zipInput) zipInput.value = address.postcode;

        if (statusEl) {
          statusEl.textContent = data.display_name
            ? 'Endereço identificado: ' + data.display_name
            : 'Ponto marcado, mas não foi possível identificar o endereço — confira os campos manualmente.';
        }
      })
      .catch(function () {
        if (statusEl) statusEl.textContent = 'Ponto marcado. Não foi possível consultar o endereço automaticamente — preencha os campos manualmente.';
      });
  }

  map.on('click', function (e) {
    handlePin(e.latlng.lat, e.latlng.lng, false);
  });

  function searchAddress() {
    var q = (searchInput.value || '').trim();
    if (!q) return;
    if (statusEl) statusEl.textContent = 'Procurando "' + q + '"...';
    fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&q=' + encodeURIComponent(q) + '&countrycodes=br&addressdetails=1&limit=1&accept-language=pt-BR')
      .then(function (r) { return r.json(); })
      .then(function (results) {
        if (!results || !results.length) {
          if (statusEl) statusEl.textContent = 'Endereço não encontrado. Tente digitar de outra forma ou clique diretamente no mapa.';
          return;
        }
        handlePin(parseFloat(results[0].lat), parseFloat(results[0].lon), true);
      })
      .catch(function () {
        if (statusEl) statusEl.textContent = 'Não foi possível buscar esse endereço agora. Clique diretamente no mapa.';
      });
  }

  if (searchBtn) searchBtn.addEventListener('click', searchAddress);
  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        searchAddress();
      }
    });
  }
})();
