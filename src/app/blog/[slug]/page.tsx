import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { getArticleBySlug } from "@/server/services/article-service";
import { formatDate } from "@/lib/format";

type Params = { slug: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);
  if (!article) return {};
  return {
    title: article.title,
    description: article.excerpt,
    alternates: { canonical: `/blog/${article.slug}` },
  };
}

export default async function BlogPostPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const article = await getArticleBySlug(slug);
  if (!article || article.kind !== "BLOG") notFound();

  return (
    <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <Link href="/blog" className="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para o blog</Link>
      <span className="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium text-brand-text-secondary">{article.category}</span>
      <h1 className="mt-3 text-3xl font-bold text-brand-text">{article.title}</h1>
      <p className="mt-2 text-sm text-brand-text-secondary">
        {article.authorName}{article.authorRole ? ` · ${article.authorRole}` : ""} · {formatDate(article.publishedAt)}
        {article.readMinutes ? ` · ${article.readMinutes} min de leitura` : ""}
      </p>
      <p className="mt-8 text-lg leading-relaxed text-brand-text">{article.excerpt}</p>
      {article.content && (
        <div className="prose mt-6 whitespace-pre-line text-brand-text-secondary">{article.content}</div>
      )}
    </article>
  );
}
