import type { Metadata } from "next";
import { listAllSubscriptions } from "@/server/services/admin-service";
import { formatDate } from "@/lib/format";
import { SubscriptionRowActions } from "./SubscriptionRowActions";

export const metadata: Metadata = { title: "Assinaturas (admin)" };

export default async function AdminSubscriptionsPage() {
  const subs = await listAllSubscriptions();

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Assinaturas ({subs.length})</h1>
      <p className="mb-4 text-sm text-brand-text-secondary">
        Não há gateway de pagamento integrado. Assinaturas ficam como &quot;PENDING&quot; até serem confirmadas manualmente aqui.
      </p>
      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Assinante</th>
              <th className="px-4 py-3">Plano</th>
              <th className="px-4 py-3">Criada em</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {subs.map((s) => (
              <tr key={s.id}>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">
                  {s.agency?.name ?? (s.user ? `${s.user.firstName} ${s.user.lastName} (${s.user.email})` : "—")}
                </td>
                <td className="px-4 py-3">{s.plan.name}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{formatDate(s.createdAt)}</td>
                <td className="px-4 py-3"><SubscriptionRowActions id={s.id} status={s.status} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
