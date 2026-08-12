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

  // --- Segmentado Todos / Comprar / Alugar ------------------------------
  document.querySelectorAll('.sp-seg').forEach(function (seg) {
    seg.addEventListener('click', function (e) {
      e.preventDefault();
      var transacao = seg.dataset.transacao || '';
      var params = new URLSearchParams();
      if (transacao) params.set('transacao', transacao);
      if (selectedCity) params.set('cidade_nome', selectedCity);
      var qs = params.toString();
      window.location.href = APP_BASE + 'imoveis.php' + (qs ? '?' + qs : '');
    });
  });

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
