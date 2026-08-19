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
