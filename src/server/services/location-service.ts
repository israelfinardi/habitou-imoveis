import "server-only";
import { prisma } from "@/lib/db";

export async function getCityBySlug(slug: string) {
  return prisma.city.findUnique({ where: { slug } });
}

export async function listCities() {
  return prisma.city.findMany({ orderBy: { name: "asc" } });
}

export async function listNeighborhoodsByCity(cityId: string) {
  return prisma.neighborhood.findMany({ where: { cityId }, orderBy: { name: "asc" } });
}

export async function getNeighborhoodBySlug(cityId: string, slug: string) {
  return prisma.neighborhood.findUnique({ where: { cityId_slug: { cityId, slug } } });
}
