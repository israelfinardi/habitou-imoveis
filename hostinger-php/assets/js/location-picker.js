// Autocomplete de cidade (todas as ~5.600 cidades do Brasil) usado no
// cadastro de imóvel e no cadastro de imobiliária/corretor.
(function () {
  var inputs = document.querySelectorAll('.js-city-picker');
  if (!inputs.length) return;

  inputs.forEach(function (input) {
    var datalist = document.getElementById(input.getAttribute('list'));
    var filled = false;
    function fill() {
      if (filled || !datalist) return;
      filled = true;
      window.loadBrazilCities().then(function (cidades) {
        var frag = document.createDocumentFragment();
        cidades.forEach(function (c) {
          var opt = document.createElement('option');
          opt.value = c;
          frag.appendChild(opt);
        });
        datalist.appendChild(frag);
      });
    }
    input.addEventListener('focus', fill, { once: true });
    input.addEventListener('input', fill, { once: true });

    var form = input.closest('form');
    if (form) {
      form.addEventListener('submit', function (e) {
        if (input.required && !/^.+\([A-Za-z]{2}\)$/.test(input.value.trim())) {
          e.preventDefault();
          alert('Selecione uma cidade da lista (digite o nome e escolha uma das opções que aparecerem).');
          input.focus();
        }
      });
    }
  });
})();
