<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$featured = get_featured_properties(6);
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];

$heroMain = $featured[0] ?? null;
$heroMini = array_slice($featured, 1, 3);

$heroCity = db()->query(
    'SELECT c.id, c.name, c.slug, c.state_code, COUNT(p.id) AS total
     FROM cities c LEFT JOIN properties p ON p.city_id = c.id AND p.status = "PUBLISHED"
     GROUP BY c.id ORDER BY total DESC LIMIT 1'
)->fetch();
$heroCityLabel = $heroCity ? $heroCity['name'] . ' (' . $heroCity['state_code'] . ')' : null;
$heroNeighborhoods = [];
if ($heroCity) {
    $stmtN = db()->prepare('SELECT name, slug FROM neighborhoods WHERE city_id = ? ORDER BY name LIMIT 5');
    $stmtN->execute([$heroCity['id']]);
    $heroNeighborhoods = $stmtN->fetchAll();
}

$stats = [
    'imoveis' => (int) db()->query('SELECT COUNT(*) FROM properties WHERE status = "PUBLISHED"')->fetchColumn(),
    'imobiliarias' => (int) db()->query('SELECT COUNT(*) FROM agencies WHERE status = "ACTIVE"')->fetchColumn(),
    'cidades' => (int) db()->query('SELECT COUNT(DISTINCT city_id) FROM properties WHERE status = "PUBLISHED"')->fetchColumn(),
];

// Contagem real de imóveis publicados por tipo, para a seção "Buscar por tipo".
$typeCountsStmt = db()->query('SELECT property_type, COUNT(*) AS total FROM properties WHERE status = "PUBLISHED" GROUP BY property_type');
$typeCounts = array_fill_keys(array_keys(PROPERTY_TYPE_LABEL), 0);
foreach ($typeCountsStmt->fetchAll() as $row) {
    $typeCounts[$row['property_type']] = (int) $row['total'];
}
arsort($typeCounts);

// Imóveis da cidade mais ativa do momento (heroCity, já usada como padrão do
// formulário de busca) — alimenta a seção "Imóveis em {cidade}" mais abaixo.
// Nacional: mostra sempre a cidade mais movimentada, seja qual for, em vez
// de cidades fixas.
$heroCityProperties = $heroCity ? get_properties_by_city($heroCity['slug'], 3) : [];

$faqs = [
    ['Como funciona o aluguel sem fiador?', 'O aluguel sem fiador funciona através de seguros fiança ou caução. Ao alugar um imóvel pela Habitou Imóveis, você pode verificar com o anunciante quais modalidades ele aceita, dispensando a necessidade de um fiador tradicional.'],
    ['Quais documentos são necessários para alugar online?', 'Normalmente RG, CPF, comprovante de renda e comprovante de residência. Cada anunciante pode pedir documentos adicionais — confira as condições diretamente com ele pela página do imóvel.'],
    ['É seguro alugar imóveis pela internet?', 'Sim. Anúncios de imobiliárias e corretores passam por verificação de CRECI, e você conversa diretamente com o anunciante pelo telefone, e-mail ou WhatsApp informado no anúncio.'],
    ['Como comparar preços de imóveis online?', 'Use os filtros de tipo, bairro e faixa de preço na busca, e a ferramenta "Comparar imóveis" para colocar até 4 anúncios lado a lado.'],
    ['Quais cuidados tomar ao comprar um imóvel online?', 'Confira a documentação do imóvel, o CRECI do anunciante e sempre visite o imóvel pessoalmente antes de fechar negócio.'],
    ['Posso visitar o imóvel antes de finalizar a compra online?', 'Sim, e recomendamos sempre visitar. Combine a visita diretamente com o anunciante pelos contatos informados no anúncio.'],
];

$pageTitle = APP_NAME . ' — Apartamentos, casas e terrenos em todo o Brasil';
$pageDescription = 'Encontre apartamentos, casas e terrenos para comprar ou alugar em todo o Brasil. Anuncie seu imóvel ou encontre imobiliárias e corretores de confiança.';
require __DIR__ . '/includes/header.php';
?>

