// Card de filtros flutuante (estilo Airbnb) da página de resultados.
(function () {
  var overlay = document.getElementById('filtros-modal-overlay');
  var card = document.getElementById('filtros-modal-card');
  var form = document.getElementById('filtros-modal-form');
  if (!overlay || !card || !form) return;

  var closeBtn = document.getElementById('filtros-modal-close');
  var clearBtn = document.getElementById('filtros-modal-clear');
  var submitBtn = document.getElementById('filtros-modal-submit');
  var countUrl = form.dataset.countUrl;

  function openModal() {
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';
    refreshCount();
  }
  function closeModal() {
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    document.body.style.overflow = '';
  }

  [document.getElementById('filtros-toggle-btn'), document.getElementById('filtros-toggle-btn-mobile')].forEach(function (btn) {
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openModal();
    });
  });
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
  });

  // --- Steppers (quartos, banheiros, suítes, vagas) -------------------------
  var STEPPER_MAX = 8;
  form.querySelectorAll('.js-stepper').forEach(function (stepper) {
    var input = stepper.querySelector('.js-stepper-input');
    var display = stepper.querySelector('.js-stepper-value');
    var dec = stepper.querySelector('.js-stepper-dec');
    var inc = stepper.querySelector('.js-stepper-inc');

    function render() {
      var v = parseInt(input.value, 10) || 0;
      display.textContent = v > 0 ? v + '+' : '0';
      dec.disabled = v <= 0;
    }
    dec.addEventListener('click', function () {
      var v = Math.max(0, (parseInt(input.value, 10) || 0) - 1);
      input.value = v || '';
      render();
      refreshCount();
    });
    inc.addEventListener('click', function () {
      var v = Math.min(STEPPER_MAX, (parseInt(input.value, 10) || 0) + 1);
      input.value = v || '';
      render();
      refreshCount();
    });
  });

  // --- Contagem ao vivo -------------------------------------------------
  var countTimer = null;
  function refreshCount() {
    if (!countUrl) return;
    clearTimeout(countTimer);
    countTimer = setTimeout(function () {
      var params = new URLSearchParams(new FormData(form));
      fetch(countUrl + '?' + params.toString())
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var n = data.count || 0;
          submitBtn.textContent = 'Mostrar ' + n + (n === 1 ? ' imóvel' : ' imóveis');
        })
        .catch(function () {});
    }, 250);
  }
  form.addEventListener('input', function (e) {
    if (e.target.classList.contains('js-filtros-live')) refreshCount();
  });
  form.addEventListener('change', function (e) {
    if (e.target.classList.contains('js-filtros-live')) refreshCount();
  });

  // --- Limpar filtros -----------------------------------------------------
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      form.querySelectorAll('input[type=text], input[type=search], input[type=number]').forEach(function (el) { el.value = ''; });
      form.querySelectorAll('input[type=radio]').forEach(function (el) { el.checked = el.value === ''; });
      form.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
      form.querySelectorAll('.js-stepper').forEach(function (stepper) {
        var input = stepper.querySelector('.js-stepper-input');
        input.value = '';
        stepper.querySelector('.js-stepper-value').textContent = '0';
        stepper.querySelector('.js-stepper-dec').disabled = true;
      });
      refreshCount();
    });
  }
})();
