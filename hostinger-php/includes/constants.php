<?php

const LISTING_TYPE_SLUG = ['SALE' => 'comprar', 'RENT' => 'alugar'];
const LISTING_TYPE_LABEL = ['SALE' => 'Venda', 'RENT' => 'Aluguel'];
const SLUG_TO_LISTING_TYPE = ['comprar' => 'SALE', 'alugar' => 'RENT'];

const PROPERTY_TYPE_SLUG = [
    'APARTMENT' => 'apartamento',
    'HOUSE' => 'casa',
    'LAND' => 'terreno',
    'COMMERCIAL_ROOM' => 'sala-escritorio',
    'STORE' => 'loja',
    'WAREHOUSE' => 'galpao',
    'RURAL' => 'imovel-rural',
    'BUILDING' => 'predio',
    'OTHER' => 'outros-imoveis',
];

const PROPERTY_TYPE_LABEL = [
    'APARTMENT' => 'Apartamento',
    'HOUSE' => 'Casa',
    'LAND' => 'Terreno',
    'COMMERCIAL_ROOM' => 'Sala/Escritório',
    'STORE' => 'Loja',
    'WAREHOUSE' => 'Galpão',
    'RURAL' => 'Imóvel Rural',
    'BUILDING' => 'Prédio',
    'OTHER' => 'Outros Imóveis',
];

const PROPERTY_STATUS_LABEL = [
    'DRAFT' => 'Rascunho',
    'PUBLISHED' => 'Publicado',
    'PAUSED' => 'Pausado',
    'ARCHIVED' => 'Arquivado',
];

const COMMON_FEATURES = [
    'Piscina', 'Churrasqueira', 'Academia', 'Portaria 24h', 'Elevador',
    'Mobiliado', 'Semi-mobiliado', 'Vista para o mar', 'Área de lazer',
    'Salão de festas', 'Playground', 'Quadra esportiva', 'Ar condicionado',
    'Aceita animais', 'Varanda gourmet', 'Armários planejados',
    'Garagem coberta', 'Segurança 24h',
    'Sacada', 'Closet', 'Lareira', 'Home office', 'Depósito',
    'Espaço pet', 'Coworking', 'Sauna', 'Portão eletrônico',
    'Câmeras de segurança', 'Gerador', 'Fechadura digital',
    'Isolamento acústico', 'Condomínio fechado', 'Aceita financiamento',
    'Estuda permuta', 'Perto de transporte público',
];

// Agrupamento de COMMON_FEATURES por categoria, usado só na exibição do
// wizard de anúncio (includes/property_wizard.php) — o filtro de busca
// (filters_modal.php) continua com a lista plana.
const FEATURE_GROUPS = [
    'Lazer' => ['Piscina', 'Churrasqueira', 'Academia', 'Área de lazer', 'Salão de festas', 'Playground', 'Quadra esportiva', 'Sauna'],
    'Conforto' => ['Mobiliado', 'Semi-mobiliado', 'Ar condicionado', 'Aceita animais', 'Varanda gourmet', 'Armários planejados', 'Sacada', 'Closet', 'Lareira', 'Home office', 'Isolamento acústico'],
    'Segurança' => ['Portaria 24h', 'Segurança 24h', 'Portão eletrônico', 'Câmeras de segurança', 'Fechadura digital', 'Gerador'],
    'Estrutura e localização' => ['Elevador', 'Garagem coberta', 'Vista para o mar', 'Depósito', 'Espaço pet', 'Coworking', 'Condomínio fechado', 'Perto de transporte público'],
    'Negociação' => ['Aceita financiamento', 'Estuda permuta'],
];

// Não é mais exibida como "cidades em destaque" em lugar nenhum do site (o
// portal cobre o Brasil todo, não uma região específica) — serve só de
// bootstrap: popula algumas cidades reais no primeiro run (includes/seed.php)
// e garante slugs estáveis para elas em get_or_create_city() /
// property_mutations.php, evitando duplicar a cidade com um slug diferente
// caso ela já tenha imóveis publicados sob o slug curto (ex. "florianopolis"
// em vez de "florianopolis-sc").
const SEED_CITIES = [
    ['name' => 'Florianópolis', 'slug' => 'florianopolis', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -27.5954, 'lng' => -48.5480],
    ['name' => 'Blumenau', 'slug' => 'blumenau', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9194, 'lng' => -49.0661],
    ['name' => 'Balneário Camboriú', 'slug' => 'balneario-camboriu', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9906, 'lng' => -48.6349],
    ['name' => 'Joinville', 'slug' => 'joinville', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.3044, 'lng' => -48.8464],
    ['name' => 'Itajaí', 'slug' => 'itajai', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9078, 'lng' => -48.6614],
    ['name' => 'Indaial', 'slug' => 'indaial', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.8977, 'lng' => -49.2306],
];

const BRAZIL_STATES = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins',
];

const BRAZIL_REGIONS = [
    'AC' => 'Norte', 'AP' => 'Norte', 'AM' => 'Norte', 'PA' => 'Norte', 'RO' => 'Norte', 'RR' => 'Norte', 'TO' => 'Norte',
    'AL' => 'Nordeste', 'BA' => 'Nordeste', 'CE' => 'Nordeste', 'MA' => 'Nordeste', 'PB' => 'Nordeste',
    'PE' => 'Nordeste', 'PI' => 'Nordeste', 'RN' => 'Nordeste', 'SE' => 'Nordeste',
    'DF' => 'Centro-Oeste', 'GO' => 'Centro-Oeste', 'MT' => 'Centro-Oeste', 'MS' => 'Centro-Oeste',
    'ES' => 'Sudeste', 'MG' => 'Sudeste', 'RJ' => 'Sudeste', 'SP' => 'Sudeste',
    'PR' => 'Sul', 'RS' => 'Sul', 'SC' => 'Sul',
];

const PAGE_SIZE = 12;

// Quantos imóveis o admin pode marcar como destaque (admin/imoveis.php) —
// usado tanto pra travar a seleção quanto pelo limite padrão da home.
const FEATURED_PROPERTIES_LIMIT = 4;

const SORT_OPTIONS = [
    'recentes' => 'Mais recentes',
    'menor-preco' => 'Menor preço',
    'maior-preco' => 'Maior preço',
    'maior-area' => 'Maior área',
];
