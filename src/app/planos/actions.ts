"use server";

import { revalidatePath } from "next/cache";
import { requireUser } from "@/lib/auth/guards";
import { requestSubscription, cancelSubscription } from "@/server/services/subscription-service";

export async function requestSubscriptionAction(planId: string) {
  const user = await requireUser();
  await requestSubscription(user, planId);
  revalidatePath("/planos");
  revalidatePath("/minha-conta");
}

export async function cancelSubscriptionAction(subscriptionId: string) {
  const user = await requireUser();
  await cancelSubscription(subscriptionId, user);
  revalidatePath("/planos");
  revalidatePath("/minha-conta");
}
