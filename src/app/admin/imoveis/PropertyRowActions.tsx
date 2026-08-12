"use client";

import { useTransition } from "react";
import { adminSetPropertyStatusAction, adminDeletePropertyAction } from "@/app/admin/actions";
import type { PropertyStatus } from "@prisma/client";

const STATUSES: PropertyStatus[] = ["DRAFT", "PUBLISHED", "PAUSED", "ARCHIVED"];

export function PropertyRowActions({ id, status }: { id: string; status: PropertyStatus }) {
  const [pending, startTransition] = useTransition();

  return (
    <div className="flex items-center gap-2">
      <select
        defaultValue={status}
        disabled={pending}
        onChange={(e) => startTransition(() => adminSetPropertyStatusAction(id, e.target.value as PropertyStatus))}
        className="rounded-lg border border-brand-border px-2 py-1 text-xs"
      >
        {STATUSES.map((s) => (
          <option key={s} value={s}>{s}</option>
        ))}
      </select>
      <button
        disabled={pending}
        onClick={() => {
          if (confirm("Excluir este imóvel permanentemente?")) startTransition(() => adminDeletePropertyAction(id));
        }}
        className="text-xs text-red-600 hover:underline"
      >
        Excluir
      </button>
    </div>
  );
}
