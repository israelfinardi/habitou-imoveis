// Carrossel de fotos dos cards de imóvel (lista/grade e popup do mapa):
// setas no hover (desktop) + deslize nativo por toque (mobile), com dots de
// paginação. Delegado em document, então funciona tanto para os cards
// renderizados pelo PHP quanto para os popups do Leaflet, montados
// dinamicamente em JS (assets/js/results-map.js).
(function () {
  function trackOf(el) {
    var root = el.closest('.js-carousel');
    return root ? root.querySelector('.js-carousel-track') : null;
  }

  function slideWidth(track) {
    var first = track.querySelector(':scope > *');
    return first ? first.getBoundingClientRect().width : track.clientWidth;
  }

  // capture:true é essencial aqui: o popup do Leaflet chama
  // stopPropagation() nos cliques dentro dele (fase de bubble, para o clique
  // não "vazar" pro mapa) — um listener em document na fase de bubble nunca
  // chegaria a rodar para os cards dentro do popup. Na fase de captura,
  // este handler roda antes disso acontecer.
  document.addEventListener('click', function (e) {
    var prev = e.target.closest('.js-carousel-prev');
    var next = e.target.closest('.js-carousel-next');
    var btn = prev || next;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    var track = trackOf(btn);
    if (!track) return;
    var w = slideWidth(track);
    track.scrollBy({ left: prev ? -w : w, behavior: 'smooth' });
  }, true);

  function updateDots(track) {
    var root = track.closest('.js-carousel');
    var dots = root ? root.querySelector('.js-carousel-dots') : null;
    if (!dots) return;
    var w = slideWidth(track) || 1;
    var index = Math.round(track.scrollLeft / w);
    Array.prototype.forEach.call(dots.children, function (dot, i) {
      dot.classList.toggle('bg-white', i === index);
      dot.classList.toggle('bg-white/50', i !== index);
    });
  }

  // 'scroll' não borbulha (bubble) — precisa de capture:true para delegar.
  document.addEventListener('scroll', function (e) {
    var track = e.target;
    if (!track.classList || !track.classList.contains('js-carousel-track')) return;
    updateDots(track);
  }, true);
})();
