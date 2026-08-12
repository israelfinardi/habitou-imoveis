import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { getContractById } from "@/server/services/contract-service";
import { formatCurrencyBRL, formatDate } from "@/lib/format";
import { StatusForm } from "./StatusForm";

export const metadata: Metadata = { title: "Detalhe do contrato" };

const STATUS_LABEL: Record<string, string> = {
  DRAFT: "Rascunho",
  ACTIVE: "Ativo",
  FINISHED: "Concluído",
  CANCELED: "Cancelado",
};

export default async function ContratoDetalhePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await requireUser();
  const contract = await getContractById(id, user);
  if (!contract) notFound();

  const canManage = user.role === "ADMIN" || contract.advertiserId === user.id || (contract.agencyId && contract.agencyId === user.agencyId);

  return (
    <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
      <Link href="/contratos" className="mb-4 inline-block text-sm text-brand-primary hover:underline">
        ← Voltar para contratos
      </Link>

      <div className="rounded-xl border border-brand-border bg-white p-6">
        <div className="mb-4 flex items-center justify-between">
          <h1 className="text-xl font-bold text-brand-text">{contract.property.title}</h1>
          <span className="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs font-semibold">{STATUS_LABEL[contract.status]}</span>
        </div>

        <dl className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt className="text-brand-text-secondary">Tipo</dt>
            <dd className="text-brand-text">{contract.type === "SALE" ? "Venda" : "Aluguel"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Valor</dt>
            <dd className="text-brand-text">{contract.value ? formatCurrencyBRL(Number(contract.value)) : "—"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Início</dt>
            <dd className="text-brand-text">{contract.startDate ? formatDate(contract.startDate) : "—"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Término</dt>
            <dd className="text-brand-text">{contract.endDate ? formatDate(contract.endDate) : "—"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Anunciante</dt>
            <dd className="text-brand-text">{contract.advertiser ? `${contract.advertiser.firstName} ${contract.advertiser.lastName}` : "—"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Imobiliária</dt>
            <dd className="text-brand-text">{contract.agency?.name ?? "—"}</dd>
          </div>
        </dl>

        {canManage && (
          <div className="mt-6 border-t border-brand-border pt-4">
            <StatusForm contractId={contract.id} currentStatus={contract.status} />
          </div>
        )}

        <div className="mt-6 border-t border-brand-border pt-4">
          <h2 className="mb-3 text-sm font-semibold text-brand-text">Histórico</h2>
          <ul className="space-y-2">
            {contract.history.map((h) => (
              <li key={h.id} className="rounded-lg bg-brand-bg-subtle p-3 text-xs">
                <span className="font-semibold text-brand-text">{STATUS_LABEL[h.status]}</span>
                <span className="ml-2 text-brand-text-secondary">{formatDate(h.createdAt)}</span>
                {h.note && <p className="mt-1 text-brand-text-secondary">{h.note}</p>}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
