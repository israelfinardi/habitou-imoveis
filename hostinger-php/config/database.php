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
    if (!in_array('expires_at', $columns('properties'), true)) {
        $pdo->exec('ALTER TABLE properties ADD COLUMN expires_at DATETIME NULL');
    }
    // Perfil completo (cadastro coleta o máximo de dados possível, editável
    // na guia Perfil): redes sociais, endereço e CNPJ em users; redes
    // sociais em agencies (endereço/CNPJ elas já tinham).
    foreach (['cnpj VARCHAR(32) NULL', 'instagram VARCHAR(120) NULL', 'facebook VARCHAR(120) NULL',
              'address VARCHAR(255) NULL', 'zip_code VARCHAR(16) NULL', 'city VARCHAR(120) NULL', 'state VARCHAR(60) NULL'] as $def) {
        $col = strtok($def, ' ');
        if (!in_array($col, $columns('users'), true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $def");
        }
    }
    $agencyColumns = $columns('agencies');
    if (!in_array('instagram', $agencyColumns, true)) {
        $pdo->exec('ALTER TABLE agencies ADD COLUMN instagram VARCHAR(120) NULL');
    }
    if (!in_array('facebook', $agencyColumns, true)) {
        $pdo->exec('ALTER TABLE agencies ADD COLUMN facebook VARCHAR(120) NULL');
    }
    // Tabelas novas (não coluna em tabela existente) usam CREATE TABLE IF NOT
    // EXISTS direto — já é idempotente por natureza, sem precisar de checagem.
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(255) NOT NULL,
        ip VARCHAR(64) NOT NULL,
        success INTEGER NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts (email, created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts (ip, created_at)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS rate_limit_hits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bucket VARCHAR(60) NOT NULL,
        rkey VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rate_limit_hits ON rate_limit_hits (bucket, rkey, created_at)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS search_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        city_id INTEGER NULL,
        listing_type TEXT NULL,
        property_type TEXT NULL,
        min_price DECIMAL(14,2) NULL,
        max_price DECIMAL(14,2) NULL,
        bedrooms INTEGER NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_search_history_user ON search_history (user_id, created_at)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS recommendation_email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        last_sent_at DATETIME NULL,
        last_property_ids TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    // Remoção do VRSync: corrige o texto do plano-100 em bancos já
    // provisionados antes da remoção (sql/schema.sql/seed.php só valem para
    // instalações novas) — WHERE garante que isso só roda uma vez, até o
    // texto antigo não existir mais.
    $pdo->prepare("UPDATE plans SET description = 'Para imobiliárias com grande carteira de imóveis.',
        features = '[\"Até 100 anúncios ativos\"]' WHERE slug = 'plano-100' AND description LIKE '%VRSync%'")->execute();

    // Algoritmo de recomendação da home (Fase 1 geo / Fase 2 conteúdo) —
    // ver includes/recommendation_service.php::get_home_recommendations().
    $pdo->exec('CREATE TABLE IF NOT EXISTS property_views (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        session_id VARCHAR(64) NULL,
        property_id INTEGER NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_property_views_user ON property_views (user_id, created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_property_views_session ON property_views (session_id, created_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_property_views_property ON property_views (property_id)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS user_location_signals (
        session_id VARCHAR(64) PRIMARY KEY,
        user_id INTEGER NULL,
        latitude DECIMAL(10,6) NOT NULL,
        longitude DECIMAL(10,6) NOT NULL,
        source TEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_location_signals_user ON user_location_signals (user_id)');

    // Blocos da home estilo Airbnb (ver includes/home_blocks_service.php) —
    // pontos de interesse cadastrados pelo admin.
    $pdo->exec('CREATE TABLE IF NOT EXISTS points_of_interest (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(160) NOT NULL,
        type TEXT NOT NULL DEFAULT "OUTRO",
        city_id INTEGER NOT NULL,
        latitude DECIMAL(10,6) NOT NULL,
        longitude DECIMAL(10,6) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_poi_city ON points_of_interest (city_id)');

    // Importação de XML de imóveis (padrão VRSync, compatível com a maioria
    // dos CRMs imobiliários do mercado brasileiro) — ver
    // includes/xml_import_parser.php e includes/xml_import_service.php.
    // Colunas novas em `properties` sem FK aqui (ALTER TABLE do SQLite não
    // permite adicionar constraint) — quem quiser a FK, começa do sql/schema.sql.
    $pdo->exec('CREATE TABLE IF NOT EXISTS xml_imports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        agency_id INTEGER NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_path VARCHAR(500) NOT NULL,
        status TEXT NOT NULL DEFAULT "PROCESSING",
        total_found INTEGER NOT NULL DEFAULT 0,
        total_created INTEGER NOT NULL DEFAULT 0,
        total_updated INTEGER NOT NULL DEFAULT 0,
        total_deactivated INTEGER NOT NULL DEFAULT 0,
        total_errors INTEGER NOT NULL DEFAULT 0,
        error_summary TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        finished_at DATETIME NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_xml_imports_user ON xml_imports (user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_xml_imports_agency ON xml_imports (agency_id)');

    $propertyColumns = $columns('properties');
    foreach ([
        'origin TEXT NOT NULL DEFAULT "MANUAL"', 'external_code VARCHAR(64) NULL', 'import_id INTEGER NULL',
    ] as $def) {
        $col = strtok($def, ' ');
        if (!in_array($col, $propertyColumns, true)) {
            $pdo->exec("ALTER TABLE properties ADD COLUMN $def");
        }
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
    require_once __DIR__ . '/../includes/password.php';
    require_once __DIR__ . '/../includes/seed.php';
    seed_database($pdo);
}
