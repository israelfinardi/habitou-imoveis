"use client";

import { useEffect, useState } from "react";
import { isInCompareList, toggleCompareItem, COMPARE_EVENT } from "@/lib/compare-storage";

export function CompareToggle({ propertyId, title, imageUrl }: { propertyId: string; title: string; imageUrl?: string }) {
  const [active, setActive] = useState(false);

  useEffect(() => {
    setActive(isInCompareList(propertyId));
    const handler = () => setActive(isInCompareList(propertyId));
    window.addEventListener(COMPARE_EVENT, handler);
    return () => window.removeEventListener(COMPARE_EVENT, handler);
  }, [propertyId]);

  return (
    <button
      type="button"
      onClick={() => setActive(toggleCompareItem({ id: propertyId, title, imageUrl }).some((i) => i.id === propertyId))}
      className={`rounded-full border px-4 py-2.5 text-sm font-semibold transition ${
        active
          ? "border-brand-primary bg-brand-primary/10 text-brand-primary"
          : "border-brand-border text-brand-text hover:border-brand-primary"
      }`}
    >
      {active ? "Remover da comparação" : "Adicionar à comparação"}
    </button>
  );
}
