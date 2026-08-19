<?php
/**
 * Limite de requisições (rate limiting) e bloqueio progressivo de conta.
 * Guardado em SQLite (login_attempts / rate_limit_hits) — não tem Redis
 * disponível na Hostinger, e o volume dessas tabelas é baixo o bastante
 * (só tentativas de login/reset/webhook) pra não pesar.
 */

function client_ip(): string
{
    // Confia em X-Forwarded-For só quando estiver atrás de um proxy
    // conhecido seria mais correto, mas a Hostinger não expõe essa
    // configuração — usa REMOTE_ADDR (o que o próprio PHP vê) como fonte
    // confiável primária, com XFF só como complemento informativo.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Limite genérico por IP: no máximo $max ações no bucket nos últimos
 * $windowSeconds. Usa uma tabela de eventos (não um contador mutável) —
 * mais simples de garantir corretude sem lidar com race conditions de
 * leitura+escrita, ao custo de uma limpeza periódica das linhas antigas.
 */
function rate_limit_check(string $bucket, string $key, int $max, int $windowSeconds): bool
{
    $pdo = db();
    // gmdate(), não date(): created_at vem do CURRENT_TIMESTAMP do SQLite,
    // que é sempre UTC — comparar com um horário em fuso local (o padrão
    // configurado é America/Sao_Paulo) faria a janela parecer maior ou
    // menor do que realmente é, dependendo do fuso.
    $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limit_hits WHERE bucket = ? AND rkey = ? AND created_at >= ?');
    $stmt->execute([$bucket, $key, $since]);
    return (int) $stmt->fetchColumn() < $max;
}

function rate_limit_hit(string $bucket, string $key): void
{
    db()->prepare('INSERT INTO rate_limit_hits (bucket, rkey) VALUES (?, ?)')->execute([$bucket, $key]);
    // Limpeza oportunista (1 em ~50 chamadas) — sem cron dedicado pra isso,
    // então aproveita o próprio tráfego pra não deixar a tabela crescer sem limite.
    if (random_int(1, 50) === 1) {
        db()->exec("DELETE FROM rate_limit_hits WHERE created_at < datetime('now', '-1 day')");
    }
}

/**
 * Nega a requisição com 429 se o limite do bucket/chave já foi atingido.
 * Chame ANTES de processar a ação; a própria action deve chamar
 * rate_limit_hit() depois (só quando quiser contar essa tentativa).
 */
function rate_limit_enforce(string $bucket, string $key, int $max, int $windowSeconds): void
{
    if (!rate_limit_check($bucket, $key, $max, $windowSeconds)) {
        http_response_code(429);
        header('Retry-After: ' . $windowSeconds);
        echo 'Muitas tentativas. Aguarde um pouco antes de tentar de novo.';
        exit;
    }
}

/**
 * Tempo de bloqueio progressivo por e-mail, contando as falhas
 * consecutivas mais recentes (para de contar no primeiro sucesso). Cresce
 * geometricamente: 5 falhas → 1 min, 8 → 5 min, 11 → 15 min, 14+ → 1h.
 */
function login_lockout_seconds_remaining(string $email): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT success, created_at FROM login_attempts WHERE email = ? ORDER BY id DESC LIMIT 30');
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll();

    $consecutiveFailures = 0;
    $lastFailureAt = null;
    foreach ($rows as $row) {
        if ((int) $row['success'] === 1) {
            break;
        }
        $consecutiveFailures++;
        $lastFailureAt = $lastFailureAt ?? $row['created_at'];
    }

    if ($consecutiveFailures < 5 || !$lastFailureAt) {
        return 0;
    }

    $lockSeconds = match (true) {
        $consecutiveFailures >= 14 => 3600,
        $consecutiveFailures >= 11 => 900,
        $consecutiveFailures >= 8 => 300,
        default => 60,
    };

    // idem: $lastFailureAt é UTC (CURRENT_TIMESTAMP do SQLite) — precisa
    // dizer isso pro strtotime(), senão ele assume o fuso local do PHP.
    $unlockAt = strtotime($lastFailureAt . ' UTC') + $lockSeconds;
    return max(0, $unlockAt - time());
}

function record_login_attempt(string $email, bool $success): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip, success) VALUES (?,?,?)')
        ->execute([$email, client_ip(), $success ? 1 : 0]);
}
