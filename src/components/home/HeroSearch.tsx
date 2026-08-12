import { FEATURED_CITIES } from "@/lib/constants/cities";
import { PROPERTY_TYPES, PROPERTY_TYPE_SLUG, PROPERTY_TYPE_LABEL } from "@/lib/constants/property";

export function HeroSearch() {
  return (
    <form action="/imoveis" method="get" className="mx-auto flex max-w-3xl flex-col gap-3 rounded-2xl bg-white p-4 shadow-lg sm:flex-row">
      <select name="transacao" defaultValue="comprar" className="rounded-lg border border-brand-border px-3 py-2.5 text-sm">
        <option value="comprar">Comprar</option>
        <option value="alugar">Alugar</option>
      </select>
      <select name="cidade" defaultValue="" className="rounded-lg border border-brand-border px-3 py-2.5 text-sm">
        <option value="">Todas as cidades</option>
        {FEATURED_CITIES.map((c) => (
          <option key={c.slug} value={c.slug}>
            {c.name}
          </option>
        ))}
      </select>
      <select name="tipo" defaultValue="" className="rounded-lg border border-brand-border px-3 py-2.5 text-sm">
        <option value="">Qualquer tipo</option>
        {PROPERTY_TYPES.map((t) => (
          <option key={t} value={PROPERTY_TYPE_SLUG[t]}>
            {PROPERTY_TYPE_LABEL[t]}
          </option>
        ))}
      </select>
      <button
        type="submit"
        className="rounded-lg bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-primary-hover"
      >
        Buscar imóveis
      </button>
    </form>
  );
}
