import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { listPropertiesForAdvertiser } from "@/server/services/property-mutations";
import { formatCurrencyBRL, formatDate } from "@/lib/format";
import { PROPERTY_STATUS_LABEL, PROPERTY_TYPE_LABEL } from "@/lib/constants/property";
import { PropertyStatusActions } from "./PropertyStatusActions";

export const metadata: Metadata = { title: "Meus imóveis" };

export default async function MeusImoveisPage({
  searchParams,
}: {
  searchParams: Promise<{ criado?: string }>;
}) {
  const user = await requireUser();
  const properties = await listPropertiesForAdvertiser(user);
  const params = await searchParams;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-brand-text">Meus imóveis</h1>
        <Link href="/anunciante/imoveis/novo" className="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">
          + Novo imóvel
        </Link>
      </div>

      {params.criado && (
        <p className="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">
          Imóvel criado. Adicione fotos e publique quando estiver pronto.
        </p>
      )}

      {properties.length === 0 ? (
        <div className="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
          Você ainda não cadastrou nenhum imóvel.
        </div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-brand-border">
          <table className="w-full text-sm">
            <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th className="px-4 py-3">Imóvel</th>
                <th className="px-4 py-3">Tipo</th>
                <th className="px-4 py-3">Preço</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Atualizado</th>
                <th className="px-4 py-3">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-brand-border">
              {properties.map((p) => (
                <tr key={p.id}>
                  <td className="flex items-center gap-3 px-4 py-3">
                    <div className="relative h-12 w-16 shrink-0 overflow-hidden rounded bg-brand-bg-subtle">
                      {p.images[0] && (
                        <Image src={p.images[0].url} alt="" fill sizes="64px" className="object-cover" />
                      )}
                    </div>
                    <div>
                      <Link href={`/anunciante/imoveis/${p.id}/editar`} className="font-medium text-brand-text hover:text-brand-primary">
                        {p.title}
                      </Link>
                      <p className="text-xs text-brand-text-secondary">
                        {p.code} · {p.neighborhood?.name}, {p.city.name}
                      </p>
                    </div>
                  </td>
                  <td className="px-4 py-3">{PROPERTY_TYPE_LABEL[p.propertyType]}</td>
                  <td className="px-4 py-3">
                    {formatCurrencyBRL(p.priceSale ? Number(p.priceSale) : p.priceRent ? Number(p.priceRent) : null)}
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium">
                      {PROPERTY_STATUS_LABEL[p.status]}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-brand-text-secondary">{formatDate(p.updatedAt)}</td>
                  <td className="px-4 py-3">
                    <PropertyStatusActions propertyId={p.id} status={p.status} />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
