import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Como anunciar imóveis em SC",
  description: "Anuncie no Habitou Imóveis e receba leads qualificados de quem realmente quer comprar ou alugar em Santa Catarina.",
};

const FEATURES = [
  { title: "Anúncios com fotos e vídeos", text: "Publique seus imóveis com galeria completa e atraia compradores com apresentações de qualidade." },
  { title: "Cadastro manual ou por integração", text: "Importe seus anúncios diretamente do seu CRM via feed VRSync ou cadastre manualmente em poucos minutos." },
  { title: "Leads onde você preferir", text: "Receba contatos por WhatsApp, telefone ou e-mail — sem intermediários." },
  { title: "Sua página no portal", text: "Tenha um espaço próprio com todos os seus imóveis, dados de contato e identidade profissional." },
];

const TESTIMONIALS = [
  { name: "Jorge", role: "Sócio · ACRC Imóveis", img: "/images/depoimentos/jorge.webp", text: "São mais de 20 anos de parceria — e o que me faz continuar é a combinação de um atendimento próximo e de qualidade com leads que realmente chegam preparados para negociar." },
  { name: "Euclides", role: "Sócio · ABVALE Imóveis", img: "/images/depoimentos/euclides.webp", text: "Aqui você encontra credibilidade, segurança e solidez. Saber que a plataforma é exclusiva para profissionais do setor traz uma confiança que outros portais simplesmente não conseguem oferecer." },
  { name: "Leonel Ribeiro", role: "Sócio · Leonel Ribeiro Imóveis", img: "/images/depoimentos/leonel.webp", text: "A visibilidade que a plataforma gera para os nossos anúncios é notável — e a qualidade dos leads que recebemos confirma isso." },
  { name: "Alaor da Silva", role: "Corretor de imóveis · Santa Catarina", img: "/images/depoimentos/alaor.webp", text: "É muito mais do que um portal para anunciar imóveis — é uma ferramenta de trabalho. Para mim, o Habitou Imóveis é indispensável." },
];

export default function ComoAnunciarPage() {
  return (
    <div>
      <div className="bg-brand-navy py-16 text-center text-white">
        <div className="mx-auto max-w-3xl px-4">
          <p className="text-sm font-semibold uppercase tracking-wide text-white/60">Para corretores e imobiliárias</p>
          <h1 className="mt-2 text-3xl font-bold sm:text-4xl">Anuncie no Habitou Imóveis e receba leads qualificados</h1>
          <p className="mt-4 text-white/70">
            O portal imobiliário com maior presença em Santa Catarina. Mais de 26 anos conectando corretores e
            imobiliárias a compradores e locatários em todo o estado.
          </p>
          <Link href="/planos" className="mt-6 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">
            Ver planos e anunciar
          </Link>
        </div>
      </div>

      <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 className="mb-6 text-2xl font-bold text-brand-text">Tudo que você precisa para anunciar e fechar negócios</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {FEATURES.map((f) => (
            <div key={f.title} className="rounded-xl border border-brand-border bg-white p-5">
              <p className="font-semibold text-brand-text">{f.title}</p>
              <p className="mt-1 text-sm text-brand-text-secondary">{f.text}</p>
            </div>
          ))}
        </div>

        <h2 className="mb-6 mt-14 text-2xl font-bold text-brand-text">Corretores e imobiliárias que já anunciam no Habitou Imóveis</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {TESTIMONIALS.map((t) => (
            <div key={t.name} className="rounded-xl border border-brand-border bg-white p-5">
              <p className="text-sm italic text-brand-text-secondary">&ldquo;{t.text}&rdquo;</p>
              <div className="mt-4 flex items-center gap-3">
                <div className="relative h-10 w-10 overflow-hidden rounded-full">
                  <Image src={t.img} alt={t.name} fill sizes="40px" className="object-cover" />
                </div>
                <div>
                  <p className="text-sm font-semibold text-brand-text">{t.name}</p>
                  <p className="text-xs text-brand-text-secondary">{t.role}</p>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="mt-14 rounded-2xl bg-brand-bg-subtle p-8 text-center">
          <h2 className="text-xl font-bold text-brand-text">Simples de contratar. Sem fidelidade, sem multa, sem taxa de adesão.</h2>
          <Link href="/planos" className="mt-4 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">
            Conhecer planos
          </Link>
        </div>
      </div>
    </div>
  );
}
