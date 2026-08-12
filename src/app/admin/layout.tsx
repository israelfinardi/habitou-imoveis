import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth/session";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.role !== "ADMIN") redirect("/minha-conta");

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
        <aside>
          <p className="mb-3 text-xs font-semibold uppercase text-brand-text-secondary">Administração</p>
          <nav className="flex flex-col gap-1">
            <Link href="/admin" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Visão geral</Link>
            <Link href="/admin/usuarios" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Usuários</Link>
            <Link href="/admin/imoveis" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Imóveis</Link>
            <Link href="/admin/imobiliarias" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Imobiliárias</Link>
            <Link href="/admin/planos" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Planos</Link>
            <Link href="/admin/assinaturas" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Assinaturas</Link>
            <Link href="/admin/feeds" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Feeds VRSync</Link>
            <Link href="/admin/contratos" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Contratos</Link>
          </nav>
        </aside>
        <main>{children}</main>
      </div>
    </div>
  );
}
