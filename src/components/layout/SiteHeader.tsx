import Link from "next/link";
import { FEATURED_CITIES } from "@/lib/constants/cities";
import type { SafeUser } from "@/lib/auth/session";
import { MobileMenu } from "@/components/layout/MobileMenu";
import { UserMenu } from "@/components/layout/UserMenu";

function CityDropdown({ label, transacao }: { label: string; transacao: "comprar" | "alugar" }) {
  return (
    <div className="group relative">
      <button className="flex items-center gap-1 py-2 text-sm font-medium text-brand-text hover:text-brand-primary">
        {label}
        <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
          <path d="M1 1l4 4 4-4" stroke="currentColor" strokeWidth="1.5" />
        </svg>
      </button>
      <div className="invisible absolute left-0 top-full z-30 w-56 rounded-lg border border-brand-border bg-white p-2 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100">
        {FEATURED_CITIES.map((city) => (
          <Link
            key={city.slug}
            href={`/${city.slug}/${transacao}`}
            className="block rounded-md px-3 py-2 text-sm text-brand-text hover:bg-brand-bg-subtle hover:text-brand-primary"
          >
            {city.name}
          </Link>
        ))}
        <Link
          href="/imoveis"
          className="mt-1 block rounded-md border-t border-brand-border px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle"
        >
          Ver todos os imóveis
        </Link>
      </div>
    </div>
  );
}

export function SiteHeader({ user }: { user: SafeUser | null }) {
  return (
    <header className="sticky top-0 z-40 border-b border-brand-border bg-white/95 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/" className="flex items-center gap-2 shrink-0">
          <span className="text-xl font-bold tracking-tight text-brand-primary">Habitou</span>
          <span className="hidden text-xl font-light text-brand-text sm:inline">Imóveis</span>
        </Link>

        <nav className="hidden items-center gap-6 lg:flex">
          <CityDropdown label="Comprar" transacao="comprar" />
          <CityDropdown label="Alugar" transacao="alugar" />
          <Link href="/imobiliarias" className="text-sm font-medium text-brand-text hover:text-brand-primary">
            Imobiliárias e corretores
          </Link>
          <Link href="/como-anunciar" className="text-sm font-medium text-brand-text hover:text-brand-primary">
            Como anunciar
          </Link>
          <Link href="/quem-somos" className="text-sm font-medium text-brand-text hover:text-brand-primary">
            Sobre nós
          </Link>
        </nav>

        <div className="hidden items-center gap-3 lg:flex">
          <Link
            href="/anunciante/imoveis/novo"
            className="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-primary-hover"
          >
            Anunciar imóvel
          </Link>
          <UserMenu user={user} />
        </div>

        <MobileMenu user={user} />
      </div>
    </header>
  );
}
