import type { Metadata } from "next";
import { propertySearchParamsSchema } from "@/lib/validation/property";
import { listProperties } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { ListingFilters } from "@/components/property/ListingFilters";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import { Pagination } from "@/components/property/Pagination";
import { buildQueryString } from "@/lib/query-string";

export const metadata: Metadata = {
  title: "Busca de imóveis",
  description: "Busque apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina.",
};

type SearchParams = Record<string, string | string[] | undefined>;

export default async function ImoveisSearchPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const raw = await searchParams;
  const params = propertySearchParamsSchema.parse(
    Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]))
  );

  const [result, user] = await Promise.all([listProperties(params), getCurrentUser()]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Busca de imóveis</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">
        {result.total} imóve{result.total === 1 ? "l encontrado" : "is encontrados"}
      </p>

      <div className="mb-6">
        <ListingFilters basePath="/imoveis" showCityAndType />
      </div>

      <PropertyGrid items={result.items} favoriteIds={favoriteIds} authenticated={!!user} />

      <Pagination
        page={result.page}
        totalPages={result.totalPages}
        buildHref={(p) => `/imoveis?${buildQueryString(raw, { pagina: p === 1 ? undefined : p })}`}
      />
    </div>
  );
}
