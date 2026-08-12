"use client";

import { useActionState } from "react";
import { updateProfileAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";
import type { SafeUser } from "@/lib/auth/session";

export function DadosForm({ user }: { user: SafeUser }) {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(updateProfileAction, null);

  return (
    <form action={formAction} className="max-w-md">
      {state?.success && (
        <p className="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">{state.success}</p>
      )}
      <div className="mb-4">
        <label className="mb-1 block text-sm font-medium text-brand-text">E-mail</label>
        <input
          disabled
          value={user.email}
          className="w-full rounded-lg border border-brand-border bg-brand-bg-subtle px-3 py-2 text-sm text-brand-text-secondary"
        />
        <p className="mt-1 text-xs text-brand-text-secondary">O e-mail não pode ser alterado.</p>
      </div>
      <div className="grid grid-cols-2 gap-3">
        <FormField label="Nome" name="firstName" required defaultValue={user.firstName} error={state?.fieldErrors?.firstName} />
        <FormField label="Sobrenome" name="lastName" required defaultValue={user.lastName} error={state?.fieldErrors?.lastName} />
      </div>
      <FormField label="Telefone" name="phone" type="tel" defaultValue={user.phone ?? undefined} error={state?.fieldErrors?.phone} />
      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Salvando..." : "Salvar alterações"}
      </button>
    </form>
  );
}
