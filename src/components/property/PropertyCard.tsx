import Image from "next/image";
import Link from "next/link";
import type { PropertyListItem } from "@/types/property";
import { formatCurrencyBRL, formatArea } from "@/lib/format";
import { LISTING_TYPE_SLUG, PROPERTY_TYPE_SLUG, PROPERTY_TYPE_LABEL } from "@/lib/constants/property";
import { FavoriteButton } from "@/components/property/FavoriteButton";

export function propertyHref(property: Pick<PropertyListItem, "slug" | "listingType" | "propertyType" | "city">) {
  return `/${property.city.slug}/${LISTING_TYPE_SLUG[property.listingType]}/${PROPERTY_TYPE_SLUG[property.propertyType]}/${property.slug}`;
}

export function PropertyCard({
  property,
  favorite = false,
  authenticated = false,
}: {
  property: PropertyListItem;
  favorite?: boolean;
  authenticated?: boolean;
}) {
  const image = property.images[0];
  const price = property.listingType === "RENT" ? property.priceRent : property.priceSale;

  return (
    <article className="group overflow-hidden rounded-xl border border-brand-border bg-white transition hover:shadow-md">
      <Link href={propertyHref(property)} className="relative block aspect-[4/3] w-full overflow-hidden bg-brand-bg-subtle">
        {image ? (
          <Image
            src={image.url}
            alt={property.title}
            fill
            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
            className="object-cover transition duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-brand-text-secondary">
            Sem foto
          </div>
        )}
        <span className="absolute left-3 top-3 rounded-full bg-brand-primary px-2.5 py-1 text-xs font-semibold text-white">
          {property.listingType === "RENT" ? "Aluguel" : "Venda"}
        </span>
        <FavoriteButton
          propertyId={property.id}
          initialFavorite={favorite}
          authenticated={authenticated}
          className="absolute right-3 top-3"
        />
      </Link>
      <div className="p-4">
        <p className="text-lg font-bold text-brand-text">{formatCurrencyBRL(price ? Number(price) : null)}</p>
        <Link href={propertyHref(property)} className="mt-1 block truncate text-sm font-medium text-brand-text hover:text-brand-primary">
          {property.title}
        </Link>
        <p className="mt-0.5 truncate text-xs text-brand-text-secondary">
          {property.neighborhood ? `${property.neighborhood.name}, ` : ""}
          {property.city.name} — {property.city.stateCode}
        </p>
        <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-brand-text-secondary">
          {property.bedrooms ? <span>{property.bedrooms} dorm</span> : null}
          {property.parkingSpaces ? <span>{property.parkingSpaces} vaga(s)</span> : null}
          {property.totalArea ? <span>{formatArea(property.totalArea)}</span> : null}
          <span className="ml-auto rounded bg-brand-bg-subtle px-1.5 py-0.5 text-[11px] font-medium text-brand-text-secondary">
            {PROPERTY_TYPE_LABEL[property.propertyType]}
          </span>
        </div>
        {property.agency ? (
          <p className="mt-2 truncate text-[11px] text-brand-text-secondary">{property.agency.name}</p>
        ) : null}
      </div>
    </article>
  );
}
