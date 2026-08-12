import type { Metadata } from "next";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { listContractsForUser } from "@/server/services/contract-service";
import { formatCurrencyBRL, formatDate } from "@/lib/format";

export const metadata: Metadata = { title: "Contratos" };

const STATUS_LABEL: Record<string, string> = {
  DRAFT: "Rascunho",
  ACTIVE: "Ativo",
  FINISHED: "Concluído",
  CANCELED: "Cancelado",
};

export default async function ContratosPage() {
  const user = await requireUser();
  const contracts = await listContractsForUser(user);

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Contratos</h1>

      {contracts.length === 0 ? (
        <div className="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
          Nenhum contrato encontrado. Contratos são criados a partir de um imóvel na área do anunciante.
        </div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-brand-border">
          <table className="w-full text-sm">
            <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th className="px-4 py-3">Imóvel</th>
                <th className="px-4 py-3">Tipo</th>
                <th className="px-4 py-3">Valor</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Atualizado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-brand-border">
              {contracts.map((c) => (
                <tr key={c.id}>
                  <td className="px-4 py-3">
                    <Link href={`/contratos/${c.id}`} className="font-medium text-brand-text hover:text-brand-primary">
                      {c.property.title}
                    </Link>
                  </td>
                  <td className="px-4 py-3">{c.type === "SALE" ? "Venda" : "Aluguel"}</td>
                  <td className="px-4 py-3">{c.value ? formatCurrencyBRL(Number(c.value)) : "—"}</td>
                  <td className="px-4 py-3">
                    <span className="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium">{STATUS_LABEL[c.status]}</span>
                  </td>
                  <td className="px-4 py-3 text-xs text-brand-text-secondary">{formatDate(c.updatedAt)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
