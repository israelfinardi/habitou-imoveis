"use client";

import { useTransition } from "react";
import { useRouter } from "next/navigation";
import { requestSubscriptionAction, cancelSubscriptionAction } from "./actions";

export function SubscribeButton({
  planId,
  authenticated,
  isCurrent,
  currentSubscriptionId,
}: {
  planId: string;
  authenticated: boolean;
  isCurrent: boolean;
  currentSubscriptionId?: string;
}) {
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  if (isCurrent && currentSubscriptionId) {
    return (
      <button
        disabled={pending}
        onClick={() => startTransition(() => cancelSubscriptionAction(currentSubscriptionId))}
        className="w-full rounded-full border border-brand-border py-2.5 text-sm font-semibold text-brand-text-secondary hover:border-red-400 hover:text-red-600"
      >
        Cancelar assinatura
      </button>
    );
  }

  return (
    <button
      disabled={pending}
      onClick={() => {
        if (!authenticated) {
          router.push("/login");
          return;
        }
        startTransition(() => requestSubscriptionAction(planId));
      }}
      className="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
    >
      {pending ? "Enviando..." : "Assinar plano"}
    </button>
  );
}
