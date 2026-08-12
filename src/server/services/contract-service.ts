import "server-only";
import { prisma } from "@/lib/db";
import { AuthError } from "@/lib/auth/guards";
import type { SafeUser } from "@/lib/auth/session";
import type { ContractStatus, ContractType } from "@prisma/client";

const contractInclude = {
  property: { include: { city: true, neighborhood: true, images: { take: 1, orderBy: { order: "asc" as const } } } },
  owner: { select: { id: true, firstName: true, lastName: true } },
  advertiser: { select: { id: true, firstName: true, lastName: true } },
  buyer: { select: { id: true, firstName: true, lastName: true } },
  tenant: { select: { id: true, firstName: true, lastName: true } },
  agent: { select: { id: true, firstName: true, lastName: true } },
  agency: true,
  history: { orderBy: { createdAt: "desc" as const } },
};

export async function createContract(
  actor: SafeUser,
  input: { propertyId: string; value?: number; startDate?: string; endDate?: string; note?: string }
) {
  const property = await prisma.property.findUnique({ where: { id: input.propertyId } });
  if (!property) throw new AuthError("Imóvel não encontrado.", 404);

  const canCreate =
    actor.role === "ADMIN" ||
    property.advertiserId === actor.id ||
    (property.agencyId && actor.agencyId === property.agencyId);
  if (!canCreate) throw new AuthError("Você não pode criar contratos para este imóvel.", 403);

  const type: ContractType = property.listingType === "RENT" ? "RENT" : "SALE";

  const contract = await prisma.contract.create({
    data: {
      propertyId: property.id,
      type,
      status: "DRAFT",
      ownerId: property.ownerId,
      advertiserId: property.advertiserId,
      agentId: property.agentId,
      agencyId: property.agencyId,
      value: input.value,
      startDate: input.startDate ? new Date(input.startDate) : undefined,
      endDate: input.endDate ? new Date(input.endDate) : undefined,
      history: {
        create: { status: "DRAFT", note: input.note || "Contrato criado." },
      },
    },
  });

  return contract;
}

export async function listContractsForUser(actor: SafeUser) {
  if (actor.role === "ADMIN") {
    return prisma.contract.findMany({ include: contractInclude, orderBy: { updatedAt: "desc" } });
  }

  return prisma.contract.findMany({
    where: {
      OR: [
        { ownerId: actor.id },
        { advertiserId: actor.id },
        { buyerId: actor.id },
        { tenantId: actor.id },
        { agentId: actor.id },
        ...(actor.agencyId ? [{ agencyId: actor.agencyId }] : []),
      ],
    },
    include: contractInclude,
    orderBy: { updatedAt: "desc" },
  });
}

export async function getContractById(id: string, actor: SafeUser) {
  const contract = await prisma.contract.findUnique({ where: { id }, include: contractInclude });
  if (!contract) return null;

  const authorized =
    actor.role === "ADMIN" ||
    [contract.ownerId, contract.advertiserId, contract.buyerId, contract.tenantId, contract.agentId].includes(actor.id) ||
    (contract.agencyId && contract.agencyId === actor.agencyId);

  if (!authorized) throw new AuthError("Você não tem acesso a este contrato.", 403);
  return contract;
}

export async function updateContractStatus(id: string, status: ContractStatus, note: string | undefined, actor: SafeUser) {
  const contract = await prisma.contract.findUnique({ where: { id } });
  if (!contract) throw new AuthError("Contrato não encontrado.", 404);

  const authorized =
    actor.role === "ADMIN" ||
    contract.advertiserId === actor.id ||
    (contract.agencyId && contract.agencyId === actor.agencyId);
  if (!authorized) throw new AuthError("Você não pode alterar este contrato.", 403);

  await prisma.$transaction([
    prisma.contract.update({ where: { id }, data: { status } }),
    prisma.contractHistoryEntry.create({ data: { contractId: id, status, note } }),
  ]);
}
