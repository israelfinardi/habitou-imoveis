import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { prisma } from "@/lib/db";
import { propertyDetailInclude } from "@/server/services/property-service";
import { propertyHref } from "@/components/property/PropertyCard";
import { formatCurrencyBRL, formatArea } from "@/lib/format";
import { PROPERTY_TYPE_LABEL } from "@/lib/constants/property";

export const metadata: Metadata = { title: "Comparar imóveis" };

type Row = { label: string; render: (p: Awaited<ReturnType<typeof loadProperties>>[number]) => React.ReactNode };

async function loadProperties(ids: string[]) {
  if (ids.length === 0) return [];
  const properties = await prisma.property.findMany({
    where: { id: { in: ids } },
    include: propertyDetailInclude,
  });
  return ids.map((id) => properties.find((p) => p.id === id)).filter((p): p is NonNullable<typeof p> => !!p);
}

export default async function CompararPage({
  searchParams,
}: {
  searchParams: Promise<{ ids?: string }>;
}) {
  const { ids } = await searchParams;
  const idList = ids ? ids.split(",").filter(Boolean) : [];
  const properties = await loadProperties(idList);

  const rows: Row[] = [
    { label: "Preço", render: (p) => formatCurrencyBRL(Number(p.priceSale ?? p.priceRent ?? 0)) },
    { label: "Tipo", render: (p) => PROPERTY_TYPE_LABEL[p.propertyType] },
    { label: "Cidade / bairro", render: (p) => `${p.neighborhood?.name ?? ""}, ${p.city.name}` },
    { label: "Área total", render: (p) => (p.totalArea ? formatArea(p.totalArea) : "—") },
    { label: "Quartos", render: (p) => p.bedrooms ?? "—" },
    { label: "Suítes", render: (p) => p.suites ?? "—" },
    { label: "Banheiros", render: (p) => p.bathrooms ?? "—" },
    { label: "Vagas", render: (p) => p.parkingSpaces ?? "—" },
    { label: "Condomínio", render: (p) => (p.condoFee ? formatCurrencyBRL(Number(p.condoFee)) : "—") },
    { label: "IPTU", render: (p) => (p.iptu ? formatCurrencyBRL(Number(p.iptu)) : "—") },
    {
      label: "Características",
      render: (p) => (p.features.length ? p.features.join(", ") : "—"),
    },
  ];

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Comparar imóveis</h1>

      {properties.length === 0 ? (
        <div className="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
          Nenhum imóvel selecionado. Use o botão &quot;Adicionar à comparação&quot; nas páginas de imóveis.
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[640px] border-collapse text-sm">
            <thead>
              <tr>
                <th className="w-40 p-3 text-left text-xs uppercase text-brand-text-secondary">Imóvel</th>
                {properties.map((p) => (
                  <th key={p.id} className="p-3 text-left">
                    <Link href={propertyHref(p)} className="block">
                      <div className="relative mb-2 h-28 w-full overflow-hidden rounded-lg bg-brand-bg-subtle">
                        {p.images[0] && (
                          <Image src={p.images[0].url} alt={p.title} fill sizes="240px" className="object-cover" />
                        )}
                      </div>
                      <span className="line-clamp-2 text-sm font-semibold text-brand-text hover:text-brand-primary">
                        {p.title}
                      </span>
                    </Link>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-brand-border">
              {rows.map((row) => (
                <tr key={row.label}>
                  <td className="p-3 text-xs font-semibold uppercase text-brand-text-secondary">{row.label}</td>
                  {properties.map((p) => (
                    <td key={p.id} className="p-3 text-brand-text">
                      {row.render(p)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
