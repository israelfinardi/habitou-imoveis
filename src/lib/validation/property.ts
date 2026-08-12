import { z } from "zod";

const numberFromString = () =>
  z
    .string()
    .optional()
    .transform((v) => (v && v.trim() !== "" ? Number(v) : undefined))
    .refine((v) => v === undefined || !Number.isNaN(v), "Valor numérico inválido.");

export const propertySearchParamsSchema = z.object({
  cidade: z.string().optional(),
  transacao: z.enum(["comprar", "alugar"]).optional(),
  tipo: z.string().optional(),
  bairro: z.string().optional(),
  precoMin: numberFromString(),
  precoMax: numberFromString(),
  quartos: numberFromString(),
  suites: numberFromString(),
  banheiros: numberFromString(),
  vagas: numberFromString(),
  areaMin: numberFromString(),
  areaMax: numberFromString(),
  caracteristicas: z
    .string()
    .optional()
    .transform((v) => (v ? v.split(",").filter(Boolean) : undefined)),
  imobiliaria: z.string().optional(),
  q: z.string().optional(),
  ordenar: z.enum(["recentes", "menor-preco", "maior-preco", "maior-area"]).optional(),
  pagina: numberFromString(),
});

export type PropertySearchParams = z.infer<typeof propertySearchParamsSchema>;

export const propertyFormSchema = z.object({
  title: z.string().trim().min(10, "O título deve ter pelo menos 10 caracteres."),
  description: z.string().trim().min(20, "Descreva o imóvel com mais detalhes.").optional().or(z.literal("")),
  listingType: z.enum(["SALE", "RENT"]),
  propertyType: z.enum([
    "APARTMENT",
    "HOUSE",
    "LAND",
    "COMMERCIAL_ROOM",
    "STORE",
    "WAREHOUSE",
    "RURAL",
    "BUILDING",
    "OTHER",
  ]),
  priceSale: numberFromString(),
  priceRent: numberFromString(),
  condoFee: numberFromString(),
  iptu: numberFromString(),
  totalArea: numberFromString(),
  builtArea: numberFromString(),
  bedrooms: numberFromString(),
  suites: numberFromString(),
  bathrooms: numberFromString(),
  parkingSpaces: numberFromString(),
  features: z
    .array(z.string())
    .optional()
    .default([]),
  cidade: z.string().min(1, "Selecione a cidade."),
  bairro: z.string().trim().min(1, "Informe o bairro."),
  street: z.string().trim().optional().or(z.literal("")),
  number: z.string().trim().optional().or(z.literal("")),
  complement: z.string().trim().optional().or(z.literal("")),
  zipCode: z.string().trim().optional().or(z.literal("")),
  latitude: numberFromString(),
  longitude: numberFromString(),
});

export type PropertyFormInput = z.infer<typeof propertyFormSchema>;
