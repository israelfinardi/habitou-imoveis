"use server";

import { revalidatePath } from "next/cache";
import { requireRole } from "@/lib/auth/guards";
import {
  updateUserRoleStatus,
  adminSetPropertyStatus,
  adminDeleteProperty,
  setAgencyStatus,
  createPlan,
  togglePlanActive,
  adminSetSubscriptionStatus,
} from "@/server/services/admin-service";
import { toSlug } from "@/lib/slug";
import type { UserRole, UserStatus, AgencyStatus, PropertyStatus, SubscriptionStatus } from "@prisma/client";

async function assertAdmin() {
  await requireRole(["ADMIN"]);
}

export async function updateUserAction(userId: string, role: UserRole, status: UserStatus) {
  await assertAdmin();
  await updateUserRoleStatus(userId, role, status);
  revalidatePath("/admin/usuarios");
}

export async function adminSetPropertyStatusAction(propertyId: string, status: PropertyStatus) {
  await assertAdmin();
  await adminSetPropertyStatus(propertyId, status as "PUBLISHED" | "PAUSED" | "ARCHIVED" | "DRAFT");
  revalidatePath("/admin/imoveis");
}

export async function adminDeletePropertyAction(propertyId: string) {
  await assertAdmin();
  await adminDeleteProperty(propertyId);
  revalidatePath("/admin/imoveis");
}

export async function setAgencyStatusAction(agencyId: string, status: AgencyStatus) {
  await assertAdmin();
  await setAgencyStatus(agencyId, status);
  revalidatePath("/admin/imobiliarias");
}

export async function createPlanAction(formData: FormData) {
  await assertAdmin();
  const name = String(formData.get("name") || "").trim();
  const price = Number(formData.get("price") || 0);
  const description = String(formData.get("description") || "");
  const maxListings = formData.get("maxListings") ? Number(formData.get("maxListings")) : undefined;
  const features = String(formData.get("features") || "")
    .split("\n")
    .map((f) => f.trim())
    .filter(Boolean);

  if (!name) return;
  await createPlan({ name, slug: toSlug(name), description, price, maxListings, features });
  revalidatePath("/admin/planos");
}

export async function togglePlanActiveAction(planId: string, active: boolean) {
  await assertAdmin();
  await togglePlanActive(planId, active);
  revalidatePath("/admin/planos");
}

export async function adminSetSubscriptionStatusAction(subscriptionId: string, status: SubscriptionStatus) {
  await assertAdmin();
  await adminSetSubscriptionStatus(subscriptionId, status as "ACTIVE" | "CANCELED" | "EXPIRED" | "PENDING");
  revalidatePath("/admin/assinaturas");
}
