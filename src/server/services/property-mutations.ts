import "server-only";
import { prisma } from "@/lib/db";
import { buildPropertySlug } from "@/lib/slug";
import { canManageProperty, AuthError } from "@/lib/auth/guards";
import type { SafeUser } from "@/lib/auth/session";
import type { PropertyFormInput } from "@/lib/validation/property";

async function generateCode(): Promise<string> {
  const last = await prisma.property.findFirst({
    orderBy: { createdAt: "desc" },
    select: { code: true },
  });
  const lastNum = last?.code?.match(/(\d+)$/)?.[1];
  const next = lastNum ? Number(lastNum) + 1 : 1000;
  return `HB-${next}`;
}

async function resolveCityAndNeighborhood(citySlug: string, neighborhoodName: string) {
  const city = await prisma.city.findUnique({ where: { slug: citySlug } });
  if (!city) throw new AuthError("Cidade inválida.", 400);

  const { toSlug } = await import("@/lib/slug");
  const slug = toSlug(neighborhoodName) || "sem-bairro";
  const neighborhood = await prisma.neighborhood.upsert({
    where: { cityId_slug: { cityId: city.id, slug } },
    update: {},
    create: { cityId: city.id, name: neighborhoodName, slug },
  });
  return { city, neighborhood };
}

export async function createProperty(
  input: PropertyFormInput,
  actor: SafeUser
): Promise<{ id: string; slug: string }> {
  const { city, neighborhood } = await resolveCityAndNeighborhood(input.cidade, input.bairro);
  const code = await generateCode();
  const suffix = Math.random().toString(36).slice(2, 8);
  const slug = buildPropertySlug({
    title: input.title,
    city: city.name,
    neighborhood: neighborhood.name,
    suffix,
  });

  const agencyId = actor.role === "AGENCY_ADMIN" || actor.role === "AGENT" ? actor.agencyId : null;

  const property = await prisma.property.create({
    data: {
      code,
      title: input.title,
      slug,
      description: input.description || null,
      listingType: input.listingType,
      propertyType: input.propertyType,
      priceSale: input.priceSale,
      priceRent: input.priceRent,
      condoFee: input.condoFee,
      iptu: input.iptu,
      totalArea: input.totalArea,
      builtArea: input.builtArea,
      bedrooms: input.bedrooms,
      suites: input.suites,
      bathrooms: input.bathrooms,
      parkingSpaces: input.parkingSpaces,
      features: input.features,
      status: "DRAFT",
      cityId: city.id,
      neighborhoodId: neighborhood.id,
      street: input.street || null,
      number: input.number || null,
      complement: input.complement || null,
      zipCode: input.zipCode || null,
      latitude: input.latitude,
      longitude: input.longitude,
      advertiserId: actor.id,
      agencyId: agencyId ?? undefined,
    },
  });

  return { id: property.id, slug: property.slug };
}

export async function updateProperty(propertyId: string, input: PropertyFormInput, actor: SafeUser) {
  const existing = await prisma.property.findUnique({ where: { id: propertyId } });
  if (!existing) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, existing)) throw new AuthError("Você não pode editar este imóvel.", 403);

  const { city, neighborhood } = await resolveCityAndNeighborhood(input.cidade, input.bairro);

  await prisma.property.update({
    where: { id: propertyId },
    data: {
      title: input.title,
      description: input.description || null,
      listingType: input.listingType,
      propertyType: input.propertyType,
      priceSale: input.priceSale,
      priceRent: input.priceRent,
      condoFee: input.condoFee,
      iptu: input.iptu,
      totalArea: input.totalArea,
      builtArea: input.builtArea,
      bedrooms: input.bedrooms,
      suites: input.suites,
      bathrooms: input.bathrooms,
      parkingSpaces: input.parkingSpaces,
      features: input.features,
      cityId: city.id,
      neighborhoodId: neighborhood.id,
      street: input.street || null,
      number: input.number || null,
      complement: input.complement || null,
      zipCode: input.zipCode || null,
      latitude: input.latitude,
      longitude: input.longitude,
    },
  });
}

export async function deleteProperty(propertyId: string, actor: SafeUser) {
  const existing = await prisma.property.findUnique({ where: { id: propertyId } });
  if (!existing) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, existing)) throw new AuthError("Você não pode excluir este imóvel.", 403);
  await prisma.property.delete({ where: { id: propertyId } });
}

export type PropertyStatusAction = "publish" | "pause" | "archive" | "reactivate";

export async function setPropertyStatus(propertyId: string, action: PropertyStatusAction, actor: SafeUser) {
  const existing = await prisma.property.findUnique({ where: { id: propertyId } });
  if (!existing) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, existing)) throw new AuthError("Você não pode alterar este imóvel.", 403);

  const statusMap: Record<PropertyStatusAction, "PUBLISHED" | "PAUSED" | "ARCHIVED"> = {
    publish: "PUBLISHED",
    reactivate: "PUBLISHED",
    pause: "PAUSED",
    archive: "ARCHIVED",
  };
  const nextStatus = statusMap[action];

  await prisma.property.update({
    where: { id: propertyId },
    data: {
      status: nextStatus,
      publishedAt: nextStatus === "PUBLISHED" && !existing.publishedAt ? new Date() : existing.publishedAt,
      deactivatedAt: nextStatus === "ARCHIVED" ? new Date() : null,
    },
  });
}

