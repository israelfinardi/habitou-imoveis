<?php
require_once __DIR__ . '/property_repo.php';
require_once __DIR__ . '/mailer.php';

/**
 * Sistema de e-mails de recomendação de imóveis. Sem Redis/BullMQ
 * disponível na hospedagem compartilhada Hostinger, a "fila" aqui é o
 * próprio cron: cron/property_recommendations.php processa um lote pequeno
 * a cada execução (RECOMMENDATION_BATCH_SIZE) e o hPanel chama o script
 * periodicamente (ex.: a cada 15 min via Cron Jobs), o que espalha o envio
 * de centenas/milhares de e-mails ao longo do dia em vez de sobrecarregar
 * o servidor numa única requisição.
 */

/**
 * Registra os filtros de uma busca feita por um usuário logado — sinal de
 * interesse usado por build_user_interest_profile(). Só grava se a busca
 * tiver algum filtro relevante (evita poluir a tabela com todo carregamento
 * simples de /imoveis.php sem parâmetro nenhum).
 */
function log_search_history(int $userId, array $params): void
{
    $hasFilter = !empty($params['cidade']) || !empty($params['tipo']) || !empty($params['transacao'])
        || !empty($params['precoMin']) || !empty($params['precoMax']) || !empty($params['quartos']);
    if (!$hasFilter) {
        return;
    }

    $pdo = db();
    $cityId = null;
    if (!empty($params['cidade'])) {
        $stmt = $pdo->prepare('SELECT id FROM cities WHERE slug = ?');
        $stmt->execute([$params['cidade']]);
        $cityId = $stmt->fetchColumn() ?: null;
    }
    $listingType = SLUG_TO_LISTING_TYPE[$params['transacao'] ?? ''] ?? null;
    $propertyType = array_search($params['tipo'] ?? '', PROPERTY_TYPE_SLUG, true) ?: null;

    $stmt = $pdo->prepare('INSERT INTO search_history (user_id, city_id, listing_type, property_type, min_price, max_price, bedrooms) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $userId,
        $cityId,
        $listingType,
        $propertyType ?: null,
        !empty($params['precoMin']) ? (float) $params['precoMin'] : null,
        !empty($params['precoMax']) ? (float) $params['precoMax'] : null,
        !empty($params['quartos']) ? (int) $params['quartos'] : null,
    ]);

    // Limpeza oportunista (1 em ~50 buscas), mesmo padrão de rate_limit_hit().
    if (random_int(1, 50) === 1) {
        $pdo->exec("DELETE FROM search_history WHERE created_at < datetime('now', '-90 day')");
    }
}

/**
 * Usuários comuns (compradores/locatários) elegíveis para receber o
 * e-mail agora: role = USER (bloqueio explícito de AGENT/AGENCY_ADMIN/ADMIN
 * — corretor, imobiliária, admin — que nunca entram nesta query), com pelo
 * menos um sinal de interesse (favorito ou busca recente), e que não
 * receberam um e-mail de recomendação nas últimas 24h.
 */
