"use client";

import { useActionState } from "react";
import Link from "next/link";
import { requestPasswordResetAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";

export function EsqueciSenhaForm() {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(
    requestPasswordResetAction,
    null
  );

  if (state?.success) {
    return <p className="rounded-lg bg-brand-green/10 px-3 py-3 text-sm text-brand-green-hover">{state.success}</p>;
  }

  return (
    <form action={formAction}>
      <FormField
        label="E-mail cadastrado"
        name="email"
        type="email"
        required
        autoComplete="email"
        error={state?.fieldErrors?.email}
      />
      <button
        type="submit"
        disabled={pending}
        className="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white transition hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Enviando..." : "Enviar link de recuperação"}
      </button>
      <p className="mt-4 text-center text-sm text-brand-text-secondary">
        <Link href="/login" className="font-medium text-brand-primary hover:underline">
          Voltar para o login
        </Link>
      </p>
    </form>
  );
}
