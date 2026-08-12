import "server-only";
import { prisma } from "@/lib/db";
import { AuthError } from "@/lib/auth/guards";
import type { SafeUser } from "@/lib/auth/session";

function assertAgencyAccess(actor: SafeUser, agencyId: string) {
  const allowed = actor.role === "ADMIN" || (actor.agencyId === agencyId && (actor.role === "AGENCY_ADMIN" || actor.role === "AGENT"));
  if (!allowed) throw new AuthError("Você não tem acesso aos feeds desta imobiliária.", 403);
}

export async function listFeedsForActor(actor: SafeUser) {
  if (actor.role === "ADMIN") {
    return prisma.feed.findMany({ include: { agency: true, _count: { select: { logs: true, properties: true } } }, orderBy: { createdAt: "desc" } });
  }
  if (!actor.agencyId) return [];
  return prisma.feed.findMany({
    where: { agencyId: actor.agencyId },
    include: { agency: true, _count: { select: { logs: true, properties: true } } },
    orderBy: { createdAt: "desc" },
  });
}

export async function getFeedWithLogs(feedId: string, actor: SafeUser) {
  const feed = await prisma.feed.findUnique({
    where: { id: feedId },
    include: { agency: true, logs: { orderBy: { startedAt: "desc" }, take: 20 } },
  });
  if (!feed) return null;
  assertAgencyAccess(actor, feed.agencyId);
  return feed;
}

export async function createFeed(actor: SafeUser, input: { name: string; url: string; frequencyMinutes: number }) {
  if (!actor.agencyId) throw new AuthError("Somente usuários de imobiliária podem criar feeds.", 403);
  assertAgencyAccess(actor, actor.agencyId);
  return prisma.feed.create({
    data: {
      agencyId: actor.agencyId,
      name: input.name,
      url: input.url,
      frequencyMinutes: input.frequencyMinutes,
      nextSyncAt: new Date(),
    },
  });
}

export async function updateFeed(feedId: string, actor: SafeUser, input: { name: string; url: string; frequencyMinutes: number }) {
  const feed = await prisma.feed.findUniqueOrThrow({ where: { id: feedId } });
  assertAgencyAccess(actor, feed.agencyId);
  return prisma.feed.update({ where: { id: feedId }, data: input });
}

export async function setFeedStatus(feedId: string, actor: SafeUser, status: "ACTIVE" | "INACTIVE") {
  const feed = await prisma.feed.findUniqueOrThrow({ where: { id: feedId } });
  assertAgencyAccess(actor, feed.agencyId);
  return prisma.feed.update({ where: { id: feedId }, data: { status } });
}

export async function deleteFeed(feedId: string, actor: SafeUser) {
  const feed = await prisma.feed.findUniqueOrThrow({ where: { id: feedId } });
  assertAgencyAccess(actor, feed.agencyId);
  await prisma.feed.delete({ where: { id: feedId } });
}