function eligible_recommendation_users(int $limit = 40): array
{
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.email
        FROM users u
        WHERE u.role = 'USER' AND u.status = 'ACTIVE'
          AND (
            EXISTS (SELECT 1 FROM favorites f WHERE f.user_id = u.id)
            OR EXISTS (SELECT 1 FROM search_history s WHERE s.user_id = u.id AND s.created_at >= datetime('now', '-90 day'))
          )
          AND NOT EXISTS (
            SELECT 1 FROM recommendation_email_log r
            WHERE r.user_id = u.id AND r.last_sent_at >= datetime('now', '-1 day')
          )
        ORDER BY u.id ASC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Cruza favoritos (sinal forte) e buscas recentes (sinal mais fraco, últimos
 * 90 dias) do usuário num perfil de interesse: cidades, transação/tipo mais
 * frequentes e uma faixa de preço (com folga de 20% pra não ser rígido
 * demais). Retorna null se não houver sinal nenhum.
 */
function build_user_interest_profile(int $userId): ?array
{
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT p.city_id, p.listing_type, p.property_type, p.price_sale, p.price_rent
        FROM favorites f
        JOIN properties p ON p.id = f.property_id
        WHERE f.user_id = ?
    ');
    $stmt->execute([$userId]);
    $favorites = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT city_id, listing_type, property_type, min_price, max_price
        FROM search_history
        WHERE user_id = ? AND created_at >= datetime('now', '-90 day')
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute([$userId]);
    $searches = $stmt->fetchAll();

    if (!$favorites && !$searches) {
        return null;
    }

    $cityIds = [];
    $listingTypes = [];
    $propertyTypes = [];
    $prices = [];

    foreach ($favorites as $f) {
        if ($f['city_id']) $cityIds[] = (int) $f['city_id'];
        if ($f['listing_type']) $listingTypes[] = $f['listing_type'];
        if ($f['property_type']) $propertyTypes[] = $f['property_type'];
        $price = $f['listing_type'] === 'RENT' ? $f['price_rent'] : $f['price_sale'];
        if ($price) $prices[] = (float) $price;
    }
    foreach ($searches as $s) {
        if ($s['city_id']) $cityIds[] = (int) $s['city_id'];
        if ($s['listing_type']) $listingTypes[] = $s['listing_type'];
        if ($s['property_type']) $propertyTypes[] = $s['property_type'];
        if ($s['min_price']) $prices[] = (float) $s['min_price'];
        if ($s['max_price']) $prices[] = (float) $s['max_price'];
    }

    if (!$cityIds && !$listingTypes && !$propertyTypes) {
        return null;
    }

    $listingType = null;
    if ($listingTypes) {
        $counts = array_count_values($listingTypes);
        arsort($counts);
        $listingType = array_key_first($counts);
    }

    return [
        'city_ids' => array_values(array_unique($cityIds)),
        'listing_type' => $listingType,
        'property_types' => array_values(array_unique($propertyTypes)),
        'price_min' => $prices ? min($prices) * 0.8 : null,
        'price_max' => $prices ? max($prices) * 1.2 : null,
    ];
}

const RECOMMENDATION_EMAIL_SELECT = "
    p.id, p.title, p.slug, p.listing_type, p.property_type, p.description,
    p.price_sale, p.price_rent, p.condo_fee, p.total_area, p.bedrooms, p.bathrooms, p.parking_spaces,
    p.street, p.number, p.city_id, p.published_at,
    c.name AS city_name, c.state_code,
    n.name AS neighborhood_name
";
const RECOMMENDATION_EMAIL_JOIN = "
    FROM properties p
    JOIN cities c ON c.id = p.city_id
    LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
";

/**
 * Busca imóveis publicados que combinam com o perfil de interesse do
 * usuário, excluindo o que ele já favoritou e o que já recebeu num e-mail
 * anterior (evita repetir a mesma recomendação todo dia). Se o perfil for
 * específico demais e não achar nada, cai pro fallback dos mais recentes na
 * cidade de interesse (ou, sem cidade nenhuma, os mais recentes do site) —
 * assim o e-mail nunca sai vazio quando existe QUALQUER imóvel disponível.
 */
function find_recommendations_for_user(int $userId, array $profile, array $excludePropertyIds, int $limit = 6): array
{
    $pdo = db();
    $exclude = array_values(array_unique(array_merge(get_favorite_ids($userId), $excludePropertyIds)));

    $items = query_recommendation_candidates($pdo, [
        'city_ids' => $profile['city_ids'],
        'listing_type' => $profile['listing_type'],
        'property_types' => $profile['property_types'],
        'price_min' => $profile['price_min'],
        'price_max' => $profile['price_max'],
    ], $exclude, $limit);

    if (count($items) < 3) {
        // Fallback 1: solta o tipo de imóvel e a faixa de preço, mantém cidade/transação.
        $items = query_recommendation_candidates($pdo, [
            'city_ids' => $profile['city_ids'],
            'listing_type' => $profile['listing_type'],
            'property_types' => [],
            'price_min' => null,
            'price_max' => null,
        ], $exclude, $limit);
    }

    if (count($items) < 3 && $profile['city_ids']) {
        // Fallback 2: solta a cidade também — melhor mandar algo relevante em transação do que nada.
        $items = query_recommendation_candidates($pdo, [
            'city_ids' => [],
            'listing_type' => $profile['listing_type'],
            'property_types' => [],
            'price_min' => null,
            'price_max' => null,
        ], $exclude, $limit);
    }

    return $items;
}

function query_recommendation_candidates(PDO $pdo, array $filters, array $excludeIds, int $limit): array
{
    $where = ['p.status = "PUBLISHED"'];
    $args = [];

    if (!empty($filters['city_ids'])) {
        $placeholders = implode(',', array_fill(0, count($filters['city_ids']), '?'));
        $where[] = "p.city_id IN ($placeholders)";
        $args = array_merge($args, $filters['city_ids']);
    }
    if (!empty($filters['listing_type'])) {
        $where[] = 'p.listing_type = ?';
        $args[] = $filters['listing_type'];
    }
    if (!empty($filters['property_types'])) {
        $placeholders = implode(',', array_fill(0, count($filters['property_types']), '?'));
        $where[] = "p.property_type IN ($placeholders)";
        $args = array_merge($args, $filters['property_types']);
    }
    $priceField = ($filters['listing_type'] ?? '') === 'RENT' ? 'price_rent' : 'price_sale';
    if (!empty($filters['price_min'])) {
        $where[] = "p.$priceField >= ?";
        $args[] = $filters['price_min'];
    }
    if (!empty($filters['price_max'])) {
        $where[] = "p.$priceField <= ?";
        $args[] = $filters['price_max'];
    }
    if ($excludeIds) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $where[] = "p.id NOT IN ($placeholders)";
        $args = array_merge($args, $excludeIds);
    }

    $sql = 'SELECT ' . RECOMMENDATION_EMAIL_SELECT . RECOMMENDATION_EMAIL_JOIN
        . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.is_featured DESC, p.published_at DESC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $items = $stmt->fetchAll();

    return attach_primary_images($pdo, $items, 1);
}

