// Modal global de "Entrar ou cadastrar-se" (includes/auth_modal.php): um
// único botão no header abre esse modal por cima da página atual (fundo
// desfocado, sem navegar pra outro lugar), e o usuário avança uma etapa
// (um assunto) de cada vez — mesma ideia do wizard de anúncio
// (assets/js/property-wizard.js), só que compacto e sem servidor no meio
// de cada etapa: só a 1ª etapa fala com o servidor (auth_check_email.php,
// pra saber se aquele e-mail já tem conta) — o resto é só navegação local,
// e o "Continuar"/"Criar conta" final é um POST de página inteira normal
// pra login.php ou cadastro.php, então toda validação de servidor que já
// existia continua valendo do jeito que sempre funcionou.
(function () {
  var overlay = document.getElementById('authm-overlay');
  if (!overlay) return;

  var form = document.getElementById('authm-form');
  var panel = document.getElementById('authm-panel');
  var backdrop = document.getElementById('authm-backdrop');
  var backBtn = document.getElementById('authm-back');
  var closeBtn = document.getElementById('authm-close');
  var nextBtn = document.getElementById('authm-next');
  var skipBtn = document.getElementById('authm-skip');
  var titleEl = document.getElementById('authm-title');
  var errorEl = document.getElementById('authm-error');
  var redirectInput = document.getElementById('authm-redirect');
  var passwordSync = document.getElementById('authm-password-sync');
  var siteHeader = document.querySelector('header');
  var captchaWidget = document.getElementById('authm-captcha-widget');

  var emailInput = document.getElementById('authm-email');
  var loginPasswordInput = document.getElementById('authm-login-password');
  var loginEmailLabel = document.getElementById('authm-login-email-label');
  var signupPasswordInput = document.getElementById('authm-signup-password');
  var passwordConfirmationInput = form.querySelector('[name="passwordConfirmation"]');
  var firstNameInput = form.querySelector('[name="firstName"]');
  var lastNameInput = form.querySelector('[name="lastName"]');
  var creciInput = form.querySelector('[name="creci"]');
  var agencyNameInput = form.querySelector('[name="agencyName"]');
  var accountTypeRadios = form.querySelectorAll('.authm-account-type');
  var hideIfImobiliariaEls = form.querySelectorAll('.authm-hide-if-imobiliaria');

  var steps = {};
  document.querySelectorAll('.authm-step').forEach(function (el) { steps[el.dataset.step] = el; });

  var SKIPPABLE = { 'signup-phone': ['phone', 'personalCnpj'], 'signup-location': ['zipCode', 'cityLabel', 'address'], 'signup-social': ['instagram', 'facebook'], 'signup-avatar': null };

  var state = { flow: null, accountType: '', current: 'entry' };
  var busy = false;

  function fieldByName(name) { return form.querySelector('[name="' + name + '"]'); }

  function nextStepKey(key) {
    switch (key) {
      case 'entry': return state.flow === 'login' ? 'login-password' : 'signup-name';
      case 'signup-name': return 'signup-password';
      case 'signup-password': return 'signup-type';
      case 'signup-type':
        if (state.accountType === 'corretor') return 'signup-creci';
        if (state.accountType === 'imobiliaria') return 'signup-agency';
        return 'signup-phone';
      case 'signup-creci': return 'signup-phone';
      case 'signup-agency': return 'signup-phone';
      case 'signup-phone': return 'signup-location';
      case 'signup-location': return 'signup-social';
      case 'signup-social': return 'signup-avatar';
      case 'signup-avatar': return 'signup-review';
      default: return null; // login-password e signup-review são finais (submetem)
    }
  }

  function prevStepKey(key) {
    switch (key) {
      case 'login-password': return 'entry';
      case 'signup-name': return 'entry';
      case 'signup-password': return 'signup-name';
      case 'signup-type': return 'signup-password';
      case 'signup-creci': return 'signup-type';
      case 'signup-agency': return 'signup-type';
      case 'signup-phone':
        if (state.accountType === 'corretor') return 'signup-creci';
        if (state.accountType === 'imobiliaria') return 'signup-agency';
        return 'signup-type';
      case 'signup-location': return 'signup-phone';
      case 'signup-social': return 'signup-location';
      case 'signup-avatar': return 'signup-social';
      case 'signup-review': return 'signup-avatar';
      default: return null; // 'entry' não tem anterior
    }
  }

  function isValid(key) {
    switch (key) {
      case 'entry': return emailInput.checkValidity() && emailInput.value.trim() !== '';
      case 'login-password': return loginPasswordInput.value.length > 0;
      case 'signup-name': return firstNameInput.value.trim().length >= 2 && lastNameInput.value.trim().length >= 2;
      case 'signup-password': return signupPasswordInput.value.length >= 8 && signupPasswordInput.value === passwordConfirmationInput.value;
      case 'signup-creci': return creciInput.value.trim() !== '';
      case 'signup-agency': return agencyNameInput.value.trim().length >= 3;
      default: return true; // demais etapas são opcionais
    }
  }

  function showError(msg) { errorEl.textContent = msg; errorEl.classList.remove('hidden'); }
  function clearError() { errorEl.classList.add('hidden'); }

  function setBusy(v) {
    busy = v;
    nextBtn.disabled = v || !isValid(state.current);
  }

  function moveCaptchaTo(stepKey) {
    var slot = steps[stepKey].querySelector('.authm-captcha-slot');
    if (!slot || !captchaWidget) return;
    captchaWidget.classList.remove('hidden');
    slot.appendChild(captchaWidget);
  }

  function updateStepChrome() {
    var key = state.current;
    backBtn.classList.toggle('hidden', prevStepKey(key) === null);
    titleEl.textContent = key === 'entry' ? 'Entrar ou cadastrar-se' : (state.flow === 'login' ? 'Entrar' : 'Criar conta');
    nextBtn.textContent = key === 'login-password' ? 'Entrar' : (key === 'signup-review' ? 'Criar conta' : 'Continuar');
    skipBtn.classList.toggle('hidden', !(key in SKIPPABLE));
    nextBtn.disabled = !isValid(key);
  }

  function goToStep(key) {
    steps[state.current].classList.remove('is-active');
    state.current = key;
    steps[key].classList.add('is-active');
    clearError();

    if (key === 'login-password') {
      loginEmailLabel.textContent = emailInput.value.trim();
      moveCaptchaTo('login-password');
      setTimeout(function () { loginPasswordInput.focus(); }, 50);
    } else if (key === 'signup-review') {
      moveCaptchaTo('signup-review');
    } else if (key === 'signup-name') {
      setTimeout(function () { firstNameInput.focus(); }, 50);
    }

    updateStepChrome();
  }

  function goBack() {
    var prev = prevStepKey(state.current);
    if (!prev) return;
    steps[state.current].classList.remove('is-active');
    state.current = prev;
    steps[prev].classList.add('is-active');
    clearError();
    updateStepChrome();
  }

  // --- Etapa 1: verifica se o e-mail já tem conta -------------------------
  function checkEmailAndAdvance() {
    if (!isValid('entry')) { showError('Digite um e-mail válido.'); return; }
    clearError();
    setBusy(true);
    var csrf = form.querySelector('[name="csrf_token"]').value;
    fetch(APP_BASE + 'actions/auth_check_email.php', {
      method: 'POST',
      body: new URLSearchParams({ csrf_token: csrf, email: emailInput.value.trim() }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        setBusy(false);
        if (data.error) { showError(data.error); return; }
        state.flow = data.exists ? 'login' : 'signup';
        form.action = APP_BASE + (state.flow === 'login' ? 'login.php' : 'cadastro.php');
        goToStep(nextStepKey('entry'));
      })
      .catch(function () {
        setBusy(false);
        showError('Não foi possível verificar agora. Tente novamente.');
      });
  }

  function handleNext() {
    if (busy) return;
    var key = state.current;
    if (!isValid(key)) { showError('Preencha os campos obrigatórios desta etapa.'); return; }

    if (key === 'entry') { checkEmailAndAdvance(); return; }

    if (key === 'login-password') {
      passwordSync.value = loginPasswordInput.value;
      form.requestSubmit ? form.requestSubmit() : form.submit();
      return;
    }
    if (key === 'signup-review') {
      passwordSync.value = signupPasswordInput.value;
      form.requestSubmit ? form.requestSubmit() : form.submit();
      return;
    }

    var next = nextStepKey(key);
    if (next) goToStep(next);
  }

  function handleSkip() {
    var fields = SKIPPABLE[state.current];
    if (fields) {
      fields.forEach(function (name) {
        var el = fieldByName(name);
        if (el) el.value = '';
      });
    }
    var next = nextStepKey(state.current);
    if (next) goToStep(next);
  }

  // --- Tipo de conta: alterna etapas condicionais e o campo de CNPJ pessoal ---
  accountTypeRadios.forEach(function (radio) {
    radio.addEventListener('change', function () {
      state.accountType = radio.value;
      hideIfImobiliariaEls.forEach(function (el) { el.classList.toggle('hidden', state.accountType === 'imobiliaria'); });
    });
  });

  // --- Preview da foto de perfil ------------------------------------------
  var avatarInput = document.getElementById('authm-avatar-input');
  if (avatarInput) {
    avatarInput.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;
      var img = document.getElementById('authm-avatar-preview');
      var fallback = document.getElementById('authm-avatar-fallback');
      img.src = URL.createObjectURL(file);
      img.classList.remove('hidden');
      if (fallback) fallback.classList.add('hidden');
    });
  }

  // --- Enter avança a etapa atual (em vez de deixar o form submeter sozinho) ---
  form.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    var tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'textarea') return;
    e.preventDefault();
    handleNext();
  });

  // Reavalia se "Continuar" pode ficar habilitado a cada digitação/escolha
  // na etapa atual — sem isso o botão fica travado no estado inicial (só
  // era recalculado na troca de etapa).
  function revalidateCurrent() { if (!busy) nextBtn.disabled = !isValid(state.current); }
  form.addEventListener('input', revalidateCurrent);
  form.addEventListener('change', revalidateCurrent);

  nextBtn.addEventListener('click', handleNext);
  skipBtn.addEventListener('click', handleSkip);
  backBtn.addEventListener('click', goBack);

  // --- Abrir / fechar -------------------------------------------------------
  function resetModal() {
    state.flow = null;
    state.accountType = '';
    steps[state.current].classList.remove('is-active');
    state.current = 'entry';
    steps.entry.classList.add('is-active');
    form.reset();
    clearError();
    updateStepChrome();
  }

  // login.php e cadastro.php continuam existindo como página cheia (fallback
  // sem JS) — mas se o JS carrega, essa página nunca deve aparecer ao lado
  // do modal (ficava "duplicado": o mesmo login em dois lugares na tela).
  // Em vez disso escondemos o bloco e abrimos o modal automaticamente, do
  // jeito que a Airbnb faz: tudo passa pelo mesmo modal, nunca por uma
  // página de login separada.
  var fallbackPage = document.getElementById('auth-fallback-page');
  var isFallbackFlow = !!fallbackPage;
  if (fallbackPage) fallbackPage.classList.add('hidden');

  function openModal(opts) {
    opts = opts || {};
    resetModal();
    redirectInput.value = opts.noRedirect ? '' : (window.location.pathname + window.location.search);
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.classList.add('authm-open');
    if (siteHeader) siteHeader.classList.add('authm-header-flat');
    setTimeout(function () { emailInput.focus(); }, 50);
  }

  function closeModal() {
    // Chegou aqui direto por /login.php ou /cadastro.php (sem outra página
    // por trás pra voltar a mostrar) — fechar o modal sem sair da tela em
    // branco: manda pra home, igual ao "Não é você?"/fechar da Airbnb.
    if (isFallbackFlow) { window.location.href = APP_BASE; return; }
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    document.body.classList.remove('authm-open');
    if (siteHeader) siteHeader.classList.remove('authm-header-flat');
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-open-auth]');
    if (trigger) { e.preventDefault(); openModal(); }
  });
  closeBtn.addEventListener('click', closeModal);
  backdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
  });
  panel.addEventListener('click', function (e) { e.stopPropagation(); });

  updateStepChrome();
  if (isFallbackFlow) openModal({ noRedirect: true });
})();
