import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth/session";

export default async function ImobiliariaLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  if (user.role !== "AGENCY_ADMIN" && user.role !== "AGENT" && user.role !== "ADMIN") redirect("/minha-conta");

  return (
    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
        <aside>
          <p className="mb-3 text-xs font-semibold uppercase text-brand-text-secondary">Painel da imobiliária</p>
          <nav className="flex flex-col gap-1">
            <Link href="/anunciante/imoveis" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Imóveis</Link>
            <Link href="/imobiliaria/feeds" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Feeds VRSync</Link>
            <Link href="/contratos" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Contratos</Link>
          </nav>
        </aside>
        <main>{children}</main>
      </div>
    </div>
  );
}
