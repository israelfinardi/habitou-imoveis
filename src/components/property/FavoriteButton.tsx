"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import clsx from "clsx";
import { toggleFavoriteAction } from "@/app/actions/favorites";

export function FavoriteButton({
  propertyId,
  initialFavorite,
  authenticated,
  className,
}: {
  propertyId: string;
  initialFavorite: boolean;
  authenticated: boolean;
  className?: string;
}) {
  const [favorite, setFavorite] = useState(initialFavorite);
  const [pending, startTransition] = useTransition();
  const router = useRouter();

  return (
    <button
      type="button"
      aria-label={favorite ? "Remover dos favoritos" : "Adicionar aos favoritos"}
      aria-pressed={favorite}
      disabled={pending}
      onClick={() => {
        if (!authenticated) {
          router.push("/login");
          return;
        }
        setFavorite((v) => !v);
        startTransition(async () => {
          const result = await toggleFavoriteAction(propertyId);
          if ("error" in result) {
            router.push("/login");
            setFavorite((v) => !v);
          } else {
            setFavorite(result.isFavorite);
          }
        });
      }}
      className={clsx(
        "flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow transition hover:scale-105",
        className
      )}
    >
      <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill={favorite ? "#c1502e" : "none"}
        stroke={favorite ? "#c1502e" : "#585b62"}
        strokeWidth="2"
        aria-hidden="true"
      >
        <path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z" />
      </svg>
    </button>
  );
}
