"use client";

import { useTransition } from "react";
import { adminSetSubscriptionStatusAction } from "@/app/admin/actions";
import type { SubscriptionStatus } from "@prisma/client";

const STATUSES: SubscriptionStatus[] = ["PENDING", "ACTIVE", "CANCELED", "EXPIRED"];

export function SubscriptionRowActions({ id, status }: { id: string; status: SubscriptionStatus }) {
  const [pending, startTransition] = useTransition();
  return (
    <select
      defaultValue={status}
      disabled={pending}
      onChange={(e) => startTransition(() => adminSetSubscriptionStatusAction(id, e.target.value as SubscriptionStatus))}
      className="rounded-lg border border-brand-border px-2 py-1 text-xs"
    >
      {STATUSES.map((s) => (
        <option key={s} value={s}>{s}</option>
      ))}
    </select>
  );
}
