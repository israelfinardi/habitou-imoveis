import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getCityBySlug } from "@/server/services/location-service";
import { propertySearchParamsSchema } from "@/lib/validation/property";
import { SLUG_TO_LISTING_TYPE, LISTING_TYPE_LABEL, PROPERTY_TYPES, PROPERTY_TYPE_SLUG, PROPERTY_TYPE_LABEL } from "@/lib/constants/property";
import { PropertyListingSection } from "@/components/property/PropertyListingSection";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import Link from "next/link";

type SearchParams = Record<string, string | string[] | undefined>;
type Params = { cidade: string; transacao: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { cidade, transacao } = await params;
  const city = await getCityBySlug(cidade);
  const listingType = SLUG_TO_LISTING_TYPE[transacao];
  if (!city || !listingType) return {};
  const action = listingType === "RENT" ? "para alugar" : "à venda";
  return {
    title: `Imóveis ${action} em ${city.name}`,
    description: `Apartamentos, casas, terrenos e mais imóveis ${action} em ${city.name} - ${city.stateCode}.`,
    alternates: { canonical: `/${city.slug}/${transacao}` },
  };
}

export default async function CityTransactionListingPage({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams: Promise<SearchParams>;
}) {
  const { cidade, transacao } = await params;
  const city = await getCityBySlug(cidade);
  const listingType = SLUG_TO_LISTING_TYPE[transacao];
  if (!city || !listingType) notFound();

  const raw = await searchParams;
  const parsedParams = propertySearchParamsSchema.parse(
    Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
  );
  const finalParams = { ...parsedParams, cidade: city.slug, transacao: transacao as "comprar" | "alugar" };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <Breadcrumbs
        items={[
          { label: "Início", href: "/" },
          { label: city.name, href: `/${city.slug}` },
          { label: LISTING_TYPE_LABEL[listingType] },
        ]}
      />
      <h1 className="mb-4 text-2xl font-bold text-brand-text">
        Imóveis {listingType === "RENT" ? "para alugar" : "à venda"} em {city.name}
      </h1>

      <div className="mb-6 flex flex-wrap gap-2">
        {PROPERTY_TYPES.map((t) => (
          <Link
            key={t}
            href={`/${city.slug}/${transacao}/${PROPERTY_TYPE_SLUG[t]}`}
            className="rounded-full border border-brand-border px-3 py-1.5 text-sm text-brand-text hover:border-brand-primary hover:text-brand-primary"
          >
            {PROPERTY_TYPE_LABEL[t]}
          </Link>
        ))}
      </div>

      <PropertyListingSection
        params={finalParams}
        rawQuery={raw}
        basePath={`/${city.slug}/${transacao}`}
        cityId={city.id}
      />
    </div>
  );
}
