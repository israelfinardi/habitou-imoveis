import Link from "next/link";
import { redirect } from "next/navigation";
import { getCurrentUser } from "@/lib/auth/session";

export default async function MinhaContaLayout({ children }: { children: React.ReactNode }) {
  const user = await getCurrentUser();
  if (!user) redirect("/login");

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
      <div className="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
        <aside>
          <div className="mb-4 flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary text-sm font-semibold text-white">
              {user.firstName.charAt(0).toUpperCase()}
            </span>
            <div>
              <p className="text-sm font-semibold text-brand-text">{user.firstName} {user.lastName}</p>
              <p className="text-xs text-brand-text-secondary">{user.email}</p>
            </div>
          </div>
          <nav className="flex flex-col gap-1">
            <Link href="/minha-conta" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Visão geral</Link>
            <Link href="/minha-conta/dados" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Meus dados</Link>
            <Link href="/minha-conta/senha" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Alterar senha</Link>
            <Link href="/minha-conta/anuncios" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Meus anúncios</Link>
            <Link href="/minha-conta/favoritos" className="rounded-lg px-3 py-2 text-sm font-medium text-brand-text hover:bg-brand-bg-subtle">Favoritos</Link>
          </nav>
        </aside>
        <main>{children}</main>
      </div>
    </div>
  );
}
