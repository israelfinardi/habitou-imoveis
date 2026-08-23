// "Vistos recentemente" (bloco da home, estilo Airbnb) é histórico LOCAL do
// navegador, sem tabela no servidor — grava o ID do imóvel aberto numa
// lista no localStorage (mais recente primeiro, sem repetir, até 20 itens).
// Roda em toda página (carregado globalmente via footer.php) mas só faz
// alguma coisa quando encontra o botão de favoritar de imovel.php, que já
// carrega data-property-id — evita precisar de mais uma variável inline só
// pra isso.
(function () {
  var favBtn = document.querySelector('.js-favorite-btn[data-property-id]');
  if (!favBtn) return;
  var propertyId = parseInt(favBtn.getAttribute('data-property-id'), 10);
  if (!propertyId) return;

  var KEY = 'habitou_recently_viewed';
  var MAX = 20;
  try {
    var list = JSON.parse(localStorage.getItem(KEY) || '[]');
    if (!Array.isArray(list)) list = [];
    list = list.filter(function (id) { return id !== propertyId; });
    list.unshift(propertyId);
    localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX)));
  } catch (e) {
    // localStorage indisponível (modo privado restrito, etc.) — sem-op,
    // só significa que o bloco "Vistos recentemente" não aparece depois.
  }
})();
