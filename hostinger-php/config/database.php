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
    run_pending_migrations($pdo);

    return $pdo;
}

/**
 * Ajustes de schema pra bancos já provisionados antes da mudança (o site já
 * está no ar) — sql/schema.sql sozinho só vale pra instalações novas, já que
 * CREATE TABLE IF NOT EXISTS não adiciona coluna em tabela existente. Cada
 * item aqui precisa ser idempotente (checa antes de alterar); a checagem em
 * si (PRAGMA table_info) é barata o bastante pra rodar a cada request.
 */
function run_pending_migrations(PDO $pdo): void
{
    $columns = fn(string $table): array => array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(), 'name');

    if (!in_array('notify_email', $columns('users'), true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN notify_email VARCHAR(255) NULL');
    }
    if (!in_array('is_featured', $columns('properties'), true)) {
        $pdo->exec('ALTER TABLE properties ADD COLUMN is_featured INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('mp_plan_id', $columns('plans'), true)) {
        $pdo->exec('ALTER TABLE plans ADD COLUMN mp_plan_id VARCHAR(120) NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_plans_mp_plan_id ON plans (mp_plan_id)');
    }
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
