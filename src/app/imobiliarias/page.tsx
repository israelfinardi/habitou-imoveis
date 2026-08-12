import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { listAgencies } from "@/server/services/agency-service";
import { Pagination } from "@/components/property/Pagination";

export const metadata: Metadata = {
  title: "Imobiliárias e corretores",
  description: "Conheça as imobiliárias parceiras Habitou Imóveis em Santa Catarina.",
};

export default async function ImobiliariasPage({
  searchParams,
}: {
  searchParams: Promise<{ pagina?: string; q?: string }>;
}) {
  const { pagina, q } = await searchParams;
  const result = await listAgencies(pagina ? Number(pagina) : 1, q);

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Imobiliárias e corretores</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">{result.total} imobiliárias parceiras</p>

      <form className="mb-6 max-w-sm">
        <input
          type="search"
          name="q"
          defaultValue={q}
          placeholder="Buscar imobiliária..."
          className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none"
        />
      </form>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {result.items.map((agency) => (
          <Link
            key={agency.id}
            href={`/imobiliarias/${agency.slug}`}
            className="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary"
          >
            <div className="mb-3 flex items-center gap-3">
              <div className="relative h-12 w-12 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
                {agency.logoUrl && <Image src={agency.logoUrl} alt={agency.name} fill sizes="48px" className="object-cover" />}
              </div>
              <div>
                <p className="font-semibold text-brand-text">{agency.name}</p>
                <p className="text-xs text-brand-text-secondary">{agency._count.properties} imóveis publicados</p>
              </div>
            </div>
            {agency.description && <p className="line-clamp-2 text-xs text-brand-text-secondary">{agency.description}</p>}
          </Link>
        ))}
      </div>

      <Pagination
        page={result.page}
        totalPages={result.totalPages}
        buildHref={(p) => `/imobiliarias?${q ? `q=${encodeURIComponent(q)}&` : ""}pagina=${p}`}
      />
    </div>
  );
}
