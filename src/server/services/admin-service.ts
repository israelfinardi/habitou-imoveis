import "server-only";
import { prisma } from "@/lib/db";
import type { UserRole, UserStatus, AgencyStatus } from "@prisma/client";

export async function getAdminStats() {
  const [users, properties, agencies, contracts, activeSubscriptions, feeds] = await Promise.all([
    prisma.user.count(),
    prisma.property.count(),
    prisma.agency.count(),
    prisma.contract.count(),
    prisma.subscription.count({ where: { status: "ACTIVE" } }),
    prisma.feed.count(),
  ]);
  return { users, properties, agencies, contracts, activeSubscriptions, feeds };
}

export async function listUsers(q?: string) {
  return prisma.user.findMany({
    where: q ? { OR: [{ email: { contains: q, mode: "insensitive" } }, { firstName: { contains: q, mode: "insensitive" } }, { lastName: { contains: q, mode: "insensitive" } }] } : undefined,
    include: { agency: { select: { name: true } } },
    orderBy: { createdAt: "desc" },
    take: 100,
  });
}

export async function updateUserRoleStatus(userId: string, role: UserRole, status: UserStatus) {
  await prisma.user.update({ where: { id: userId }, data: { role, status } });
}

export async function listAllProperties(q?: string) {
  return prisma.property.findMany({
    where: q ? { title: { contains: q, mode: "insensitive" } } : undefined,
    include: { city: true, agency: { select: { name: true } }, advertiser: { select: { firstName: true, lastName: true } } },
    orderBy: { createdAt: "desc" },
    take: 100,
  });
}

export async function adminSetPropertyStatus(propertyId: string, status: "PUBLISHED" | "PAUSED" | "ARCHIVED" | "DRAFT") {
  await prisma.property.update({ where: { id: propertyId }, data: { status } });
}

export async function adminDeleteProperty(propertyId: string) {
  await prisma.property.delete({ where: { id: propertyId } });
}

export async function listAllAgencies() {
  return prisma.agency.findMany({
    include: { _count: { select: { properties: true, users: true, feeds: true } } },
    orderBy: { name: "asc" },
    take: 200,
  });
}

export async function setAgencyStatus(agencyId: string, status: AgencyStatus) {
  await prisma.agency.update({ where: { id: agencyId }, data: { status } });
}

export async function listAllPlans() {
  return prisma.plan.findMany({ orderBy: { price: "asc" } });
}

export async function createPlan(input: { name: string; slug: string; description?: string; price: number; maxListings?: number; features: string[] }) {
  return prisma.plan.create({
    data: {
      name: input.name,
      slug: input.slug,
      description: input.description,
      price: input.price,
      maxListings: input.maxListings,
      features: input.features,
    },
  });
}

export async function togglePlanActive(planId: string, active: boolean) {
  await prisma.plan.update({ where: { id: planId }, data: { active } });
}

export async function listAllSubscriptions() {
  return prisma.subscription.findMany({
    include: { plan: true, user: { select: { firstName: true, lastName: true, email: true } }, agency: { select: { name: true } } },
    orderBy: { createdAt: "desc" },
    take: 200,
  });
}

export async function adminSetSubscriptionStatus(subscriptionId: string, status: "ACTIVE" | "CANCELED" | "EXPIRED" | "PENDING") {
  await prisma.subscription.update({
    where: { id: subscriptionId },
    data: {
      status,
      startedAt: status === "ACTIVE" ? new Date() : undefined,
      canceledAt: status === "CANCELED" ? new Date() : undefined,
    },
  });
}

export async function listAllFeeds() {
  return prisma.feed.findMany({
    include: { agency: { select: { name: true } }, _count: { select: { logs: true, properties: true } } },
    orderBy: { createdAt: "desc" },
  });
}
