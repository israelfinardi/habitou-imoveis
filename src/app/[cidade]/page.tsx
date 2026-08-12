import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getCityBySlug } from "@/server/services/location-service";
import { getPropertiesByCity } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import { PROPERTY_TYPES, PROPERTY_TYPE_SLUG, PROPERTY_TYPE_LABEL } from "@/lib/constants/property";

type Params = { cidade: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { cidade } = await params;
  const city = await getCityBySlug(cidade);
  if (!city) return {};
  return {
    title: `Imóveis em ${city.name}`,
    description:
      city.description ||
      `Encontre apartamentos, casas e terrenos para comprar ou alugar em ${city.name} - ${city.stateCode}.`,
    alternates: { canonical: `/${city.slug}` },
  };
}

export default async function CityPage({ params }: { params: Promise<Params> }) {
  const { cidade } = await params;
  const city = await getCityBySlug(cidade);
  if (!city) notFound();

  const [properties, user] = await Promise.all([getPropertiesByCity(city.slug, 6), getCurrentUser()]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  return (
    <div>
      <div className="bg-brand-bg-subtle">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <h1 className="text-3xl font-bold text-brand-text">Imóveis em {city.name}</h1>
          <p className="mt-2 max-w-2xl text-brand-text-secondary">
            {city.description ||
              `Explore os melhores apartamentos, casas e terrenos disponíveis para comprar ou alugar em ${city.name}, Santa Catarina.`}
          </p>
          <div className="mt-6 flex gap-3">
            <Link href={`/${city.slug}/comprar`} className="rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">
              Comprar
            </Link>
            <Link href={`/${city.slug}/alugar`} className="rounded-full border border-brand-border bg-white px-5 py-2.5 text-sm font-semibold text-brand-text hover:border-brand-primary">
              Alugar
            </Link>
          </div>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 className="mb-4 text-xl font-bold text-brand-text">Tipos de imóvel em {city.name}</h2>
        <div className="mb-10 flex flex-wrap gap-2">
          {PROPERTY_TYPES.map((t) => (
            <Link
              key={t}
              href={`/${city.slug}/comprar/${PROPERTY_TYPE_SLUG[t]}`}
              className="rounded-full border border-brand-border px-3 py-1.5 text-sm text-brand-text hover:border-brand-primary hover:text-brand-primary"
            >
              {PROPERTY_TYPE_LABEL[t]}
            </Link>
          ))}
        </div>

        <h2 className="mb-4 text-xl font-bold text-brand-text">Imóveis recentes</h2>
        <PropertyGrid items={properties} favoriteIds={favoriteIds} authenticated={!!user} />
      </div>
    </div>
  );
}
