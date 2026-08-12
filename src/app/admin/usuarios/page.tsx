import type { Metadata } from "next";
import { listUsers } from "@/server/services/admin-service";
import { formatDate } from "@/lib/format";
import { UserRow } from "./UserRow";

export const metadata: Metadata = { title: "Usuários" };

export default async function AdminUsersPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}) {
  const { q } = await searchParams;
  const users = await listUsers(q);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Usuários ({users.length})</h1>
      <form className="mb-4 max-w-sm">
        <input name="q" defaultValue={q} placeholder="Buscar por nome ou e-mail" className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" />
      </form>
      <div className="overflow-hidden rounded-xl border border-brand-border">
        <table className="w-full text-sm">
          <thead className="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr>
              <th className="px-4 py-3">Nome</th>
              <th className="px-4 py-3">E-mail</th>
              <th className="px-4 py-3">Imobiliária</th>
              <th className="px-4 py-3">Papel / Status</th>
              <th className="px-4 py-3">Criado em</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-brand-border">
            {users.map((u) => (
              <tr key={u.id}>
                <td className="px-4 py-3">{u.firstName} {u.lastName}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{u.email}</td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{u.agency?.name ?? "—"}</td>
                <td className="px-4 py-3"><UserRow id={u.id} role={u.role} status={u.status} /></td>
                <td className="px-4 py-3 text-xs text-brand-text-secondary">{formatDate(u.createdAt)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
