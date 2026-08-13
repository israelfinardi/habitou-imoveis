<?php
/**
 * Renderiza a barra de filtros em coluna única (sidebar vertical, ~15% da
 * largura da página de resultados). $action é a URL base (imoveis.php ou
 * cidade.php?slug=...). $showCityType controla se os selects de
 * cidade/transação/tipo aparecem (na busca geral) ou não (já fixos na URL).
 */
function render_filters_form(string $action, array $get, bool $showCityType, array $neighborhoods = []): void
{
    $val = fn($key) => e($get[$key] ?? '');
    ?>
    <form method="get" action="<?= e($action) ?>" class="flex flex-col gap-4 rounded-xl border border-brand-border bg-white p-4" id="filters-form">
      <?php foreach ($get as $k => $v): if (!in_array($k, ['q','transacao','cidade','tipo','bairro','precoMin','precoMax','quartos','suites','banheiros','vagas','areaMin','areaMax','ordenar','pagina'], true)): ?>
        <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
      <?php endif; endforeach; ?>

      <h2 class="text-sm font-semibold text-brand-text">Filtros</h2>

      <div>
        <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Buscar</label>
        <input type="search" name="q" value="<?= $val('q') ?>" placeholder="Título, código, bairro..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>

      <?php if ($showCityType): ?>
        <div>
          <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Transação</label>
          <select name="transacao" class="js-auto-submit w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="comprar" <?= ($get['transacao'] ?? '') === 'comprar' ? 'selected' : '' ?>>Comprar</option>
            <option value="alugar" <?= ($get['transacao'] ?? '') === 'alugar' ? 'selected' : '' ?>>Alugar</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Cidade</label>
          <select name="cidade" class="js-auto-submit w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
            <option value="">Todas</option>
            <?php foreach (FEATURED_CITIES as $c): ?>
              <option value="<?= e($c['slug']) ?>" <?= ($get['cidade'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Tipo</label>
          <select name="tipo" class="js-auto-submit w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
            <option value="">Todos</option>
            <?php foreach (PROPERTY_TYPE_SLUG as $type => $slug): ?>
              <option value="<?= e($slug) ?>" <?= ($get['tipo'] ?? '') === $slug ? 'selected' : '' ?>><?= e(PROPERTY_TYPE_LABEL[$type]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php elseif (!empty($neighborhoods)): ?>
        <div>
          <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Bairro</label>
          <select name="bairro" class="js-auto-submit w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
            <option value="">Todos</option>
            <?php foreach ($neighborhoods as $n): ?>
              <option value="<?= e($n['slug']) ?>" <?= ($get['bairro'] ?? '') === $n['slug'] ? 'selected' : '' ?>><?= e($n['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div>
        <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Ordenar</label>
        <select name="ordenar" class="js-auto-submit w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
          <?php foreach (SORT_OPTIONS as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($get['ordenar'] ?? 'recentes') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex flex-col gap-3 border-t border-brand-border pt-4">
        <?php
        $numFields = [
            'precoMin' => 'Preço mín.', 'precoMax' => 'Preço máx.',
            'quartos' => 'Quartos (mín.)', 'suites' => 'Suítes (mín.)',
            'banheiros' => 'Banheiros (mín.)', 'vagas' => 'Vagas (mín.)',
            'areaMin' => 'Área mín. (m²)', 'areaMax' => 'Área máx. (m²)',
        ];
        ?>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach ($numFields as $field => $label): ?>
            <div>
              <label class="mb-1 block text-xs font-medium text-brand-text-secondary"><?= e($label) ?></label>
              <input type="number" min="0" name="<?= e($field) ?>" value="<?= $val($field) ?>" class="w-full rounded-lg border border-brand-border px-2 py-2 text-sm">
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="w-full rounded-lg bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Filtrar</button>
    </form>
    <script>
    document.querySelectorAll('.js-auto-submit').forEach(function (el) {
      el.addEventListener('change', function () { document.getElementById('filters-form').submit(); });
    });
    </script>
    <?php
}
