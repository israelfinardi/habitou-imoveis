import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getCityBySlug } from "@/server/services/location-service";
import { propertySearchParamsSchema } from "@/lib/validation/property";
import {
  SLUG_TO_LISTING_TYPE,
  SLUG_TO_PROPERTY_TYPE,
  LISTING_TYPE_LABEL,
  PROPERTY_TYPE_LABEL,
} from "@/lib/constants/property";
import { PropertyListingSection } from "@/components/property/PropertyListingSection";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";

type SearchParams = Record<string, string | string[] | undefined>;
type Params = { cidade: string; transacao: string; tipo: string };

async function resolve(params: Promise<Params>) {
  const { cidade, transacao, tipo } = await params;
  const city = await getCityBySlug(cidade);
  const listingType = SLUG_TO_LISTING_TYPE[transacao];
  const propertyType = SLUG_TO_PROPERTY_TYPE[tipo];
  if (!city || !listingType || !propertyType) return null;
  return { city, transacao, tipo, listingType, propertyType };
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const resolved = await resolve(params);
  if (!resolved) return {};
  const { city, listingType, propertyType } = resolved;
  const action = listingType === "RENT" ? "para alugar" : "à venda";
  const title = `${PROPERTY_TYPE_LABEL[propertyType]} ${action} em ${city.name}`;
  const description = `Confira ${PROPERTY_TYPE_LABEL[propertyType].toLowerCase()}s ${action} em ${city.name} - ${city.stateCode}. Anúncios verificados, filtre por preço, bairro e características.`;
  return {
    title,
    description,
    alternates: { canonical: `/${city.slug}/${resolved.transacao}/${resolved.tipo}` },
  };
}

export default async function CityTypeListingPage({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams: Promise<SearchParams>;
}) {
  const resolved = await resolve(params);
  if (!resolved) notFound();
  const { city, transacao, tipo, listingType, propertyType } = resolved;

  const raw = await searchParams;
  const parsedParams = propertySearchParamsSchema.parse(
    Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
  );
  const finalParams = { ...parsedParams, cidade: city.slug, transacao: transacao as "comprar" | "alugar", tipo };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <Breadcrumbs
        items={[
          { label: "Início", href: "/" },
          { label: city.name, href: `/${city.slug}` },
          { label: LISTING_TYPE_LABEL[listingType], href: `/${city.slug}/${transacao}` },
          { label: PROPERTY_TYPE_LABEL[propertyType] },
        ]}
      />
      <h1 className="mb-4 text-2xl font-bold text-brand-text">
        {PROPERTY_TYPE_LABEL[propertyType]} {listingType === "RENT" ? "para alugar" : "à venda"} em {city.name}
      </h1>
      <PropertyListingSection
        params={finalParams}
        rawQuery={raw}
        basePath={`/${city.slug}/${transacao}/${tipo}`}
        cityId={city.id}
      />
    </div>
  );
}
