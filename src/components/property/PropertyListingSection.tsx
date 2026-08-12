import { listProperties } from "@/server/services/property-service";
import { listNeighborhoodsByCity } from "@/server/services/location-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { ListingFilters } from "@/components/property/ListingFilters";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import { Pagination } from "@/components/property/Pagination";
import { buildQueryString } from "@/lib/query-string";
import type { PropertySearchParams } from "@/lib/validation/property";

export async function PropertyListingSection({
  params,
  rawQuery,
  basePath,
  cityId,
}: {
  params: PropertySearchParams;
  rawQuery: Record<string, string | string[] | undefined>;
  basePath: string;
  cityId: string;
}) {
  const [result, user, neighborhoods] = await Promise.all([
    listProperties(params),
    getCurrentUser(),
    listNeighborhoodsByCity(cityId),
  ]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  return (
    <>
      <p className="mb-4 text-sm text-brand-text-secondary">
        {result.total} imóve{result.total === 1 ? "l encontrado" : "is encontrados"}
      </p>

      <div className="mb-6">
        <ListingFilters
          basePath={basePath}
          showCityAndType={false}
          neighborhoods={neighborhoods.map((n) => ({ slug: n.slug, name: n.name }))}
        />
      </div>

      <PropertyGrid items={result.items} favoriteIds={favoriteIds} authenticated={!!user} />

      <Pagination
        page={result.page}
        totalPages={result.totalPages}
        buildHref={(p) => `${basePath}?${buildQueryString(rawQuery, { pagina: p === 1 ? undefined : p })}`}
      />
    </>
  );
}
