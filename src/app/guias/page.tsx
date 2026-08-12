import type { Metadata } from "next";
import Link from "next/link";
import { listArticles, listCategories } from "@/server/services/article-service";

export const metadata: Metadata = {
  title: "Central de ajuda",
  description: "Guias e respostas para dúvidas frequentes sobre o Habitou Imóveis.",
};

export default async function GuiasPage() {
  const [articles, categories] = await Promise.all([listArticles("GUIDE"), listCategories("GUIDE")]);

  return (
    <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 className="mb-2 text-3xl font-bold text-brand-text">Como podemos te ajudar?</h1>
      <p className="mb-8 text-brand-text-secondary">
        Não encontrou o que procura? <Link href="/fale-conosco" className="text-brand-primary hover:underline">Fale com a gente</Link>.
      </p>

      <div className="mb-8 flex flex-wrap gap-2">
        {categories.map((c) => (
          <span key={c.category} className="rounded-full border border-brand-border px-3 py-1.5 text-sm text-brand-text">
            {c.category} ({c.count})
          </span>
        ))}
      </div>

      <h2 className="mb-4 text-lg font-bold text-brand-text">Últimos artigos</h2>
      <div className="space-y-3">
        {articles.map((a) => (
          <Link key={a.id} href={`/guias/${a.slug}`} className="block rounded-xl border border-brand-border bg-white p-4 hover:border-brand-primary">
            <span className="text-xs font-medium text-brand-text-secondary">{a.category}</span>
            <p className="font-semibold text-brand-text">{a.title}</p>
            <p className="mt-1 text-sm text-brand-text-secondary">{a.excerpt}</p>
          </Link>
        ))}
      </div>
    </div>
  );
}
