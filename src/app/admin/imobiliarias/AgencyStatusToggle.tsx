"use client";

import { useTransition } from "react";
import { setAgencyStatusAction } from "@/app/admin/actions";
import type { AgencyStatus } from "@prisma/client";

const STATUSES: AgencyStatus[] = ["ACTIVE", "INACTIVE", "PENDING"];

export function AgencyStatusToggle({ id, status }: { id: string; status: AgencyStatus }) {
  const [pending, startTransition] = useTransition();
  return (
    <select
      defaultValue={status}
      disabled={pending}
      onChange={(e) => startTransition(() => setAgencyStatusAction(id, e.target.value as AgencyStatus))}
      className="rounded-lg border border-brand-border px-2 py-1 text-xs"
    >
      {STATUSES.map((s) => (
        <option key={s} value={s}>{s}</option>
      ))}
    </select>
  );
}
