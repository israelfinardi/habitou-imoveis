"use client";

import { useTransition } from "react";
import { useRouter } from "next/navigation";
import { syncFeedNowAction, toggleFeedStatusAction, deleteFeedAction } from "../actions";

export function FeedActions({ feedId, status }: { feedId: string; status: "ACTIVE" | "INACTIVE" | "ERROR" }) {
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  return (
    <div className="flex flex-wrap gap-2">
      <button
        disabled={pending}
        onClick={() => startTransition(async () => { await syncFeedNowAction(feedId); router.refresh(); })}
        className="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Sincronizando..." : "Sincronizar agora"}
      </button>
      <button
        disabled={pending}
        onClick={() => startTransition(() => toggleFeedStatusAction(feedId, status === "ACTIVE" ? "INACTIVE" : "ACTIVE"))}
        className="rounded-full border border-brand-border px-4 py-2 text-sm font-medium text-brand-text hover:border-brand-primary disabled:opacity-60"
      >
        {status === "ACTIVE" ? "Desativar" : "Ativar"}
      </button>
      <button
        disabled={pending}
        onClick={() => {
          if (confirm("Excluir este feed? Os imóveis já importados permanecerão no catálogo.")) {
            startTransition(() => deleteFeedAction(feedId));
          }
        }}
        className="rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:border-red-400 disabled:opacity-60"
      >
        Excluir feed
      </button>
    </div>
  );
}
