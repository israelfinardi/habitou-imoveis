// Barra superior: pílula de cidade (com sugestões), segmentado
// Todos/Comprar/Alugar, menu da conta e menu mobile.
(function () {
  // --- Pílula de cidade ------------------------------------------------
  var pill = document.getElementById('cidade-pill');
  var pillBtn = document.getElementById('cidade-pill-btn');
  var pillLabel = document.getElementById('cidade-pill-label');
  var input = document.getElementById('cidade-busca-input');
  var sugestoesEl = document.getElementById('cidade-sugestoes');
  var selectedCity = null; // "Nome (UF)" escolhido, ou null = todas as cidades

  // --- Sincroniza a barra com os filtros já aplicados na página ------------
  var current = window.__CURRENT_FILTERS || {};
  if (current.cidade) {
    selectedCity = current.cidade;
    if (pillLabel) pillLabel.textContent = current.cidade.replace(/\s*\([A-Za-z]{2}\)$/, '');
  }

  function renderSugestoes(lista) {
    sugestoesEl.innerHTML = '';
    if (!lista.length) {
      var vazio = document.createElement('div');
      vazio.className = 'cidade-sugestao-vazio';
      vazio.textContent = 'Nenhuma cidade encontrada.';
      sugestoesEl.appendChild(vazio);
      return;
    }
    lista.slice(0, 8).forEach(function (c) {
      var item = document.createElement('div');
      item.className = 'cidade-sugestao';
      item.innerHTML =
        '<span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span><b></b>';
      item.querySelector('b').textContent = c;
      item.addEventListener('click', function () {
        selectedCity = c;
        pillLabel.textContent = c.replace(/\s*\([A-Za-z]{2}\)$/, '');
        pill.classList.remove('on');
      });
      sugestoesEl.appendChild(item);
    });
  }

  if (pill && pillBtn && input && sugestoesEl) {
    pillBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var opening = !pill.classList.contains('on');
      pill.classList.toggle('on');
      if (opening) {
        input.value = '';
        input.focus();
        window.loadBrazilCities().then(function (cidades) {
          renderSugestoes(cidades.slice(0, 8));
        });
      }
    });
    input.addEventListener('click', function (e) {
      e.stopPropagation();
    });
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      window.loadBrazilCities().then(function (cidades) {
        if (!q) {
          renderSugestoes(cidades.slice(0, 8));
          return;
        }
        renderSugestoes(cidades.filter(function (c) { return c.toLowerCase().indexOf(q) !== -1; }));
      });
    });
    document.addEventListener('click', function () {
      pill.classList.remove('on');
    });
  }

  // --- Segmentado Todos / Comprar / Alugar + botão de busca -----------------
  var activeTransacao = current.transacao || '';
  var segs = document.querySelectorAll('.sp-seg');
  segs.forEach(function (seg) {
    seg.classList.toggle('on', (seg.dataset.transacao || '') === activeTransacao);
    seg.addEventListener('click', function (e) {
      e.preventDefault();
      activeTransacao = seg.dataset.transacao || '';
      segs.forEach(function (s) { s.classList.toggle('on', s === seg); });
    });
  });

  function irParaBusca() {
    // Preserva os demais filtros já aplicados (preço, quartos, tipo...) e só
    // atualiza cidade/transação, mantendo a barra superior e os filtros
    // laterais sempre sincronizados.
    var params = new URLSearchParams(window.location.search);
    params.delete('slug');
    params.delete('pagina');
    if (activeTransacao) params.set('transacao', activeTransacao); else params.delete('transacao');
    if (selectedCity) {
      params.set('cidade_nome', selectedCity);
      params.delete('cidade');
    } else {
      params.delete('cidade_nome');
      params.delete('cidade');
    }
    var qs = params.toString();
    window.location.href = APP_BASE + 'imoveis.php' + (qs ? '?' + qs : '');
  }
  var searchBtn = document.getElementById('airbnb-bar-search-btn');
  if (searchBtn) {
    searchBtn.addEventListener('click', irParaBusca);
  }

  // --- Botão "Filtros": esconde/mostra a coluna de filtros na página de
  // resultados (mapa+lista passam a dividir 50/50). Fora dessas páginas,
  // continua navegando normalmente para imoveis.php.
  var layout = document.getElementById('results-layout');
  var sidebar = document.getElementById('results-sidebar');
  var STORAGE_KEY = 'habitou_filtros_escondidos';

  function applyFiltrosState(hidden) {
    if (!layout || !sidebar) return;
    sidebar.style.display = hidden ? 'none' : '';
    layout.classList.toggle('filtros-escondidos', hidden);
  }

  if (layout && sidebar) {
    applyFiltrosState(window.localStorage.getItem(STORAGE_KEY) === '1');
    [document.getElementById('filtros-toggle-btn'), document.getElementById('filtros-toggle-btn-mobile')].forEach(function (btn) {
      if (!btn) return;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var hidden = !(window.localStorage.getItem(STORAGE_KEY) === '1');
        window.localStorage.setItem(STORAGE_KEY, hidden ? '1' : '0');
        applyFiltrosState(hidden);
      });
    });
  }

  // --- Menu da conta -------------------------------------------------------
  var userBtn = document.getElementById('nav-user-btn');
  var userMenu = document.getElementById('nav-user-menu');
  if (userBtn && userMenu) {
    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      userMenu.classList.toggle('on');
    });
    document.addEventListener('click', function () {
      userMenu.classList.remove('on');
    });
  }

  // --- Menu mobile -----------------------------------------------------
  var mobileBtn = document.getElementById('mobile-menu-btn');
  var mobileMenu = document.getElementById('mobile-menu');
  if (mobileBtn && mobileMenu) {
    mobileBtn.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
    });
  }
})();
