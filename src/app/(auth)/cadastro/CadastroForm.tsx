"use client";

import { useActionState } from "react";
import Link from "next/link";
import { registerAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";

export function CadastroForm() {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(registerAction, null);

  return (
    <form action={formAction}>
      {state?.error && (
        <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>
      )}
      <div className="grid grid-cols-2 gap-3">
        <FormField label="Nome" name="firstName" required error={state?.fieldErrors?.firstName} />
        <FormField label="Sobrenome" name="lastName" required error={state?.fieldErrors?.lastName} />
      </div>
      <FormField
        label="E-mail"
        name="email"
        type="email"
        required
        autoComplete="email"
        error={state?.fieldErrors?.email}
      />
      <FormField
        label="Telefone (WhatsApp)"
        name="phone"
        type="tel"
        placeholder="(47) 99999-9999"
        error={state?.fieldErrors?.phone}
      />
      <FormField
        label="Senha"
        name="password"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.password}
      />
      <FormField
        label="Confirmar senha"
        name="passwordConfirmation"
        type="password"
        required
        autoComplete="new-password"
        error={state?.fieldErrors?.passwordConfirmation}
      />
      <button
        type="submit"
        disabled={pending}
        className="mt-2 w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white transition hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Criando conta..." : "Criar conta"}
      </button>
      <p className="mt-4 text-center text-sm text-brand-text-secondary">
        Já tem uma conta?{" "}
        <Link href="/login" className="font-medium text-brand-primary hover:underline">
          Entrar
        </Link>
      </p>
    </form>
  );
}
