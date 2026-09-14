// Pílula de cidade (com sugestões nacionais) — reutilizável: liga em
// qualquer elemento raiz que tenha os filhos .cidade-pill-btn/
// .cidade-pill-label/.cidade-busca-input/.cidade-sugestoes. Usada na barra
// superior (topbar) e no campo "Cidade" do formulário de busca da home.
window.initCidadePicker = function (root, opts) {
  opts = opts || {};
  var pillBtn = root.querySelector('.cidade-pill-btn');
  var pillLabel = root.querySelector('.cidade-pill-label');
  var input = root.querySelector('.cidade-busca-input');
  var sugestoesEl = root.querySelector('.cidade-sugestoes');
  var hiddenInput = root.querySelector('.cidade-hidden-input');
  if (!root || !pillBtn || !input || !sugestoesEl) return null;

  var selectedCity = opts.initialValue || (hiddenInput ? hiddenInput.value : null) || null;
  if (selectedCity) {
    if (pillLabel) pillLabel.textContent = selectedCity.replace(/\s*\([A-Za-z]{2}\)$/, '');
    if (hiddenInput) hiddenInput.value = selectedCity;
  }

  function selecionar(c) {
    selectedCity = c;
    if (pillLabel) pillLabel.textContent = c.replace(/\s*\([A-Za-z]{2}\)$/, '');
    if (hiddenInput) hiddenInput.value = c;
    root.classList.remove('on');
    if (opts.onSelect) opts.onSelect(c);
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
      item.addEventListener('click', function () { selecionar(c); });
      sugestoesEl.appendChild(item);
    });
  }

  pillBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    var opening = !root.classList.contains('on');
    root.classList.toggle('on');
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
    root.classList.remove('on');
  });

  return { getSelected: function () { return selectedCity; } };
};

// Barra superior (desktop): pílula de cidade (com sugestões), segmentado
// Todos/Comprar/Alugar e menu da conta. No mobile, essa barra fica oculta —
// a navegação vira a barra inferior fixa (includes/bottom_nav.php).
(function () {
  // --- Pílula de cidade ------------------------------------------------
  var pill = document.getElementById('cidade-pill');
  var current = window.__CURRENT_FILTERS || {};
  var picker = pill ? window.initCidadePicker(pill, { initialValue: current.cidade || null }) : null;

  // Campo "Cidade" do formulário de busca do hero da home (index.php), se
  // presente na página — mesmo componente, já vem com o valor inicial
  // preenchido via o hidden input renderizado pelo PHP.
  var heroPill = document.getElementById('hero-cidade-pill');
  if (heroPill) window.initCidadePicker(heroPill);

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
    var selectedCity = picker ? picker.getSelected() : null;
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
})();
