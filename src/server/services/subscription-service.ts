import "server-only";
import { prisma } from "@/lib/db";
import type { SafeUser } from "@/lib/auth/session";

export async function listActivePlans() {
  return prisma.plan.findMany({ where: { active: true }, orderBy: { price: "asc" } });
}

export async function getCurrentSubscription(actor: SafeUser) {
  return prisma.subscription.findFirst({
    where: {
      status: { in: ["ACTIVE", "PENDING"] },
      OR: [{ userId: actor.id }, ...(actor.agencyId ? [{ agencyId: actor.agencyId }] : [])],
    },
    include: { plan: true },
    orderBy: { createdAt: "desc" },
  });
}

/**
 * Cria uma solicitação de assinatura (PENDING). Não há gateway de pagamento
 * integrado — a ativação (status ACTIVE) é feita manualmente pelo
 * administrador até que uma integração real seja configurada.
 */
export async function requestSubscription(actor: SafeUser, planId: string) {
  return prisma.subscription.create({
    data: {
      userId: actor.id,
      agencyId: actor.agencyId,
      planId,
      status: "PENDING",
    },
  });
}

export async function cancelSubscription(subscriptionId: string, actor: SafeUser) {
  const sub = await prisma.subscription.findUnique({ where: { id: subscriptionId } });
  if (!sub) return;
  const authorized = sub.userId === actor.id || (sub.agencyId && sub.agencyId === actor.agencyId) || actor.role === "ADMIN";
  if (!authorized) return;
  await prisma.subscription.update({
    where: { id: subscriptionId },
    data: { status: "CANCELED", canceledAt: new Date() },
  });
}
