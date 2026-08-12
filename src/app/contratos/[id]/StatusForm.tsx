"use client";

import { useTransition } from "react";
import { updateContractStatusAction } from "@/app/contratos/actions";
import type { ContractStatus } from "@prisma/client";

const OPTIONS: { value: ContractStatus; label: string }[] = [
  { value: "DRAFT", label: "Rascunho" },
  { value: "ACTIVE", label: "Ativo" },
  { value: "FINISHED", label: "Concluído" },
  { value: "CANCELED", label: "Cancelado" },
];

export function StatusForm({ contractId, currentStatus }: { contractId: string; currentStatus: ContractStatus }) {
  const [pending, startTransition] = useTransition();

  return (
    <form
      className="flex flex-wrap items-end gap-3"
      action={(formData) => {
        const status = formData.get("status") as ContractStatus;
        const note = String(formData.get("note") || "");
        startTransition(() => updateContractStatusAction(contractId, status, note || undefined));
      }}
    >
      <div>
        <label className="mb-1 block text-xs font-medium text-brand-text-secondary">Novo status</label>
        <select name="status" defaultValue={currentStatus} className="rounded-lg border border-brand-border px-3 py-2 text-sm">
          {OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>
      <div className="flex-1 min-w-[200px]">
        <label className="mb-1 block text-xs font-medium text-brand-text-secondary">Observação</label>
        <input name="note" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" placeholder="Opcional" />
      </div>
      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Salvando..." : "Atualizar status"}
      </button>
    </form>
  );
}
