import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { requireUser } from "@/lib/auth/guards";
import { canManageProperty } from "@/lib/auth/guards";
import { getPropertyById } from "@/server/services/property-service";
import { updatePropertyAction } from "@/app/anunciante/actions";
import { PropertyForm } from "@/components/property/PropertyForm";
import { PhotoManager } from "@/components/property/PhotoManager";

export const metadata: Metadata = { title: "Editar imóvel" };

export default async function EditarImovelPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ criado?: string }>;
}) {
  const { id } = await params;
  const user = await requireUser();
  const property = await getPropertyById(id);
  if (!property || !canManageProperty(user, property)) notFound();

  const boundAction = updatePropertyAction.bind(null, id);
  const search = await searchParams;

  return (
    <div>
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Editar imóvel</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">Código {property.code}</p>

      {search.criado && (
        <p className="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">
          Imóvel criado com sucesso. Adicione fotos abaixo e publique quando estiver pronto.
        </p>
      )}

      <section className="mb-8">
        <h2 className="mb-3 text-lg font-bold text-brand-text">Fotos</h2>
        <PhotoManager
          propertyId={id}
          images={property.images.map((i) => ({ id: i.id, url: i.url, isPrimary: i.isPrimary }))}
        />
      </section>

      <PropertyForm
        action={boundAction}
        submitLabel="Salvar alterações"
        defaults={{
          title: property.title,
          description: property.description ?? undefined,
          listingType: property.listingType,
          propertyType: property.propertyType,
          priceSale: property.priceSale?.toString(),
          priceRent: property.priceRent?.toString(),
          condoFee: property.condoFee?.toString(),
          iptu: property.iptu?.toString(),
          totalArea: property.totalArea?.toString(),
          builtArea: property.builtArea?.toString(),
          bedrooms: property.bedrooms?.toString(),
          suites: property.suites?.toString(),
          bathrooms: property.bathrooms?.toString(),
          parkingSpaces: property.parkingSpaces?.toString(),
          features: property.features,
          cidade: property.city.slug,
          bairro: property.neighborhood?.name,
          street: property.street ?? undefined,
          number: property.number ?? undefined,
          complement: property.complement ?? undefined,
          zipCode: property.zipCode ?? undefined,
          latitude: property.latitude?.toString(),
          longitude: property.longitude?.toString(),
        }}
      />
    </div>
  );
}
