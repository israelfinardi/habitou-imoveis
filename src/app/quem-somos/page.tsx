import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Quem somos",
  description: "26 anos de história conectando corretores, imobiliárias e famílias em Santa Catarina.",
};

const TIMELINE = [
  { year: "1999", title: "Fundação", text: "O Habitou Imóveis nasce — pioneiro digital do setor. Criado com uma visão à frente do seu tempo: levar o mercado imobiliário de Santa Catarina para a internet, em um momento em que poucos acreditavam no potencial do ambiente digital." },
  { year: "2000s", title: "Expansão pelo Vale do Itajaí", text: "Consolidamos presença no Vale do Itajaí — uma das regiões de maior dinamismo imobiliário do estado. A marca se tornou referência entre corretores e imobiliárias de Blumenau, Itajaí e cidades vizinhas." },
  { year: "2010s", title: "Audiência consolidada", text: "O Habitou Imóveis chegou à marca de 2 milhões de usuários únicos por ano — uma audiência qualificada e engajada, com tempo médio de permanência superior a 10 minutos no portal." },
  { year: "2025", title: "Decisão de reinvenção tecnológica", text: "Com 25 anos de história e uma base sólida, iniciamos o maior investimento tecnológico da nossa trajetória: o desenvolvimento completo de uma nova plataforma." },
  { year: "2026", title: "Nova era", text: "Lançamos a nova versão do Habitou Imóveis: uma plataforma completa para corretores e imobiliárias, construída sobre 26 anos de confiança em Santa Catarina." },
];

const STATS = [
  { value: "1999", label: "Um dos primeiros portais imobiliários criados no Brasil." },
  { value: "26 anos", label: "De presença contínua no mercado de Santa Catarina." },
  { value: "2M+/ano", label: "Usuários únicos qualificados navegando no portal todos os anos." },
];

const VALUES = [
  { title: "Parceria real", text: "Estamos ao lado dos corretores — não competimos com eles. Nossa missão é entregar audiência, ferramentas e presença digital aos profissionais." },
  { title: "Exclusividade profissional", text: "Somente corretores e imobiliárias credenciados pelo CRECI-SC anunciam aqui. Isso garante qualidade, ética e confiança em cada anúncio." },
  { title: "Raízes catarinenses", text: "Somos de SC, para SC. Conhecemos o mercado local — e isso se reflete na qualidade da audiência e na relevância dos anúncios." },
  { title: "Confiança construída", text: "26 anos de presença contínua constroem algo que não se compra: reputação." },
];

export default function QuemSomosPage() {
  return (
    <div>
      <div className="bg-brand-navy py-16 text-center text-white">
        <div className="mx-auto max-w-3xl px-4">
          <p className="text-sm font-semibold uppercase tracking-wide text-white/60">Quem somos</p>
          <h1 className="mt-2 text-3xl font-bold sm:text-4xl">26 anos de história e uma nova tecnologia para liderar o futuro.</h1>
          <p className="mt-4 text-white/70">
            O Habitou Imóveis nasceu junto com o mercado imobiliário digital no Brasil. Agora, com a mais nova versão
            da plataforma, reafirmamos nosso compromisso de estar sempre à frente — ao lado dos corretores e
            imobiliárias que constroem Santa Catarina.
          </p>
        </div>
      </div>

      <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          {STATS.map((s) => (
            <div key={s.value} className="rounded-xl border border-brand-border bg-white p-5 text-center">
              <p className="text-2xl font-bold text-brand-primary">{s.value}</p>
              <p className="mt-1 text-sm text-brand-text-secondary">{s.label}</p>
            </div>
          ))}
        </div>

        <h2 className="mb-6 mt-14 text-2xl font-bold text-brand-text">Nossa história</h2>
        <div className="space-y-6 border-l-2 border-brand-border pl-6">
          {TIMELINE.map((t) => (
            <div key={t.year} className="relative">
              <span className="absolute -left-[31px] flex h-4 w-4 items-center justify-center rounded-full bg-brand-primary" />
              <p className="text-sm font-semibold text-brand-primary">{t.year}</p>
              <p className="font-semibold text-brand-text">{t.title}</p>
              <p className="mt-1 text-sm text-brand-text-secondary">{t.text}</p>
            </div>
          ))}
        </div>

        <h2 className="mb-2 mt-14 text-2xl font-bold text-brand-text">O corretor é o protagonista. Sempre foi.</h2>
        <p className="mb-6 text-sm text-brand-text-secondary">
          O mercado imobiliário de Santa Catarina é construído por corretores e imobiliárias. O Habitou Imóveis
          existe para fortalecer esses profissionais — nunca para concorrer com eles.
        </p>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {VALUES.map((v) => (
            <div key={v.title} className="rounded-xl border border-brand-border bg-white p-5">
              <p className="font-semibold text-brand-text">{v.title}</p>
              <p className="mt-1 text-sm text-brand-text-secondary">{v.text}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
