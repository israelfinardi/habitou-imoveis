import "server-only";
import { prisma } from "@/lib/db";
import { Prisma } from "@prisma/client";
import type { PropertySearchParams } from "@/lib/validation/property";
import { SLUG_TO_LISTING_TYPE, SLUG_TO_PROPERTY_TYPE, PAGE_SIZE } from "@/lib/constants/property";

export const propertyListItemInclude = {
  city: true,
  neighborhood: true,
  images: { orderBy: { order: "asc" as const }, take: 1 },
  agency: { select: { id: true, name: true, slug: true, logoUrl: true } },
  advertiser: { select: { id: true, firstName: true, lastName: true } },
} satisfies Prisma.PropertyInclude;

export const propertyDetailInclude = {
  city: true,
  neighborhood: true,
  images: { orderBy: { order: "asc" as const } },
  agency: true,
  advertiser: { select: { id: true, firstName: true, lastName: true, phone: true, email: true, avatarUrl: true } },
  agent: { select: { id: true, firstName: true, lastName: true, phone: true, email: true, avatarUrl: true, creci: true } },
} satisfies Prisma.PropertyInclude;

function buildWhere(params: PropertySearchParams, extra?: Prisma.PropertyWhereInput): Prisma.PropertyWhereInput {
  const where: Prisma.PropertyWhereInput = {
    status: "PUBLISHED",
    ...extra,
  };

  if (params.cidade) where.city = { slug: params.cidade };
  if (params.bairro) where.neighborhood = { slug: params.bairro };
  if (params.transacao) where.listingType = SLUG_TO_LISTING_TYPE[params.transacao];
  if (params.tipo) {
    const type = SLUG_TO_PROPERTY_TYPE[params.tipo];
    if (type) where.propertyType = type;
  }
  if (params.imobiliaria) where.agency = { slug: params.imobiliaria };

  const priceField = params.transacao === "alugar" ? "priceRent" : "priceSale";
  if (params.precoMin !== undefined || params.precoMax !== undefined) {
    where[priceField] = {
      ...(params.precoMin !== undefined ? { gte: params.precoMin } : {}),
      ...(params.precoMax !== undefined ? { lte: params.precoMax } : {}),
    };
  }

  if (params.quartos !== undefined) where.bedrooms = { gte: params.quartos };
  if (params.suites !== undefined) where.suites = { gte: params.suites };
  if (params.banheiros !== undefined) where.bathrooms = { gte: params.banheiros };
  if (params.vagas !== undefined) where.parkingSpaces = { gte: params.vagas };

  if (params.areaMin !== undefined || params.areaMax !== undefined) {
    where.totalArea = {
      ...(params.areaMin !== undefined ? { gte: params.areaMin } : {}),
      ...(params.areaMax !== undefined ? { lte: params.areaMax } : {}),
    };
  }

  if (params.caracteristicas?.length) {
    where.features = { hasEvery: params.caracteristicas };
  }

  if (params.q) {
    where.OR = [
      { title: { contains: params.q, mode: "insensitive" } },
      { description: { contains: params.q, mode: "insensitive" } },
      { code: { contains: params.q, mode: "insensitive" } },
      { neighborhood: { name: { contains: params.q, mode: "insensitive" } } },
    ];
  }

  return where;
}

function buildOrderBy(sort: PropertySearchParams["ordenar"], transacao?: string): Prisma.PropertyOrderByWithRelationInput[] {
  switch (sort) {
    case "menor-preco":
      return transacao === "alugar" ? [{ priceRent: "asc" }] : [{ priceSale: "asc" }];
    case "maior-preco":
      return transacao === "alugar" ? [{ priceRent: "desc" }] : [{ priceSale: "desc" }];
    case "maior-area":
      return [{ totalArea: "desc" }];
    case "recentes":
    default:
      return [{ publishedAt: "desc" }];
  }
}

export async function listProperties(
  params: PropertySearchParams,
  extraWhere?: Prisma.PropertyWhereInput
) {
  const where = buildWhere(params, extraWhere);
  const page = Math.max(1, params.pagina ?? 1);
  const skip = (page - 1) * PAGE_SIZE;

  const [items, total] = await Promise.all([
    prisma.property.findMany({
      where,
      include: propertyListItemInclude,
      orderBy: buildOrderBy(params.ordenar, params.transacao),
      skip,
      take: PAGE_SIZE,
    }),
    prisma.property.count({ where }),
  ]);

  return {
    items,
    total,
    page,
    pageSize: PAGE_SIZE,
    totalPages: Math.max(1, Math.ceil(total / PAGE_SIZE)),
  };
}

export async function getPropertyBySlug(slug: string) {
  return prisma.property.findUnique({
    where: { slug },
    include: propertyDetailInclude,
  });
}

export async function getPropertyById(id: string) {
  return prisma.property.findUnique({
    where: { id },
    include: propertyDetailInclude,
  });
}

export async function getSimilarProperties(property: {
  id: string;
  cityId: string;
  neighborhoodId: string | null;
  propertyType: string;
  listingType: string;
  priceSale: Prisma.Decimal | null;
  priceRent: Prisma.Decimal | null;
}) {
  const priceRef = Number(property.priceSale ?? property.priceRent ?? 0);
  const priceMin = priceRef > 0 ? priceRef * 0.6 : undefined;
  const priceMax = priceRef > 0 ? priceRef * 1.6 : undefined;
  const priceField = property.listingType === "RENT" ? "priceRent" : "priceSale";

  const byNeighborhood = property.neighborhoodId
    ? await prisma.property.findMany({
        where: {
          id: { not: property.id },
          status: "PUBLISHED",
          neighborhoodId: property.neighborhoodId,
          propertyType: property.propertyType as never,
          listingType: property.listingType as never,
        },
        include: propertyListItemInclude,
        take: 4,
        orderBy: { publishedAt: "desc" },
      })
    : [];

  if (byNeighborhood.length >= 4) return byNeighborhood;

  const byCity = await prisma.property.findMany({
    where: {
      id: { not: property.id, notIn: byNeighborhood.map((p) => p.id) },
      status: "PUBLISHED",
      cityId: property.cityId,
      propertyType: property.propertyType as never,
      listingType: property.listingType as never,
      ...(priceMin !== undefined && priceMax !== undefined
        ? { [priceField]: { gte: priceMin, lte: priceMax } }
        : {}),
    },
    include: propertyListItemInclude,
    take: 4 - byNeighborhood.length,
    orderBy: { publishedAt: "desc" },
  });

  return [...byNeighborhood, ...byCity];
}

export async function getFeaturedProperties(limit = 6) {
  return prisma.property.findMany({
    where: { status: "PUBLISHED" },
    include: propertyListItemInclude,
    orderBy: { publishedAt: "desc" },
    take: limit,
  });
}

export async function getPropertiesByCity(citySlug: string, limit = 6) {
  return prisma.property.findMany({
    where: { status: "PUBLISHED", city: { slug: citySlug } },
    include: propertyListItemInclude,
    orderBy: { publishedAt: "desc" },
    take: limit,
  });
}
