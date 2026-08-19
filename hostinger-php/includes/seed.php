<?php
/**
 * Popula o banco com os dados essenciais para o site funcionar (planos,
 * conta de administrador, cidades em destaque e conteúdo institucional de
 * blog/central de ajuda) — sem nenhum imóvel ou imobiliária de exemplo.
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

    // --- Blog e central de ajuda -------------------------------------------
    $blogPosts = [
        ['seo-local-para-imobiliaria', 'SEO local para imobiliária: como aparecer no Google quando alguém busca imóvel no seu bairro', 'SEO para imobiliária na prática: arquitetura de URLs, quando criar página de bairro, Perfil da Empresa, avaliações dentro da política e plano de 90 dias.', 'Marketing', 'Camila Duarte', 'Especialista em SEO e conteúdo digital', 9],
        ['custos-de-comprar-um-imovel', 'Custos de comprar um imóvel: ITBI, escritura, registro e o que mais entra na conta', 'Guia dos custos da compra: como calcular o ITBI, quando a escritura é dispensada, emolumentos por faixa e as reduções que só valem se você pedir.', 'Comprar', 'Eduardo Lins', 'Contador, especialista fiscal e tributário imobiliário', 11],
        ['lei-do-inquilinato-explicada', 'Lei do Inquilinato explicada: direitos e deveres de quem aluga e de quem loca', 'Guia da Lei do Inquilinato: deveres de cada lado, prazo de 30 meses, multa proporcional, venda do imóvel, direito de preferência e quando cabe despejo.', 'Alugar', 'Patrícia Nogueira', 'Advogada, especialista em direito imobiliário', 9],
        ['investir-em-imoveis-no-brasil', 'Investir em imóveis no Brasil: rentabilidade real, regiões e os erros que corroem o retorno', 'Como calcular a rentabilidade real de um imóvel, o peso da vacância, cinco estratégias comparadas e os números de diferentes regiões do país.', 'Investir', 'Fernanda Coelho', 'Analista de mercado imobiliário', 10],
        ['custo-por-lead-imobiliario', 'Custo por lead imobiliário: quanto você realmente paga e qual é o número saudável', 'Como calcular o custo por lead imobiliário sem se enganar: as fórmulas de CPL e CAC, o teto que a sua comissão suporta e seis alavancas para baixá-lo.', 'CRM', 'Bruno Tavares', 'Especialista em mídia paga e Meta Ads', 8],
        ['publicar-imoveis-no-instagram-automaticamente', 'Publicar imóveis no Instagram automaticamente: como sair de 2 posts por semana para 2 por dia', 'Como publicar imóveis no Instagram automaticamente: o fluxo do cadastro ao post, o que a API oficial permite e a proporção que não derruba o seu alcance.', 'Marketing', 'Larissa Prado', 'Especialista em marketing e redes sociais', 8],
        ['lgpd-para-imobiliarias', 'LGPD para imobiliárias: o guia prático de adequação (com checklist e modelos)', 'Guia de LGPD para imobiliárias: base legal por finalidade, análise cadastral, documento por WhatsApp, prazos de resposta e o regime de pequeno porte.', 'Gestão', 'Patrícia Nogueira', 'Advogada, especialista em direito imobiliário', 12],
    ];
    $insertArticle = $pdo->prepare('INSERT INTO articles (kind, slug, title, excerpt, category, author_name, author_role, read_minutes) VALUES (?,?,?,?,?,?,?,?)
        ON CONFLICT(slug) DO UPDATE SET title = excluded.title');
    foreach ($blogPosts as $post) {
        $insertArticle->execute(['BLOG', ...$post]);
    }
    $guides = [
        ['como-configurar-dns-registro-br', 'Como configurar o DNS do seu domínio no Registro.br', 'Passo a passo para apontar um domínio .com.br para o seu site, criando os registros CNAME e A no painel do Registro.br.', 'Site', 'Equipe Habitou Imóveis', null, 5],
    ];
    foreach ($guides as $guide) {
        $insertArticle->execute(['GUIDE', ...$guide]);
    }

    $pdo->commit();
    error_log('[habitou] Seed concluído: dados iniciais prontos (sem imóveis de exemplo).');
}
