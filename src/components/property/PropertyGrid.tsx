import type { PropertyListItem } from "@/types/property";
import { PropertyCard } from "@/components/property/PropertyCard";

export function PropertyGrid({
  items,
  favoriteIds,
  authenticated,
  emptyMessage = "Nenhum imóvel encontrado com esses filtros.",
}: {
  items: PropertyListItem[];
  favoriteIds: Set<string>;
  authenticated: boolean;
  emptyMessage?: string;
}) {
  if (items.length === 0) {
    return (
      <div className="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
        {emptyMessage}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((property) => (
        <PropertyCard
          key={property.id}
          property={property}
          favorite={favoriteIds.has(property.id)}
          authenticated={authenticated}
        />
      ))}
    </div>
  );
}
