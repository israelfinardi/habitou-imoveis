<?php
/**
 * Popula o banco com dados reais extraídos do site original (mesmos dados
 * usados na versão Next.js do projeto): imóveis, imobiliárias, planos e
 * conteúdo de blog/central de ajuda.
 *
 * Uso: php data/seed.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/constants.php';

$pdo = db();

$TYPE_MAP = [
    'Apartamento' => 'APARTMENT',
    'Casa' => 'HOUSE',
    'Terreno' => 'LAND',
    'Sala/Escritório' => 'COMMERCIAL_ROOM',
    'Loja' => 'STORE',
    'Galpão' => 'WAREHOUSE',
    'Imóvel Rural' => 'RURAL',
    'Outros Imóveis' => 'OTHER',
];

$VALID_CITIES = array_merge(array_column(FEATURED_CITIES, 'name'), ['Campo Alegre']);

function agency_slug_from_url(?string $url, string $name): string
{
    if ($url && preg_match('#/imobiliarias/([^/]+)#', $url, $m)) {
        return $m[1];
    }
    return slugify($name);
}

echo "Lendo dados extraídos...\n";
$properties = json_decode(file_get_contents(__DIR__ . '/properties.json'), true);
$properties = array_values(array_filter($properties, function ($p) use ($VALID_CITIES, $TYPE_MAP) {
    return $p['city'] && in_array($p['city'], $VALID_CITIES, true) && $p['type'] && isset($TYPE_MAP[$p['type']]);
}));
printf("%d imóveis válidos para importar.\n", count($properties));

// --- Planos --------------------------------------------------------------
echo "Criando planos...\n";
$plansData = [
    ['name' => 'Básico', 'slug' => 'basico', 'price' => 0, 'max_listings' => 3, 'description' => 'Ideal para anunciantes individuais.', 'features' => ['Até 3 anúncios ativos', 'Suporte por e-mail']],
    ['name' => 'Profissional', 'slug' => 'profissional', 'price' => 99.9, 'max_listings' => 30, 'description' => 'Para corretores autônomos.', 'features' => ['Até 30 anúncios ativos', 'Destaque nos resultados', 'Suporte prioritário']],
    ['name' => 'Imobiliária', 'slug' => 'imobiliaria', 'price' => 349.9, 'max_listings' => null, 'description' => 'Para imobiliárias com sincronização VRSync.', 'features' => ['Anúncios ilimitados', 'Sincronização automática de feeds (VRSync)', 'Múltiplos corretores', 'Painel administrativo']],
];
$planIds = [];
foreach ($plansData as $p) {
    $stmt = $pdo->prepare('INSERT INTO plans (name, slug, description, price, max_listings, features) VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $stmt->execute([$p['name'], $p['slug'], $p['description'], $p['price'], $p['max_listings'], json_encode($p['features'], JSON_UNESCAPED_UNICODE)]);
    $planIds[$p['slug']] = (int) $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM plans WHERE slug='{$p['slug']}'")->fetchColumn();
}

// --- Administrador ---------------------------------------------------------
echo "Criando usuário administrador...\n";
$adminHash = password_hash('Admin@12345', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES (?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE first_name = VALUES(first_name)');
$stmt->execute(['Administrador', 'Habitou', 'admin@habitou.com.br', $adminHash, 'ADMIN', 'ACTIVE']);

// --- Cidades e bairros -----------------------------------------------------
echo "Criando cidades e bairros...\n";
$cityIds = [];
foreach (FEATURED_CITIES as $c) {
    $stmt = $pdo->prepare('INSERT INTO cities (name, slug, state, state_code, region, latitude, longitude) VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $stmt->execute([$c['name'], $c['slug'], $c['state'], $c['state_code'], 'Santa Catarina', $c['lat'], $c['lng']]);
    $cityIds[$c['name']] = (int) $pdo->query("SELECT id FROM cities WHERE slug='{$c['slug']}'")->fetchColumn();
}
if (!isset($cityIds['Campo Alegre'])) {
    $stmt = $pdo->prepare('INSERT INTO cities (name, slug, state, state_code, region, latitude, longitude) VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $stmt->execute(['Campo Alegre', 'campo-alegre', 'Santa Catarina', 'SC', 'Santa Catarina', -26.3853, -49.2444]);
    $cityIds['Campo Alegre'] = (int) $pdo->query("SELECT id FROM cities WHERE slug='campo-alegre'")->fetchColumn();
}

$neighborhoodCache = [];
function get_neighborhood(PDO $pdo, int $cityId, string $name, array &$cache): int
{
    $key = $cityId . ':' . $name;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $slug = slugify($name) ?: 'sem-bairro';
    $stmt = $pdo->prepare('INSERT INTO neighborhoods (city_id, name, slug) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $stmt->execute([$cityId, $name, $slug]);
    $id = (int) $pdo->query("SELECT id FROM neighborhoods WHERE city_id={$cityId} AND slug=" . $pdo->quote($slug))->fetchColumn();
    $cache[$key] = $id;
    return $id;
}

// --- Imobiliárias e usuários-anunciantes -----------------------------------
echo "Criando imobiliárias...\n";
$distinctAgencies = [];
foreach ($properties as $p) {
    if (!empty($p['agencyName']) && !isset($distinctAgencies[$p['agencyName']])) {
        $distinctAgencies[$p['agencyName']] = $p['agencyUrl'] ?? null;
    }
}

$agencyInfo = []; // name => ['id' => x, 'advertiser_id' => y]
foreach ($distinctAgencies as $name => $url) {
    $slug = agency_slug_from_url($url, $name);
    $stmt = $pdo->prepare('INSERT INTO agencies (name, slug, status, description) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $stmt->execute([$name, $slug, 'ACTIVE', "{$name} é parceira Habitou Imóveis, com anúncios verificados em Santa Catarina."]);
    $agencyId = (int) $pdo->query("SELECT id FROM agencies WHERE slug=" . $pdo->quote($slug))->fetchColumn();

    $email = substr('contato+' . $slug . '@habitou.com.br', 0, 254);
    $userHash = password_hash('Imobiliaria@123', PASSWORD_BCRYPT);
    $firstName = explode(' ', $name)[0] ?: 'Imobiliária';
    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status, agency_id) VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE agency_id = VALUES(agency_id)');
    $stmt->execute([$firstName, 'Parceira', $email, $userHash, 'AGENCY_ADMIN', 'ACTIVE', $agencyId]);
    $advertiserId = (int) $pdo->query("SELECT id FROM users WHERE email=" . $pdo->quote($email))->fetchColumn();

    $agencyInfo[$name] = ['id' => $agencyId, 'advertiser_id' => $advertiserId];
}
printf("%d imobiliárias criadas.\n", count($agencyInfo));

// Feed de demonstração para a imobiliária com mais anúncios.
$agencyNames = array_keys($distinctAgencies);
$topAgencyName = $agencyNames[0] ?? null;
$demoFeedId = null;
if ($topAgencyName && isset($agencyInfo[$topAgencyName])) {
    $stmt = $pdo->prepare('INSERT INTO feeds (agency_id, name, url, status, frequency_minutes, last_sync_at, last_run_status) VALUES (?,?,?,?,?,NOW(),?)');
    $stmt->execute([$agencyInfo[$topAgencyName]['id'], 'Feed principal (VRSync)', 'https://exemplo-crm.com.br/feeds/vrsync.xml', 'ACTIVE', 1440, 'SUCCESS']);
    $demoFeedId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO feed_sync_logs (feed_id, started_at, finished_at, status) VALUES (?, NOW(), NOW(), ?)');
    $stmt->execute([$demoFeedId, 'SUCCESS']);
}

// --- Anunciante de fallback --------------------------------------------
$fallbackEmail = 'anunciante-demo@habitou.com.br';
$stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES (?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE first_name = VALUES(first_name)');
$stmt->execute(['Anunciante', 'Demo', $fallbackEmail, password_hash('Anunciante@123', PASSWORD_BCRYPT), 'ADVERTISER', 'ACTIVE']);
$fallbackAdvertiserId = (int) $pdo->query("SELECT id FROM users WHERE email=" . $pdo->quote($fallbackEmail))->fetchColumn();

// --- Imóveis -----------------------------------------------------------
echo "Criando imóveis (isso pode levar um tempo)...\n";
$created = 0;
$insertProperty = $pdo->prepare('INSERT INTO properties
    (code, external_code, origin, source_feed_id, title, slug, description, listing_type, property_type,
     price_sale, price_rent, total_area, bedrooms, suites, parking_spaces, status, published_at,
     city_id, neighborhood_id, street, advertiser_id, agency_id)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
$insertImage = $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin) VALUES (?,?,?,?,?)');
$existsStmt = $pdo->prepare('SELECT id FROM properties WHERE slug = ?');

foreach ($properties as $p) {
    $propertyType = $TYPE_MAP[$p['type']];
    $listingType = ($p['transaction'] ?? '') === 'Locação' ? 'RENT' : 'SALE';
    $cityId = $cityIds[$p['city']] ?? null;
    if (!$cityId) {
        continue;
    }
    $neighborhoodId = !empty($p['neighborhood']) ? get_neighborhood($pdo, $cityId, $p['neighborhood'], $neighborhoodCache) : null;
    $info = $p['agencyName'] ? ($agencyInfo[$p['agencyName']] ?? null) : null;

    $slug = pathinfo($p['sourcePath'], PATHINFO_FILENAME);
    $existsStmt->execute([$slug]);
    if ($existsStmt->fetchColumn()) {
        continue;
    }

    $isTopAgency = $p['agencyName'] === $topAgencyName;
    $code = next_property_code($pdo);

    $insertProperty->execute([
        $code,
        $p['code'] ?? $p['externalId'] ?? null,
        $isTopAgency ? 'VRSYNC' : 'MANUAL',
        $isTopAgency ? $demoFeedId : null,
        $p['title'],
        $slug,
        $p['description'] ?: null,
        $listingType,
        $propertyType,
        $listingType === 'SALE' ? $p['price'] : null,
        $listingType === 'RENT' ? $p['price'] : null,
        $p['area'] ?? null,
        $p['bedrooms'] ?? null,
        $p['suites'] ?? null,
        $p['parkingSpaces'] ?? null,
        'PUBLISHED',
        !empty($p['publishedAt']) ? date('Y-m-d H:i:s', strtotime($p['publishedAt'])) : date('Y-m-d H:i:s'),
        $cityId,
        $neighborhoodId,
        $p['address'] ?? null,
        $info['advertiser_id'] ?? $fallbackAdvertiserId,
        $info['id'] ?? null,
    ]);
    $propertyId = (int) $pdo->lastInsertId();

    $photos = array_slice($p['photos'] ?? [], 0, 20);
    foreach ($photos as $idx => $url) {
        $insertImage->execute([$propertyId, $url, $idx, $idx === 0 ? 1 : 0, $isTopAgency ? 'VRSYNC' : 'MANUAL']);
    }

    $created++;
    if ($created % 100 === 0) {
        echo "  {$created} imóveis criados...\n";
    }
}
printf("Concluído: %d imóveis criados.\n", $created);

// --- Blog e central de ajuda -------------------------------------------
echo "Criando artigos do blog e da central de ajuda...\n";
$blogPosts = [
    ['seo-local-para-imobiliaria', 'SEO local para imobiliária: como aparecer no Google quando alguém busca imóvel no seu bairro', 'SEO para imobiliária na prática: arquitetura de URLs, quando criar página de bairro, Perfil da Empresa, avaliações dentro da política e plano de 90 dias.', 'Marketing', 'Camila Duarte', 'Especialista em SEO e conteúdo digital', 9],
    ['custos-de-comprar-um-imovel-sc', 'Custos de comprar um imóvel: ITBI, escritura, registro e o que mais entra na conta em SC', 'Guia dos custos da compra em SC: como calcular o ITBI, quando a escritura é dispensada, emolumentos por faixa e as reduções que só valem se você pedir.', 'Comprar', 'Eduardo Lins', 'Contador, especialista fiscal e tributário imobiliário', 11],
    ['lei-do-inquilinato-explicada', 'Lei do Inquilinato explicada: direitos e deveres de quem aluga e de quem loca', 'Guia da Lei do Inquilinato: deveres de cada lado, prazo de 30 meses, multa proporcional, venda do imóvel, direito de preferência e quando cabe despejo.', 'Alugar', 'Patrícia Nogueira', 'Advogada, especialista em direito imobiliário', 9],
    ['investir-em-imoveis-em-santa-catarina', 'Investir em imóveis em Santa Catarina: rentabilidade real, regiões e os erros que corroem o retorno', 'Como calcular a rentabilidade real de um imóvel, o peso da vacância, cinco estratégias comparadas e os números de nove regiões de Santa Catarina.', 'Investir', 'Fernanda Coelho', 'Analista de mercado imobiliário', 10],
    ['custo-por-lead-imobiliario', 'Custo por lead imobiliário: quanto você realmente paga e qual é o número saudável', 'Como calcular o custo por lead imobiliário sem se enganar: as fórmulas de CPL e CAC, o teto que a sua comissão suporta e seis alavancas para baixá-lo.', 'CRM', 'Bruno Tavares', 'Especialista em mídia paga e Meta Ads', 8],
    ['publicar-imoveis-no-instagram-automaticamente', 'Publicar imóveis no Instagram automaticamente: como sair de 2 posts por semana para 2 por dia', 'Como publicar imóveis no Instagram automaticamente: o fluxo do cadastro ao post, o que a API oficial permite e a proporção que não derruba o seu alcance.', 'Marketing', 'Larissa Prado', 'Especialista em marketing e redes sociais', 8],
    ['lgpd-para-imobiliarias', 'LGPD para imobiliárias: o guia prático de adequação (com checklist e modelos)', 'Guia de LGPD para imobiliárias: base legal por finalidade, análise cadastral, documento por WhatsApp, prazos de resposta e o regime de pequeno porte.', 'Gestão', 'Patrícia Nogueira', 'Advogada, especialista em direito imobiliário', 12],
];
$insertArticle = $pdo->prepare('INSERT INTO articles (kind, slug, title, excerpt, category, author_name, author_role, read_minutes) VALUES (?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE title = VALUES(title)');
foreach ($blogPosts as $post) {
    $insertArticle->execute(['BLOG', ...$post]);
}
$guides = [
    ['como-configurar-dns-registro-br', 'Como configurar o DNS do seu domínio no Registro.br', 'Passo a passo para apontar um domínio .com.br para o seu site, criando os registros CNAME e A no painel do Registro.br.', 'Site', 'Equipe Habitou Imóveis', null, 5],
];
foreach ($guides as $guide) {
    $insertArticle->execute(['GUIDE', ...$guide]);
}
printf("%d posts de blog e %d guias criados.\n", count($blogPosts), count($guides));

echo "Seed concluído com sucesso.\n";
