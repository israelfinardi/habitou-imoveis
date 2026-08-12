"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { readCompareList, removeCompareItem, clearCompareList, COMPARE_EVENT, type CompareItem } from "@/lib/compare-storage";

export function CompareBar() {
  const [items, setItems] = useState<CompareItem[]>([]);

  useEffect(() => {
    setItems(readCompareList());
    const handler = () => setItems(readCompareList());
    window.addEventListener(COMPARE_EVENT, handler);
    window.addEventListener("storage", handler);
    return () => {
      window.removeEventListener(COMPARE_EVENT, handler);
      window.removeEventListener("storage", handler);
    };
  }, []);

  if (items.length === 0) return null;

  return (
    <div className="fixed inset-x-0 bottom-0 z-40 border-t border-brand-border bg-white shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
      <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div className="flex flex-1 items-center gap-2 overflow-x-auto">
          {items.map((item) => (
            <div key={item.id} className="relative flex shrink-0 items-center gap-2 rounded-lg border border-brand-border px-2 py-1">
              {item.imageUrl && (
                <div className="relative h-8 w-10 overflow-hidden rounded">
                  <Image src={item.imageUrl} alt="" fill sizes="40px" className="object-cover" />
                </div>
              )}
              <span className="max-w-[140px] truncate text-xs text-brand-text">{item.title}</span>
              <button
                aria-label="Remover"
                onClick={() => setItems(removeCompareItem(item.id))}
                className="text-xs text-brand-text-secondary hover:text-red-600"
              >
                ✕
              </button>
            </div>
          ))}
        </div>
        <button onClick={() => setItems(clearCompareList())} className="text-xs text-brand-text-secondary hover:underline">
          Limpar
        </button>
        <Link
          href={`/comparar?ids=${items.map((i) => i.id).join(",")}`}
          className="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover"
        >
          Comparar ({items.length})
        </Link>
      </div>
    </div>
  );
}
