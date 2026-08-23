</main>

<?php
$__newsletterSuccess = $_SESSION['newsletter_success'] ?? null;
$__newsletterError = $_SESSION['newsletter_error'] ?? null;
unset($_SESSION['newsletter_success'], $_SESSION['newsletter_error']);
?>
<footer class="bg-brand-navy text-white">
  <div class="mx-auto flex max-w-[1800px] flex-col gap-4 border-b border-white/10 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
    <p class="text-base font-bold">Cadastre-se e receba novidades</p>
    <form action="<?= base_url('actions/newsletter_action.php') ?>" method="post" class="flex w-full max-w-md gap-2">
      <?= csrf_field() ?>
      <input type="hidden" name="voltar" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
      <input type="email" name="email" required placeholder="Digite seu e-mail" class="w-full rounded-full border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-white/50 focus:border-white/40 focus:outline-none">
      <button type="submit" class="shrink-0 rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Cadastrar</button>
    </form>
  </div>
  <?php if ($__newsletterSuccess || $__newsletterError): ?>
    <div class="mx-auto max-w-[1800px] px-4 pt-4 sm:px-6 lg:px-8">
      <p class="rounded-lg <?= $__newsletterSuccess ? 'bg-brand-green/15 text-brand-green' : 'bg-red-500/15 text-red-300' ?> px-4 py-2.5 text-sm font-medium">
        <?= e($__newsletterSuccess ?: $__newsletterError) ?>
      </p>
    </div>
  <?php endif; ?>

  <div class="mx-auto grid max-w-[1800px] grid-cols-2 gap-8 px-4 py-10 sm:px-6 md:grid-cols-4 lg:px-8">
    <div>
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-white/60">Conta</p>
      <ul class="space-y-2.5 text-sm text-white/80">
        <li><a href="<?= base_url('minha-conta.php') ?>" class="hover:underline">Minha conta</a></li>
        <li><a href="<?= base_url('minha-conta-favoritos.php') ?>" class="hover:underline">Favoritos</a></li>
        <li><a href="<?= base_url('comparar.php') ?>" class="hover:underline">Comparar imóveis</a></li>
      </ul>
    </div>
    <div>
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-white/60">Sobre</p>
      <ul class="space-y-2.5 text-sm text-white/80">
        <li><a href="<?= base_url('quem-somos.php') ?>" class="hover:underline">Quem somos</a></li>
        <li><a href="<?= base_url('como-anunciar.php') ?>" class="hover:underline">Como anunciar</a></li>
        <li><a href="<?= base_url('fale-conosco.php') ?>" class="hover:underline">Fale conosco</a></li>
      </ul>
    </div>
    <div>
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-white/60">Anunciantes</p>
      <ul class="space-y-2.5 text-sm text-white/80">
        <li><a href="<?= base_url('imobiliarias.php') ?>" class="hover:underline">Imobiliárias e corretores</a></li>
        <li><a href="<?= base_url('anunciante/novo.php') ?>" class="hover:underline">Anunciar imóvel</a></li>
        <li><a href="<?= base_url('planos.php') ?>" class="hover:underline">Planos</a></li>
        <li><a href="<?= base_url('guias.php') ?>" class="hover:underline">Central de ajuda</a></li>
        <li><a href="<?= base_url('blog.php') ?>" class="hover:underline">Blog</a></li>
      </ul>
    </div>
    <div>
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-white/60">Legal</p>
      <ul class="space-y-2.5 text-sm text-white/80">
        <li><a href="<?= base_url('termos-de-uso.php') ?>" class="hover:underline">Termos de uso</a></li>
        <li><a href="<?= base_url('politica-de-privacidade.php') ?>" class="hover:underline">Política de privacidade</a></li>
      </ul>
    </div>
  </div>

  <div class="mx-auto flex max-w-[1800px] flex-col items-center gap-4 border-t border-white/10 px-4 py-6 sm:flex-row sm:justify-between sm:px-6 lg:px-8">
    <div class="flex items-center gap-2">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Habitou Imóveis" class="h-7 w-auto brightness-0 invert">
      <span class="text-xs text-white/60">&copy; <?= date('Y') ?> Habitou Imóveis. Todos os direitos reservados.</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?= base_url('fale-conosco.php') ?>" aria-label="Instagram" class="flex h-8 w-8 items-center justify-center rounded-full border border-white/20 hover:border-white/40">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.5" fill="currentColor"/></svg>
      </a>
      <a href="<?= base_url('fale-conosco.php') ?>" aria-label="Facebook" class="flex h-8 w-8 items-center justify-center rounded-full border border-white/20 hover:border-white/40">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h-2a4 4 0 0 0-4 4v3H7v4h2v7h4v-7h2.5l.5-4H13V7a1 1 0 0 1 1-1h2z"/></svg>
      </a>
    </div>
  </div>
</footer>

<div id="compare-bar" class="fixed inset-x-0 bottom-0 z-40 hidden border-t border-brand-border bg-white shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
  <div class="mx-auto flex max-w-[1800px] items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
    <div id="compare-items" class="flex flex-1 items-center gap-2 overflow-x-auto"></div>
    <button id="compare-clear" class="text-xs text-brand-text-secondary hover:underline">Limpar</button>
    <a id="compare-link" href="<?= base_url('comparar.php') ?>" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Comparar</a>
  </div>
</div>

<script>
const APP_BASE = <?= json_encode(rtrim(base_url('/'), '/') . '/') ?>;
window.__CURRENT_FILTERS = {
  transacao: <?= json_encode($__topbarTransacao ?? '') ?>,
  cidade: <?= json_encode($__topbarCidade ?? '') ?>
};
</script>
<script src="<?= asset_url('assets/js/app.js') ?>"></script>
<script src="<?= asset_url('assets/js/carousels.js') ?>"></script>
<script src="<?= asset_url('assets/js/cidades.js') ?>"></script>
<script src="<?= asset_url('assets/js/location-picker.js') ?>"></script>
<script src="<?= asset_url('assets/js/topbar.js') ?>"></script>
<script src="<?= asset_url('assets/js/home-blocks.js') ?>"></script>
<script src="<?= asset_url('assets/js/property-view-tracker.js') ?>"></script>
</body>
</html>
