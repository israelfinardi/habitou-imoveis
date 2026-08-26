<?php
/**
 * Popula o banco com os dados essenciais para o site funcionar (planos,
 * conta de administrador, cidades em destaque e conteúdo institucional da
 * central de ajuda) — sem nenhum imóvel ou imobiliária de exemplo.
 * Chamada automaticamente por config/database.php na primeira vez que o
 * site roda (banco ainda não existe) — não precisa ser executada manualmente.
 */

function seed_database(PDO $pdo): void
{
    error_log('[habitou] Primeira execução: populando dados iniciais...');
    // Uma única transação: sem isso, cada INSERT isolado força um fsync no
    // SQLite e a primeira carga do site (que dispara esse seed) fica
    // visivelmente lenta para quem acessar primeiro.
    $pdo->beginTransaction();

    // --- Planos --------------------------------------------------------------
    $plansData = [
        ['name' => 'Plano 5 imóveis', 'slug' => 'plano-5', 'price' => 89.9, 'max_listings' => 5, 'description' => 'Ideal para anunciantes individuais.', 'features' => ['Até 5 anúncios ativos']],
        ['name' => 'Plano 15 imóveis', 'slug' => 'plano-15', 'price' => 199.9, 'max_listings' => 15, 'description' => 'Para corretores autônomos.', 'features' => ['Até 15 anúncios ativos']],
        ['name' => 'Plano 25 imóveis', 'slug' => 'plano-25', 'price' => 349.9, 'max_listings' => 25, 'description' => 'Para corretores e pequenas imobiliárias.', 'features' => ['Até 25 anúncios ativos']],
        ['name' => 'Plano 50 imóveis', 'slug' => 'plano-50', 'price' => 400, 'max_listings' => 50, 'description' => 'Para imobiliárias em crescimento.', 'features' => ['Até 50 anúncios ativos']],
        ['name' => 'Plano 75 imóveis', 'slug' => 'plano-75', 'price' => 500, 'max_listings' => 75, 'description' => 'Para imobiliárias com carteira ampla.', 'features' => ['Até 75 anúncios ativos']],
        ['name' => 'Novo plano 100 imóveis', 'slug' => 'plano-100', 'price' => 700, 'max_listings' => 100, 'description' => 'Para imobiliárias com grande carteira de imóveis.', 'features' => ['Até 100 anúncios ativos']],
    ];
    foreach ($plansData as $p) {
        $stmt = $pdo->prepare('INSERT INTO plans (name, slug, description, price, max_listings, features) VALUES (?,?,?,?,?,?)
            ON CONFLICT(slug) DO UPDATE SET name = excluded.name');
        $stmt->execute([$p['name'], $p['slug'], $p['description'], $p['price'], $p['max_listings'], json_encode($p['features'], JSON_UNESCAPED_UNICODE)]);
    }

    // --- Administrador ---------------------------------------------------------
    $adminHash = hash_password('Admin@12345');
    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES (?,?,?,?,?,?)
        ON CONFLICT(email) DO UPDATE SET first_name = excluded.first_name');
    $stmt->execute(['Administrador', 'Habitou', 'admin@habitou.com.br', $adminHash, 'ADMIN', 'ACTIVE']);

    // --- Cidades iniciais (bootstrap) ---------------------------------------
    // Garante que algumas cidades reais já existam no primeiro run, mesmo
    // sem nenhum imóvel cadastrado ainda.
    foreach (SEED_CITIES as $c) {
        $stmt = $pdo->prepare('INSERT INTO cities (name, slug, state, state_code, region, latitude, longitude) VALUES (?,?,?,?,?,?,?)
            ON CONFLICT(slug) DO UPDATE SET name = excluded.name');
        $stmt->execute([$c['name'], $c['slug'], $c['state'], $c['state_code'], BRAZIL_REGIONS[$c['state_code']] ?? null, $c['lat'], $c['lng']]);
    }

    // --- Central de ajuda ---------------------------------------------------
    // O blog foi descontinuado no portal (passa a viver num subdomínio
    // WordPress à parte) — só a central de ajuda (kind GUIDE) continua aqui.
    $insertArticle = $pdo->prepare('INSERT INTO articles (kind, slug, title, excerpt, category, author_name, author_role, read_minutes) VALUES (?,?,?,?,?,?,?,?)
        ON CONFLICT(slug) DO UPDATE SET title = excluded.title');
    $guides = [
        ['como-configurar-dns-registro-br', 'Como configurar o DNS do seu domínio no Registro.br', 'Passo a passo para apontar um domínio .com.br para o seu site, criando os registros CNAME e A no painel do Registro.br.', 'Site', 'Equipe Habitou Imóveis', null, 5],
    ];
    foreach ($guides as $guide) {
        $insertArticle->execute(['GUIDE', ...$guide]);
    }

    $pdo->commit();
    error_log('[habitou] Seed concluído: dados iniciais prontos (sem imóveis de exemplo).');
}
