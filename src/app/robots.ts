import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  const appUrl = process.env.APP_URL || "http://localhost:3000";

  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: ["/admin", "/anunciante", "/imobiliaria", "/minha-conta", "/api", "/contratos", "/planos/actions"],
      },
    ],
    sitemap: `${appUrl}/sitemap.xml`,
  };
}
