// Lightbox em tela cheia da página do imóvel (includes/property_card.php,
// render_property_gallery()): clicar em qualquer foto da grade/carrossel, ou
// no botão "Mostrar todas as fotos", abre o modal com todas as fotos —
// navegação por seta (desktop), deslize por toque (mobile, nativo via
// scroll-snap) e teclado (Esc/setas). O deslize em si é resolvido pelo
// mesmo assets/js/carousels.js usado nos cards de listagem, delegado em
// document — só precisamos montar as imagens e controlar abrir/fechar/contador.
(function () {
  var root = document.querySelector('.js-lightbox-root');
  var modal = document.getElementById('lightbox-modal');
  if (!root || !modal) return;

  var dataEl = root.querySelector('.js-lightbox-data');
  var images = dataEl ? JSON.parse(dataEl.textContent) : [];
  var title = root.dataset.title || '';
  var track = document.getElementById('lightbox-track');
  var counter = document.getElementById('lightbox-counter');
  var closeBtn = document.getElementById('lightbox-close');

  if (!images.length) return;

  images.forEach(function (url) {
    var img = document.createElement('img');
    img.src = url;
    img.alt = title;
    img.loading = 'lazy';
    img.className = 'h-full w-full shrink-0 snap-center object-contain';
    track.appendChild(img);
  });

  function updateCounter() {
    var w = track.clientWidth || 1;
    var index = Math.min(images.length - 1, Math.round(track.scrollLeft / w));
    counter.textContent = (index + 1) + ' / ' + images.length;
  }
  track.addEventListener('scroll', updateCounter);

  function openAt(index) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    track.scrollLeft = index * track.clientWidth;
    updateCounter();
  }
  function closeLightbox() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('.js-lightbox-trigger');
    if (trigger) {
      e.preventDefault();
      openAt(parseInt(trigger.dataset.index, 10) || 0);
    }
  });
  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

  document.addEventListener('keydown', function (e) {
    if (modal.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') track.scrollBy({ left: -track.clientWidth, behavior: 'smooth' });
    if (e.key === 'ArrowRight') track.scrollBy({ left: track.clientWidth, behavior: 'smooth' });
  });

  window.addEventListener('resize', function () {
    if (!modal.classList.contains('hidden')) updateCounter();
  });
})();
