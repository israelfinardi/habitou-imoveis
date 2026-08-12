"use client";

import { useActionState } from "react";
import { resetPasswordAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";

export function RedefinirSenhaForm({ token }: { token: string }) {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(
    resetPasswordAction,
    null
  );

  return (
    <form action={formAction}>
      <input type="hidden" name="token" value={token} />
      {state?.error && (
        <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>
      )}
      <FormField
        label="Nova senha"
        name="password"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.password}
      />
      <FormField
        label="Confirmar nova senha"
        name="passwordConfirmation"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.passwordConfirmation}
      />
      <button
        type="submit"
        disabled={pending}
        className="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white transition hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Salvando..." : "Redefinir senha"}
      </button>
    </form>
  );
}