function previously_sent_property_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT last_property_ids FROM recommendation_email_log WHERE user_id = ?');
    $stmt->execute([$userId]);
    $json = $stmt->fetchColumn();
    if (!$json) {
        return [];
    }
    $ids = json_decode((string) $json, true);
    return is_array($ids) ? array_map('intval', $ids) : [];
}

/** Marca o throttle diário — chamado mesmo quando não há recomendações boas o suficiente, pra não reprocessar o mesmo usuário várias vezes no mesmo dia. */
function mark_recommendation_sent(int $userId, array $propertyIds): void
{
    $stmt = db()->prepare('
        INSERT INTO recommendation_email_log (user_id, last_sent_at, last_property_ids)
        VALUES (?, CURRENT_TIMESTAMP, ?)
        ON CONFLICT(user_id) DO UPDATE SET last_sent_at = CURRENT_TIMESTAMP, last_property_ids = excluded.last_property_ids
    ');
    $stmt->execute([$userId, json_encode(array_values($propertyIds))]);
}

function render_recommendation_email_html(array $user, array $properties): string
{
    $firstName = e($user['first_name'] ?? '');
    $rows = '';
    foreach ($properties as $p) {
        $rows .= render_recommendation_email_card($p);
    }
    $siteUrl = e(base_url('imoveis.php'));

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Habitou Imóveis</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F7F7;font-family:Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F7F7;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;">
          <tr>
            <td style="background-color:#C1502E;padding:24px 32px;">
              <span style="font-size:20px;font-weight:700;color:#ffffff;">Habitou Imóveis</span>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px 8px;">
              <h1 style="margin:0 0 6px;font-size:21px;line-height:1.3;color:#222222;">Olá {$firstName}! Encontramos todos esses imóveis para você.</h1>
              <p style="margin:0;font-size:14px;color:#717171;line-height:1.5;">Com base nos seus favoritos e nas suas últimas buscas, separamos as oportunidades abaixo.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 24px 24px;">
              {$rows}
            </td>
          </tr>
          <tr>
            <td style="padding:0 32px 32px;text-align:center;">
              <a href="{$siteUrl}" style="font-size:13px;color:#717171;text-decoration:underline;">Ver mais imóveis no site</a>
            </td>
          </tr>
          <tr>
            <td style="background-color:#F7F7F7;padding:20px 32px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#9B9B9B;">Você recebeu este e-mail porque tem uma conta na Habitou Imóveis.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

// ---------------------------------------------------------------------
// Algoritmo de recomendação da home (index.php): Fase 1 (cold start, por
// geolocalização) e Fase 2 (personalização por conteúdo, reaproveitando o
// perfil de interesse já usado no e-mail de recomendação acima). Tudo em
// PHP/SQLite puro — sem serviço externo, sem fila, sem dependência que não
// suba junto no zip da Hostinger.
// ---------------------------------------------------------------------

const COLD_START_INTERACTION_THRESHOLD = 3;
const RECOMMENDATION_SIGNAL_WINDOW_DAYS = 90;
const HOME_RECOMMENDATION_RADIUS_KM = 15.0;
const HOME_RECOMMENDATION_RADIUS_FALLBACK_KM = 60.0;
const HOME_RECOMMENDATIONS_LIMIT = 8;

/**
 * Registra um clique/visualização de anúncio — sinal de interesse mais forte
 * que busca (log_search_history), mais fraco que favoritar. Chamado uma vez
 * por carregamento de imovel.php. session_id() já é iniciado globalmente em
 * includes/auth.php, então cobre visitante anônimo e logado com a mesma
 * chamada, sem precisar de cookie próprio.
 */
function log_property_view(?int $userId, int $propertyId): void
{
    db()->prepare('INSERT INTO property_views (user_id, session_id, property_id) VALUES (?,?,?)')
        ->execute([$userId, session_id(), $propertyId]);

    // Limpeza oportunista (1 em ~50), mesmo padrão do resto do app.
    if (random_int(1, 50) === 1) {
        db()->exec("DELETE FROM property_views WHERE created_at < datetime('now', '-" . RECOMMENDATION_SIGNAL_WINDOW_DAYS . " day')");
    }
}

/**
 * Quantos sinais de interesse o usuário logado tem nos últimos 90 dias:
 * buscas com filtro + cliques em anúncios + favoritos (esses não expiram).
 * < COLD_START_INTERACTION_THRESHOLD → ainda está em cold start (Fase 1).
 */
function count_user_interactions(int $userId): int
{
    $pdo = db();
    $since = "datetime('now', '-" . RECOMMENDATION_SIGNAL_WINDOW_DAYS . " day')";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM search_history WHERE user_id = ? AND created_at >= $since");
    $stmt->execute([$userId]);
    $searches = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_views WHERE user_id = ? AND created_at >= $since");
    $stmt->execute([$userId]);
    $views = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
    $stmt->execute([$userId]);
    $favorites = (int) $stmt->fetchColumn();

    return $searches + $views + $favorites;
}

/**
 * Geolocalização aproximada por IP, via API pública gratuita (sem chave,
 * ~45 req/min — suficiente aqui porque o resultado fica em cache por sessão
 * em user_location_signals, então cada IP só é resolvido uma vez por
 * sessão). Timeout curto e nunca lança: se falhar ou o IP for local/privado
 * (ambiente de dev), a Fase 1 cai no fallback de "mais recentes do site"
 * mais adiante em get_home_recommendations().
 */
function resolve_ip_geolocation(string $ip): ?array
{
    if ($ip === '' || $ip === '0.0.0.0' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }
    try {
        $ch = curl_init('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,lat,lon');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 1]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = $raw ? json_decode($raw, true) : null;
        if (!$data || ($data['status'] ?? '') !== 'success' || !isset($data['lat'], $data['lon'])) {
            return null;
        }
        return ['lat' => (float) $data['lat'], 'lng' => (float) $data['lon']];
    } catch (\Throwable $e) {
        error_log('[recommendation] Falha na geolocalização por IP: ' . $e->getMessage());
        return null;
    }
}

/** Grava/atualiza a última localização conhecida da sessão (GPS ou IP). */
function upsert_location_signal(string $sessionId, ?int $userId, float $lat, float $lng, string $source): void
{
    db()->prepare('
        INSERT INTO user_location_signals (session_id, user_id, latitude, longitude, source, updated_at)
        VALUES (?,?,?,?,?, CURRENT_TIMESTAMP)
        ON CONFLICT(session_id) DO UPDATE SET
            user_id = excluded.user_id, latitude = excluded.latitude, longitude = excluded.longitude,
            source = excluded.source, updated_at = CURRENT_TIMESTAMP
    ')->execute([$sessionId, $userId, $lat, $lng, $source]);
}

/**
 * Localização aproximada da sessão atual: usa o que já está salvo (GPS tem
 * prioridade e não expira dentro da sessão — o navegador só pede de novo se
 * quiser; IP expira em 24h pra acompanhar quem viaja) e só chama a API
 * externa de geolocalização por IP quando não há nada aproveitável ainda.
 */
function get_session_location(string $sessionId, ?int $userId, string $ip): ?array
{
    $stmt = db()->prepare('SELECT latitude, longitude, source, updated_at FROM user_location_signals WHERE session_id = ?');
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch();

    if ($row) {
        $isFreshEnough = $row['source'] === 'gps' || strtotime($row['updated_at'] . ' UTC') > time() - 86400;
        if ($isFreshEnough) {
            return ['lat' => (float) $row['latitude'], 'lng' => (float) $row['longitude'], 'source' => $row['source']];
        }
    }

    $geo = resolve_ip_geolocation($ip);
    if (!$geo) {
        return null;
    }
    upsert_location_signal($sessionId, $userId, $geo['lat'], $geo['lng'], 'ip');
    return ['lat' => $geo['lat'], 'lng' => $geo['lng'], 'source' => 'ip'];
}

/**
 * Imóveis publicados num raio de $radiusKm ao redor de (lat,lng), mais
 * próximos primeiro. SQLite (sem a extensão de funções matemáticas, que não
 * é garantida na Hostinger) não tem seno/cosseno nativo pra Haversine em
 * SQL, então o filtro aqui é em duas etapas: 1) uma caixa delimitadora
 * (bounding box) barata em SQL, só com +/-, que já elimina a maior parte do
 * banco usando os índices de latitude/longitude; 2) distância exata
 * (Haversine) e ordenação final em PHP, só sobre os poucos candidatos que
 * sobraram da caixa — rápido mesmo sem índice geoespacial de verdade.
 */
function query_properties_near(PDO $pdo, float $lat, float $lng, float $radiusKm, ?string $listingType, array $excludeIds, int $limit): array
{
    // 1 grau de latitude ≈ 111km sempre; 1 grau de longitude encolhe perto
    // dos polos (~111km * cos(latitude)) — relevante mesmo num país só
    // continental como o Brasil, que vai de ~5°N a ~34°S.
    $latDelta = $radiusKm / 111.0;
    $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad($lat)), 0.1));

    $where = [
        'p.status = "PUBLISHED"',
        'p.latitude IS NOT NULL', 'p.longitude IS NOT NULL',
        'p.latitude BETWEEN ? AND ?',
        'p.longitude BETWEEN ? AND ?',
    ];
    $args = [$lat - $latDelta, $lat + $latDelta, $lng - $lngDelta, $lng + $lngDelta];

    if ($listingType) {
        $where[] = 'p.listing_type = ?';
        $args[] = $listingType;
    }
    if ($excludeIds) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $where[] = "p.id NOT IN ($placeholders)";
        $args = array_merge($args, $excludeIds);
    }

    // Limite generoso na caixa (até 300 candidatos) — o corte final por
    // distância + $limit acontece em PHP logo abaixo.
    $sql = 'SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . ' WHERE ' . implode(' AND ', $where)
        . ' ORDER BY p.published_at DESC LIMIT 300';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $candidates = $stmt->fetchAll();

    foreach ($candidates as &$row) {
        $row['_distance_km'] = haversine_distance_km($lat, $lng, (float) $row['latitude'], (float) $row['longitude']);
    }
    unset($row);

    $candidates = array_values(array_filter($candidates, fn ($row) => $row['_distance_km'] <= $radiusKm));
    usort($candidates, fn ($a, $b) => $a['_distance_km'] <=> $b['_distance_km']);
    $candidates = array_slice($candidates, 0, $limit);

    return attach_primary_images($pdo, $candidates, 1);
}

