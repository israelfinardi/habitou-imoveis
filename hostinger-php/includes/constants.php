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

const PAGE_SIZE = 12;

const SORT_OPTIONS = [
    'recentes' => 'Mais recentes',
    'menor-preco' => 'Menor preço',
    'maior-preco' => 'Maior preço',
    'maior-area' => 'Maior área',
];
