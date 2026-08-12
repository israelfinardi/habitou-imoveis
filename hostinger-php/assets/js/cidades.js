// Carrega (uma única vez, com cache) a lista de todas as cidades do Brasil
// no formato "Nome da Cidade (UF)", usada no cadastro e na busca por cidade.
window.loadBrazilCities = (function () {
  var promise = null;
  return function () {
    if (!promise) {
      promise = fetch(APP_BASE + 'assets/data/cidades-br.json').then(function (r) {
        return r.json();
      });
    }
    return promise;
  };
})();
