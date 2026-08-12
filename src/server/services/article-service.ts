import "server-only";
import { prisma } from "@/lib/db";
import type { ArticleKind } from "@prisma/client";

export async function listArticles(kind: ArticleKind, category?: string) {
  return prisma.article.findMany({
    where: { kind, ...(category ? { category } : {}) },
    orderBy: { publishedAt: "desc" },
  });
}

export async function getArticleBySlug(slug: string) {
  return prisma.article.findUnique({ where: { slug } });
}

export async function listCategories(kind: ArticleKind) {
  const articles = await prisma.article.findMany({ where: { kind }, select: { category: true } });
  const counts = new Map<string, number>();
  for (const a of articles) counts.set(a.category, (counts.get(a.category) ?? 0) + 1);
  return [...counts.entries()].map(([category, count]) => ({ category, count }));
}
