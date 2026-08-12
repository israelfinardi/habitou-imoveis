import type { Metadata } from "next";
import Link from "next/link";
import { listArticles, listCategories } from "@/server/services/article-service";
import { formatDate } from "@/lib/format";

export const metadata: Metadata = {
  title: "Blog",
  description: "Análises de mercado, guias práticos e o que muda de verdade para quem compra, vende, aluga ou anuncia em Santa Catarina.",
};

export default async function BlogPage({
  searchParams,
}: {
  searchParams: Promise<{ categoria?: string }>;
}) {
  const { categoria } = await searchParams;
  const [posts, categories] = await Promise.all([listArticles("BLOG", categoria), listCategories("BLOG")]);

  return (
    <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
      <p className="text-sm font-semibold uppercase tracking-wide text-brand-primary">Habitou Imóveis · Conteúdo</p>
      <h1 className="mt-2 text-3xl font-bold text-brand-text">O mercado imobiliário de Santa Catarina, explicado.</h1>
      <p className="mt-2 max-w-2xl text-brand-text-secondary">
        Análises de mercado, guias práticos e o que muda de verdade para quem compra, vende, aluga ou anuncia no
        estado.
      </p>

      <div className="mb-8 mt-6 flex flex-wrap gap-2">
        <Link href="/blog" className={`rounded-full px-3 py-1.5 text-sm ${!categoria ? "bg-brand-primary text-white" : "border border-brand-border text-brand-text hover:border-brand-primary"}`}>
          Todos
        </Link>
        {categories.map((c) => (
          <Link
            key={c.category}
            href={`/blog?categoria=${encodeURIComponent(c.category)}`}
            className={`rounded-full px-3 py-1.5 text-sm ${categoria === c.category ? "bg-brand-primary text-white" : "border border-brand-border text-brand-text hover:border-brand-primary"}`}
          >
            {c.category} ({c.count})
          </Link>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {posts.map((post) => (
          <Link key={post.id} href={`/blog/${post.slug}`} className="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
            <span className="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium text-brand-text-secondary">{post.category}</span>
            <p className="mt-3 font-semibold text-brand-text">{post.title}</p>
            <p className="mt-1 line-clamp-2 text-sm text-brand-text-secondary">{post.excerpt}</p>
            <p className="mt-3 text-xs text-brand-text-secondary">
              {post.authorName} · {formatDate(post.publishedAt)}{post.readMinutes ? ` · ${post.readMinutes} min de leitura` : ""}
            </p>
          </Link>
        ))}
      </div>
    </div>
  );
}
