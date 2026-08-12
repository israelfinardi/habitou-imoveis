import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { requireUser, canManageProperty } from "@/lib/auth/guards";
import { getPropertyById } from "@/server/services/property-service";
import { createContractAction } from "@/app/contratos/actions";

export const metadata: Metadata = { title: "Novo contrato" };

export default async function NovoContratoPage({
  searchParams,
}: {
  searchParams: Promise<{ imovelId?: string }>;
}) {
  const { imovelId } = await searchParams;
  const user = await requireUser();
  if (!imovelId) notFound();

  const property = await getPropertyById(imovelId);
  if (!property || !canManageProperty(user, property)) notFound();

  return (
    <div className="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Novo contrato</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">{property.title}</p>

      <form action={createContractAction} className="space-y-4">
        <input type="hidden" name="propertyId" value={property.id} />
        <div>
          <label className="mb-1 block text-sm font-medium text-brand-text">Valor (R$)</label>
          <input
            name="value"
            type="number"
            step="0.01"
            defaultValue={property.priceSale?.toString() ?? property.priceRent?.toString() ?? ""}
            className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
          />
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="mb-1 block text-sm font-medium text-brand-text">Data de início</label>
            <input name="startDate" type="date" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-brand-text">Data de término</label>
            <input name="endDate" type="date" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
          </div>
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-brand-text">Observação</label>
          <textarea name="note" rows={3} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
        </div>
        <button
          type="submit"
          className="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover"
        >
          Criar contrato
        </button>
      </form>
    </div>
  );
}
