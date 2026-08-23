<?php
/**
 * Apaga TODOS os dados de demonstração: imóveis, fotos, favoritos,
 * imobiliárias, bairros e cidades — além das contas de usuário criadas pelo
 * seed (imobiliárias parceiras de exemplo e o anunciante demo). A conta
 * admin@habitou.com.br e qualquer conta real que você já tenha criado NÃO
 * são apagadas.
 *
 * Acesse: https://seu-dominio/limpar-dados.php?token=SEU_AUTH_SECRET
 *
 * IMPORTANTE: isso é destrutivo e não pode ser desfeito. Apague este arquivo
 * do servidor depois de usá-lo.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(0);

$token = $_GET['token'] ?? '';
if (!hash_equals(AUTH_SECRET, $token)) {
    http_response_code(403);
    echo "Acesso negado. Use: limpar-dados.php?token=SEU_AUTH_SECRET\n";
    exit;
}
if (($_GET['confirmar'] ?? '') !== 'sim') {
    echo "Isso vai apagar TODOS os imóveis, imobiliárias, cidades e bairros do banco.\n";
    echo "Não pode ser desfeito. Para confirmar, adicione &confirmar=sim na mesma URL:\n\n";
    echo $_SERVER['REQUEST_URI'] . "&confirmar=sim\n";
    exit;
}

echo "== Habitou Imóveis — limpeza de dados de demonstração ==\n\n";
$pdo = db();

function contar(PDO $pdo, string $tabela): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM {$tabela}")->fetchColumn();
}

$antes = [
    'properties' => contar($pdo, 'properties'),
    'agencies' => contar($pdo, 'agencies'),
    'cities' => contar($pdo, 'cities'),
    'neighborhoods' => contar($pdo, 'neighborhoods'),
];
// Contratos foram removidos do site — a tabela só existe em instalações
// antigas que já tinham sido provisionadas antes da remoção; se ainda
// existir, some junto (referenciava imóveis sem CASCADE).
$hasLegacyContracts = (bool) $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='contracts'")->fetchColumn();

$pdo->beginTransaction();
try {
    if ($hasLegacyContracts) {
        $pdo->exec('DELETE FROM contracts');
    }
    // Apaga os imóveis: fotos e favoritos saem junto (ON DELETE CASCADE).
    $pdo->exec('DELETE FROM properties');
    // Bairros e cidades só podem sair depois que nenhum imóvel os referencia.
    $pdo->exec('DELETE FROM neighborhoods');
    $pdo->exec('DELETE FROM cities');
    $pdo->exec('DELETE FROM agencies');
    // Contas criadas pelo seed de demonstração (imobiliárias parceiras de exemplo e anunciante demo).
    $stmtUsers = $pdo->prepare("DELETE FROM users WHERE email LIKE 'contato+%@habitou.com.br' OR email = 'anunciante-demo@habitou.com.br'");
    $stmtUsers->execute();
    $usersRemovidos = $stmtUsers->rowCount();

    // Reinicia a contagem de id (equivalente ao AUTO_INCREMENT do MySQL).
    $resetSeq = $pdo->prepare('DELETE FROM sqlite_sequence WHERE name = ?');
    foreach (['properties', 'agencies', 'cities', 'neighborhoods'] as $tabela) {
        $resetSeq->execute([$tabela]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    echo "Falha ao limpar: " . $e->getMessage() . "\n";
    exit;
}

printf("Imóveis removidos: %d\n", $antes['properties']);
printf("Imobiliárias removidas: %d\n", $antes['agencies']);
printf("Cidades removidas: %d\n", $antes['cities']);
printf("Bairros removidos: %d\n", $antes['neighborhoods']);
printf("Contas de demonstração removidas: %d\n", $usersRemovidos);

echo "\n== Limpeza concluída. ==\n";
echo "O banco está pronto para receber imóveis, imobiliárias e cidades reais.\n";
echo "IMPORTANTE: apague este arquivo (limpar-dados.php) do servidor agora.\n";
