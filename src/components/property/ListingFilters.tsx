"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useState } from "react";
import { FEATURED_CITIES } from "@/lib/constants/cities";
import { PROPERTY_TYPES, PROPERTY_TYPE_LABEL, PROPERTY_TYPE_SLUG, SORT_OPTIONS } from "@/lib/constants/property";

type NeighborhoodOption = { slug: string; name: string };

export function ListingFilters({
  basePath,
  showCityAndType,
  neighborhoods,
}: {
  basePath: string;
  showCityAndType: boolean;
  neighborhoods?: NeighborhoodOption[];
}) {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [open, setOpen] = useState(false);

  const update = useCallback(
    (patch: Record<string, string | undefined>) => {
      const params = new URLSearchParams(searchParams.toString());
      for (const [key, value] of Object.entries(patch)) {
        if (value) params.set(key, value);
        else params.delete(key);
      }
      params.delete("pagina");
      router.push(`${basePath}?${params.toString()}`);
    },
    [basePath, router, searchParams]
  );

  const val = (key: string) => searchParams.get(key) ?? "";

  return (
    <div className="rounded-xl border border-brand-border bg-white p-4">
      <div className="flex flex-wrap items-end gap-3">
        <div className="min-w-[180px] flex-1">
          <label className="mb-1 block text-xs font-medium text-brand-text-secondary">Buscar</label>
          <input
            type="search"
            defaultValue={val("q")}
            placeholder="Título, código, bairro..."
            onKeyDown={(e) => {
              if (e.key === "Enter") update({ q: (e.target as HTMLInputElement).value || undefined });
            }}
            onBlur={(e) => update({ q: e.target.value || undefined })}
            className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none"
          />
        </div>

        {showCityAndType && (
          <>
            <Select
              label="Transação"
              value={val("transacao")}
              onChange={(v) => update({ transacao: v || undefined })}
              options={[
                { value: "comprar", label: "Comprar" },
                { value: "alugar", label: "Alugar" },
              ]}
            />
            <Select
              label="Cidade"
              value={val("cidade")}
              onChange={(v) => update({ cidade: v || undefined })}
              options={FEATURED_CITIES.map((c) => ({ value: c.slug, label: c.name }))}
            />
            <Select
              label="Tipo"
              value={val("tipo")}
              onChange={(v) => update({ tipo: v || undefined })}
              options={PROPERTY_TYPES.map((t) => ({
                value: PROPERTY_TYPE_SLUG[t],
                label: PROPERTY_TYPE_LABEL[t],
              }))}
            />
          </>
        )}

        {!showCityAndType && neighborhoods && neighborhoods.length > 0 && (
          <Select
            label="Bairro"
            value={val("bairro")}
            onChange={(v) => update({ bairro: v || undefined })}
            options={neighborhoods.map((n) => ({ value: n.slug, label: n.name }))}
          />
        )}

        <Select
          label="Ordenar"
          value={val("ordenar")}
          onChange={(v) => update({ ordenar: v || undefined })}
          options={SORT_OPTIONS.map((o) => ({ value: o.value, label: o.label }))}
        />

        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="rounded-lg border border-brand-border px-3 py-2 text-sm font-medium text-brand-text hover:border-brand-primary"
        >
          Mais filtros {open ? "▲" : "▼"}
        </button>
      </div>

      {open && (
        <div className="mt-4 grid grid-cols-2 gap-3 border-t border-brand-border pt-4 sm:grid-cols-4">
          <NumberInput label="Preço mín." value={val("precoMin")} onCommit={(v) => update({ precoMin: v })} />
          <NumberInput label="Preço máx." value={val("precoMax")} onCommit={(v) => update({ precoMax: v })} />
          <NumberInput label="Quartos (mín.)" value={val("quartos")} onCommit={(v) => update({ quartos: v })} />
          <NumberInput label="Suítes (mín.)" value={val("suites")} onCommit={(v) => update({ suites: v })} />
          <NumberInput label="Banheiros (mín.)" value={val("banheiros")} onCommit={(v) => update({ banheiros: v })} />
          <NumberInput label="Vagas (mín.)" value={val("vagas")} onCommit={(v) => update({ vagas: v })} />
          <NumberInput label="Área mín. (m²)" value={val("areaMin")} onCommit={(v) => update({ areaMin: v })} />
          <NumberInput label="Área máx. (m²)" value={val("areaMax")} onCommit={(v) => update({ areaMax: v })} />
        </div>
      )}
    </div>
  );
}

function Select({
  label,
  value,
  onChange,
  options,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  options: { value: string; label: string }[];
}) {
  return (
    <div className="min-w-[140px]">
      <label className="mb-1 block text-xs font-medium text-brand-text-secondary">{label}</label>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg border border-brand-border bg-white px-3 py-2 text-sm focus:border-brand-primary focus:outline-none"
      >
        <option value="">Todos</option>
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </div>
  );
}

function NumberInput({
  label,
  value,
  onCommit,
}: {
  label: string;
  value: string;
  onCommit: (v: string | undefined) => void;
}) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-brand-text-secondary">{label}</label>
      <input
        type="number"
        min={0}
        defaultValue={value}
        onBlur={(e) => onCommit(e.target.value || undefined)}
        onKeyDown={(e) => {
          if (e.key === "Enter") onCommit((e.target as HTMLInputElement).value || undefined);
        }}
        className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none"
      />
    </div>
  );
}
