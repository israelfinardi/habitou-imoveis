import type { Metadata } from "next";
import { ContactForm } from "./ContactForm";

export const metadata: Metadata = {
  title: "Fale conosco",
  description: "Dúvida, sugestão, proposta de parceria ou quer anunciar? Fale com o time Habitou Imóveis.",
};

export default async function FaleConoscoPage({
  searchParams,
}: {
  searchParams: Promise<{ imovel?: string }>;
}) {
  const { imovel } = await searchParams;

  return (
    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
      <div className="mb-10 text-center">
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-primary">Atendimento · seg a sex, 8h–18h</p>
        <h1 className="mt-2 text-3xl font-bold text-brand-text">Fale com a gente</h1>
        <p className="mx-auto mt-2 max-w-xl text-brand-text-secondary">
          Dúvida, sugestão, proposta de parceria ou quer anunciar? Preencha o formulário — normalmente respondemos
          em até 2 horas úteis.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
        <div className="rounded-2xl border border-brand-border bg-white p-6">
          <ContactForm defaultSubject={imovel ? "Dúvida sobre um imóvel" : undefined} />
          {imovel && <p className="mt-2 text-xs text-brand-text-secondary">Referente ao imóvel {imovel}.</p>}
        </div>

        <div className="space-y-4">
          <div className="rounded-2xl border border-brand-border bg-white p-5">
            <p className="text-sm font-semibold text-brand-text">WhatsApp</p>
            <a href="https://wa.me/5547964279000" className="text-sm text-brand-primary hover:underline">(47) 96427-9000</a>
            <p className="text-xs text-brand-text-secondary">resposta rápida em horário comercial</p>
          </div>
          <div className="rounded-2xl border border-brand-border bg-white p-5">
            <p className="text-sm font-semibold text-brand-text">E-mail</p>
            <a href="mailto:contato@habitou.com.br" className="text-sm text-brand-primary hover:underline">contato@habitou.com.br</a>
            <p className="text-xs text-brand-text-secondary">assuntos gerais e suporte</p>
          </div>
          <div className="rounded-2xl border border-brand-border bg-white p-5">
            <p className="text-sm font-semibold text-brand-text">Horário de atendimento</p>
            <p className="text-xs text-brand-text-secondary">Seg — Sex: 8h — 18h</p>
            <p className="text-xs text-brand-text-secondary">Sáb, Dom e feriados: fechado</p>
          </div>
        </div>
      </div>
    </div>
  );
}
