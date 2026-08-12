"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { requireUser } from "@/lib/auth/guards";
import { createContract, updateContractStatus } from "@/server/services/contract-service";
import type { ContractStatus } from "@prisma/client";

export async function createContractAction(formData: FormData) {
  const user = await requireUser();
  const propertyId = String(formData.get("propertyId"));
  const value = formData.get("value") ? Number(formData.get("value")) : undefined;
  const startDate = formData.get("startDate") ? String(formData.get("startDate")) : undefined;
  const endDate = formData.get("endDate") ? String(formData.get("endDate")) : undefined;
  const note = formData.get("note") ? String(formData.get("note")) : undefined;

  const contract = await createContract(user, { propertyId, value, startDate, endDate, note });
  revalidatePath("/contratos");
  redirect(`/contratos/${contract.id}`);
}

export async function updateContractStatusAction(contractId: string, status: ContractStatus, note?: string) {
  const user = await requireUser();
  await updateContractStatus(contractId, status, note, user);
  revalidatePath(`/contratos/${contractId}`);
  revalidatePath("/contratos");
}
