<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';
require_once __DIR__ . '/includes/avatar.php';

$error = null;
$fieldErrors = [];
$accountType = in_array($_GET['tipo'] ?? '', ['corretor', 'imobiliaria'], true) ? $_GET['tipo'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit_enforce('cadastro_ip', client_ip(), 10, 3600); // 10 cadastros / hora por IP
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['passwordConfirmation'] ?? '';
    $accountType = in_array($_POST['accountType'] ?? '', ['corretor', 'imobiliaria'], true) ? $_POST['accountType'] : '';
    $creci = trim($_POST['creci'] ?? '');
    $agencyName = trim($_POST['agencyName'] ?? '');
    $agencyCnpj = trim($_POST['cnpj'] ?? '');
    $agencyPhone = trim($_POST['agencyPhone'] ?? '');
    $agencyCity = trim($_POST['agencyCity'] ?? '');
    $personalCnpj = trim($_POST['personalCnpj'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');
    $cityLabel = trim($_POST['cityLabel'] ?? '');
    $cnpj = $accountType === 'imobiliaria' ? $agencyCnpj : $personalCnpj;
    $acceptTerms = isset($_POST['acceptTerms']);

    if (mb_strlen($firstName) < 2) $fieldErrors['firstName'] = 'Informe seu nome.';
    if (mb_strlen($lastName) < 2) $fieldErrors['lastName'] = 'Informe seu sobrenome.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $fieldErrors['email'] = 'E-mail inválido.';
    if (mb_strlen($password) < 8) $fieldErrors['password'] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($password !== $passwordConfirmation) $fieldErrors['passwordConfirmation'] = 'As senhas não coincidem.';
    if ($accountType === 'corretor' && $creci === '') $fieldErrors['creci'] = 'Informe seu número de CRECI.';
    if ($accountType === 'imobiliaria' && mb_strlen($agencyName) < 3) $fieldErrors['agencyName'] = 'Informe o nome da imobiliária.';
    if (!$acceptTerms) $fieldErrors['acceptTerms'] = 'Você precisa ler e aceitar os Termos de uso e a Política de privacidade para criar sua conta.';
    if (!verify_captcha()) $fieldErrors['captcha'] = 'Não foi possível confirmar que você não é um robô. Tente novamente.';

    if (empty($fieldErrors)) {
        rate_limit_hit('cadastro_ip', client_ip());
        try {
            ['city' => $city, 'state' => $state] = parse_city_state_label($cityLabel);
            $userId = register_user($firstName, $lastName, $email, $phone, $password, [
                'type' => $accountType,
                'creci' => $creci,
                'agencyName' => $agencyName,
                'cnpj' => $cnpj,
                'agencyPhone' => $agencyPhone,
                'agencyCity' => $agencyCity,
                'instagram' => $instagram,
                'facebook' => $facebook,
                'address' => $address,
                'zipCode' => $zipCode,
                'city' => $city,
                'state' => $state,
            ]);
            login_user($userId);
            if (!empty($_FILES['avatar']['name'])) {
                try {
                    handle_avatar_upload($userId, $_FILES['avatar']);
                } catch (AvatarUploadError $e) {
                    // Não bloqueia o cadastro — a foto pode ser enviada depois na guia Perfil.
                }
            }
            redirect(base_url($accountType === 'imobiliaria' ? 'imobiliaria/perfil.php' : 'minha-conta.php'));
        } catch (AuthServiceError $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<!-- Se o JS carregar, assets/js/auth-modal.js esconde este bloco e abre o
     modal global automaticamente — pra quem chegou direto em /cadastro.php
     ver a mesma experiência do resto do site (um único fluxo, sem página
     cheia duplicando o que o modal já mostra). Sem JS, isso aqui continua
     funcionando normalmente como fallback. -->
<div id="auth-fallback-page" class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-lg rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Criar conta</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Cadastre-se para favoritar imóveis, anunciar ou representar sua imobiliária.</p>

    <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

    <form method="post" id="cadastro-form" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="mb-5 flex items-center gap-4">
        <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
          <span id="avatar-preview-fallback" class="flex h-full w-full items-center justify-center text-xl font-semibold text-brand-primary">?</span>
          <img id="avatar-preview" src="" class="hidden h-full w-full object-cover" alt="">
        </div>
        <div>
          <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">
            <span>Adicionar foto de perfil</span>
            <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp" class="hidden">
          </label>
          <p class="mt-1 text-xs text-brand-text-secondary">Opcional — JPG, PNG ou WEBP, até 8MB.</p>
        </div>
      </div>

      <div class="mb-5 grid grid-cols-3 gap-2 text-center text-xs font-semibold">
        <label class="js-account-type-label cursor-pointer rounded-lg border-2 px-2 py-2.5 <?= $accountType === '' ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-brand-border text-brand-text-secondary' ?>">
          <input type="radio" name="accountType" value="" class="js-account-type hidden" <?= $accountType === '' ? 'checked' : '' ?>> Comprador / anunciante
        </label>
        <label class="js-account-type-label cursor-pointer rounded-lg border-2 px-2 py-2.5 <?= $accountType === 'corretor' ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-brand-border text-brand-text-secondary' ?>">
          <input type="radio" name="accountType" value="corretor" class="js-account-type hidden" <?= $accountType === 'corretor' ? 'checked' : '' ?>> Corretor autônomo
        </label>
        <label class="js-account-type-label cursor-pointer rounded-lg border-2 px-2 py-2.5 <?= $accountType === 'imobiliaria' ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-brand-border text-brand-text-secondary' ?>">
          <input type="radio" name="accountType" value="imobiliaria" class="js-account-type hidden" <?= $accountType === 'imobiliaria' ? 'checked' : '' ?>> Imobiliária
        </label>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Nome</label>
          <input name="firstName" required value="<?= e($_POST['firstName'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php if (!empty($fieldErrors['firstName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['firstName']) ?></p><?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Sobrenome</label>
          <input name="lastName" required value="<?= e($_POST['lastName'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php if (!empty($fieldErrors['lastName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['lastName']) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">E-mail</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['email'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['email']) ?></p><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Telefone (WhatsApp)</label>
        <input type="tel" name="phone" placeholder="(47) 99999-9999" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>

      <div class="mb-4 grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium">CEP</label>
          <input name="zipCode" value="<?= e($_POST['zipCode'] ?? '') ?>" placeholder="00000-000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Cidade</label>
          <input type="text" name="cityLabel" class="js-city-picker w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
                 list="cidades-datalist-pessoal" autocomplete="off"
                 placeholder="Digite o nome da cidade..." value="<?= e($_POST['cityLabel'] ?? '') ?>">
          <datalist id="cidades-datalist-pessoal"></datalist>
        </div>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Endereço</label>
        <input name="address" value="<?= e($_POST['address'] ?? '') ?>" placeholder="Rua, número, bairro" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
      <div class="mb-4 grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium">Instagram</label>
          <input name="instagram" value="<?= e($_POST['instagram'] ?? '') ?>" placeholder="@usuario" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Facebook</label>
          <input name="facebook" value="<?= e($_POST['facebook'] ?? '') ?>" placeholder="facebook.com/usuario" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
      </div>

      <div class="js-fields-corretor mb-4 <?= $accountType === 'corretor' ? '' : 'hidden' ?>">
        <label class="mb-1 block text-sm font-medium">CRECI</label>
        <input name="creci" value="<?= e($_POST['creci'] ?? '') ?>" placeholder="Ex.: 12345-F" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['creci'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['creci']) ?></p><?php endif; ?>
      </div>

      <div class="js-fields-personal-cnpj mb-4 <?= $accountType === 'imobiliaria' ? 'hidden' : '' ?>">
        <label class="mb-1 block text-sm font-medium">CNPJ (opcional)</label>
        <input name="personalCnpj" value="<?= e($_POST['personalCnpj'] ?? '') ?>" placeholder="Se você anuncia como pessoa jurídica" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>

      <div class="js-fields-imobiliaria <?= $accountType === 'imobiliaria' ? '' : 'hidden' ?>">
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Nome da imobiliária</label>
          <input name="agencyName" value="<?= e($_POST['agencyName'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php if (!empty($fieldErrors['agencyName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['agencyName']) ?></p><?php endif; ?>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">CNPJ</label>
            <input name="cnpj" value="<?= e($_POST['cnpj'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">Telefone comercial</label>
            <input name="agencyPhone" value="<?= e($_POST['agencyPhone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Cidade de atuação</label>
          <input type="text" name="agencyCity" class="js-city-picker w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
                 list="cidades-datalist-imobiliaria" autocomplete="off"
                 placeholder="Digite o nome da cidade..." value="<?= e($_POST['agencyCity'] ?? '') ?>">
          <datalist id="cidades-datalist-imobiliaria"></datalist>
        </div>
        <p class="mb-4 -mt-2 text-xs text-brand-text-secondary">O perfil público da imobiliária fica em análise até a aprovação do administrador do site.</p>
      </div>

      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Senha</label>
        <input type="password" name="password" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['password'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['password']) ?></p><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Confirmar senha</label>
        <input type="password" name="passwordConfirmation" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['passwordConfirmation'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['passwordConfirmation']) ?></p><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="flex items-start gap-2 text-xs text-brand-text-secondary">
          <input type="checkbox" name="acceptTerms" required class="mt-0.5 h-4 w-4 shrink-0 rounded border-brand-border text-brand-primary focus:ring-brand-primary">
          <span>Li e aceito os <a href="<?= base_url('termos-de-uso.php') ?>" target="_blank" class="font-medium text-brand-primary hover:underline">Termos de uso</a> e a <a href="<?= base_url('politica-de-privacidade.php') ?>" target="_blank" class="font-medium text-brand-primary hover:underline">Política de privacidade</a> do Habitou Imóveis, incluindo a isenção de responsabilidade do portal pela negociação entre corretores, imobiliárias, proprietários e clientes.</span>
        </label>
        <?php if (!empty($fieldErrors['acceptTerms'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['acceptTerms']) ?></p><?php endif; ?>
      </div>
      <?= render_captcha_widget() ?>
      <?php if (!empty($fieldErrors['captcha'])): ?><p class="mb-4 text-xs text-red-600"><?= e($fieldErrors['captcha']) ?></p><?php endif; ?>
      <button type="submit" class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar conta</button>
      <p class="mt-4 text-center text-sm text-brand-text-secondary">Já tem uma conta? <a href="<?= base_url('login.php') ?>" class="font-medium text-brand-primary hover:underline">Entrar</a></p>
    </form>
  </div>
</div>
<script>
(function () {
  var radios = document.querySelectorAll('.js-account-type');
  var fieldsCorretor = document.querySelector('.js-fields-corretor');
  var fieldsImobiliaria = document.querySelector('.js-fields-imobiliaria');
  var fieldsPersonalCnpj = document.querySelector('.js-fields-personal-cnpj');
  function apply() {
    var value = document.querySelector('.js-account-type:checked').value;
    document.querySelectorAll('.js-account-type-label').forEach(function (label) {
      var active = label.querySelector('.js-account-type').checked;
      label.classList.toggle('border-brand-primary', active);
      label.classList.toggle('bg-brand-primary/5', active);
      label.classList.toggle('text-brand-primary', active);
      label.classList.toggle('border-brand-border', !active);
      label.classList.toggle('text-brand-text-secondary', !active);
    });
    fieldsCorretor.classList.toggle('hidden', value !== 'corretor');
    fieldsImobiliaria.classList.toggle('hidden', value !== 'imobiliaria');
    fieldsPersonalCnpj.classList.toggle('hidden', value === 'imobiliaria');
  }
  radios.forEach(function (r) { r.addEventListener('change', apply); });
  apply();

  var avatarInput = document.getElementById('avatar-input');
  avatarInput.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var img = document.getElementById('avatar-preview');
    var fallback = document.getElementById('avatar-preview-fallback');
    img.src = URL.createObjectURL(file);
    img.classList.remove('hidden');
    if (fallback) fallback.classList.add('hidden');
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
