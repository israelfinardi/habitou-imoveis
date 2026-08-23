// Upgrade progressivo da Fase 1 (cold start) do algoritmo de recomendação:
// index.php já renderiza a seção #home-recommendations no primeiro load
// usando geolocalização por IP (ou o fallback de mais recentes) — bem mais
// grosseira que o GPS do navegador, mas disponível sem esperar nenhuma
// permissão. Se o visitante conceder localização precisa, mandamos pro
// servidor (actions/set_location.php) e recarregamos só essa seção via
// AJAX (actions/home_recommendations.php), sem reload de página inteira.
// Se recusar ou o navegador não suportar, a versão por IP/fallback fica
// como está — nunca trava nem mostra erro pro usuário.
(function () {
  var section = document.getElementById('home-recommendations');
  if (!section || !navigator.geolocation || typeof APP_BASE === 'undefined') return;

  navigator.geolocation.getCurrentPosition(
    function (pos) {
      var lat = pos.coords.latitude;
      var lng = pos.coords.longitude;
      var body = 'lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);

      fetch(APP_BASE + 'actions/set_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
      })
        .then(function () {
          var transacao = section.getAttribute('data-transacao') || '';
          return fetch(APP_BASE + 'actions/home_recommendations.php?transacao=' + encodeURIComponent(transacao));
        })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var grid = document.getElementById('home-recommendations-grid');
          if (grid) grid.outerHTML = html;
          var label = document.getElementById('home-recommendations-label');
          if (label) label.textContent = 'Perto de você';
        })
        .catch(function () { /* mantém o que já foi renderizado no servidor */ });
    },
    function () { /* permissão negada ou indisponível — sem-op, mantém IP/fallback */ },
    { timeout: 5000, maximumAge: 10 * 60 * 1000 }
  );
})();
