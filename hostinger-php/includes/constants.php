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
];

const FEATURED_CITIES = [
    ['name' => 'Florianópolis', 'slug' => 'florianopolis', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -27.5954, 'lng' => -48.5480],
    ['name' => 'Blumenau', 'slug' => 'blumenau', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9194, 'lng' => -49.0661],
    ['name' => 'Balneário Camboriú', 'slug' => 'balneario-camboriu', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9906, 'lng' => -48.6349],
    ['name' => 'Joinville', 'slug' => 'joinville', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.3044, 'lng' => -48.8464],
    ['name' => 'Itajaí', 'slug' => 'itajai', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.9078, 'lng' => -48.6614],
    ['name' => 'Indaial', 'slug' => 'indaial', 'state' => 'Santa Catarina', 'state_code' => 'SC', 'lat' => -26.8977, 'lng' => -49.2306],
];

// Mesorregiões de Santa Catarina, usadas na seção "Explore por região" da
// home — agrupamento geográfico real (não depende de cities.region, que só
// é preenchido para cidades cadastradas via o localizador de cadastro).
const SC_REGIONS = [
    ['name' => 'Grande Florianópolis', 'subtitle' => 'Capital · Litoral central', 'cities' => ['Florianópolis', 'São José', 'Palhoça', 'Biguaçu']],
    ['name' => 'Vale Europeu', 'subtitle' => 'Imigração · Tradição', 'cities' => ['Blumenau', 'Brusque', 'Gaspar', 'Indaial', 'Pomerode']],
    ['name' => 'Costa Esmeralda', 'subtitle' => 'Litoral norte', 'cities' => ['Balneário Camboriú', 'Itajaí', 'Itapema', 'Bombinhas']],
    ['name' => 'Norte Catarinense', 'subtitle' => 'Indústria · Logística', 'cities' => ['Joinville', 'Jaraguá do Sul', 'São Bento do Sul']],
    ['name' => 'Alto Vale do Itajaí', 'subtitle' => 'Vale interior', 'cities' => ['Rio do Sul', 'Ituporanga', 'Ibirama', 'Taió']],
    ['name' => 'Serrana', 'subtitle' => 'Serra Catarinense', 'cities' => ['Lages', 'São Joaquim', 'Bom Retiro', 'Urubici']],
    ['name' => 'Oeste Catarinense', 'subtitle' => 'Agronegócio · Fronteira', 'cities' => ['Chapecó', 'Concórdia', 'São Miguel do Oeste']],
    ['name' => 'Sul Catarinense', 'subtitle' => 'Litoral sul · Carvão', 'cities' => ['Criciúma', 'Tubarão', 'Araranguá', 'Laguna']],
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

const SORT_OPTIONS = [
    'recentes' => 'Mais recentes',
    'menor-preco' => 'Menor preço',
    'maior-preco' => 'Maior preço',
    'maior-area' => 'Maior área',
];
