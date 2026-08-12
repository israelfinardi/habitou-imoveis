</main>

<footer class="border-t border-brand-border bg-brand-navy text-white/80">
  <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
    <div class="col-span-2 md:col-span-1">
      <p class="mb-3 text-lg font-bold text-white">Habitou Imóveis</p>
      <p class="text-sm text-white/60">26 anos conectando pessoas aos melhores imóveis de Santa Catarina.</p>
    </div>
    <div>
      <p class="mb-3 text-sm font-semibold text-white">Cidades</p>
      <ul class="space-y-2 text-sm text-white/60">
        <?php foreach (FEATURED_CITIES as $c): ?>
          <li><a href="<?= base_url('cidade.php?slug=' . $c['slug']) ?>" class="hover:text-white"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <p class="mb-3 text-sm font-semibold text-white">Institucional</p>
      <ul class="space-y-2 text-sm text-white/60">
        <li><a href="<?= base_url('quem-somos.php') ?>" class="hover:text-white">Quem somos</a></li>
        <li><a href="<?= base_url('como-anunciar.php') ?>" class="hover:text-white">Como anunciar</a></li>
        <li><a href="<?= base_url('imobiliarias.php') ?>" class="hover:text-white">Imobiliárias e corretores</a></li>
        <li><a href="<?= base_url('blog.php') ?>" class="hover:text-white">Blog</a></li>
        <li><a href="<?= base_url('guias.php') ?>" class="hover:text-white">Central de ajuda</a></li>
        <li><a href="<?= base_url('planos.php') ?>" class="hover:text-white">Planos</a></li>
        <li><a href="<?= base_url('fale-conosco.php') ?>" class="hover:text-white">Fale conosco</a></li>
      </ul>
    </div>
    <div>
      <p class="mb-3 text-sm font-semibold text-white">Legal</p>
      <ul class="space-y-2 text-sm text-white/60">
        <li><a href="<?= base_url('termos-de-uso.php') ?>" class="hover:text-white">Termos de uso</a></li>
        <li><a href="<?= base_url('politica-de-privacidade.php') ?>" class="hover:text-white">Política de privacidade</a></li>
      </ul>
    </div>
  </div>
  <div class="border-t border-white/10 px-4 py-4 text-center text-xs text-white/50">
    &copy; <?= date('Y') ?> Habitou Imóveis. Todos os direitos reservados.
  </div>
</footer>

<div id="compare-bar" class="fixed inset-x-0 bottom-0 z-40 hidden border-t border-brand-border bg-white shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
  <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
    <div id="compare-items" class="flex flex-1 items-center gap-2 overflow-x-auto"></div>
    <button id="compare-clear" class="text-xs text-brand-text-secondary hover:underline">Limpar</button>
    <a id="compare-link" href="<?= base_url('comparar.php') ?>" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Comparar</a>
  </div>
</div>

<script>const APP_BASE = <?= json_encode(rtrim(base_url('/'), '/') . '/') ?>;</script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script src="<?= base_url('assets/js/cidades.js') ?>"></script>
<script src="<?= base_url('assets/js/location-picker.js') ?>"></script>
<script src="<?= base_url('assets/js/topbar.js') ?>"></script>
</body>
</html>
