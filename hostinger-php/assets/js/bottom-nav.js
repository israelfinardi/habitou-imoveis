// Esconde a barra inferior mobile (includes/bottom_nav.php) e a barra
// superior compacta (só a logo, no mobile) ao rolar pra baixo, e mostra de
// novo ao rolar pra cima ou perto do topo — mesmo padrão do Airbnb.
// Enquanto o mapa em tela cheia da busca está ativo (results-map.js), a
// barra inferior fica sempre escondida, dando a tela toda pro mapa.
(function () {
  var nav = document.getElementById('hb-bottom-nav');
  var header = document.querySelector('header');
  if (!nav && !header) return;

  var lastY = window.scrollY;
  var ticking = false;

  function isMapFullscreen() {
    return document.body.classList.contains('hb-map-fullscreen');
  }

  function onScroll() {
    var y = Math.max(0, window.scrollY);
    var goingDown = y > lastY + 4;
    var goingUp = y < lastY - 4;

    if (isMapFullscreen()) {
      if (nav) nav.classList.add('hb-hidden');
    } else if (y < 80) {
      if (nav) nav.classList.remove('hb-hidden');
      if (header) header.classList.remove('hb-hidden');
    } else if (goingDown) {
      if (nav) nav.classList.add('hb-hidden');
      if (header) header.classList.add('hb-hidden');
    } else if (goingUp) {
      if (nav) nav.classList.remove('hb-hidden');
      if (header) header.classList.remove('hb-hidden');
    }

    lastY = y;
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(onScroll);
      ticking = true;
    }
  }, { passive: true });

  // O mapa em tela cheia (results-map.js) trava o scroll do body (a lista
  // vira uma bandeja arrastável por cima do mapa) — nesse caso não há
  // evento de scroll no body pra reagir, então observamos a classe
  // diretamente e reagimos na hora que ela muda. results-map.js roda antes
  // deste script (seu <script> vem antes no HTML) e já pode ter marcado o
  // body como hb-map-fullscreen logo no carregamento da página, antes do
  // observer abaixo existir — por isso o check imediato aqui também.
  if (isMapFullscreen() && nav) {
    nav.classList.add('hb-hidden');
  }
  var observer = new MutationObserver(function () {
    if (isMapFullscreen() && nav) {
      nav.classList.add('hb-hidden');
    }
  });
  observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
})();
