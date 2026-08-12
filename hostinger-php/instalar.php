<?php
/**
 * Instalador web (use apenas se você não tiver acesso SSH na Hostinger).
 * Cria as tabelas (schema.sql) e popula com os dados reais de exemplo.
 *
 * Acesse: https://seu-dominio/instalar.php?token=SEU_AUTH_SECRET
 * (o token é o valor de AUTH_SECRET definido em config/config.php)
 *
 * IMPORTANTE: apague este arquivo do servidor depois de usá-lo.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(0); // alguns provedores ignoram; se o script cair por timeout, basta recarregar a página (o processo retoma de onde parou)

$token = $_GET['token'] ?? '';
if (!hash_equals(AUTH_SECRET, $token)) {
    http_response_code(403);
    echo "Acesso negado. Use: instalar.php?token=SEU_AUTH_SECRET\n";
    exit;
}

echo "== Habitou Imóveis — instalação ==\n\n";

echo "1) Criando tabelas...\n";
$pdo = db();
$schema = file_get_contents(__DIR__ . '/sql/schema.sql');
// Remove linhas de comentário completas antes de dividir por ";", para que
// um comentário "colado" ao início de um CREATE TABLE não descarte o bloco inteiro.
$schemaNoComments = preg_replace('/^--.*$/m', '', $schema);
$created = 0;
foreach (array_filter(array_map('trim', explode(';', $schemaNoComments))) as $statement) {
    if ($statement === '') {
        continue;
    }
    try {
        $pdo->exec($statement);
        $created++;
    } catch (\PDOException $e) {
        echo '  aviso: ' . $e->getMessage() . "\n";
    }
}
echo "   OK ({$created} comandos executados).\n\n";

echo "2) Populando dados de exemplo (pode levar 1-2 minutos)...\n";
ob_start();
require __DIR__ . '/data/seed.php';
$seedOutput = ob_get_clean();
echo $seedOutput . "\n";

echo "\n== Instalação concluída. ==\n";
echo "Acesse o site e faça login com admin@habitou.com.br / Admin@12345\n";
echo "IMPORTANTE: troque essa senha e APAGUE este arquivo (instalar.php) agora.\n";
