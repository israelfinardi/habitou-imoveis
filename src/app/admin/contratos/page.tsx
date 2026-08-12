import type { Metadata } from "next";
import Link from "next/link";
import { requireRole } from "@/lib/auth/guards";
import { listContractsForUser } from "@/server/services/contract-service";
import { formatCurrencyBRL, formatDate } from "@/lib/format";

export const metadata: Metadata = { title: "Contratos (admin)" };

export default async function AdminContractsPage() {
  const user = await requireRole(["ADMIN"]);
  const contracts = await listContractsForUser(user);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Contratos ({contracts.length})</h1>
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
                  <Link href={`/contratos/${c.id}`} className="font-medium text-brand-text hover:text-brand-primary">{c.property.title}</Link>
                </td>
                <td className="px-4 py-3 text-xs">{c.type === "SALE" ? "Venda" : "Aluguel"}</td>
                <td className="px-4 py-3 text-xs">{c.value ? formatCurrencyBRL(Number(c.value)) : "—"}</td>
                <td className="px-4 py-3 text-xs">{c.status}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{formatDate(c.updatedAt)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
