"use client";

import { useRef, useState, useTransition } from "react";
import Image from "next/image";
import { useRouter } from "next/navigation";
import {
  removeImageAction,
  setPrimaryImageAction,
  reorderImagesAction,
} from "@/app/anunciante/actions";

type PhotoItem = { id: string; url: string; isPrimary: boolean };

export function PhotoManager({ propertyId, images }: { propertyId: string; images: PhotoItem[] }) {
  const [items, setItems] = useState(images);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pending, startTransition] = useTransition();
  const dragIndex = useRef<number | null>(null);
  const router = useRouter();

  async function handleUpload(files: FileList | null) {
    if (!files || files.length === 0) return;
    setUploading(true);
    setError(null);
    try {
      const formData = new FormData();
      Array.from(files).forEach((f) => formData.append("files", f));
      const res = await fetch(`/api/anunciante/imoveis/${propertyId}/fotos`, {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Erro ao enviar fotos.");
      setItems((prev) => [...prev, ...data.images]);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao enviar fotos.");
    } finally {
      setUploading(false);
    }
  }

  function persistOrder(next: PhotoItem[]) {
    setItems(next);
    startTransition(() => reorderImagesAction(propertyId, next.map((i) => i.id)));
  }

  return (
    <div>
      <div className="mb-4 flex flex-wrap gap-3">
        {items.map((img, idx) => (
          <div
            key={img.id}
            draggable
            onDragStart={() => (dragIndex.current = idx)}
            onDragOver={(e) => e.preventDefault()}
            onDrop={() => {
              if (dragIndex.current === null || dragIndex.current === idx) return;
              const next = [...items];
              const [moved] = next.splice(dragIndex.current, 1);
              next.splice(idx, 0, moved);
              dragIndex.current = null;
              persistOrder(next);
            }}
            className="group relative h-28 w-40 shrink-0 cursor-move overflow-hidden rounded-lg border border-brand-border"
          >
            <Image src={img.url} alt="" fill sizes="160px" className="object-cover" />
            {img.isPrimary && (
              <span className="absolute left-1 top-1 rounded bg-brand-primary px-1.5 py-0.5 text-[10px] font-semibold text-white">
                Principal
              </span>
            )}
            <div className="absolute inset-x-0 bottom-0 flex justify-between gap-1 bg-black/60 p-1 opacity-0 transition group-hover:opacity-100">
              {!img.isPrimary && (
                <button
                  type="button"
                  onClick={() => {
                    setItems((prev) => prev.map((i) => ({ ...i, isPrimary: i.id === img.id })));
                    startTransition(() => setPrimaryImageAction(propertyId, img.id));
                  }}
                  className="text-[10px] text-white hover:underline"
                >
                  Tornar principal
                </button>
              )}
              <button
                type="button"
                onClick={() => {
                  setItems((prev) => prev.filter((i) => i.id !== img.id));
                  startTransition(() => removeImageAction(propertyId, img.id));
                }}
                className="ml-auto text-[10px] text-white hover:underline"
              >
                Remover
              </button>
            </div>
          </div>
        ))}
      </div>

      <label className="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium text-brand-text hover:border-brand-primary">
        {uploading ? "Enviando..." : "+ Adicionar fotos"}
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          disabled={uploading}
          className="hidden"
          onChange={(e) => handleUpload(e.target.files)}
        />
      </label>
      <p className="mt-1 text-xs text-brand-text-secondary">JPG, PNG ou WEBP, até 8MB por foto. Arraste para reordenar.</p>
      {error && <p className="mt-2 text-xs text-red-600">{error}</p>}
      {pending && <p className="mt-1 text-xs text-brand-text-secondary">Salvando alterações...</p>}
    </div>
  );
}
