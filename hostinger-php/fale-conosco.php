<?php
require_once __DIR__ . '/includes/bootstrap.php';

$subjects = ['Anunciar no Habitou Imóveis', 'Suporte para minha conta', 'Dúvida sobre um imóvel', 'Parcerias e imprensa', 'Reportar um problema', 'Sugestão ou elogio', 'Outro'];
$success = $_SESSION['contact_success'] ?? null;
$error = $_SESSION['contact_error'] ?? null;
$formData = $_SESSION['contact_form'] ?? [];
unset($_SESSION['contact_success'], $_SESSION['contact_error'], $_SESSION['contact_form']);

$imovel = $_GET['imovel'] ?? null;

$pageTitle = 'Fale conosco';
$pageDescription = 'Dúvida, sugestão, proposta de parceria ou quer anunciar? Fale com o time Habitou Imóveis.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <div class="mb-10 text-center">
    <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Atendimento · seg a sex, 8h–18h</p>
    <h1 class="mt-2 text-3xl font-bold">Fale com a gente</h1>
    <p class="mx-auto mt-2 max-w-xl text-brand-text-secondary">Dúvida, sugestão, proposta de parceria ou quer anunciar? Preencha o formulário — normalmente respondemos em até 2 horas úteis.</p>
  </div>

  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
    <div class="rounded-2xl border border-brand-border bg-white p-6">
      <?php if ($success): ?>
        <p class="rounded-lg bg-brand-green/10 px-4 py-3 text-sm text-brand-green-hover"><?= e($success) ?></p>
      <?php else: ?>
        <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
        <form method="post" action="<?= base_url('actions/contact_action.php') ?>">
          <?= csrf_field() ?>
          <div class="grid grid-cols-1 gap-x-3 sm:grid-cols-2">
            <div class="mb-4"><label class="mb-1 block text-sm font-medium">Nome *</label><input name="name" required value="<?= e($formData['name'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
            <div class="mb-4"><label class="mb-1 block text-sm font-medium">E-mail *</label><input name="email" type="email" required value="<?= e($formData['email'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          </div>
          <div class="mb-4"><label class="mb-1 block text-sm font-medium">Telefone</label><input name="phone" type="tel" value="<?= e($formData['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">Assunto *</label>
            <select name="subject" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              <option value="">Selecione</option>
              <?php foreach ($subjects as $s): ?><option value="<?= e($s) ?>" <?= ($imovel ? $s === 'Dúvida sobre um imóvel' : ($formData['subject'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-4"><label class="mb-1 block text-sm font-medium">Mensagem *</label><textarea name="message" required maxlength="1000" rows="5" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"><?= e($formData['message'] ?? ($imovel ? "Referente ao imóvel {$imovel}: " : '')) ?></textarea></div>
          <p class="mb-4 text-xs text-brand-text-secondary">Ao enviar, você concorda com a <a href="<?= base_url('politica-de-privacidade.php') ?>" class="text-brand-primary hover:underline">Política de Privacidade</a>.</p>
          <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Enviar mensagem</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="space-y-4">
      <div class="rounded-2xl border border-brand-border bg-white p-5"><p class="text-sm font-semibold">WhatsApp</p><a href="https://wa.me/5547964279000" class="text-sm text-brand-primary hover:underline">(47) 96427-9000</a><p class="text-xs text-brand-text-secondary">resposta rápida em horário comercial</p></div>
      <div class="rounded-2xl border border-brand-border bg-white p-5"><p class="text-sm font-semibold">E-mail</p><a href="mailto:contato@habitou.com.br" class="text-sm text-brand-primary hover:underline">contato@habitou.com.br</a><p class="text-xs text-brand-text-secondary">assuntos gerais e suporte</p></div>
      <div class="rounded-2xl border border-brand-border bg-white p-5"><p class="text-sm font-semibold">Horário de atendimento</p><p class="text-xs text-brand-text-secondary">Seg — Sex: 8h — 18h</p><p class="text-xs text-brand-text-secondary">Sáb, Dom e feriados: fechado</p></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
