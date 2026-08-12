import type { MetadataRoute } from "next";
import { prisma } from "@/lib/db";
import { LISTING_TYPE_SLUG, PROPERTY_TYPE_SLUG } from "@/lib/constants/property";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const appUrl = process.env.APP_URL || "http://localhost:3000";

  const staticPages = [
    "",
    "/imoveis",
    "/imobiliarias",
    "/como-anunciar",
    "/quem-somos",
    "/fale-conosco",
    "/planos",
    "/blog",
    "/guias",
    "/comparar",
    "/termos-de-uso",
    "/politica-de-privacidade",
    "/login",
    "/cadastro",
  ].map((path) => ({
    url: `${appUrl}${path}`,
    lastModified: new Date(),
    changeFrequency: "daily" as const,
    priority: path === "" ? 1 : 0.6,
  }));

  const [cities, properties, agencies, posts, guides] = await Promise.all([
    prisma.city.findMany({ select: { slug: true } }),
    prisma.property.findMany({
      where: { status: "PUBLISHED" },
      select: { slug: true, listingType: true, propertyType: true, city: { select: { slug: true } }, updatedAt: true },
      take: 5000,
    }),
    prisma.agency.findMany({ where: { status: "ACTIVE" }, select: { slug: true } }),
    prisma.article.findMany({ where: { kind: "BLOG" }, select: { slug: true, publishedAt: true } }),
    prisma.article.findMany({ where: { kind: "GUIDE" }, select: { slug: true, publishedAt: true } }),
  ]);

  const cityPages = cities.flatMap((c) => [
    { url: `${appUrl}/${c.slug}`, changeFrequency: "daily" as const, priority: 0.8 },
    { url: `${appUrl}/${c.slug}/comprar`, changeFrequency: "daily" as const, priority: 0.7 },
    { url: `${appUrl}/${c.slug}/alugar`, changeFrequency: "daily" as const, priority: 0.7 },
  ]);

  const propertyPages = properties.map((p) => ({
    url: `${appUrl}/${p.city.slug}/${LISTING_TYPE_SLUG[p.listingType]}/${PROPERTY_TYPE_SLUG[p.propertyType]}/${p.slug}`,
    lastModified: p.updatedAt,
    changeFrequency: "weekly" as const,
    priority: 0.9,
  }));

  const agencyPages = agencies.map((a) => ({
    url: `${appUrl}/imobiliarias/${a.slug}`,
    changeFrequency: "weekly" as const,
    priority: 0.5,
  }));

  const blogPages = posts.map((p) => ({
    url: `${appUrl}/blog/${p.slug}`,
    lastModified: p.publishedAt,
    changeFrequency: "monthly" as const,
    priority: 0.4,
  }));

  const guidePages = guides.map((g) => ({
    url: `${appUrl}/guias/${g.slug}`,
    lastModified: g.publishedAt,
    changeFrequency: "monthly" as const,
    priority: 0.3,
  }));

  return [...staticPages, ...cityPages, ...propertyPages, ...agencyPages, ...blogPages, ...guidePages];
}
