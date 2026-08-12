import type { Metadata } from "next";
import { getAdminStats } from "@/server/services/admin-service";

export const metadata: Metadata = { title: "Administração" };

export default async function AdminHomePage() {
  const stats = await getAdminStats();

  const cards = [
    { label: "Usuários", value: stats.users },
    { label: "Imóveis", value: stats.properties },
    { label: "Imobiliárias", value: stats.agencies },
    { label: "Contratos", value: stats.contracts },
    { label: "Assinaturas ativas", value: stats.activeSubscriptions },
    { label: "Feeds VRSync", value: stats.feeds },
  ];

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Visão geral</h1>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
        {cards.map((c) => (
          <div key={c.label} className="rounded-xl border border-brand-border bg-white p-5">
            <p className="text-2xl font-bold text-brand-text">{c.value}</p>
            <p className="text-sm text-brand-text-secondary">{c.label}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
