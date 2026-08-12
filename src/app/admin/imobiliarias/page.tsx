import type { Metadata } from "next";
import Link from "next/link";
import { listAllAgencies } from "@/server/services/admin-service";
import { AgencyStatusToggle } from "./AgencyStatusToggle";

export const metadata: Metadata = { title: "Imobiliárias (admin)" };

export default async function AdminAgenciesPage() {
  const agencies = await listAllAgencies();

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Imobiliárias ({agencies.length})</h1>
      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Nome</th>
              <th className="px-4 py-3">Imóveis</th>
              <th className="px-4 py-3">Usuários</th>
              <th className="px-4 py-3">Feeds</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {agencies.map((a) => (
              <tr key={a.id}>
                <td className="px-4 py-3">
                  <Link href={`/imobiliarias/${a.slug}`} className="font-medium text-brand-text hover:text-brand-primary">{a.name}</Link>
                </td>
                <td className="px-4 py-3 text-xs">{a._count.properties}</td>
                <td className="px-4 py-3 text-xs">{a._count.users}</td>
                <td className="px-4 py-3 text-xs">{a._count.feeds}</td>
                <td className="px-4 py-3"><AgencyStatusToggle id={a.id} status={a.status} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
