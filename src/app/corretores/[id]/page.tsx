import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { getAgentById } from "@/server/services/agency-service";
import { listProperties } from "@/server/services/property-service";
import { getCurrentUser } from "@/lib/auth/session";
import { listFavoriteIds } from "@/server/services/favorite-service";
import { PropertyGrid } from "@/components/property/PropertyGrid";

type Params = { id: string };

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { id } = await params;
  const agent = await getAgentById(id);
  if (!agent) return {};
  return { title: `${agent.firstName} ${agent.lastName} — Corretor` };
}

export default async function CorretorPage({ params }: { params: Promise<Params> }) {
  const { id } = await params;
  const agent = await getAgentById(id);
  if (!agent) notFound();

  const [result, user] = await Promise.all([
    listProperties({}, { agentId: agent.id }),
    getCurrentUser(),
  ]);
  const favoriteIds = user ? await listFavoriteIds(user.id) : new Set<string>();

  return (
    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <div className="mb-8 flex items-center gap-4">
        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-brand-primary text-xl font-semibold text-white">
          {agent.firstName.charAt(0)}
        </span>
        <div>
          <h1 className="text-2xl font-bold text-brand-text">{agent.firstName} {agent.lastName}</h1>
          {agent.creci && <p className="text-sm text-brand-text-secondary">CRECI {agent.creci}</p>}
          {agent.agency && (
            <Link href={`/imobiliarias/${agent.agency.slug}`} className="text-sm text-brand-primary hover:underline">
              {agent.agency.name}
            </Link>
          )}
        </div>
      </div>

      <h2 className="mb-4 text-lg font-bold text-brand-text">Imóveis deste corretor ({result.total})</h2>
      <PropertyGrid items={result.items} favoriteIds={favoriteIds} authenticated={!!user} />
    </div>
  );
}
