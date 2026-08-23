// Orquestra os blocos de sugestão da home estilo Airbnb: cada bloco é
// carregado de forma assíncrona (fetch em paralelo, depois que a página já
// terminou de carregar) pra não travar o carregamento inicial com as
// consultas mais pesadas (geolocalização, médias de preço por bairro etc).
// Um esqueleto <section data-block-endpoint="..."> fica no lugar de cada
// bloco até a resposta chegar; se o servidor devolver vazio (bloco sem
// dado suficiente pra aparecer, como no Airbnb), o esqueleto vira um
// marcador invisível (comentário) no DOM — só pra saber onde reinserir o
// bloco depois, caso o GPS (mais preciso que geo por IP) desbloqueie ele.
(function () {
  var skeletons = document.querySelectorAll('.js-home-block-skeleton');
  if (!skeletons.length || typeof APP_BASE === 'undefined') return;

  // Blocos que dependem de localização (recomendação geo, POI, bom valor,
  // cidades vizinhas) — "vistos recentemente" é local (localStorage) e
  // carrega independente disso.
  var LOCATION_ENDPOINTS = [
    'actions/home_recommendations.php',
    'actions/block_poi.php',
    'actions/block_good_value.php',
    'actions/block_nearby_cities.php',
  ];
  var GPS_RACE_MS = 1200;

  // nó atual (section ou comentário-marcador) de cada bloco de
  // localização, pra reloadLocationBlocks() achar onde substituir depois.
  var currentNodes = {};

  function transacaoParam() {
    var checked = document.querySelector('input[name="transacao"]:checked');
    return checked ? checked.value : '';
  }

  function withTransacao(endpoint) {
    return endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'transacao=' + encodeURIComponent(transacaoParam());
  }

  function renderInto(node, html, trackKey) {
    var next;
    if (!html.trim()) {
      next = document.createComment('home-block:' + trackKey);
    } else {
      var temp = document.createElement('div');
      temp.innerHTML = html.trim();
      next = temp.firstElementChild;
    }
    node.replaceWith(next);
    if (trackKey) currentNodes[trackKey] = next;
    return next;
  }

  function loadBlock(el, fetchUrl, trackKey) {
    fetch(APP_BASE + fetchUrl)
      .then(function (r) { return r.text(); })
      .then(function (html) { renderInto(el, html, trackKey); })
      .catch(function () { renderInto(el, '', trackKey); });
  }

  // "Vistos recentemente": não depende de localização, carrega de cara.
  skeletons.forEach(function (el) {
    var endpoint = el.getAttribute('data-block-endpoint');
    if (endpoint !== 'actions/block_recently_viewed.php') return;
    try {
      var ids = JSON.parse(localStorage.getItem('habitou_recently_viewed') || '[]');
      if (!ids.length) { el.remove(); return; }
      loadBlock(el, endpoint + '?ids=' + encodeURIComponent(ids.join(',')), null);
    } catch (e) { el.remove(); }
  });

  function reloadLocationBlocks() {
    LOCATION_ENDPOINTS.forEach(function (endpoint) {
      var node = currentNodes[endpoint];
      if (!node) return;
      fetch(APP_BASE + withTransacao(endpoint))
        .then(function (r) { return r.text(); })
        .then(function (html) { renderInto(node, html, endpoint); })
        .catch(function () {});
    });
  }

  function wait(ms) {
    return new Promise(function (resolve) { setTimeout(resolve, ms); });
  }

  // Pede GPS uma única vez; se conceder, manda a posição pro servidor
  // (upgrade sobre a geo por IP, que já rodou no primeiro request de cada
  // bloco). O resultado é reaproveitado tanto pela corrida abaixo quanto
  // pelo reload tardio, sem duplicar a chamada ao navigator.geolocation.
  var gpsPromise = navigator.geolocation
    ? new Promise(function (resolve, reject) {
      navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 8000, maximumAge: 10 * 60 * 1000 });
    }).then(function (pos) {
      var body = 'lat=' + encodeURIComponent(pos.coords.latitude) + '&lng=' + encodeURIComponent(pos.coords.longitude);
      return fetch(APP_BASE + 'actions/set_location.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body });
    }).catch(function () {})
    : Promise.resolve();

  // Dá até GPS_RACE_MS pro GPS responder antes do primeiro carregamento
  // dos blocos de localização — evita marcar um bloco como "vazio" só
  // porque a geo por IP (mais lenta de melhorar) ainda não tinha a posição
  // precisa. Se o GPS demorar mais que isso, os blocos já sobem com a geo
  // por IP mesmo, e o .then() logo abaixo os atualiza quando o GPS chegar.
  Promise.race([gpsPromise, wait(GPS_RACE_MS)]).then(function () {
    skeletons.forEach(function (el) {
      var endpoint = el.getAttribute('data-block-endpoint');
      if (LOCATION_ENDPOINTS.indexOf(endpoint) === -1) return;
      currentNodes[endpoint] = el;
      loadBlock(el, withTransacao(endpoint), endpoint);
    });
  });
  gpsPromise.then(reloadLocationBlocks);

  // Setas de navegação dos carrosséis — delegado no documento porque os
  // blocos são injetados dinamicamente depois do carregamento inicial.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-home-carousel-prev, .js-home-carousel-next');
    if (!btn) return;
    var track = btn.parentElement.querySelector('.js-home-carousel-track');
    if (!track) return;
    var amount = track.clientWidth * 0.9;
    track.scrollBy({ left: btn.classList.contains('js-home-carousel-prev') ? -amount : amount, behavior: 'smooth' });
  });

  function updateArrows(track) {
    var wrap = track.parentElement;
    var prev = wrap.querySelector('.js-home-carousel-prev');
    var next = wrap.querySelector('.js-home-carousel-next');
    if (!prev || !next) return;
    var overflowing = track.scrollWidth > track.clientWidth + 4;
    prev.classList.toggle('lg:flex', overflowing && track.scrollLeft > 4);
    prev.classList.toggle('hidden', !(overflowing && track.scrollLeft > 4));
    var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
    next.classList.toggle('lg:flex', overflowing && !atEnd);
    next.classList.toggle('hidden', !(overflowing && !atEnd));
  }

  document.addEventListener('scroll', function (e) {
    if (e.target.classList && e.target.classList.contains('js-home-carousel-track')) {
      updateArrows(e.target);
    }
  }, true);

  // Reavalia as setas sempre que um bloco termina de carregar (o
  // MutationObserver cobre a troca via replaceWith() feita em renderInto acima).
  new MutationObserver(function () {
    document.querySelectorAll('.js-home-carousel-track').forEach(updateArrows);
  }).observe(document.body, { childList: true, subtree: true });
})();