export async function duplicateProperty(propertyId: string, actor: SafeUser) {
  const existing = await prisma.property.findUnique({
    where: { id: propertyId },
    include: { images: true },
  });
  if (!existing) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, existing)) throw new AuthError("Você não pode duplicar este imóvel.", 403);

  const code = await generateCode();
  const suffix = Math.random().toString(36).slice(2, 8);
  const slug = buildPropertySlug({ title: `${existing.title} copia`, city: "", suffix });

  const clone = await prisma.property.create({
    data: {
      code,
      title: `${existing.title} (cópia)`,
      slug,
      description: existing.description,
      listingType: existing.listingType,
      propertyType: existing.propertyType,
      priceSale: existing.priceSale,
      priceRent: existing.priceRent,
      condoFee: existing.condoFee,
      iptu: existing.iptu,
      totalArea: existing.totalArea,
      builtArea: existing.builtArea,
      bedrooms: existing.bedrooms,
      suites: existing.suites,
      bathrooms: existing.bathrooms,
      parkingSpaces: existing.parkingSpaces,
      features: existing.features,
      status: "DRAFT",
      cityId: existing.cityId,
      neighborhoodId: existing.neighborhoodId,
      street: existing.street,
      number: existing.number,
      complement: existing.complement,
      zipCode: existing.zipCode,
      latitude: existing.latitude,
      longitude: existing.longitude,
      advertiserId: actor.id,
      agencyId: existing.agencyId,
      images: {
        create: existing.images.map((img) => ({
          url: img.url,
          order: img.order,
          isPrimary: img.isPrimary,
          origin: img.origin,
        })),
      },
    },
  });

  return clone;
}

export async function listPropertiesForAdvertiser(actor: SafeUser) {
  const where =
    actor.role === "ADMIN"
      ? {}
      : actor.agencyId
        ? { OR: [{ advertiserId: actor.id }, { agencyId: actor.agencyId }] }
        : { advertiserId: actor.id };

  return prisma.property.findMany({
    where,
    include: {
      city: true,
      neighborhood: true,
      images: { orderBy: { order: "asc" as const }, take: 1 },
    },
    orderBy: { updatedAt: "desc" },
  });
}

// --- Fotos ---------------------------------------------------------------

export async function addPropertyImage(
  propertyId: string,
  url: string,
  actor: SafeUser,
  meta?: { width?: number; height?: number; sizeBytes?: number }
) {
  const existing = await prisma.property.findUnique({ where: { id: propertyId }, include: { images: true } });
  if (!existing) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, existing)) throw new AuthError("Você não pode editar fotos deste imóvel.", 403);

  const maxOrder = existing.images.reduce((m, i) => Math.max(m, i.order), -1);
  return prisma.propertyImage.create({
    data: {
      propertyId,
      url,
      order: maxOrder + 1,
      isPrimary: existing.images.length === 0,
      origin: "MANUAL",
      width: meta?.width,
      height: meta?.height,
      sizeBytes: meta?.sizeBytes,
    },
  });
}

export async function removePropertyImage(imageId: string, actor: SafeUser) {
  const image = await prisma.propertyImage.findUnique({
    where: { id: imageId },
    include: { property: true },
  });
  if (!image) throw new AuthError("Foto não encontrada.", 404);
  if (!canManageProperty(actor, image.property)) throw new AuthError("Você não pode remover esta foto.", 403);

  await prisma.propertyImage.delete({ where: { id: imageId } });

  if (image.isPrimary) {
    const next = await prisma.propertyImage.findFirst({
      where: { propertyId: image.propertyId },
      orderBy: { order: "asc" },
    });
    if (next) await prisma.propertyImage.update({ where: { id: next.id }, data: { isPrimary: true } });
  }
}

export async function setPrimaryImage(imageId: string, actor: SafeUser) {
  const image = await prisma.propertyImage.findUnique({
    where: { id: imageId },
    include: { property: true },
  });
  if (!image) throw new AuthError("Foto não encontrada.", 404);
  if (!canManageProperty(actor, image.property)) throw new AuthError("Você não pode editar fotos deste imóvel.", 403);

  await prisma.$transaction([
    prisma.propertyImage.updateMany({ where: { propertyId: image.propertyId }, data: { isPrimary: false } }),
    prisma.propertyImage.update({ where: { id: imageId }, data: { isPrimary: true } }),
  ]);
}

export async function reorderPropertyImages(propertyId: string, orderedIds: string[], actor: SafeUser) {
  const property = await prisma.property.findUnique({ where: { id: propertyId } });
  if (!property) throw new AuthError("Imóvel não encontrado.", 404);
  if (!canManageProperty(actor, property)) throw new AuthError("Você não pode editar fotos deste imóvel.", 403);

  await prisma.$transaction(
    orderedIds.map((id, index) =>
      prisma.propertyImage.update({ where: { id }, data: { order: index } })
    )
  );
}
