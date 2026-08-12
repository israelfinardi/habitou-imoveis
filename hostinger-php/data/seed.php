<?php
/**
 * Popula manualmente o banco com os dados de demonstração. Normalmente não
 * é preciso rodar isso à mão — o site faz isso sozinho na primeira
 * requisição (veja config/database.php). Útil só para desenvolvimento.
 *
 * Uso: php data/seed.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/constants.php';
require_once __DIR__ . '/../includes/seed.php';

seed_database(db());
echo "Seed concluído.\n";
