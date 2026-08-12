import type { ListingType, PropertyType } from "@prisma/client";

/** Segmento de URL usado nas páginas de listagem, compatível com as URLs originais do site. */
export const LISTING_TYPE_SLUG: Record<ListingType, string> = {
  SALE: "comprar",
  RENT: "alugar",
};

export const LISTING_TYPE_LABEL: Record<ListingType, string> = {
  SALE: "Venda",
  RENT: "Aluguel",
};

export const SLUG_TO_LISTING_TYPE: Record<string, ListingType> = {
  comprar: "SALE",
  alugar: "RENT",
};

export const PROPERTY_TYPE_SLUG: Record<PropertyType, string> = {
  APARTMENT: "apartamento",
  HOUSE: "casa",
  LAND: "terreno",
  COMMERCIAL_ROOM: "sala-escritorio",
  STORE: "loja",
  WAREHOUSE: "galpao",
  RURAL: "imovel-rural",
  BUILDING: "predio",
  OTHER: "outros-imoveis",
};

export const PROPERTY_TYPE_LABEL: Record<PropertyType, string> = {
  APARTMENT: "Apartamento",
  HOUSE: "Casa",
  LAND: "Terreno",
  COMMERCIAL_ROOM: "Sala/Escritório",
  STORE: "Loja",
  WAREHOUSE: "Galpão",
  RURAL: "Imóvel Rural",
  BUILDING: "Prédio",
  OTHER: "Outros Imóveis",
};

export const SLUG_TO_PROPERTY_TYPE: Record<string, PropertyType> = Object.fromEntries(
  Object.entries(PROPERTY_TYPE_SLUG).map(([type, slug]) => [slug, type as PropertyType])
) as Record<string, PropertyType>;

export const PROPERTY_TYPES: PropertyType[] = Object.keys(PROPERTY_TYPE_SLUG) as PropertyType[];
export const LISTING_TYPES: ListingType[] = ["SALE", "RENT"];

export const PROPERTY_STATUS_LABEL: Record<string, string> = {
  DRAFT: "Rascunho",
  PUBLISHED: "Publicado",
  PAUSED: "Pausado",
  ARCHIVED: "Arquivado",
};

export const COMMON_FEATURES = [
  "Piscina",
  "Churrasqueira",
  "Academia",
  "Portaria 24h",
  "Elevador",
  "Mobiliado",
  "Semi-mobiliado",
  "Vista para o mar",
  "Área de lazer",
  "Salão de festas",
  "Playground",
  "Quadra esportiva",
  "Ar condicionado",
  "Aceita animais",
  "Varanda gourmet",
  "Armários planejados",
  "Garagem coberta",
  "Segurança 24h",
];

export const SORT_OPTIONS = [
  { value: "recentes", label: "Mais recentes" },
  { value: "menor-preco", label: "Menor preço" },
  { value: "maior-preco", label: "Maior preço" },
  { value: "maior-area", label: "Maior área" },
] as const;

export type SortOption = (typeof SORT_OPTIONS)[number]["value"];

export const PAGE_SIZE = 12;