/** Distância em linha reta entre duas coordenadas, em km (fórmula de Haversine, raio médio da Terra = 6371km). */
function haversine_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadiusKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * Fallback final quando nem geolocalização nem perfil de conteúdo dão
 * resultado (banco muito pequeno, ou imóvel sem lat/lng cadastrado): os
 * publicados mais recentes, respeitando a transação padrão quando possível
 * — nunca deixa a seção da home vazia enquanto existir QUALQUER imóvel.
 */
function query_most_recent_properties(PDO $pdo, ?string $listingType, array $excludeIds, int $limit): array
{
    $where = ['p.status = "PUBLISHED"'];
    $args = [];
    if ($listingType) {
        $where[] = 'p.listing_type = ?';
        $args[] = $listingType;
    }
    if ($excludeIds) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $where[] = "p.id NOT IN ($placeholders)";
        $args = array_merge($args, $excludeIds);
    }
    $sql = 'SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . ' WHERE ' . implode(' AND ', $where)
        . " ORDER BY p.published_at DESC LIMIT $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $items = $stmt->fetchAll();

    if (count($items) < $limit && $listingType) {
        // Solta a transação também — melhor mostrar algo do que nada.
        $more = query_most_recent_properties($pdo, null, array_merge($excludeIds, array_column($items, 'id')), $limit - count($items));
        $items = array_merge($items, $more);
    }

    return attach_primary_images($pdo, $items, 1);
}

