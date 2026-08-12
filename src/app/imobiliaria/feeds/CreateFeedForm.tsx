"use client";

import { useActionState } from "react";
import { createFeedAction, type FeedActionState } from "./actions";

export function CreateFeedForm() {
  const [state, formAction, pending] = useActionState<FeedActionState, FormData>(createFeedAction, null);

  return (
    <form action={formAction} className="flex flex-wrap items-end gap-3">
      {state?.error && <p className="w-full rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>}
      <div className="flex-1 min-w-[160px]">
        <label className="mb-1 block text-xs font-medium text-brand-text-secondary">Nome do feed</label>
        <input name="name" required className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
      </div>
      <div className="flex-[2] min-w-[220px]">
        <label className="mb-1 block text-xs font-medium text-brand-text-secondary">URL do feed (XML)</label>
        <input name="url" type="url" required placeholder="https://" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
      </div>
      <div className="min-w-[140px]">
        <label className="mb-1 block text-xs font-medium text-brand-text-secondary">Frequência</label>
        <select name="frequencyMinutes" defaultValue="1440" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <option value="60">A cada hora</option>
          <option value="360">A cada 6 horas</option>
          <option value="1440">Diariamente</option>
          <option value="10080">Semanalmente</option>
        </select>
      </div>
      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Criando..." : "Adicionar feed"}
      </button>
    </form>
  );
}
