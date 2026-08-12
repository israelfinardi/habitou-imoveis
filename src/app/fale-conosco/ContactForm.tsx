"use client";

import { useActionState } from "react";
import { sendContactMessageAction, type ContactActionState } from "./actions";
import { FormField } from "@/components/ui/FormField";

const SUBJECTS = [
  "Anunciar no Habitou Imóveis",
  "Suporte para minha conta",
  "Dúvida sobre um imóvel",
  "Parcerias e imprensa",
  "Reportar um problema",
  "Sugestão ou elogio",
  "Outro",
];

export function ContactForm({ defaultSubject }: { defaultSubject?: string }) {
  const [state, formAction, pending] = useActionState<ContactActionState, FormData>(sendContactMessageAction, null);

  if (state?.success) {
    return <p className="rounded-lg bg-brand-green/10 px-4 py-3 text-sm text-brand-green-hover">{state.success}</p>;
  }

  return (
    <form action={formAction} className="space-y-1">
      {state?.error && <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>}
      <div className="grid grid-cols-1 gap-x-3 sm:grid-cols-2">
        <FormField label="Nome *" name="name" required error={state?.fieldErrors?.name} />
        <FormField label="E-mail *" name="email" type="email" required error={state?.fieldErrors?.email} />
      </div>
      <FormField label="Telefone" name="phone" type="tel" />
      <div className="mb-4">
        <label className="mb-1 block text-sm font-medium text-brand-text">Assunto *</label>
        <select name="subject" required defaultValue={defaultSubject} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <option value="">Selecione</option>
          {SUBJECTS.map((s) => (
            <option key={s} value={s}>{s}</option>
          ))}
        </select>
        {state?.fieldErrors?.subject && <p className="mt-1 text-xs text-red-600">{state.fieldErrors.subject}</p>}
      </div>
      <div className="mb-4">
        <label className="mb-1 block text-sm font-medium text-brand-text">Mensagem *</label>
        <textarea name="message" required maxLength={1000} rows={5} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
        {state?.fieldErrors?.message && <p className="mt-1 text-xs text-red-600">{state.fieldErrors.message}</p>}
      </div>
      <p className="mb-4 text-xs text-brand-text-secondary">
        Ao enviar, você concorda com a{" "}
        <a href="/politica-de-privacidade" className="text-brand-primary hover:underline">Política de Privacidade</a>{" "}
        e autoriza o Habitou Imóveis a entrar em contato sobre esta mensagem.
      </p>
      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Enviando..." : "Enviar mensagem"}
      </button>
    </form>
  );
}
