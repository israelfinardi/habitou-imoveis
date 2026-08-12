import type { Metadata } from "next";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { listFeedsForActor } from "@/server/services/feed-service";
import { formatDate } from "@/lib/format";
import { CreateFeedForm } from "./CreateFeedForm";

export const metadata: Metadata = { title: "Feeds VRSync" };

export default async function FeedsPage() {
  const user = await requireUser();
  const feeds = await listFeedsForActor(user);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Feeds VRSync</h1>

      <div className="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 className="mb-3 text-sm font-semibold text-brand-text">Novo feed</h2>
        <CreateFeedForm />
      </div>

      {feeds.length === 0 ? (
        <div className="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
          Nenhum feed cadastrado.
        </div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-brand-border">
          <table className="w-full text-sm">
            <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th className="px-4 py-3">Nome</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Última sincronização</th>
                <th className="px-4 py-3">Resultado</th>
                <th className="px-4 py-3">Imóveis</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-brand-border">
              {feeds.map((f) => (
                <tr key={f.id}>
                  <td className="px-4 py-3">
                    <Link href={`/imobiliaria/feeds/${f.id}`} className="font-medium text-brand-text hover:text-brand-primary">
                      {f.name}
                    </Link>
                    <p className="text-xs text-brand-text-secondary">{f.agency.name}</p>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-2 py-1 text-xs font-medium ${f.status === "ACTIVE" ? "bg-brand-green/10 text-brand-green-hover" : "bg-brand-bg-subtle text-brand-text-secondary"}`}>
                      {f.status === "ACTIVE" ? "Ativo" : "Inativo"}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-xs text-brand-text-secondary">{f.lastSyncAt ? formatDate(f.lastSyncAt) : "Nunca"}</td>
                  <td className="px-4 py-3 text-xs">{f.lastRunStatus ?? "—"}</td>
                  <td className="px-4 py-3 text-xs">{f._count.properties}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
