<?php
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$cidade = trim($_GET['cidade'] ?? '');
$onlyVerified = !empty($_GET['verificadas']);
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$pageSize = 12;
$offset = ($page - 1) * $pageSize;

$where = ["status = 'ACTIVE'"];
$args = [];
if ($q) {
    $where[] = 'name LIKE ?';
    $args[] = '%' . $q . '%';
}
if ($cidade) {
    $where[] = 'city = ?';
    $args[] = $cidade;
}
if ($onlyVerified) {
    $where[] = "cnpj IS NOT NULL AND cnpj != ''";
}
$whereSql = implode(' AND ', $where);

$pdo = db();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agencies WHERE $whereSql");
$stmt->execute($args);
$total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT a.*, (SELECT COUNT(*) FROM properties p WHERE p.agency_id = a.id AND p.status = 'PUBLISHED') AS property_count
    FROM agencies a WHERE $whereSql ORDER BY property_count DESC, name LIMIT $offset, $pageSize");
$stmt->execute($args);
$agencies = $stmt->fetchAll();
$totalPages = max(1, (int) ceil($total / $pageSize));

// Dados extras por imobiliária (cidades atendidas, bairros mais frequentes,
// fotos recentes) — buscados em lote pras <= 12 imobiliárias da página
// atual, em vez de uma consulta por card (evita N+1).
$agencyIds = array_column($agencies, 'id');
$cityCounts = [];
$neighborhoodTags = [];
$agencyPhotos = [];
if ($agencyIds) {
    $placeholders = implode(',', array_fill(0, count($agencyIds), '?'));

    $stmt = $pdo->prepare("SELECT agency_id, COUNT(DISTINCT city_id) AS c FROM properties
        WHERE agency_id IN ($placeholders) AND status = 'PUBLISHED' GROUP BY agency_id");
    $stmt->execute($agencyIds);
    foreach ($stmt->fetchAll() as $row) {
        $cityCounts[(int) $row['agency_id']] = (int) $row['c'];
    }

    $stmt = $pdo->prepare("SELECT p.agency_id, n.name, COUNT(*) AS c FROM properties p
        JOIN neighborhoods n ON n.id = p.neighborhood_id
        WHERE p.agency_id IN ($placeholders) AND p.status = 'PUBLISHED'
        GROUP BY p.agency_id, n.name ORDER BY c DESC");
    $stmt->execute($agencyIds);
    foreach ($stmt->fetchAll() as $row) {
        $aid = (int) $row['agency_id'];
        $neighborhoodTags[$aid] ??= [];
        if (count($neighborhoodTags[$aid]) < 2) {
            $neighborhoodTags[$aid][] = $row['name'];
        }
    }

    $stmt = $pdo->prepare("SELECT p.agency_id, pi.url FROM properties p
        JOIN property_images pi ON pi.property_id = p.id
        WHERE p.agency_id IN ($placeholders) AND p.status = 'PUBLISHED' AND pi.is_primary = 1
        ORDER BY p.published_at DESC");
    $stmt->execute($agencyIds);
    foreach ($stmt->fetchAll() as $row) {
        $aid = (int) $row['agency_id'];
        $agencyPhotos[$aid] ??= [];
        if (count($agencyPhotos[$aid]) < 3) {
            $agencyPhotos[$aid][] = $row['url'];
        }
    }
}

// Estatísticas reais da faixa (nada de números fixos de mockup) e cidades
// mais frequentes entre as imobiliárias ativas, para os chips de atalho.
$stats = [
    'imobiliarias' => (int) $pdo->query("SELECT COUNT(*) FROM agencies WHERE status = 'ACTIVE'")->fetchColumn(),
    'imoveis' => (int) $pdo->query("SELECT COUNT(*) FROM properties p JOIN agencies a ON a.id = p.agency_id WHERE a.status = 'ACTIVE' AND p.status = 'PUBLISHED'")->fetchColumn(),
    'cidades' => (int) $pdo->query("SELECT COUNT(DISTINCT city) FROM agencies WHERE status = 'ACTIVE' AND city IS NOT NULL AND city != ''")->fetchColumn(),
    'corretores' => (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN agencies a ON a.id = u.agency_id WHERE a.status = 'ACTIVE' AND u.creci IS NOT NULL AND u.creci != ''")->fetchColumn(),
];
$popularCities = $pdo->query("SELECT city, COUNT(*) AS c FROM agencies WHERE status = 'ACTIVE' AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY c DESC LIMIT 5")->fetchAll();

$avatarPalette = ['#C1502E', '#0369A1', '#16A085', '#7C3AED', '#B45309', '#0F766E'];

$pageTitle = 'Imobiliárias e corretores';
$pageDescription = 'Conheça as imobiliárias e corretores parceiros da Habitou Imóveis em todo o Brasil.';
require __DIR__ . '/includes/header.php';
?>
<section class="relative border-b border-brand-border bg-gradient-to-b from-brand-bg-subtle to-white py-14 sm:py-16">
  <div class="mx-auto max-w-[1800px] px-4 text-center sm:px-6 lg:px-8">
    <span class="mb-4 inline-block rounded-md bg-brand-primary/10 px-2.5 py-1.5 text-[11px] font-bold uppercase tracking-wide text-brand-primary">Imobiliárias e corretores</span>
    <h1 class="mx-auto mb-3 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight text-brand-text sm:text-4xl">As <span class="text-brand-primary">melhores imobiliárias</span> de todo o Brasil.</h1>
    <p class="mx-auto max-w-xl text-sm text-brand-text-secondary sm:text-base">
      <strong class="font-semibold text-brand-text"><?= number_format($stats['imobiliarias'], 0, ',', '.') ?> imobiliárias</strong> cadastradas, com
      <strong class="font-semibold text-brand-text"><?= number_format($stats['imoveis'], 0, ',', '.') ?> imóveis ativos</strong> em
      <strong class="font-semibold text-brand-text"><?= number_format($stats['cidades'], 0, ',', '.') ?> cidades</strong>. Encontre quem entende do seu bairro.
    </p>

    <form method="get" class="mx-auto mt-7 w-full max-w-3xl rounded-2xl border border-brand-border bg-white p-3 shadow-lg">
      <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[1.4fr_1fr_auto]">
        <label class="flex h-12 items-center gap-2 rounded-xl bg-brand-bg-subtle px-3.5 focus-within:ring-2 focus-within:ring-brand-primary/30">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-brand-text-secondary"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar imobiliária pelo nome" class="flex-1 bg-transparent text-sm outline-none placeholder:text-brand-text-secondary">
        </label>
        <label class="flex h-12 items-center gap-2 rounded-xl bg-brand-bg-subtle px-3.5 focus-within:ring-2 focus-within:ring-brand-primary/30">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-brand-text-secondary"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
          <select name="cidade" class="w-full cursor-pointer appearance-none bg-transparent text-sm outline-none">
            <option value="">Todas as cidades</option>
            <?php foreach ($popularCities as $c): ?>
              <option value="<?= e($c['city']) ?>" <?= $cidade === $c['city'] ? 'selected' : '' ?>><?= e($c['city']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="inline-flex h-12 items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-brand-primary px-6 text-sm font-bold text-white hover:bg-brand-primary-hover">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
          Buscar
        </button>
      </div>
      <div class="flex flex-wrap items-center gap-2 pt-3">
        <button type="submit" name="verificadas" value="1" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium <?= $onlyVerified ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-brand-border text-brand-text-secondary hover:border-brand-primary hover:text-brand-primary' ?>">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          Somente verificadas
        </button>
        <?php foreach ($popularCities as $c): ?>
          <a href="<?= base_url('imobiliarias.php?' . http_build_query(['q' => $q, 'cidade' => $c['city']])) ?>" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium <?= $cidade === $c['city'] ? 'border-brand-primary bg-brand-primary/5 text-brand-primary' : 'border-brand-border text-brand-text-secondary hover:border-brand-primary hover:text-brand-primary' ?>"><?= e($c['city']) ?></a>
        <?php endforeach; ?>
        <?php if ($q || $cidade || $onlyVerified): ?>
          <a href="<?= base_url('imobiliarias.php') ?>" class="ml-auto inline-flex items-center gap-1.5 rounded-full border border-transparent px-3 py-1.5 text-xs font-medium text-brand-text-secondary/70 hover:text-red-500">Limpar filtros</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="mx-auto mt-6 grid max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4">
      <div class="px-2 py-2 text-center"><strong class="block text-2xl font-extrabold tracking-tight text-brand-primary sm:text-3xl"><?= number_format($stats['imobiliarias'], 0, ',', '.') ?></strong><span class="mt-1 block text-xs text-brand-text-secondary">imobiliárias ativas</span></div>
      <div class="px-2 py-2 text-center"><strong class="block text-2xl font-extrabold tracking-tight text-brand-primary sm:text-3xl"><?= number_format($stats['imoveis'], 0, ',', '.') ?></strong><span class="mt-1 block text-xs text-brand-text-secondary">imóveis anunciados</span></div>
      <div class="px-2 py-2 text-center"><strong class="block text-2xl font-extrabold tracking-tight text-brand-primary sm:text-3xl"><?= number_format($stats['cidades'], 0, ',', '.') ?></strong><span class="mt-1 block text-xs text-brand-text-secondary">cidades atendidas</span></div>
      <div class="px-2 py-2 text-center"><strong class="block text-2xl font-extrabold tracking-tight text-brand-primary sm:text-3xl"><?= number_format($stats['corretores'], 0, ',', '.') ?></strong><span class="mt-1 block text-xs text-brand-text-secondary">CRECI verificados</span></div>
    </div>
  </div>
</section>

<section class="bg-brand-bg-subtle px-4 py-10 sm:px-6 lg:px-8">
  <div class="mx-auto max-w-[1800px]">
    <?php if (empty($agencies)): ?>
      <div class="rounded-xl border border-dashed border-brand-border bg-white p-12 text-center text-brand-text-secondary">Nenhuma imobiliária encontrada com esses filtros.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($agencies as $a):
          $aid = (int) $a['id'];
          $years = max(0, (int) floor((time() - strtotime($a['created_at'] . ' UTC')) / (365 * 86400)));
          $isVerified = !empty($a['cnpj']);
          $color = $avatarPalette[$aid % count($avatarPalette)];
          $locationLabel = trim(($a['city'] ?: '') . ($a['state'] ? ', ' . $a['state'] : ''));
          $photos = $agencyPhotos[$aid] ?? [];
          $photosLeft = max(0, ($a['property_count'] ?? 0) - count($photos));
        ?>
          <article class="group flex flex-col gap-4 rounded-2xl border border-brand-border bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-primary hover:shadow-lg">
            <div class="flex items-start gap-3">
              <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-2xl font-bold text-white" style="background:<?= e($color) ?>">
                <?php if ($a['logo_url']): ?><img src="<?= e($a['logo_url']) ?>" class="h-full w-full object-cover" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($a['name'], 0, 1))) ?><?php endif; ?>
              </div>
              <div class="min-w-0 flex-1">
                <div class="mb-1 flex items-center gap-1.5">
                  <h3 class="truncate text-[17px] font-semibold leading-tight tracking-tight"><a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="hover:text-brand-primary"><?= e($a['name']) ?></a></h3>
                  <?php if ($isVerified): ?>
                    <span title="Verificada" class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-brand-green">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                  <?php endif; ?>
                </div>
                <?php if ($locationLabel): ?>
                  <div class="flex flex-wrap items-center gap-1.5 text-xs text-brand-text-secondary">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="truncate"><?= e($locationLabel) ?></span>
                    <?php if ($years > 0): ?><span aria-hidden="true">·</span><span><?= $years ?> <?= $years === 1 ? 'ano' : 'anos' ?></span><?php endif; ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($neighborhoodTags[$aid])): ?>
                  <div class="mt-2 flex flex-wrap gap-1.5">
                    <?php foreach ($neighborhoodTags[$aid] as $tag): ?>
                      <span class="rounded bg-brand-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-primary"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-2 border-y border-brand-border py-3 text-center">
              <div><strong class="block text-[17px] font-bold tracking-tight"><?= number_format($a['property_count'], 0, ',', '.') ?></strong><span class="mt-0.5 block text-[10px] text-brand-text-secondary">imóveis</span></div>
              <div><strong class="block text-[17px] font-bold tracking-tight"><?= $cityCounts[$aid] ?? 0 ?></strong><span class="mt-0.5 block text-[10px] text-brand-text-secondary">cidades</span></div>
              <div><strong class="block text-[17px] font-bold tracking-tight"><?= $years ?></strong><span class="mt-0.5 block text-[10px] text-brand-text-secondary">anos</span></div>
            </div>

            <?php if ($photos): ?>
              <div class="flex items-center gap-1.5">
                <?php foreach ($photos as $url): ?>
                  <div class="h-11 flex-1 overflow-hidden rounded-md border border-brand-border"><img src="<?= e($url) ?>" alt="" class="h-full w-full object-cover"></div>
                <?php endforeach; ?>
                <?php if ($photosLeft > 0): ?>
                  <div class="flex h-11 flex-1 items-center justify-center rounded-md bg-brand-bg-subtle text-[11px] font-semibold text-brand-text-secondary">+<?= $photosLeft ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-2">
              <a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="inline-flex h-9 items-center justify-center rounded-lg border border-brand-primary text-xs font-bold text-brand-primary hover:bg-brand-primary/5">Contatar</a>
              <a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-primary text-xs font-bold text-white hover:bg-brand-primary-hover">Ver imóveis</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php render_pagination($page, $totalPages, base_url('imobiliarias.php?' . http_build_query(['q' => $q, 'cidade' => $cidade, 'verificadas' => $onlyVerified ? 1 : '']))); ?>
    <?php endif; ?>
  </div>
</section>

<section class="relative overflow-hidden bg-brand-navy py-16">
  <div class="mx-auto max-w-[1800px] px-4 text-center sm:px-6 lg:px-8">
    <p class="text-xs font-semibold uppercase tracking-wide text-brand-light">Para imobiliárias</p>
    <h2 class="mx-auto mt-2 max-w-lg text-3xl font-extrabold leading-tight text-white sm:text-4xl">Sua imobiliária aqui.</h2>
    <p class="mx-auto mt-3 max-w-md text-white/70">Cadastre sua imobiliária, publique seus imóveis e apareça pra quem está procurando exatamente na sua região.</p>
    <div class="mt-7 flex flex-wrap items-center justify-center gap-5">
      <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="rounded-full bg-brand-green px-6 py-3 text-sm font-semibold text-white hover:bg-brand-green-hover">Cadastrar minha imobiliária</a>
      <a href="<?= base_url('planos.php') ?>" class="text-sm font-semibold text-white hover:underline">Ver planos →</a>
    </div>
  </div>
</section>

<section class="mx-auto max-w-[1800px] px-4 py-16 sm:px-6 lg:px-8">
  <div class="grid gap-10 lg:grid-cols-[minmax(0,320px)_1fr]">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-brand-primary">Dúvidas</p>
      <h2 class="mt-2 text-2xl font-bold text-brand-text">Perguntas frequentes</h2>
      <p class="mt-2 text-brand-text-secondary">Como funciona a verificação, a parceria com imobiliárias listadas e como cadastrar a sua.</p>
      <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="mt-4 inline-block rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Sua imobiliária aqui →</a>
    </div>
    <div class="divide-y divide-brand-border border-t border-brand-border">
      <?php
      $agencyFaqs = [
          ['Como funciona a verificação das imobiliárias?', 'Toda imobiliária cadastrada passa por aprovação do administrador do site antes de aparecer publicamente. O selo de verificada indica que a imobiliária informou o CNPJ no cadastro.'],
          ['Quanto custa listar minha imobiliária?', 'O cadastro em si é gratuito. A quantidade de imóveis publicados simultaneamente depende do plano contratado — veja as opções na página de planos.'],
          ['Como meus corretores aparecem no perfil da imobiliária?', 'Corretores vinculados à sua imobiliária (com CRECI cadastrado) aparecem automaticamente na página pública da imobiliária, junto com os imóveis que anunciarem.'],
          ['Posso mudar a cidade de atuação depois?', 'Sim, todos os dados da imobiliária — cidade, contato, descrição, redes sociais — podem ser editados a qualquer momento na guia de perfil da conta.'],
      ];
      foreach ($agencyFaqs as $i => [$faqQuestion, $faqAnswer]): ?>
        <details class="group py-4" <?= $i === 0 ? 'open' : '' ?>>
          <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-brand-text">
            <?= e($faqQuestion) ?>
            <svg class="ml-3 shrink-0 transition group-open:rotate-180" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-text-secondary"><?= e($faqAnswer) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
