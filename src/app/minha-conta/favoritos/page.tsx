import type { Metadata } from "next";
import { requireUser } from "@/lib/auth/guards";
import { listFavoriteProperties } from "@/server/services/favorite-service";
import { PropertyGrid } from "@/components/property/PropertyGrid";

export const metadata: Metadata = { title: "Favoritos" };

export default async function FavoritosPage() {
  const user = await requireUser();
  const properties = await listFavoriteProperties(user.id);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Meus favoritos</h1>
      <PropertyGrid
        items={properties}
        favoriteIds={new Set(properties.map((p) => p.id))}
        authenticated
        emptyMessage="Você ainda não favoritou nenhum imóvel."
      />
    </div>
  );
}
