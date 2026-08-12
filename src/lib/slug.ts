import slugify from "slugify";

export function toSlug(input: string): string {
  return slugify(input, { lower: true, strict: true, locale: "pt" });
}

/** Gera um slug único de imóvel: título + cidade + bairro + sufixo curto. */
export function buildPropertySlug(params: {
  title: string;
  city: string;
  neighborhood?: string | null;
  suffix: string;
}): string {
  const parts = [params.title, params.neighborhood ?? undefined, params.city].filter(
    Boolean
  ) as string[];
  const base = toSlug(parts.join(" "));
  return `${base}-${params.suffix}`;
}
