<?php
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = DATA_DIR . '/database.sqlite';
    $isNew = !is_file($dbPath);

    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0755, true) && !is_dir(DATA_DIR)) {
        throw new \RuntimeException('Não foi possível criar a pasta ' . DATA_DIR . ' — verifique as permissões de escrita.');
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    provision_database_if_needed($pdo, $isNew);

    return $pdo;
}

/**
 * Cria o schema e popula os dados de demonstração na primeira vez que o
 * site é acessado — é isso que torna a instalação "jogar os arquivos na
 * pasta e pronto", sem precisar criar banco nem rodar instalador manual.
 * Protegido por um lock de arquivo pra evitar duas requisições simultâneas
 * provisionando ao mesmo tempo.
 */
function provision_database_if_needed(PDO $pdo, bool $isNew): void
{
    if (!$isNew) {
        return;
    }

    $lockPath = DATA_DIR . '/.provisioning.lock';
    $lock = fopen($lockPath, 'c');
    if (!$lock) {
        // Sem lock, cria mesmo assim (schema é idempotente via IF NOT EXISTS).
        run_provisioning($pdo);
        return;
    }

    flock($lock, LOCK_EX);
    try {
        $hasUsers = false;
        try {
            $hasUsers = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn() > 0
                && (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
        } catch (\Throwable $e) {
            $hasUsers = false;
        }
        if (!$hasUsers) {
            run_provisioning($pdo);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function run_provisioning(PDO $pdo): void
{
    $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
    $pdo->exec($schema);

    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/constants.php';
    require_once __DIR__ . '/../includes/seed.php';
    seed_database($pdo);
}