<section class="relative border-b border-brand-border bg-gradient-to-b from-brand-bg-subtle to-white py-12 sm:py-16">
  <div class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block" aria-hidden="true">
    <div class="absolute -left-10 top-6 h-28 w-28 rounded-[2rem] border-2 border-brand-light/40"></div>
    <div class="absolute left-24 top-0 h-20 w-20 rounded-[1.5rem] bg-brand-primary/10"></div>
    <div class="absolute -left-4 bottom-10 h-24 w-24 rounded-[1.5rem] border-2 border-brand-primary/25"></div>
    <div class="absolute left-28 bottom-0 h-16 w-16 rounded-2xl bg-brand-light/10"></div>
    <div class="absolute right-4 top-8 h-20 w-20 rounded-2xl border-2 border-brand-light/30"></div>
    <div class="absolute right-24 bottom-6 h-24 w-24 rounded-[1.5rem] bg-brand-primary/10"></div>
  </div>

  <div class="relative mx-auto grid max-w-[1800px] gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-6 lg:px-8">
    <div>
      <span class="inline-flex items-center gap-2 rounded-full border border-brand-border bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-brand-text-secondary">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-green"></span> O portal de imóveis do Brasil
      </span>
      <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-brand-text sm:text-4xl lg:text-[2.6rem]">
        Encontre seu <span class="text-brand-primary">próximo lar</span> em qualquer lugar do Brasil.
      </h1>
      <p class="mt-3 max-w-xl text-brand-text-secondary">
        Apartamentos, casas e terrenos verificados em todo o país.
      </p>

      <form action="<?= base_url('imoveis.php') ?>" method="get" class="mt-7 rounded-3xl border border-brand-border bg-white p-4 shadow-lg">
        <div class="mb-3 flex gap-1">
          <label class="hero-transacao-tab">
            <input type="radio" name="transacao" value="comprar" checked>
            <span>Comprar</span>
          </label>
          <label class="hero-transacao-tab">
            <input type="radio" name="transacao" value="alugar">
            <span>Alugar</span>
          </label>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
          <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-text-secondary">Tipo</label>
            <select name="tipo" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm">
              <option value="">Qualquer tipo</option>
              <?php foreach (PROPERTY_TYPE_SLUG as $type => $slug): ?>
                <option value="<?= e($slug) ?>"><?= e(PROPERTY_TYPE_LABEL[$type]) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-text-secondary">Cidade</label>
            <div class="cidade-pill cidade-pill--field" id="hero-cidade-pill">
              <button type="button" class="cidade-pill-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" class="shrink-0 text-brand-text-secondary"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span class="cidade-pill-label">Todas as cidades</span>
              </button>
              <input type="hidden" name="cidade_nome" class="cidade-hidden-input" value="<?= e($heroCityLabel ?? '') ?>">
              <div class="cidade-dropdown">
                <input type="text" class="cidade-busca-input" placeholder="Digite o nome da cidade..." autocomplete="off">
                <div class="cidade-sugestoes"></div>
              </div>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-brand-text-secondary">Valor</label>
            <select name="precoMax" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm">
              <option value="">Qualquer valor</option>
              <option value="300000">Até R$ 300 mil</option>
              <option value="600000">Até R$ 600 mil</option>
              <option value="1000000">Até R$ 1 milhão</option>
              <option value="2000000">Até R$ 2 milhões</option>
            </select>
          </div>
          <button type="submit" class="flex h-[42px] w-full items-center justify-center rounded-xl bg-brand-green text-white transition hover:bg-brand-green-hover sm:w-[42px]" aria-label="Buscar imóveis">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
          </button>
        </div>

        <?php if ($heroNeighborhoods): ?>
          <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-brand-border pt-4">
            <span class="text-xs font-medium text-brand-text-secondary">Bairros populares:</span>
            <?php foreach ($heroNeighborhoods as $n): ?>
              <a href="<?= base_url('cidade.php?slug=' . $heroCity['slug'] . '&bairro=' . e($n['slug'])) ?>" class="rounded-full border border-brand-border px-3 py-1 text-xs font-medium hover:border-brand-primary hover:text-brand-primary"><?= e($n['name']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="mt-3 flex flex-wrap items-center gap-4 text-xs font-medium">
          <a href="<?= base_url('imoveis.php') ?>" class="flex items-center gap-1.5 text-brand-primary hover:underline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
            Filtros avançados
          </a>
          <span class="text-brand-border">|</span>
          <a href="#buscar-codigo" id="buscar-codigo-link" class="flex items-center gap-1.5 text-brand-primary hover:underline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h2M7 13h6"/></svg>
            Buscar por código
          </a>
        </div>
        <div id="buscar-codigo" class="mt-3 hidden gap-2 border-t border-brand-border pt-3">
          <input type="text" name="q" placeholder="Ex.: HB-0042" class="w-full rounded-xl border border-brand-border px-3 py-2 text-sm">
        </div>
      </form>

      <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-xs font-medium text-brand-text-secondary">
        <span class="flex items-center gap-1.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Anúncios verificados</span>
        <span class="flex items-center gap-1.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Imobiliárias parceiras</span>
        <span class="flex items-center gap-1.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Sem taxa para buscar</span>
      </div>
    </div>

    <?php if ($heroMain): ?>
      <?php
      $heroPrice = $heroMain['listing_type'] === 'RENT' ? ($heroMain['price_rent'] ?? null) : ($heroMain['price_sale'] ?? null);
      $heroMeta = array_filter([
          !empty($heroMain['bedrooms']) ? (int) $heroMain['bedrooms'] . ' qts' : null,
          !empty($heroMain['total_area']) ? (int) $heroMain['total_area'] . 'm²' : null,
          !empty($heroMain['parking_spaces']) ? (int) $heroMain['parking_spaces'] . ' vagas' : null,
      ]);
      ?>
      <div class="hidden lg:block">
        <div class="relative overflow-hidden rounded-3xl border border-brand-border bg-white shadow-xl">
          <span class="absolute left-3 top-3 z-10 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-brand-text shadow">✨ Destaque da semana</span>
          <a href="<?= e(property_href($heroMain)) ?>" class="relative block aspect-[4/3] w-full bg-brand-bg-subtle">
            <?php if (!empty($heroMain['image_url'])): ?>
              <img src="<?= e($heroMain['image_url']) ?>" alt="<?= e($heroMain['title']) ?>" class="h-full w-full object-cover">
            <?php endif; ?>
          </a>
          <div class="p-4">
            <p class="flex items-center gap-1 text-xs text-brand-text-secondary">
              <?= e(trim((!empty($heroMain['neighborhood_name']) ? $heroMain['neighborhood_name'] . ' · ' : '') . $heroMain['city_name'])) ?>
            </p>
            <a href="<?= e(property_href($heroMain)) ?>" class="mt-1 block text-sm font-semibold text-brand-text"><?= e($heroMain['title']) ?></a>
            <?php if ($heroMeta): ?><p class="mt-1 text-sm text-brand-text-secondary"><?= e(implode(' · ', $heroMeta)) ?></p><?php endif; ?>
            <p class="mt-2 text-base font-bold text-brand-text"><?= format_currency_brl($heroPrice) ?></p>
          </div>
        </div>

        <?php if ($heroMini): ?>
          <div class="mt-3 grid grid-cols-3 gap-3">
            <?php foreach ($heroMini as $mini): $miniPrice = $mini['listing_type'] === 'RENT' ? ($mini['price_rent'] ?? null) : ($mini['price_sale'] ?? null); ?>
              <a href="<?= e(property_href($mini)) ?>" class="group relative block aspect-square overflow-hidden rounded-2xl border border-brand-border bg-brand-bg-subtle">
                <?php if (!empty($mini['image_url'])): ?>
                  <img src="<?= e($mini['image_url']) ?>" alt="<?= e($mini['title']) ?>" class="h-full w-full object-cover transition group-hover:scale-105">
                <?php endif; ?>
                <span class="absolute bottom-2 left-2 rounded-full bg-white/95 px-2 py-0.5 text-[11px] font-bold text-brand-text shadow"><?= format_price_short($miniPrice, $mini['listing_type'] === 'RENT') ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.hero-transacao-tab{display:inline-flex;flex:1}
.hero-transacao-tab input{position:absolute;opacity:0;width:0;height:0}
.hero-transacao-tab span{display:block;width:100%;text-align:center;border-radius:10px;padding:8px 12px;font-size:13.5px;font-weight:600;color:#717171;cursor:pointer;transition:.15s}
.hero-transacao-tab input:checked + span{background:#F7F7F7;color:#222222}
</style>
<script>
document.getElementById('buscar-codigo-link')?.addEventListener('click', function (e) {
  e.preventDefault();
  document.getElementById('buscar-codigo').classList.toggle('hidden');
});
</script>

<?php if ($stats['imoveis'] > 0): ?>
<section class="border-b border-brand-border bg-white py-10">
  <div class="mx-auto grid max-w-[1800px] grid-cols-2 gap-6 px-4 text-center sm:px-6 lg:grid-cols-4 lg:px-8">
    <div><p class="text-2xl font-extrabold text-brand-text sm:text-3xl"><?= number_format($stats['imoveis'], 0, ',', '.') ?></p><p class="mt-1 text-sm text-brand-text-secondary">imóveis anunciados</p></div>
    <div><p class="text-2xl font-extrabold text-brand-text sm:text-3xl"><?= number_format($stats['imobiliarias'], 0, ',', '.') ?></p><p class="mt-1 text-sm text-brand-text-secondary">imobiliárias parceiras</p></div>
    <div><p class="text-2xl font-extrabold text-brand-text sm:text-3xl"><?= number_format($stats['cidades'], 0, ',', '.') ?></p><p class="mt-1 text-sm text-brand-text-secondary">cidades atendidas</p></div>
    <div><p class="text-2xl font-extrabold text-brand-text sm:text-3xl">100%</p><p class="mt-1 text-sm text-brand-text-secondary">corretores com CRECI verificado</p></div>
  </div>
</section>
<?php endif; ?>

<section class="border-t border-brand-border bg-brand-bg-subtle py-12">
  <div class="mx-auto max-w-[1800px] px-4 sm:px-6 lg:px-8">
    <p class="text-sm font-medium text-brand-text-secondary">O que você procura</p>
    <h2 class="mt-1 text-2xl font-bold text-brand-text">Buscar por tipo</h2>
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
      <?php foreach ($typeCounts as $type => $count): ?>
        <a href="<?= base_url('imoveis.php?tipo=' . PROPERTY_TYPE_SLUG[$type]) ?>" class="flex flex-col items-center gap-2 rounded-2xl border border-brand-border bg-white p-4 text-center transition hover:border-brand-primary">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-brand-text"><?= CATEGORY_ICONS[$type] ?></svg>
          <span class="text-sm font-semibold text-brand-text"><?= e(PROPERTY_TYPE_LABEL[$type]) ?></span>
          <span class="text-xs text-brand-text-secondary"><?= number_format($count, 0, ',', '.') ?> anúncio<?= $count === 1 ? '' : 's' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="mx-auto max-w-[1800px] px-4 py-12 sm:px-6 lg:px-8">
  <div class="mb-4 flex items-center justify-between">
    <h2 class="text-xl font-bold">Imóveis em destaque</h2>
    <a href="<?= base_url('imoveis.php') ?>" class="text-sm font-medium text-brand-primary hover:underline">Ver todos</a>
  </div>
  <?php render_property_grid($featured, $favoriteIds); ?>

  <?php if ($heroCity && $heroCityProperties): ?>
    <div class="mt-12">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">Imóveis em <?= e($heroCity['name']) ?></h2>
        <a href="<?= base_url('cidade.php?slug=' . $heroCity['slug']) ?>" class="text-sm font-medium text-brand-primary hover:underline">Ver mais</a>
      </div>
      <?php render_property_grid($heroCityProperties, $favoriteIds); ?>
    </div>
  <?php endif; ?>
</section>

<section class="relative overflow-hidden bg-brand-navy py-16">
  <div class="mx-auto max-w-[1800px] px-4 sm:px-6 lg:px-8">
    <p class="text-xs font-semibold uppercase tracking-wide text-brand-light">Para imobiliárias</p>
    <h2 class="mt-2 max-w-lg text-3xl font-extrabold leading-tight text-white sm:text-4xl">Seus imóveis.<br>Mais longe.</h2>
    <p class="mt-3 max-w-md text-white/70">Publique seus anúncios, receba leads qualificados direto no WhatsApp e acompanhe o desempenho dos seus imóveis em um só lugar.</p>
    <ul class="mt-6 flex max-w-xl flex-col gap-2.5 text-sm text-white/85">
      <li class="flex items-center gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Leads qualificados direto no WhatsApp</li>
      <li class="flex items-center gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Painel com o desempenho dos seus anúncios</li>
      <li class="flex items-center gap-2"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>Perfil de imobiliária com todos os seus imóveis</li>
    </ul>
    <div class="mt-7 flex flex-wrap items-center gap-5">
      <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-green px-6 py-3 text-sm font-semibold text-white hover:bg-brand-green-hover">Anunciar na Habitou Imóveis</a>
      <a href="<?= base_url('planos.php') ?>" class="text-sm font-semibold text-white hover:underline">Ver planos →</a>
    </div>
  </div>
</section>

<section class="mx-auto max-w-[1800px] px-4 py-16 sm:px-6 lg:px-8">
  <div class="grid gap-10 lg:grid-cols-[minmax(0,320px)_1fr]">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Dúvidas</p>
      <h2 class="mt-2 text-2xl font-bold text-brand-text">Dúvidas frequentes</h2>
      <p class="mt-2 text-brand-text-secondary">Não achou o que procura? Fale com nosso time e tire todas as suas dúvidas.</p>
      <a href="<?= base_url('fale-conosco.php') ?>" class="mt-4 inline-block rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Falar com consultor →</a>
    </div>
    <div class="divide-y divide-brand-border border-t border-brand-border">
      <?php foreach ($faqs as $i => [$q, $a]): ?>
        <details class="group py-4" <?= $i === 0 ? 'open' : '' ?>>
          <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-brand-text">
            <?= e($q) ?>
            <svg class="ml-3 shrink-0 transition group-open:rotate-180" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-text-secondary"><?= e($a) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
