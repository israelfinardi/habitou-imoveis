import type { Metadata } from "next";
import { PropertyForm } from "@/components/property/PropertyForm";
import { createPropertyAction } from "@/app/anunciante/actions";

export const metadata: Metadata = { title: "Novo imóvel" };

export default function NovoImovelPage() {
  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Novo imóvel</h1>
      <PropertyForm action={createPropertyAction} submitLabel="Criar imóvel" />
    </div>
  );
}
