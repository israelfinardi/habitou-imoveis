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
    'EXPIRED' => 'Expirado',
];

/** Pontos de interesse cadastrados pelo admin — bloco "Perto de pontos de interesse" na home. */
const POI_TYPE_LABEL = [
    'UNIVERSIDADE' => 'Universidade',
    'POLO_EMPRESARIAL' => 'Polo empresarial',
    'HOSPITAL' => 'Hospital',
    'SHOPPING' => 'Shopping',
    'PARQUE' => 'Parque',
    'OUTRO' => 'Outro',
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

// Grid padrão de cards (mínimo 250px cada, preenchendo as colunas
// disponíveis) reaproveitado em toda página que lista alguma coisa —
// usuários, imobiliárias, planos, assinaturas, contratos, feeds no admin,
// e nas páginas de conta que já usam grid de imóvel — pra manter o mesmo
// alinhamento visual em qualquer lugar do site.
const CARD_GRID_CLASS = 'grid grid-cols-1 gap-4 sm:grid-cols-[repeat(auto-fill,minmax(250px,1fr))]';

// Quantos imóveis o admin pode marcar como destaque (admin/imoveis.php) —
// usado tanto pra travar a seleção quanto pelo limite padrão da home.
const FEATURED_PROPERTIES_LIMIT = 4;

const SORT_OPTIONS = [
    'recentes' => 'Mais recentes',
    'menor-preco' => 'Menor preço',
    'maior-preco' => 'Maior preço',
    'maior-area' => 'Maior área',
];

// Ícones (paths de svg, viewBox 24x24) usados nos menus da área logada —
// includes/account_nav.php, includes/admin_nav.php e o dropdown "Minha
// conta" do header. Centralizados aqui pra não duplicar o mesmo path em
// mais de um lugar.
const NAV_ICONS = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'house' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
    'building' => '<path d="M6 21V7l6-4 6 4v14"/><path d="M3 21h18"/><path d="M10 21v-4h4v4"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1"/>',
    'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
    'document' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/><path d="M9 13h6M9 17h6"/>',
    'sync' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
    'tag' => '<path d="M20.6 12.4 12.6 20.4a2 2 0 0 1-2.8 0l-6.2-6.2a2 2 0 0 1 0-2.8L11.6 3.4a2 2 0 0 1 1.4-.6H19a2 2 0 0 1 2 2v5.6a2 2 0 0 1-.4 1.4z"/><circle cx="15.5" cy="7.5" r="1.4"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>',
    'users' => '<circle cx="8" cy="8" r="3.2"/><path d="M2 20c0-3.3 2.8-5.2 6-5.2s6 1.9 6 5.2"/><circle cx="17" cy="8" r="2.6"/><path d="M15.5 14.3c2.7.4 4.5 2.2 4.5 5.7"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    'pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
];
