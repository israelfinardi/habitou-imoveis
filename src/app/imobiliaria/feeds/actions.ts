"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { requireUser, AuthError } from "@/lib/auth/guards";
import { createFeed, updateFeed, setFeedStatus, deleteFeed } from "@/server/services/feed-service";
import { runFeedSync } from "@/server/vrsync/sync-service";

export type FeedActionState = { error?: string } | null;

export async function createFeedAction(_prev: FeedActionState, formData: FormData): Promise<FeedActionState> {
  const user = await requireUser();
  const name = String(formData.get("name") || "").trim();
  const url = String(formData.get("url") || "").trim();
  const frequencyMinutes = Number(formData.get("frequencyMinutes") || 1440);

  if (!name || !url) return { error: "Preencha nome e URL do feed." };
  try {
    new URL(url);
  } catch {
    return { error: "URL inválida." };
  }

  let feed;
  try {
    feed = await createFeed(user, { name, url, frequencyMinutes });
  } catch (err) {
    if (err instanceof AuthError) return { error: err.message };
    throw err;
  }

  revalidatePath("/imobiliaria/feeds");
  redirect(`/imobiliaria/feeds/${feed.id}`);
}

export async function updateFeedAction(feedId: string, formData: FormData) {
  const user = await requireUser();
  const name = String(formData.get("name") || "").trim();
  const url = String(formData.get("url") || "").trim();
  const frequencyMinutes = Number(formData.get("frequencyMinutes") || 1440);
  await updateFeed(feedId, user, { name, url, frequencyMinutes });
  revalidatePath(`/imobiliaria/feeds/${feedId}`);
}

export async function toggleFeedStatusAction(feedId: string, status: "ACTIVE" | "INACTIVE") {
  const user = await requireUser();
  await setFeedStatus(feedId, user, status);
  revalidatePath(`/imobiliaria/feeds/${feedId}`);
  revalidatePath("/imobiliaria/feeds");
}

export async function deleteFeedAction(feedId: string) {
  const user = await requireUser();
  await deleteFeed(feedId, user);
  revalidatePath("/imobiliaria/feeds");
  redirect("/imobiliaria/feeds");
}

export async function syncFeedNowAction(feedId: string) {
  await requireUser();
  await runFeedSync(feedId);
  revalidatePath(`/imobiliaria/feeds/${feedId}`);
}
