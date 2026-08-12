import Link from "next/link";
import { FEATURED_CITIES } from "@/lib/constants/cities";

export function SiteFooter() {
  const year = new Date().getFullYear();

  return (
    <footer className="border-t border-brand-border bg-brand-navy text-white/80">
      <div className="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
        <div className="col-span-2 md:col-span-1">
          <p className="mb-3 text-lg font-bold text-white">Habitou Imóveis</p>
          <p className="text-sm text-white/60">
            26 anos conectando pessoas aos melhores imóveis de Santa Catarina.
          </p>
        </div>

        <div>
          <p className="mb-3 text-sm font-semibold text-white">Cidades</p>
          <ul className="space-y-2 text-sm text-white/60">
            {FEATURED_CITIES.map((c) => (
              <li key={c.slug}>
                <Link href={`/${c.slug}`} className="hover:text-white">
                  {c.name}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <p className="mb-3 text-sm font-semibold text-white">Institucional</p>
          <ul className="space-y-2 text-sm text-white/60">
            <li><Link href="/quem-somos" className="hover:text-white">Quem somos</Link></li>
            <li><Link href="/como-anunciar" className="hover:text-white">Como anunciar</Link></li>
            <li><Link href="/imobiliarias" className="hover:text-white">Imobiliárias e corretores</Link></li>
            <li><Link href="/blog" className="hover:text-white">Blog</Link></li>
            <li><Link href="/fale-conosco" className="hover:text-white">Fale conosco</Link></li>
          </ul>
        </div>

        <div>
          <p className="mb-3 text-sm font-semibold text-white">Legal</p>
          <ul className="space-y-2 text-sm text-white/60">
            <li><Link href="/termos-de-uso" className="hover:text-white">Termos de uso</Link></li>
            <li><Link href="/politica-de-privacidade" className="hover:text-white">Política de privacidade</Link></li>
          </ul>
        </div>
      </div>
      <div className="border-t border-white/10 px-4 py-4 text-center text-xs text-white/50">
        © {year} Habitou Imóveis. Todos os direitos reservados.
      </div>
    </footer>
  );
}
