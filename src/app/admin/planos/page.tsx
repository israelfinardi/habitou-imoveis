import type { Metadata } from "next";
import { listAllPlans } from "@/server/services/admin-service";
import { createPlanAction } from "@/app/admin/actions";
import { formatCurrencyBRL } from "@/lib/format";
import { PlanToggle } from "./PlanToggle";

export const metadata: Metadata = { title: "Planos (admin)" };

export default async function AdminPlansPage() {
  const plans = await listAllPlans();

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Planos</h1>

      <div className="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 className="mb-3 text-sm font-semibold text-brand-text">Novo plano</h2>
        <form action={createPlanAction} className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <input name="name" required placeholder="Nome" className="rounded-lg border border-brand-border px-3 py-2 text-sm" />
          <input name="price" required type="number" step="0.01" placeholder="Preço mensal" className="rounded-lg border border-brand-border px-3 py-2 text-sm" />
          <input name="maxListings" type="number" placeholder="Limite de anúncios (vazio = ilimitado)" className="rounded-lg border border-brand-border px-3 py-2 text-sm" />
          <input name="description" placeholder="Descrição" className="rounded-lg border border-brand-border px-3 py-2 text-sm" />
          <textarea name="features" placeholder="Um recurso por linha" rows={3} className="sm:col-span-2 rounded-lg border border-brand-border px-3 py-2 text-sm" />
          <button type="submit" className="sm:col-span-2 rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">
            Criar plano
          </button>
        </form>
      </div>

      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Nome</th>
              <th className="px-4 py-3">Preço</th>
              <th className="px-4 py-3">Limite</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {plans.map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3 font-medium text-brand-text">{p.name}</td>
                <td className="px-4 py-3 text-xs">{formatCurrencyBRL(Number(p.price))}</td>
                <td className="px-4 py-3 text-xs">{p.maxListings ?? "Ilimitado"}</td>
                <td className="px-4 py-3"><PlanToggle id={p.id} active={p.active} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
