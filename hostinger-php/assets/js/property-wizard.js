// Motor do wizard de "Anunciar imóvel" (includes/property_wizard.php):
// navega entre as etapas, valida cada uma antes de liberar "Avançar", e
// fala com o servidor só nos pontos que fazem sentido — cria o rascunho
// ao sair de "comodidades" (checkpoint com todos os dados essenciais já
// coletados), depois atualiza campo a campo nas etapas seguintes, e
// publica no final. Fotos usam os endpoints que já existiam pro fluxo de
// edição (upload_foto.php / photo_action.php), só que ligados ao
// rascunho criado nesse fluxo em vez de a um imóvel já existente.
(function () {
  var wizard = document.getElementById('wizard');
  if (!wizard) return;
  var csrf = wizard.dataset.csrf;

  var STEPS = ['endereco', 'tipo', 'transacao', 'detalhes', 'mapa', 'comodidades', 'fotos', 'titulo', 'descricao', 'preco', 'contato', 'revisar'];

  var state = {
    propertyType: '', listingType: 'SALE',
    bedrooms: 0, suites: 0, bathrooms: 0, parkingSpaces: 0,
    totalArea: '', builtArea: '', features: [],
    cidade: '', bairro: '', street: '', number: '', complement: '', zipCode: '', latitude: '', longitude: '',
    title: '', description: '', priceSale: '', priceRent: '', condoFee: '', iptu: '',
    contactPhone: '', contactWhatsapp: '', contactEmail: '',
  };
  var propertyId = null;
  var photos = [];
  var stepIndex = 0;
  var busy = false;
  var mapPollTimer = null;

  var stepEls = {};
  document.querySelectorAll('.wizard-step').forEach(function (el) { stepEls[el.dataset.step] = el; });

  var backBtn = document.getElementById('wizard-back');
  var nextBtn = document.getElementById('wizard-next');
  var errorEl = document.getElementById('wizard-error');
  var labelEl = document.getElementById('wizard-step-label');
  var titleCountEl = document.getElementById('wz-title-count');

  function currentKey() { return STEPS[stepIndex]; }
  function showError(msg) { errorEl.textContent = msg; errorEl.classList.remove('hidden'); }
  function clearError() { errorEl.classList.add('hidden'); }

  function esc(s) {
    return (s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function formatBRL(v) {
    var n = Number(v);
    return isNaN(n) ? '' : 'R$ ' + n.toLocaleString('pt-BR');
  }

  // --- Sincronização dos campos de endereço/mapa (preenchidos por busca,
  // datalist ou clique no mapa, sem disparar eventos que o listener
  // genérico de .wz-field capturaria) -------------------------------------
  function syncAddressFieldsFromDOM() {
    var byId = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
    state.cidade = byId('js-cidade-input');
    state.bairro = byId('js-bairro-input');
    state.street = byId('js-street-input');
    state.number = byId('js-number-input');
    state.complement = byId('js-complement-input');
    state.zipCode = byId('js-zip-input');
    state.latitude = byId('js-lat-input');
    state.longitude = byId('js-lng-input');
  }

  // --- Validação por etapa -------------------------------------------------
  function isValid(key) {
    switch (key) {
      case 'endereco': return !!(state.cidade && state.bairro);
      case 'tipo': return !!state.propertyType;
      case 'transacao': return !!state.listingType;
      case 'detalhes': return true;
      case 'mapa': return !!(state.latitude && state.longitude);
      case 'comodidades': return true;
      case 'fotos': return photos.length >= 1;
      case 'titulo': return state.title.trim().length >= 10;
      case 'descricao': return true;
      case 'preco':
        if (state.listingType === 'RENT') return !!state.priceRent && Number(state.priceRent) > 0;
        return !!state.priceSale && Number(state.priceSale) > 0;
      case 'contato': return true;
      case 'revisar': return true;
      default: return true;
    }
  }

  function updateNextEnabled() {
    syncAddressFieldsFromDOM();
    nextBtn.disabled = !isValid(currentKey());
  }

  // --- Render ---------------------------------------------------------------
  function render() {
    var key = currentKey();
    Object.keys(stepEls).forEach(function (k) { stepEls[k].classList.toggle('is-active', k === key); });
    backBtn.disabled = stepIndex === 0;
    labelEl.textContent = (stepIndex + 1) + ' de ' + STEPS.length;
    nextBtn.textContent = key === 'revisar' ? 'Publicar' : 'Avançar';

    clearInterval(mapPollTimer);
    if (key === 'mapa') {
      if (window.__propertyMap) {
        setTimeout(function () {
          window.__propertyMap.invalidateSize();
          if (state.latitude && state.longitude) {
            window.__propertyMap.setView([parseFloat(state.latitude), parseFloat(state.longitude)], 16);
          }
        }, 50);
      }
      // O clique/arraste do pino (assets/js/property-location-map.js) só
      // atribui .value nos inputs de lat/lng e endereço — atribuição
      // programática não dispara 'input'/'change', então o botão Avançar
      // não reavaliaria sozinho. Consultamos o DOM periodicamente enquanto
      // essa etapa está visível.
      mapPollTimer = setInterval(updateNextEnabled, 400);
    }
    if (key === 'preco') {
      document.getElementById('wz-price-sale-wrap').classList.toggle('hidden', state.listingType !== 'SALE');
      document.getElementById('wz-price-rent-wrap').classList.toggle('hidden', state.listingType !== 'RENT');
    }
    if (key === 'fotos') {
      document.getElementById('wz-photo-status').textContent = photos.length + ' foto(s) adicionada(s) — mínimo recomendado: 5.';
    }
    if (key === 'revisar') {
      buildReviewCard();
    }
    updateNextEnabled();
    wizard.querySelector('.min-h-0').scrollTop = 0;
  }

  function buildReviewCard() {
    var card = document.getElementById('wz-review-card');
    var cover = photos[0] ? photos[0].url : '';
    var priceLabel = state.listingType === 'RENT'
      ? (state.priceRent ? formatBRL(state.priceRent) + '/mês' : 'Preço a combinar')
      : (state.priceSale ? formatBRL(state.priceSale) : 'Preço a combinar');
    var meta = [];
    if (Number(state.bedrooms) > 0) meta.push(state.bedrooms + ' quartos');
    if (Number(state.bathrooms) > 0) meta.push(state.bathrooms + ' banheiros');
    if (Number(state.parkingSpaces) > 0) meta.push(state.parkingSpaces + ' vagas');
    card.innerHTML =
      (cover ? '<img src="' + esc(cover) + '" class="h-56 w-full object-cover" alt="">' : '<div class="h-56 w-full bg-brand-bg-subtle"></div>') +
      '<div class="p-4">' +
      '<p class="text-base font-bold">' + esc(state.title || 'Sem título') + '</p>' +
      '<p class="mt-1 text-sm text-brand-text-secondary">' + esc(state.bairro) + (state.bairro && state.cidade ? ', ' : '') + esc(state.cidade.replace(/\s*\([A-Za-z]{2}\)\s*$/, '')) + '</p>' +
      (meta.length ? '<p class="mt-1 text-sm text-brand-text-secondary">' + esc(meta.join(' · ')) + '</p>' : '') +
      '<p class="mt-2 text-base font-bold">' + esc(priceLabel) + '</p>' +
      '</div>';
  }

  // --- Campos genéricos (.wz-field / data-field) -----------------------------
  wizard.addEventListener('input', onFieldChange);
  wizard.addEventListener('change', onFieldChange);

  function onFieldChange(e) {
    var el = e.target;
    if (!el.classList || !el.classList.contains('wz-field')) return;
    var field = el.dataset.field;
    if (!field) return;
    if (el.type === 'checkbox' && field === 'features') {
      var idx = state.features.indexOf(el.value);
      if (el.checked && idx === -1) state.features.push(el.value);
      if (!el.checked && idx !== -1) state.features.splice(idx, 1);
    } else {
      state[field] = el.value;
    }
    if (field === 'title' && titleCountEl) titleCountEl.textContent = el.value.length;
    clearError();
    updateNextEnabled();
  }

  ['js-cidade-input', 'js-bairro-input'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function () { clearError(); updateNextEnabled(); });
  });

  // --- Steppers (quartos/suítes/banheiros/vagas) ------------------------------
  document.querySelectorAll('.wz-stepper').forEach(function (stepper) {
    var field = stepper.dataset.field;
    var valueEl = stepper.querySelector('.wz-stepper-value');
    var decBtn = stepper.querySelector('.wz-stepper-dec');
    var incBtn = stepper.querySelector('.wz-stepper-inc');
    function sync() {
      valueEl.textContent = state[field];
      decBtn.disabled = state[field] <= 0;
    }
    decBtn.addEventListener('click', function () { if (state[field] > 0) { state[field]--; sync(); } });
    incBtn.addEventListener('click', function () { state[field]++; sync(); });
    sync();
  });

  // --- Busca de endereço (etapa 1) --------------------------------------------
  var STATE_NAME_TO_UF = {
    'Acre': 'AC', 'Alagoas': 'AL', 'Amapá': 'AP', 'Amazonas': 'AM', 'Bahia': 'BA', 'Ceará': 'CE',
    'Distrito Federal': 'DF', 'Espírito Santo': 'ES', 'Goiás': 'GO', 'Maranhão': 'MA', 'Mato Grosso': 'MT',
    'Mato Grosso do Sul': 'MS', 'Minas Gerais': 'MG', 'Pará': 'PA', 'Paraíba': 'PB', 'Paraná': 'PR',
    'Pernambuco': 'PE', 'Piauí': 'PI', 'Rio de Janeiro': 'RJ', 'Rio Grande do Norte': 'RN', 'Rio Grande do Sul': 'RS',
    'Rondônia': 'RO', 'Roraima': 'RR', 'Santa Catarina': 'SC', 'São Paulo': 'SP', 'Sergipe': 'SE', 'Tocantins': 'TO',
  };
  function resolveUf(address) {
    var iso = address['ISO3166-2-lvl4'];
    if (iso && iso.indexOf('BR-') === 0) return iso.split('-')[1];
    return STATE_NAME_TO_UF[address.state] || null;
  }

  var addrSearchInput = document.getElementById('wz-address-search');
  var addrSearchBtn = document.getElementById('wz-address-search-btn');
  var addrStatus = document.getElementById('wz-address-status');

  function fillAddressFromNominatim(result) {
    var address = result.address || {};
    var cityName = address.city || address.town || address.village || address.municipality || address.county;
    var uf = resolveUf(address);
    if (cityName && uf) document.getElementById('js-cidade-input').value = cityName + ' (' + uf + ')';
    var bairro = address.suburb || address.neighbourhood || address.quarter || address.city_district;
    if (bairro) document.getElementById('js-bairro-input').value = bairro;
    if (address.road) document.getElementById('js-street-input').value = address.road;
    if (address.house_number) document.getElementById('js-number-input').value = address.house_number;
    if (address.postcode) document.getElementById('js-zip-input').value = address.postcode;
    document.getElementById('js-lat-input').value = result.lat;
    document.getElementById('js-lng-input').value = result.lon;
    clearError();
    updateNextEnabled();
  }

  function searchWizardAddress() {
    var q = (addrSearchInput.value || '').trim();
    if (!q) return;
    addrStatus.textContent = 'Procurando "' + q + '"...';
    fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&q=' + encodeURIComponent(q) + '&countrycodes=br&addressdetails=1&limit=1&accept-language=pt-BR')
      .then(function (r) { return r.json(); })
      .then(function (results) {
        if (!results || !results.length) { addrStatus.textContent = 'Endereço não encontrado. Preencha os campos abaixo manualmente.'; return; }
        fillAddressFromNominatim(results[0]);
        addrStatus.textContent = 'Endereço encontrado: ' + results[0].display_name;
      })
      .catch(function () { addrStatus.textContent = 'Não foi possível buscar agora. Preencha os campos abaixo manualmente.'; });
  }
  if (addrSearchBtn) addrSearchBtn.addEventListener('click', searchWizardAddress);
  if (addrSearchInput) {
    addrSearchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); searchWizardAddress(); }
    });
  }

  // --- Fotos (etapa 7) --------------------------------------------------------
  var photoInput = document.getElementById('wz-photo-input');
  var photoDrop = document.getElementById('wz-photo-drop');
  var photoGrid = document.getElementById('wz-photo-grid');
  var photoStatus = document.getElementById('wz-photo-status');

  if (photoDrop) {
    photoDrop.addEventListener('click', function () { photoInput.click(); });
    photoDrop.addEventListener('dragover', function (e) { e.preventDefault(); photoDrop.classList.add('border-brand-primary'); });
    photoDrop.addEventListener('dragleave', function () { photoDrop.classList.remove('border-brand-primary'); });
    photoDrop.addEventListener('drop', function (e) {
      e.preventDefault();
      photoDrop.classList.remove('border-brand-primary');
      if (e.dataTransfer.files.length) uploadPhotos(e.dataTransfer.files);
    });
  }
  if (photoInput) {
    photoInput.addEventListener('change', function () {
      if (photoInput.files.length) uploadPhotos(photoInput.files);
      photoInput.value = '';
    });
  }

  function uploadPhotos(fileList) {
    if (!propertyId) { showError('Não foi possível preparar o imóvel para receber fotos. Volte e tente novamente.'); return; }
    photoStatus.textContent = 'Enviando fotos...';
    var fd = new FormData();
    fd.append('property_id', propertyId);
    Array.prototype.forEach.call(fileList, function (f) { fd.append('files[]', f); });
    fetch(APP_BASE + 'actions/upload_foto.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.images) data.images.forEach(function (img) { photos.push(img); });
        renderPhotoGrid();
        photoStatus.textContent = photos.length + ' foto(s) adicionada(s) — mínimo recomendado: 5.';
        if (data.errors && data.errors.length) showError(data.errors.join(' '));
        updateNextEnabled();
      })
      .catch(function () { photoStatus.textContent = 'Falha ao enviar. Tente novamente.'; });
  }

  function renderPhotoGrid() {
    photoGrid.innerHTML = '';
    photos.forEach(function (p, i) {
      var tile = document.createElement('div');
      tile.className = 'wz-photo-tile';
      tile.draggable = true;
      tile.dataset.id = p.id;
      tile.innerHTML = '<img src="' + esc(p.url) + '" alt="">' +
        (i === 0 ? '<span class="wz-photo-cover-badge">Capa</span>' : '') +
        '<button type="button" class="wz-photo-remove" data-id="' + p.id + '" aria-label="Remover">&times;</button>';
      photoGrid.appendChild(tile);
    });
    wirePhotoDrag();
  }

  function wirePhotoDrag() {
    var dragging = null;
    photoGrid.querySelectorAll('.wz-photo-tile').forEach(function (tile) {
      tile.addEventListener('dragstart', function () { dragging = tile; tile.classList.add('dragging'); });
      tile.addEventListener('dragend', function () { if (dragging) { dragging.classList.remove('dragging'); dragging = null; persistPhotoOrder(); } });
      tile.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragging || dragging === tile) return;
        var rect = tile.getBoundingClientRect();
        var before = (e.clientX - rect.left) < rect.width / 2;
        tile.parentNode.insertBefore(dragging, before ? tile : tile.nextSibling);
      });
    });
  }

  function persistPhotoOrder() {
    var ids = Array.prototype.map.call(photoGrid.querySelectorAll('.wz-photo-tile'), function (t) { return parseInt(t.dataset.id, 10); });
    photos = ids.map(function (id) { return photos.filter(function (p) { return p.id === id; })[0]; }).filter(Boolean);
    renderPhotoGrid();
    fetch(APP_BASE + 'actions/photo_action.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ do: 'reorder', ordered_ids: ids }),
    });
  }

  if (photoGrid) {
    photoGrid.addEventListener('click', function (e) {
      var btn = e.target.closest('.wz-photo-remove');
      if (!btn) return;
      var id = parseInt(btn.dataset.id, 10);
      fetch(APP_BASE + 'actions/photo_action.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ do: 'remove', image_id: id }),
      }).then(function () {
        photos = photos.filter(function (p) { return p.id !== id; });
        renderPhotoGrid();
        photoStatus.textContent = photos.length + ' foto(s) adicionada(s) — mínimo recomendado: 5.';
        updateNextEnabled();
      });
    });
  }

  // --- Comunicação com o servidor (actions/property_draft.php) ---------------
  function apiCall(action, extra) {
    var body = Object.assign({ action: action, csrf: csrf }, extra || {});
    return fetch(APP_BASE + 'actions/property_draft.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function coreFieldsPayload() {
    return {
      propertyType: state.propertyType, listingType: state.listingType,
      bedrooms: state.bedrooms, suites: state.suites, bathrooms: state.bathrooms, parkingSpaces: state.parkingSpaces,
      totalArea: state.totalArea || null, builtArea: state.builtArea || null,
      features: state.features,
      cidade: state.cidade, bairro: state.bairro, street: state.street, number: state.number,
      complement: state.complement, zipCode: state.zipCode,
      latitude: state.latitude || null, longitude: state.longitude || null,
    };
  }

  function syncCore() {
    var action = propertyId ? 'update' : 'create';
    var payload = { fields: coreFieldsPayload() };
    if (propertyId) payload.id = propertyId;
    return apiCall(action, payload).then(function (data) {
      if (!data.ok) throw new Error(data.error || 'Erro ao salvar o imóvel.');
      if (!propertyId) propertyId = data.id;
    });
  }

  function updateField(fields) {
    return apiCall('update', { id: propertyId, fields: fields }).then(function (data) {
      if (!data.ok) throw new Error(data.error || 'Erro ao salvar.');
    });
  }

  // --- Navegação --------------------------------------------------------------
  nextBtn.addEventListener('click', function () {
    if (busy) return;
    syncAddressFieldsFromDOM();
    if (!isValid(currentKey())) return;
    clearError();
    var key = currentKey();
    busy = true;
    nextBtn.disabled = true;

    var chain;
    if (key === 'comodidades') {
      chain = syncCore();
    } else if (key === 'titulo') {
      chain = updateField({ title: state.title });
    } else if (key === 'descricao') {
      chain = updateField({ description: state.description });
    } else if (key === 'preco') {
      chain = updateField({ priceSale: state.priceSale || null, priceRent: state.priceRent || null, condoFee: state.condoFee || null, iptu: state.iptu || null });
    } else if (key === 'contato') {
      chain = updateField({ contactPhone: state.contactPhone, contactWhatsapp: state.contactWhatsapp, contactEmail: state.contactEmail });
    } else if (key === 'revisar') {
      chain = apiCall('publish', { id: propertyId }).then(function (data) {
        if (!data.ok) throw new Error(data.error || 'Erro ao publicar.');
        window.location.href = data.href;
      });
      chain.catch(function (err) { showError(err.message); busy = false; nextBtn.disabled = false; });
      return;
    } else {
      chain = Promise.resolve();
    }

    chain.then(function () {
      stepIndex++;
      busy = false;
      render();
    }).catch(function (err) {
      showError(err.message);
      busy = false;
      nextBtn.disabled = false;
    });
  });

  backBtn.addEventListener('click', function () {
    if (busy || stepIndex === 0) return;
    clearError();
    stepIndex--;
    render();
  });

  render();
})();
