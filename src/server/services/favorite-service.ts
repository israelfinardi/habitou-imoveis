import "server-only";
import { prisma } from "@/lib/db";

export async function isFavorite(userId: string, propertyId: string) {
  const fav = await prisma.favorite.findUnique({
    where: { userId_propertyId: { userId, propertyId } },
  });
  return !!fav;
}

export async function toggleFavorite(userId: string, propertyId: string) {
  const existing = await prisma.favorite.findUnique({
    where: { userId_propertyId: { userId, propertyId } },
  });
  if (existing) {
    await prisma.favorite.delete({ where: { id: existing.id } });
    return false;
  }
  await prisma.favorite.create({ data: { userId, propertyId } });
  return true;
}

export async function listFavoriteIds(userId: string) {
  const favs = await prisma.favorite.findMany({ where: { userId }, select: { propertyId: true } });
  return new Set(favs.map((f) => f.propertyId));
}

export async function listFavoriteProperties(userId: string) {
  const { propertyListItemInclude } = await import("@/server/services/property-service");
  const favs = await prisma.favorite.findMany({
    where: { userId },
    include: { property: { include: propertyListItemInclude } },
    orderBy: { createdAt: "desc" },
  });
  return favs.map((f) => f.property);
}
