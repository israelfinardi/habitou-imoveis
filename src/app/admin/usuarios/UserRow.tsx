"use client";

import { useTransition } from "react";
import { updateUserAction } from "@/app/admin/actions";
import type { UserRole, UserStatus } from "@prisma/client";

const ROLES: UserRole[] = ["USER", "ADVERTISER", "OWNER", "AGENT", "AGENCY_ADMIN", "ADMIN"];
const STATUSES: UserStatus[] = ["ACTIVE", "INACTIVE", "SUSPENDED", "PENDING"];

export function UserRow({ id, role, status }: { id: string; role: UserRole; status: UserStatus }) {
  const [pending, startTransition] = useTransition();

  return (
    <div className="flex items-center gap-2">
      <select
        defaultValue={role}
        disabled={pending}
        onChange={(e) => startTransition(() => updateUserAction(id, e.target.value as UserRole, status))}
        className="rounded-lg border border-brand-border px-2 py-1 text-xs"
      >
        {ROLES.map((r) => (
          <option key={r} value={r}>{r}</option>
        ))}
      </select>
      <select
        defaultValue={status}
        disabled={pending}
        onChange={(e) => startTransition(() => updateUserAction(id, role, e.target.value as UserStatus))}
        className="rounded-lg border border-brand-border px-2 py-1 text-xs"
      >
        {STATUSES.map((s) => (
          <option key={s} value={s}>{s}</option>
        ))}
      </select>
    </div>
  );
}
