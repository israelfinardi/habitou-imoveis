import type { Metadata } from "next";
import Image from "next/image";
import { notFound } from "next/navigation";
import { getAgencyBySlug, getAgencyAgents } from "@/server/services/agency-service";
import { listProperties } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { PropertyGrid } from "@/components/property/PropertyGrid";
import Link from "next/link";

type Params = { slug: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const agency = await getAgencyBySlug(slug);
  if (!agency) return {};
  return {
    title: agency.name,
    description: agency.description || `Imóveis anunciados por ${agency.name} no Habitou Imóveis.`,
    alternates: { canonical: `/imobiliarias/${agency.slug}` },
  };
}

export default async function AgencyPage({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams: Promise<{ pagina?: string }>;
}) {
  const { slug } = await params;
  const agency = await getAgencyBySlug(slug);
  if (!agency) notFound();

  const { pagina } = await searchParams;
  const [result, agents, user] = await Promise.all([
    listProperties({ pagina: pagina ? Number(pagina) : 1 }, { agencyId: agency.id }),
    getAgencyAgents(agency.id),
    getCurrentUser(),
  ]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  return (
    <div>
      <div className="border-b border-brand-border bg-brand-bg-subtle">
        <div className="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 lg:px-8">
          <div className="relative h-20 w-20 overflow-hidden rounded-full bg-white shadow">
            {agency.logoUrl && <Image src={agency.logoUrl} alt={agency.name} fill sizes="80px" className="object-cover" />}
          </div>
          <h1 className="text-2xl font-bold text-brand-text">{agency.name}</h1>
          {agency.description && <p className="max-w-2xl text-sm text-brand-text-secondary">{agency.description}</p>}
          <div className="flex flex-wrap justify-center gap-4 text-sm text-brand-text-secondary">
            {agency.phone && <span>{agency.phone}</span>}
            {agency.email && <span>{agency.email}</span>}
            {agency.city && <span>{agency.city} — {agency.state}</span>}
            {agency.website && (
              <a href={agency.website} target="_blank" rel="noopener noreferrer" className="text-brand-primary hover:underline">
                {agency.website}
              </a>
            )}
          </div>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {agents.length > 0 && (
          <div className="mb-10">
            <h2 className="mb-3 text-lg font-bold text-brand-text">Corretores</h2>
            <div className="flex flex-wrap gap-3">
              {agents.map((agent) => (
                <Link
                  key={agent.id}
                  href={`/corretores/${agent.id}`}
                  className="flex items-center gap-2 rounded-full border border-brand-border px-3 py-2 text-sm hover:border-brand-primary"
                >
                  <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white">
                    {agent.firstName.charAt(0)}
                  </span>
                  {agent.firstName} {agent.lastName}
                </Link>
              ))}
            </div>
          </div>
        )}

        <h2 className="mb-4 text-lg font-bold text-brand-text">Imóveis publicados ({result.total})</h2>
        <PropertyGrid items={result.items} favoriteIds={favoriteIds} authenticated={!!user} />
      </div>
    </div>
  );
}