/**
 * Ponto de entrada único do algoritmo de recomendação da home — chamado de
 * index.php (render inicial, com IP) e de actions/home_recommendations.php
 * (refresh via AJAX, depois que o navegador cede o GPS).
 *
 * Fase 1 (cold start): usuário anônimo OU logado com poucas interações
 * registradas (< COLD_START_INTERACTION_THRESHOLD) → geolocalização
 * aproximada, raio de HOME_RECOMMENDATION_RADIUS_KM (com um raio maior de
 * fallback se não achar nada perto).
 *
 * Fase 2 (personalização): logado com histórico suficiente → filtragem
 * baseada em conteúdo (bairros/cidades e faixa de preço mais buscados,
 * tipo de transação preferido), reaproveitando build_user_interest_profile()
 * e find_recommendations_for_user() já usados no e-mail de recomendação.
 *
 * @return array{items: array, mode: string, radius_km: ?float}
 *   mode: 'personalized' (Fase 2) | 'geo' (Fase 1 com localização) | 'fallback' (sem geo/sem perfil)
 */
function get_home_recommendations(?array $user, string $sessionId, string $ip, string $defaultListingType, int $limit = HOME_RECOMMENDATIONS_LIMIT): array
{
    $pdo = db();
    $interactionCount = $user ? count_user_interactions((int) $user['id']) : 0;
    $isColdStart = !$user || $interactionCount < COLD_START_INTERACTION_THRESHOLD;

    // --- Fase 2: personalização por conteúdo ---------------------------
    if (!$isColdStart) {
        $profile = build_user_interest_profile((int) $user['id']);
        if ($profile) {
            $items = find_recommendations_for_user((int) $user['id'], $profile, [], $limit);
            if (count($items) >= 3) {
                return ['items' => $items, 'mode' => 'personalized', 'radius_km' => null];
            }
        }
        // Perfil vazio ou resultado curto demais: continua pro fallback
        // geo/recente abaixo em vez de devolver poucos itens.
    }

    // --- Fase 1: geolocalização aproximada ------------------------------
    $excludeIds = $user ? get_favorite_ids((int) $user['id']) : [];
    $location = get_session_location($sessionId, $user ? (int) $user['id'] : null, $ip);
    if ($location) {
        $items = query_properties_near($pdo, $location['lat'], $location['lng'], HOME_RECOMMENDATION_RADIUS_KM, $defaultListingType, $excludeIds, $limit);
        if (count($items) < 3) {
            // Raio curto não achou o suficiente — tenta um raio bem maior
            // antes de desistir da geolocalização de vez.
            $items = query_properties_near($pdo, $location['lat'], $location['lng'], HOME_RECOMMENDATION_RADIUS_FALLBACK_KM, $defaultListingType, $excludeIds, $limit);
        }
        if (count($items) >= 3) {
            return ['items' => $items, 'mode' => 'geo', 'radius_km' => HOME_RECOMMENDATION_RADIUS_FALLBACK_KM];
        }
    }

    // --- Fallback final: sem GPS, sem IP resolvido, ou raio sem imóveis --
    return ['items' => query_most_recent_properties($pdo, $defaultListingType, $excludeIds, $limit), 'mode' => 'fallback', 'radius_km' => null];
}

