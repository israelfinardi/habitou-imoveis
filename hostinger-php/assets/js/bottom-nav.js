// Esconde a barra inferior mobile (includes/bottom_nav.php) e a barra
// superior compacta (só a logo, no mobile) ao rolar pra baixo, e mostra de
// novo ao rolar pra cima ou perto do topo — mesmo padrão do Airbnb.
(function () {
  var nav = document.getElementById('hb-bottom-nav');
  var header = document.querySelector('header');
  if (!nav && !header) return;

  function show() {
    if (nav) nav.classList.remove('hb-hidden');
    if (header) header.classList.remove('hb-hidden');
  }
  function hide() {
    if (nav) nav.classList.add('hb-hidden');
    if (header) header.classList.add('hb-hidden');
  }
  // assets/js/results-map.js chama isso direto ao trocar o estado da
  // bandeja de resultados (recolhida/metade/cheia) — nesse modo o scroll da
  // página inteira fica travado, então o scroll da janela abaixo nunca
  // dispara e essa página precisa de outro gatilho pra mostrar/esconder.
  window.__hbBottomNav = { show: show, hide: hide };

  // Altura real da barra (varia por aparelho — iPhone com faixa de gesto
  // soma env(safe-area-inset-bottom) por cima do conteúdo) — outras barras
  // fixas (CTA de contato do imóvel, comparador) encostam nela por essa
  // variável; sem medir de verdade, um valor fixo no CSS abre um vão
  // transparente entre as duas em aparelhos com safe-area maior.
  function measureHeight() {
    if (nav) {
      document.documentElement.style.setProperty('--hb-bn-h', nav.offsetHeight + 'px');
    }
  }
  measureHeight();
  window.addEventListener('resize', measureHeight);

  function bindScroll(target, isWindow) {
    var lastY = isWindow ? window.scrollY : target.scrollTop;
    var ticking = false;
    function onScroll() {
      var y = isWindow ? Math.max(0, window.scrollY) : target.scrollTop;
      var goingDown = y > lastY + 4;
      var goingUp = y < lastY - 4;
      if (y < 80) {
        show();
      } else if (goingDown) {
        hide();
      } else if (goingUp) {
        show();
      }
      lastY = y;
      ticking = false;
    }
    target.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(onScroll);
        ticking = true;
      }
    }, { passive: true });
  }

  bindScroll(window, true);

  // Página de resultados/mapa (imoveis.php, cidade.php): no mobile, quem
  // rola é a lista por dentro da bandeja arrastável (o scroll da página
  // fica travado) — reage a esse scroll aqui também, em vez de só ao da
  // janela.
  var sheet = document.getElementById('results-list-col');
  if (sheet) bindScroll(sheet, false);
})();
