import type { Metadata } from "next";
import Link from "next/link";
import { listAllFeeds } from "@/server/services/admin-service";
import { formatDate } from "@/lib/format";

export const metadata: Metadata = { title: "Feeds VRSync (admin)" };

export default async function AdminFeedsPage() {
  const feeds = await listAllFeeds();

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Feeds VRSync ({feeds.length})</h1>
      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Feed</th>
              <th className="px-4 py-3">Imobiliária</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Última sinc.</th>
              <th className="px-4 py-3">Resultado</th>
              <th className="px-4 py-3">Imóveis</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {feeds.map((f) => (
              <tr key={f.id}>
                <td className="px-4 py-3">
                  <Link href={`/imobiliaria/feeds/${f.id}`} className="font-medium text-brand-text hover:text-brand-primary">{f.name}</Link>
                </td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{f.agency.name}</td>
                <td className="px-4 py-3 text-xs">{f.status}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{f.lastSyncAt ? formatDate(f.lastSyncAt) : "Nunca"}</td>
                <td className="px-4 py-3 text-xs">{f.lastRunStatus ?? "—"}</td>
                <td className="px-4 py-3 text-xs">{f._count.properties}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
