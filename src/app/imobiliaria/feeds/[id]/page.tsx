import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { getFeedWithLogs } from "@/server/services/feed-service";
import { formatDate } from "@/lib/format";
import { FeedActions } from "./FeedActions";

export const metadata: Metadata = { title: "Detalhe do feed" };

export default async function FeedDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await requireUser();
  const feed = await getFeedWithLogs(id, user);
  if (!feed) notFound();

  return (
    <div>
      <Link href="/imobiliaria/feeds" className="mb-4 inline-block text-sm text-brand-primary hover:underline">
        ← Voltar para feeds
      </Link>

      <div className="mb-6 rounded-xl border border-brand-border bg-white p-5">
        <div className="mb-3 flex items-start justify-between">
          <div>
            <h1 className="text-xl font-bold text-brand-text">{feed.name}</h1>
            <p className="break-all text-xs text-brand-text-secondary">{feed.url}</p>
          </div>
          <span className={`shrink-0 rounded-full px-2 py-1 text-xs font-medium ${feed.status === "ACTIVE" ? "bg-brand-green/10 text-brand-green-hover" : "bg-brand-bg-subtle text-brand-text-secondary"}`}>
            {feed.status === "ACTIVE" ? "Ativo" : "Inativo"}
          </span>
        </div>
        <div className="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
          <div>
            <p className="text-xs text-brand-text-secondary">Frequência</p>
            <p className="text-brand-text">a cada {feed.frequencyMinutes} min</p>
          </div>
          <div>
            <p className="text-xs text-brand-text-secondary">Última sincronização</p>
            <p className="text-brand-text">{feed.lastSyncAt ? formatDate(feed.lastSyncAt) : "Nunca"}</p>
          </div>
          <div>
            <p className="text-xs text-brand-text-secondary">Próxima sincronização</p>
            <p className="text-brand-text">{feed.nextSyncAt ? formatDate(feed.nextSyncAt) : "—"}</p>
          </div>
          <div>
            <p className="text-xs text-brand-text-secondary">Último resultado</p>
            <p className="text-brand-text">{feed.lastRunStatus ?? "—"}</p>
          </div>
        </div>
        <FeedActions feedId={feed.id} status={feed.status} />
      </div>

      <h2 className="mb-3 text-lg font-bold text-brand-text">Histórico de sincronizações</h2>
      {feed.logs.length === 0 ? (
        <p className="text-sm text-brand-text-secondary">Nenhuma sincronização executada ainda.</p>
      ) : (
        <div className="overflow-hidden rounded-xl border border-brand-border">
          <table className="w-full text-sm">
            <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th className="px-4 py-2">Início</th>
                <th className="px-4 py-2">Status</th>
                <th className="px-4 py-2">Encontrados</th>
                <th className="px-4 py-2">Criados</th>
                <th className="px-4 py-2">Atualizados</th>
                <th className="px-4 py-2">Sem alteração</th>
                <th className="px-4 py-2">Desativados</th>
                <th className="px-4 py-2">Erros</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-brand-border">
              {feed.logs.map((log) => (
                <tr key={log.id}>
                  <td className="px-4 py-2 text-xs">{formatDate(log.startedAt)}</td>
                  <td className="px-4 py-2 text-xs">{log.status}</td>
                  <td className="px-4 py-2 text-xs">{log.totalFound}</td>
                  <td className="px-4 py-2 text-xs">{log.totalCreated}</td>
                  <td className="px-4 py-2 text-xs">{log.totalUpdated}</td>
                  <td className="px-4 py-2 text-xs">{log.totalUnchanged}</td>
                  <td className="px-4 py-2 text-xs">{log.totalDeactivated}</td>
                  <td className="px-4 py-2 text-xs">{log.totalErrors}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
