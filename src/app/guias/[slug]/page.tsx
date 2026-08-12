import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { getArticleBySlug } from "@/server/services/article-service";

type Params = { slug: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);
  if (!article) return {};
  return { title: article.title, description: article.excerpt };
}

export default async function GuidePage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);
  if (!article || article.kind !== "GUIDE") notFound();

  return (
    <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <Link href="/guias" className="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para a central de ajuda</Link>
      <span className="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium text-brand-text-secondary">{article.category}</span>
      <h1 className="mt-3 text-3xl font-bold text-brand-text">{article.title}</h1>
      <p className="mt-8 text-lg leading-relaxed text-brand-text">{article.excerpt}</p>
      {article.content && <div className="prose mt-6 whitespace-pre-line text-brand-text-secondary">{article.content}</div>}
      <p className="mt-10 text-sm text-brand-text-secondary">
        Não resolveu? <Link href="/fale-conosco" className="text-brand-primary hover:underline">Fale com a gente</Link>.
      </p>
    </article>
  );
}
