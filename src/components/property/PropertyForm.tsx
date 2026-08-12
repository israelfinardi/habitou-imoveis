"use client";

import { useActionState } from "react";
import { FormField } from "@/components/ui/FormField";
import { FEATURED_CITIES } from "@/lib/constants/cities";
import { LISTING_TYPES, LISTING_TYPE_LABEL, PROPERTY_TYPES, PROPERTY_TYPE_LABEL, COMMON_FEATURES } from "@/lib/constants/property";
import type { PropertyActionState } from "@/app/anunciante/actions";

export type PropertyFormDefaults = {
  title?: string;
  description?: string;
  listingType?: string;
  propertyType?: string;
  priceSale?: string;
  priceRent?: string;
  condoFee?: string;
  iptu?: string;
  totalArea?: string;
  builtArea?: string;
  bedrooms?: string;
  suites?: string;
  bathrooms?: string;
  parkingSpaces?: string;
  features?: string[];
  cidade?: string;
  bairro?: string;
  street?: string;
  number?: string;
  complement?: string;
  zipCode?: string;
  latitude?: string;
  longitude?: string;
};

export function PropertyForm({
  action,
  defaults,
  submitLabel = "Salvar imóvel",
}: {
  action: (prev: PropertyActionState, formData: FormData) => Promise<PropertyActionState>;
  defaults?: PropertyFormDefaults;
  submitLabel?: string;
}) {
  const [state, formAction, pending] = useActionState<PropertyActionState, FormData>(action, null);
  const d = defaults ?? {};
  const selectedFeatures = new Set(d.features ?? []);

  return (
    <form action={formAction} className="space-y-8">
      {state?.error && <p className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{state.error}</p>}

      <section>
        <h2 className="mb-3 text-lg font-bold text-brand-text">Informações básicas</h2>
        <FormField label="Título do anúncio" name="title" required defaultValue={d.title} error={state?.fieldErrors?.title} />
        <div className="mb-4">
          <label className="mb-1 block text-sm font-medium text-brand-text">Descrição</label>
          <textarea
            name="description"
            rows={5}
            defaultValue={d.description}
            className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none"
          />
          {state?.fieldErrors?.description && <p className="mt-1 text-xs text-red-600">{state.fieldErrors.description}</p>}
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="mb-1 block text-sm font-medium text-brand-text">Transação</label>
            <select name="listingType" defaultValue={d.listingType ?? "SALE"} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              {LISTING_TYPES.map((t) => (
                <option key={t} value={t}>{LISTING_TYPE_LABEL[t]}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-brand-text">Tipo de imóvel</label>
            <select name="propertyType" defaultValue={d.propertyType ?? "APARTMENT"} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              {PROPERTY_TYPES.map((t) => (
                <option key={t} value={t}>{PROPERTY_TYPE_LABEL[t]}</option>
              ))}
            </select>
          </div>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-lg font-bold text-brand-text">Valores</h2>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <FormField label="Preço de venda (R$)" name="priceSale" type="number" defaultValue={d.priceSale} />
          <FormField label="Preço de aluguel (R$)" name="priceRent" type="number" defaultValue={d.priceRent} />
          <FormField label="Condomínio (R$)" name="condoFee" type="number" defaultValue={d.condoFee} />
          <FormField label="IPTU (R$)" name="iptu" type="number" defaultValue={d.iptu} />
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-lg font-bold text-brand-text">Características</h2>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <FormField label="Área total (m²)" name="totalArea" type="number" defaultValue={d.totalArea} />
          <FormField label="Área construída (m²)" name="builtArea" type="number" defaultValue={d.builtArea} />
          <FormField label="Quartos" name="bedrooms" type="number" defaultValue={d.bedrooms} />
          <FormField label="Suítes" name="suites" type="number" defaultValue={d.suites} />
          <FormField label="Banheiros" name="bathrooms" type="number" defaultValue={d.bathrooms} />
          <FormField label="Vagas" name="parkingSpaces" type="number" defaultValue={d.parkingSpaces} />
        </div>
        <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
          {COMMON_FEATURES.map((f) => (
            <label key={f} className="flex items-center gap-2 text-sm text-brand-text">
              <input type="checkbox" name="features" value={f} defaultChecked={selectedFeatures.has(f)} />
              {f}
            </label>
          ))}
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-lg font-bold text-brand-text">Localização</h2>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="mb-1 block text-sm font-medium text-brand-text">Cidade</label>
            <select name="cidade" required defaultValue={d.cidade} className="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              <option value="">Selecione</option>
              {FEATURED_CITIES.map((c) => (
                <option key={c.slug} value={c.slug}>{c.name}</option>
              ))}
            </select>
            {state?.fieldErrors?.cidade && <p className="mt-1 text-xs text-red-600">{state.fieldErrors.cidade}</p>}
          </div>
          <FormField label="Bairro" name="bairro" required defaultValue={d.bairro} error={state?.fieldErrors?.bairro} />
        </div>
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <FormField label="Rua" name="street" defaultValue={d.street} />
          <FormField label="Número" name="number" defaultValue={d.number} />
          <FormField label="Complemento" name="complement" defaultValue={d.complement} />
          <FormField label="CEP" name="zipCode" defaultValue={d.zipCode} />
        </div>
        <div className="grid grid-cols-2 gap-3">
          <FormField label="Latitude (opcional)" name="latitude" type="number" defaultValue={d.latitude} />
          <FormField label="Longitude (opcional)" name="longitude" type="number" defaultValue={d.longitude} />
        </div>
      </section>

      <button
        type="submit"
        disabled={pending}
        className="rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover disabled:opacity-60"
      >
        {pending ? "Salvando..." : submitLabel}
      </button>
    </form>
  );
}
