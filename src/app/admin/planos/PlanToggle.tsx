"use client";

import { useTransition } from "react";
import { togglePlanActiveAction } from "@/app/admin/actions";

export function PlanToggle({ id, active }: { id: string; active: boolean }) {
  const [pending, startTransition] = useTransition();
  return (
    <button
      disabled={pending}
      onClick={() => startTransition(() => togglePlanActiveAction(id, !active))}
      className={`rounded-full px-3 py-1 text-xs font-medium ${active ? "bg-brand-green/10 text-brand-green-hover" : "bg-brand-bg-subtle text-brand-text-secondary"}`}
    >
      {active ? "Ativo" : "Inativo"}
    </button>
  );
}
