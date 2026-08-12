import Link from "next/link";
import { getFeaturedProperties, getPropertiesByCity } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import { HeroSearch } from "@/components/home/HeroSearch";
import { FEATURED_CITIES } from "@/lib/constants/cities";

export default async function HomePage() {
  const user = await getCurrentUser();
  const [featured, favoriteIds] = await Promise.all([
    getFeaturedProperties(6),
    user ? listFavoriteIds(user.id) : Promise.resolve(new Set<string>()),
  ]);

  const florianopolis = await getPropertiesByCity("florianopolis", 3);
  const blumenau = await getPropertiesByCity("blumenau", 3);

  return (
    <div>
      <section
        className="relative bg-brand-navy py-16 sm:py-24"
        style={{
          backgroundImage:
            "linear-gradient(180deg, rgba(61,29,16,0.85), rgba(61,29,16,0.9))",
        }}
      >
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-3xl font-bold text-white sm:text-4xl">
            Encontre o seu imóvel em Santa Catarina
          </h1>
          <p className="mx-auto mt-3 max-w-xl text-white/70">
            26 anos conectando pessoas aos melhores apartamentos, casas e terrenos do estado.
          </p>
          <div className="mt-8">
            <HeroSearch />
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h2 className="mb-2 text-xl font-bold text-brand-text">Cidades em destaque</h2>
        <div className="mb-10 flex flex-wrap gap-2">
          {FEATURED_CITIES.map((c) => (
            <Link
              key={c.slug}
              href={`/${c.slug}`}
              className="rounded-full border border-brand-border px-4 py-2 text-sm font-medium text-brand-text hover:border-brand-primary hover:text-brand-primary"
            >
              {c.name}
            </Link>
          ))}
        </div>

        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-xl font-bold text-brand-text">Imóveis em destaque</h2>
          <Link href="/imoveis" className="text-sm font-medium text-brand-primary hover:underline">
            Ver todos
          </Link>
        </div>
        <PropertyGrid items={featured} favoriteIds={favoriteIds} authenticated={!!user} />

        {florianopolis.length > 0 && (
          <div className="mt-12">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-xl font-bold text-brand-text">Imóveis em Florianópolis</h2>
              <Link href="/florianopolis" className="text-sm font-medium text-brand-primary hover:underline">
                Ver mais
              </Link>
            </div>
            <PropertyGrid items={florianopolis} favoriteIds={favoriteIds} authenticated={!!user} />
          </div>
        )}

        {blumenau.length > 0 && (
          <div className="mt-12">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-xl font-bold text-brand-text">Imóveis em Blumenau</h2>
              <Link href="/blumenau" className="text-sm font-medium text-brand-primary hover:underline">
                Ver mais
              </Link>
            </div>
            <PropertyGrid items={blumenau} favoriteIds={favoriteIds} authenticated={!!user} />
          </div>
        )}
      </section>

      <section className="bg-brand-bg-subtle py-12">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h2 className="text-xl font-bold text-brand-text">Tem um imóvel para anunciar?</h2>
          <p className="mt-2 text-brand-text-secondary">
            Publique gratuitamente e alcance milhares de interessados em Santa Catarina.
          </p>
          <Link
            href="/anunciante/imoveis/novo"
            className="mt-5 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover"
          >
            Anunciar imóvel
          </Link>
        </div>
      </section>
    </div>
  );
}
