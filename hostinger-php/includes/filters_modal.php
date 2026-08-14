<?php
/**
 * Filtros da página de resultados como um card flutuante centralizado
 * (estilo Airbnb), aberto pelo botão "Filtros" da barra superior — em vez
 * de uma coluna fixa na lateral. Cidade e transação continuam só na barra
 * superior; aqui ficam tipo, preço, quartos/banheiros/vagas, bairro (quando
 * aplicável) e ordenação.
 */
function render_filters_modal(string $action, array $get, array $neighborhoods = []): void
{
    $val = fn($key) => e($get[$key] ?? '');
    $currentType = $get['tipo'] ?? '';
    $countUrl = base_url('actions/contar_imoveis.php');
    ?>
    <div id="filtros-modal-overlay" class="fixed inset-0 z-[300] hidden items-center justify-center bg-black/50 p-4">
      <div id="filtros-modal-card" class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between border-b border-brand-border px-5 py-4">
          <button type="button" id="filtros-modal-close" class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-brand-bg-subtle" aria-label="Fechar">&times;</button>
          <h2 class="text-base font-bold">Filtros</h2>
          <span class="w-8"></span>
        </div>

        <form method="get" action="<?= e($action) ?>" id="filtros-modal-form" data-count-url="<?= e($countUrl) ?>" class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
          <?php foreach ($get as $k => $v): if (!in_array($k, ['q','tipo','bairro','precoMin','precoMax','quartos','suites','banheiros','vagas','areaMin','areaMax','caracteristicas','ordenar','pagina'], true)): ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endif; endforeach; ?>

          <div class="mb-6">
            <label class="mb-2 block text-xs font-medium text-brand-text-secondary">Buscar</label>
            <input type="search" name="q" value="<?= $val('q') ?>" placeholder="Título, código, bairro..." class="js-filtros-live w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
          </div>

          <div class="mb-6 border-t border-brand-border pt-5">
            <h3 class="mb-3 text-sm font-semibold">Tipo de imóvel</h3>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
              <label class="filtro-tipo-card">
                <input type="radio" name="tipo" value="" <?= $currentType === '' ? 'checked' : '' ?> class="js-filtros-live">
                <span>
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/></svg>
                  <em>Qualquer tipo</em>
                </span>
              </label>
              <?php foreach (PROPERTY_TYPE_SLUG as $type => $slug): ?>
                <label class="filtro-tipo-card">
                  <input type="radio" name="tipo" value="<?= e($slug) ?>" <?= $currentType === $slug ? 'checked' : '' ?> class="js-filtros-live">
                  <span>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><?= CATEGORY_ICONS[$type] ?></svg>
                    <em><?= e(PROPERTY_TYPE_LABEL[$type]) ?></em>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <?php if (!empty($neighborhoods)): ?>
            <div class="mb-6 border-t border-brand-border pt-5">
              <h3 class="mb-3 text-sm font-semibold">Bairro</h3>
              <select name="bairro" class="js-filtros-live w-full rounded-lg border border-brand-border bg-white px-3 py-2.5 text-sm">
                <option value="">Todos</option>
                <?php foreach ($neighborhoods as $n): ?>
                  <option value="<?= e($n['slug']) ?>" <?= ($get['bairro'] ?? '') === $n['slug'] ? 'selected' : '' ?>><?= e($n['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-6 border-t border-brand-border pt-5">
            <h3 class="mb-1 text-sm font-semibold">Faixa de preço</h3>
            <p class="mb-3 text-xs text-brand-text-secondary">Valor de venda ou aluguel, conforme a transação escolhida.</p>
            <div class="flex items-center gap-3">
              <div class="flex-1">
                <label class="mb-1 block text-[11px] font-medium text-brand-text-secondary">Mínimo</label>
                <input type="number" min="0" name="precoMin" value="<?= $val('precoMin') ?>" placeholder="R$" class="js-filtros-live w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
              </div>
              <span class="mt-4 text-brand-text-secondary">—</span>
              <div class="flex-1">
                <label class="mb-1 block text-[11px] font-medium text-brand-text-secondary">Máximo</label>
                <input type="number" min="0" name="precoMax" value="<?= $val('precoMax') ?>" placeholder="R$" class="js-filtros-live w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
              </div>
            </div>
          </div>

          <div class="mb-6 border-t border-brand-border pt-5">
            <h3 class="mb-3 text-sm font-semibold">Quartos, banheiros e vagas</h3>
            <div class="flex flex-col divide-y divide-brand-border">
              <?php
              $steppers = [
                  'quartos' => 'Quartos',
                  'banheiros' => 'Banheiros',
                  'suites' => 'Suítes',
                  'vagas' => 'Vagas de garagem',
              ];
              foreach ($steppers as $field => $label):
                  $v = (int) ($get[$field] ?? 0);
              ?>
                <div class="js-stepper flex items-center justify-between py-3" data-field="<?= e($field) ?>">
                  <span class="text-sm"><?= e($label) ?></span>
                  <div class="flex items-center gap-3">
                    <button type="button" class="js-stepper-dec flex h-8 w-8 items-center justify-center rounded-full border border-brand-border text-lg leading-none hover:border-brand-primary disabled:cursor-not-allowed disabled:opacity-30" <?= $v <= 0 ? 'disabled' : '' ?>>&minus;</button>
                    <span class="js-stepper-value w-6 shrink-0 text-center text-sm"><?= $v > 0 ? $v . '+' : '0' ?></span>
                    <button type="button" class="js-stepper-inc flex h-8 w-8 items-center justify-center rounded-full border border-brand-border text-lg leading-none hover:border-brand-primary">+</button>
                    <input type="hidden" name="<?= e($field) ?>" value="<?= $v ?: '' ?>" class="js-stepper-input js-filtros-live">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-6 border-t border-brand-border pt-5">
            <h3 class="mb-3 text-sm font-semibold">Área útil (m²)</h3>
            <div class="flex items-center gap-3">
              <div class="flex-1">
                <label class="mb-1 block text-[11px] font-medium text-brand-text-secondary">Mínimo</label>
                <input type="number" min="0" name="areaMin" value="<?= $val('areaMin') ?>" placeholder="m²" class="js-filtros-live w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
              </div>
              <span class="mt-4 text-brand-text-secondary">—</span>
              <div class="flex-1">
                <label class="mb-1 block text-[11px] font-medium text-brand-text-secondary">Máximo</label>
                <input type="number" min="0" name="areaMax" value="<?= $val('areaMax') ?>" placeholder="m²" class="js-filtros-live w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
              </div>
            </div>
          </div>

          <div class="mb-6 border-t border-brand-border pt-5">
            <h3 class="mb-3 text-sm font-semibold">Comodidades</h3>
            <div class="flex flex-wrap gap-2">
              <?php $selectedFeatures = (array) ($get['caracteristicas'] ?? []); ?>
              <?php foreach (COMMON_FEATURES as $feature): ?>
                <label class="filtro-pill">
                  <input type="checkbox" name="caracteristicas[]" value="<?= e($feature) ?>" <?= in_array($feature, $selectedFeatures, true) ? 'checked' : '' ?> class="js-filtros-live">
                  <span><?= e($feature) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-2 border-t border-brand-border pt-5">
            <h3 class="mb-3 text-sm font-semibold">Ordenar por</h3>
            <select name="ordenar" class="js-filtros-live w-full rounded-lg border border-brand-border bg-white px-3 py-2.5 text-sm">
              <?php foreach (SORT_OPTIONS as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= ($get['ordenar'] ?? 'recentes') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <div class="flex shrink-0 items-center justify-between border-t border-brand-border px-5 py-4">
          <button type="button" id="filtros-modal-clear" class="text-sm font-semibold underline hover:text-brand-primary">Limpar filtros</button>
          <button type="submit" form="filtros-modal-form" id="filtros-modal-submit" class="rounded-lg bg-brand-primary px-5 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Mostrar imóveis</button>
        </div>
      </div>
    </div>
    <style>
    .filtro-pill{display:inline-flex}
    .filtro-pill input{position:absolute;opacity:0;width:0;height:0}
    .filtro-pill span{border:1px solid #DDDDDD;border-radius:999px;padding:8px 14px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.15s}
    .filtro-pill input:checked + span{border-color:#222222;background:#222222;color:#fff}
    .filtro-pill:hover span{border-color:#222222}
    .filtro-tipo-card{display:block}
    .filtro-tipo-card input{position:absolute;opacity:0;width:0;height:0}
    .filtro-tipo-card span{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;border:1px solid #DDDDDD;border-radius:16px;padding:14px 6px;font-size:12px;font-weight:600;text-align:center;cursor:pointer;transition:.15s;color:#222}
    .filtro-tipo-card em{font-style:normal;line-height:1.2}
    .filtro-tipo-card input:checked + span{border-color:#222222;border-width:2px;background:#F7F7F7}
    .filtro-tipo-card:hover span{border-color:#222222}
    </style>
    <script src="<?= asset_url('assets/js/filters-modal.js') ?>"></script>
    <?php
}
