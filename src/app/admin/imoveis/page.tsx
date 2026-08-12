import type { Metadata } from "next";
import { listAllProperties } from "@/server/services/admin-service";
import { formatCurrencyBRL } from "@/lib/format";
import { PropertyRowActions } from "./PropertyRowActions";

export const metadata: Metadata = { title: "Imóveis (admin)" };

export default async function AdminPropertiesPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}) {
  const { q } = await searchParams;
  const properties = await listAllProperties(q);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Imóveis ({properties.length})</h1>
      <form className="mb-4 max-w-sm">
        <input name="q" defaultValue={q} placeholder="Buscar por título" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
      </form>
      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Título</th>
              <th className="px-4 py-3">Origem</th>
              <th className="px-4 py-3">Anunciante / Imobiliária</th>
              <th className="px-4 py-3">Preço</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {properties.map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3">
                  <p className="font-medium text-brand-text">{p.title}</p>
                  <p className="text-xs text-brand-text-secondary">{p.code} · {p.city.name}</p>
                </td>
                <td className="px-4 py-3 text-xs">{p.origin}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">
                  {p.agency?.name ?? `${p.advertiser.firstName} ${p.advertiser.lastName}`}
                </td>
                <td className="px-4 py-3 text-xs">{formatCurrencyBRL(p.priceSale ? Number(p.priceSale) : p.priceRent ? Number(p.priceRent) : null)}</td>
                <td className="px-4 py-3"><PropertyRowActions id={p.id} status={p.status} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
