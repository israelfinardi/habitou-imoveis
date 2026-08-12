import type { Metadata } from "next";
import { notFound, redirect } from "next/navigation";
import Link from "next/link";
import { getPropertyBySlug, getSimilarProperties } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { isFavorite } from "@/server/services/favorite-service";
import { LISTING_TYPE_SLUG, PROPERTY_TYPE_SLUG, PROPERTY_TYPE_LABEL, LISTING_TYPE_LABEL } from "@/lib/constants/property";
import { formatCurrencyBRL, formatArea, formatDate } from "@/lib/format";
import { PropertyGallery } from "@/components/property/PropertyGallery";
import { PropertyMapClient } from "@/components/property/PropertyMapClient";
import { ContactCard } from "@/components/property/ContactCard";
import { FavoriteButton } from "@/components/property/FavoriteButton";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { listFavoriteIds } from "@/server/services/favorite-service";

type Params = { cidade: string; transacao: string; tipo: string; slug: string };

function canonicalPath(property: NonNullable<Awaited<ReturnType<typeof getPropertyBySlug>>>) {
  return `/${property.city.slug}/${LISTING_TYPE_SLUG[property.listingType]}/${PROPERTY_TYPE_SLUG[property.propertyType]}/${property.slug}`;
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const property = await getPropertyBySlug(slug);
  if (!property || property.status !== "PUBLISHED") return {};

  const description = property.description?.slice(0, 155) || property.title;
  return {
    title: property.title,
    description,
    alternates: { canonical: canonicalPath(property) },
    openGraph: {
      title: property.title,
      description,
      images: property.images[0] ? [property.images[0].url] : undefined,
    },
  };
}

export default async function PropertyDetailPage({ params }: { params: Promise<Params> }) {
  const { cidade, transacao, tipo, slug } = await params;
  const property = await getPropertyBySlug(slug);
  if (!property || property.status !== "PUBLISHED") notFound();

  const expectedPath = canonicalPath(property);
  const actualPath = `/${cidade}/${transacao}/${tipo}/${slug}`;
  if (expectedPath !== actualPath) redirect(expectedPath);

  const user = await getCurrentUser();
  const [favorite, similar] = await Promise.all([
    user ? isFavorite(user.id, property.id) : Promise.resolve(false),
    getSimilarProperties(property),
  ]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  const price = property.listingType === "RENT" ? property.priceRent : property.priceSale;
  const lat = property.latitude ?? property.city.latitude;
  const lng = property.longitude ?? property.city.longitude;
  const approximateLocation = !property.latitude || !property.longitude;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "RealEstateListing",
    name: property.title,
    description: property.description ?? undefined,
    url: `${process.env.APP_URL || ""}${expectedPath}`,
    image: property.images.map((i) => i.url),
    address: {
      "@type": "PostalAddress",
      addressLocality: property.city.name,
      addressRegion: property.city.stateCode,
      addressCountry: "BR",
      streetAddress: property.street ?? undefined,
      postalCode: property.zipCode ?? undefined,
    },
    offers: price
      ? { "@type": "Offer", price: Number(price), priceCurrency: "BRL" }
      : undefined,
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />

      <Breadcrumbs
        items={[
          { label: "Início", href: "/" },
          { label: property.city.name, href: `/${property.city.slug}` },
          { label: LISTING_TYPE_LABEL[property.listingType], href: `/${property.city.slug}/${transacao}` },
          { label: PROPERTY_TYPE_LABEL[property.propertyType], href: `/${property.city.slug}/${transacao}/${tipo}` },
          { label: property.title },
        ]}
      />

      <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <PropertyGallery images={property.images} title={property.title} />

          <div className="mt-6 flex items-start justify-between gap-4">
            <div>
              <h1 className="text-2xl font-bold text-brand-text">{property.title}</h1>
              <p className="mt-1 text-sm text-brand-text-secondary">
                {property.neighborhood ? `${property.neighborhood.name}, ` : ""}
                {property.city.name} — {property.city.stateCode}
                {property.street ? ` · ${property.street}` : ""}
              </p>
            </div>
            <FavoriteButton propertyId={property.id} initialFavorite={favorite} authenticated={!!user} />
          </div>

          <p className="mt-4 text-3xl font-bold text-brand-primary">{formatCurrencyBRL(price ? Number(price) : null)}</p>
          {property.condoFee && <p className="text-sm text-brand-text-secondary">Condomínio: {formatCurrencyBRL(Number(property.condoFee))}</p>}
          {property.iptu && <p className="text-sm text-brand-text-secondary">IPTU: {formatCurrencyBRL(Number(property.iptu))}</p>}

          <div className="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-brand-border p-4 sm:grid-cols-4">
            <Spec label="Área total" value={property.totalArea ? formatArea(property.totalArea) : "—"} />
            <Spec label="Quartos" value={property.bedrooms ?? "—"} />
            <Spec label="Suítes" value={property.suites ?? "—"} />
            <Spec label="Banheiros" value={property.bathrooms ?? "—"} />
            <Spec label="Vagas" value={property.parkingSpaces ?? "—"} />
            <Spec label="Tipo" value={PROPERTY_TYPE_LABEL[property.propertyType]} />
            <Spec label="Código" value={property.code} />
            <Spec label="Publicado" value={formatDate(property.publishedAt)} />
          </div>

          {property.description && (
            <div className="mt-6">
              <h2 className="mb-2 text-lg font-bold text-brand-text">Descrição</h2>
              <p className="whitespace-pre-line text-sm leading-relaxed text-brand-text-secondary">
                {property.description}
              </p>
            </div>
          )}

          {property.features.length > 0 && (
            <div className="mt-6">
              <h2 className="mb-2 text-lg font-bold text-brand-text">Características</h2>
              <div className="flex flex-wrap gap-2">
                {property.features.map((f) => (
                  <span key={f} className="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs text-brand-text">
                    {f}
                  </span>
                ))}
              </div>
            </div>
          )}

          {lat && lng && (
            <div className="mt-6">
              <h2 className="mb-2 text-lg font-bold text-brand-text">Localização</h2>
              {approximateLocation && (
                <p className="mb-2 text-xs text-brand-text-secondary">
                  Localização aproximada (centro de {property.city.name}).
                </p>
              )}
              <PropertyMapClient latitude={lat} longitude={lng} label={property.title} />
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="sticky top-24">
            <ContactCard property={property} />
          </div>
        </div>
      </div>

      {similar.length > 0 && (
        <div className="mt-12">
          <h2 className="mb-4 text-xl font-bold text-brand-text">Imóveis semelhantes</h2>
          <PropertyGrid items={similar} favoriteIds={favoriteIds} authenticated={!!user} />
        </div>
      )}

      <p className="mt-8 text-center text-xs text-brand-text-secondary">
        <Link href="/comparar" className="hover:underline">
          Comparar este imóvel com outros
        </Link>
      </p>
    </div>
  );
}

function Spec({ label, value }: { label: string; value: string | number }) {
  return (
    <div>
      <p className="text-xs text-brand-text-secondary">{label}</p>
      <p className="text-sm font-semibold text-brand-text">{value}</p>
    </div>
  );
}