function render_recommendation_email_card(array $p): string
{
    $isRent = $p['listing_type'] === 'RENT';
    $price = e(format_currency_brl($isRent ? $p['price_rent'] : $p['price_sale']) . ($isRent ? '/mês' : ''));
    $typeLabel = e(LISTING_TYPE_LABEL[$p['listing_type']] ?? '');
    $image = e($p['image_url'] ?: 'https://habitou.com.br/assets/img/placeholder-property.jpg');
    $title = e($p['title']);

    $addressParts = array_filter([
        $p['street'] ? $p['street'] . ($p['number'] ? ', ' . $p['number'] : '') : null,
        $p['neighborhood_name'] ?: null,
        trim(($p['city_name'] ?? '') . ($p['state_code'] ? '/' . $p['state_code'] : '')),
    ]);
    $address = e(implode(' — ', $addressParts));

    $summarySource = trim((string) ($p['description'] ?? ''));
    $summary = $summarySource !== ''
        ? mb_strimwidth($summarySource, 0, 110, '…')
        : (PROPERTY_TYPE_LABEL[$p['property_type']] ?? 'Imóvel') . ' ' . ($isRent ? 'para alugar' : 'à venda') . ' em ' . ($p['city_name'] ?? '') . '.';
    $summary = e($summary);

    $tags = array_filter([
        !empty($p['total_area']) ? format_area($p['total_area']) : null,
        !empty($p['bedrooms']) ? (int) $p['bedrooms'] . ' quarto' . ((int) $p['bedrooms'] > 1 ? 's' : '') : null,
        !empty($p['bathrooms']) ? (int) $p['bathrooms'] . ' banheiro' . ((int) $p['bathrooms'] > 1 ? 's' : '') : null,
        !empty($p['parking_spaces']) ? (int) $p['parking_spaces'] . ' vaga' . ((int) $p['parking_spaces'] > 1 ? 's' : '') : null,
    ]);
    $tagsHtml = '';
    foreach ($tags as $tag) {
        $tagsHtml .= '<span style="display:inline-block;background-color:#F7F7F7;color:#484848;font-size:11px;padding:4px 10px;border-radius:999px;margin:0 6px 6px 0;">' . e($tag) . '</span>';
    }

    $condo = !empty($p['condo_fee'])
        ? '<p style="margin:2px 0 0;font-size:12px;color:#717171;">Condomínio: ' . e(format_currency_brl($p['condo_fee'])) . '</p>'
        : '';

    $href = e(property_href($p));

    return <<<HTML
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;border:1px solid #EBEBEB;border-radius:14px;">
      <tr>
        <td width="140" valign="top" style="padding:0;">
          <a href="{$href}" style="display:block;">
            <img src="{$image}" width="140" alt="{$title}" style="display:block;width:140px;height:140px;object-fit:cover;border-radius:14px 0 0 14px;">
          </a>
        </td>
        <td valign="top" style="padding:14px 16px;">
          <span style="display:inline-block;background-color:#FBEAE3;color:#C1502E;font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;text-transform:uppercase;">{$typeLabel}</span>
          <p style="margin:8px 0 0;font-size:17px;font-weight:700;color:#222222;">{$price}</p>
          {$condo}
          <p style="margin:8px 0 2px;font-size:13px;font-weight:700;color:#222222;">{$address}</p>
          <p style="margin:0 0 8px;font-size:12px;color:#717171;line-height:1.4;">{$summary}</p>
          <div style="margin-bottom:10px;">{$tagsHtml}</div>
          <a href="{$href}" style="display:inline-block;background-color:#C1502E;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;padding:10px 18px;border-radius:999px;">Ir para o anúncio</a>
        </td>
      </tr>
    </table>
    HTML;
}
