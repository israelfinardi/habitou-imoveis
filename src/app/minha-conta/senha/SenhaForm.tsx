"use client";

import { useActionState } from "react";
import { changePasswordAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";

export function SenhaForm() {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(changePasswordAction, null);

  return (
    <form action={formAction} className="max-w-md">
      {state?.success && (
        <p className="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">{state.success}</p>
      )}
      {state?.error && <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>}
      <FormField
        label="Senha atual"
        name="currentPassword"
        type="password"
        required
        autoComplete="current-password"
        error={state?.fieldErrors?.currentPassword}
      />
      <FormField
        label="Nova senha"
        name="newPassword"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.newPassword}
      />
      <FormField
        label="Confirmar nova senha"
        name="newPasswordConfirmation"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.newPasswordConfirmation}
      />
      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Salvando..." : "Alterar senha"}
      </button>
    </form>
  );
}
