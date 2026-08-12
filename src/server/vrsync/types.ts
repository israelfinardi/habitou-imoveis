/**
 * Modelo interno de imóvel normalizado a partir de um feed VRSync (ou de
 * qualquer outra origem de importação). O restante da aplicação nunca
 * trabalha diretamente com o XML — apenas com este DTO.
 */
export type NormalizedListing = {
  externalId: string;
  externalCode?: string;
  title: string;
  description?: string;
  listingType: "SALE" | "RENT";
  propertyType:
    | "APARTMENT"
    | "HOUSE"
    | "LAND"
    | "COMMERCIAL_ROOM"
    | "STORE"
    | "WAREHOUSE"
    | "RURAL"
    | "BUILDING"
    | "OTHER";
  priceSale?: number;
  priceRent?: number;
  condoFee?: number;
  iptu?: number;
  totalArea?: number;
  builtArea?: number;
  bedrooms?: number;
  suites?: number;
  bathrooms?: number;
  parkingSpaces?: number;
  features: string[];
  active: boolean;
  address?: {
    street?: string;
    number?: string;
    neighborhood?: string;
    city: string;
    state: string;
    zipCode?: string;
    latitude?: number;
    longitude?: number;
  };
  photos: string[];
};

export type ParseIssue = { externalId?: string; message: string };

export type ParseResult = {
  listings: NormalizedListing[];
  issues: ParseIssue[];
};
