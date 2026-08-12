"use client";

import { useActionState } from "react";
import Link from "next/link";
import { loginAction, type ActionState } from "@/app/(auth)/actions";
import { FormField } from "@/components/ui/FormField";

export function LoginForm({ redeemed }: { redeemed?: boolean }) {
  const [state, formAction, pending] = useActionState<ActionState, FormData>(loginAction, null);

  return (
    <form action={formAction}>
      {redeemed && (
        <p className="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">
          Senha redefinida com sucesso. Faça login com sua nova senha.
        </p>
      )}
      {state?.error && (
        <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>
      )}
      <FormField
        label="E-mail"
        name="email"
        type="email"
        required
        autoComplete="email"
        error={state?.fieldErrors?.email}
      />
      <FormField
        label="Senha"
        name="password"
        type="password"
        required
        autoComplete="current-password"
        error={state?.fieldErrors?.password}
      />
      <div className="mb-4 text-right">
        <Link href="/esqueci-senha" className="text-xs font-medium text-brand-primary hover:underline">
          Esqueci minha senha
        </Link>
      </div>
      <button
        type="submit"
        disabled={pending}
        className="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white transition hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Entrando..." : "Entrar"}
      </button>
      <p className="mt-4 text-center text-sm text-brand-text-secondary">
        Não tem uma conta?{" "}
        <Link href="/cadastro" className="font-medium text-brand-primary hover:underline">
          Cadastre-se
        </Link>
      </p>
    </form>
  );
}
