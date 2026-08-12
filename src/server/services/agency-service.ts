import "server-only";
import { prisma } from "@/lib/db";

const PAGE_SIZE = 12;

export async function listAgencies(page = 1, q?: string) {
  const where = {
    status: "ACTIVE" as const,
    ...(q ? { name: { contains: q, mode: "insensitive" as const } } : {}),
  };
  const skip = (Math.max(1, page) - 1) * PAGE_SIZE;

  const [items, total] = await Promise.all([
    prisma.agency.findMany({
      where,
      orderBy: { name: "asc" },
      skip,
      take: PAGE_SIZE,
      include: { _count: { select: { properties: { where: { status: "PUBLISHED" } } } } },
    }),
    prisma.agency.count({ where }),
  ]);

  return { items, total, page: Math.max(1, page), totalPages: Math.max(1, Math.ceil(total / PAGE_SIZE)) };
}

export async function getAgencyBySlug(slug: string) {
  return prisma.agency.findUnique({ where: { slug } });
}

export async function getAgencyAgents(agencyId: string) {
  return prisma.user.findMany({
    where: { agencyId, role: { in: ["AGENT", "AGENCY_ADMIN"] }, status: "ACTIVE" },
    select: { id: true, firstName: true, lastName: true, avatarUrl: true, creci: true, role: true },
    orderBy: { firstName: "asc" },
  });
}

export async function getAgentById(id: string) {
  return prisma.user.findFirst({
    where: { id, role: { in: ["AGENT", "AGENCY_ADMIN"] }, status: "ACTIVE" },
    include: { agency: true },
  });
}
