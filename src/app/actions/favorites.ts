"use server";

import { revalidatePath } from "next/cache";
import { getCurrentUser } from "@/lib/auth/session";
import { toggleFavorite } from "@/server/services/favorite-service";

export async function toggleFavoriteAction(propertyId: string) {
  const user = await getCurrentUser();
  if (!user) {
    return { error: "unauthenticated" as const };
  }
  const isNowFavorite = await toggleFavorite(user.id, propertyId);
  revalidatePath("/minha-conta/favoritos");
  return { isFavorite: isNowFavorite };
}
