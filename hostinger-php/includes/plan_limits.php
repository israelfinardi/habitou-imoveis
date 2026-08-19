<?php
/**
 * Limites de anúncio por plano. Todo usuário sem assinatura paga ativa está
 * implicitamente no plano grátis (sem precisar de uma linha em
 * `subscriptions`): até FREE_PLAN_MAX_LISTINGS imóveis, cada um válido por
 * FREE_PLAN_VALIDITY_DAYS dias — depois disso o cron
 * (cron/property_expiration.php) expira o anúncio e avisa por e-mail. Uma
 * assinatura paga ACTIVE torna os anúncios eternos (sem expires_at) e usa o
 * max_listings cadastrado no plano (NULL = ilimitado).
 */

const FREE_PLAN_MAX_LISTINGS = 3;
const FREE_PLAN_VALIDITY_DAYS = 30;

/**
 * Resolve o plano "efetivo" de quem está anunciando: se for um usuário
 * ligado a uma agência (corretor/imobiliária), a assinatura da agência vale
 * pra todo mundo nela — mesma regra de busca já usada em planos.php
 * (assinatura por user_id OU agency_id, a mais recente ACTIVE).
 */
function get_effective_plan_for_actor(array $actor): array
{
    // Literal com aspas simples (não duplas): plans tem uma coluna chamada
    // `active`, e o SQLite resolve um literal entre aspas duplas como
    // identificador de coluna quando o nome bate (case-insensitive) com
    // alguma coluna visível na query — "ACTIVE" viraria uma referência a
    // p.active (0/1) em vez do texto 'ACTIVE', quebrando a comparação
    // silenciosamente (sem erro de SQL, só zero linhas encontradas).
    $stmt = db()->prepare("
        SELECT s.*, p.name AS plan_name, p.max_listings
        FROM subscriptions s
        JOIN plans p ON p.id = s.plan_id
        WHERE (s.user_id = ? OR s.agency_id = ?) AND s.status = 'ACTIVE'
        ORDER BY s.created_at DESC LIMIT 1
    ");
    $stmt->execute([$actor['id'], $actor['agency_id'] ?? 0]);
    $sub = $stmt->fetch();

    if ($sub) {
        return [
            'max_listings' => $sub['max_listings'] !== null ? (int) $sub['max_listings'] : null,
            'eternal' => true,
            'source' => 'PAID',
            'plan_name' => $sub['plan_name'],
        ];
    }

    return [
        'max_listings' => FREE_PLAN_MAX_LISTINGS,
        'eternal' => false,
        'source' => 'FREE',
        'plan_name' => 'Grátis',
    ];
}

/**
 * Conta quantos imóveis já ocupam uma "vaga" do plano — tudo que não foi
 * arquivado (rascunho, publicado, pausado ou expirado ainda conta; só
 * arquivar libera espaço pra outro anúncio). Corretor/imobiliária conta pela
 * agência inteira (mesma escopo de list_properties_for_advertiser).
 */
function count_active_listings_for_actor(array $actor): int
{
    $pdo = db();
    if (!empty($actor['agency_id']) && in_array($actor['role'], AGENCY_ROLES, true)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE agency_id = ? AND status != "ARCHIVED"');
        $stmt->execute([$actor['agency_id']]);
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE advertiser_id = ? AND status != "ARCHIVED"');
        $stmt->execute([$actor['id']]);
    }
    return (int) $stmt->fetchColumn();
}

/** Lança se o ator já atingiu o limite de imóveis do seu plano — chamado antes de criar/duplicar. */
function assert_can_create_listing(array $actor): void
{
    if ($actor['role'] === 'ADMIN') {
        return;
    }
    $plan = get_effective_plan_for_actor($actor);
    if ($plan['max_listings'] === null) {
        return;
    }
    $current = count_active_listings_for_actor($actor);
    if ($current >= $plan['max_listings']) {
        throw new \RuntimeException(
            $plan['source'] === 'FREE'
                ? 'Você atingiu o limite de ' . FREE_PLAN_MAX_LISTINGS . ' imóveis do plano grátis. Assine um plano pago para anunciar mais imóveis, ou arquive um anúncio existente para liberar espaço.'
                : 'Você atingiu o limite de ' . $plan['max_listings'] . ' imóveis do seu plano (' . $plan['plan_name'] . '). Assine um plano com mais vagas para anunciar mais imóveis.'
        );
    }
}

/**
 * Data de expiração pra aplicar num anúncio ao ser publicado/reativado/
 * renovado: null pro plano pago (eterno), ou "agora + 30 dias" pro grátis —
 * calculado no próprio SQLite (mesma convenção UTC do CURRENT_TIMESTAMP)
 * pra não misturar fuso horário local do PHP com o fuso do banco.
 */
function compute_listing_expiration(array $actor): ?string
{
    $plan = get_effective_plan_for_actor($actor);
    if ($actor['role'] === 'ADMIN' || $plan['eternal']) {
        return null;
    }
    return db()->query('SELECT datetime(\'now\', \'+' . FREE_PLAN_VALIDITY_DAYS . ' days\')')->fetchColumn();
}
