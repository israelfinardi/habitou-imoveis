"use client";

import { useState, useCallback, useEffect } from "react";
import Image from "next/image";

type GalleryImage = { id: string; url: string };

export function PropertyGallery({ images, title }: { images: GalleryImage[]; title: string }) {
  const [active, setActive] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);

  const safeImages = images.length > 0 ? images : [{ id: "placeholder", url: "" }];

  const goTo = useCallback(
    (delta: number) => {
      setActive((v) => (v + delta + safeImages.length) % safeImages.length);
    },
    [safeImages.length]
  );

  useEffect(() => {
    if (!lightboxOpen) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") setLightboxOpen(false);
      if (e.key === "ArrowRight") goTo(1);
      if (e.key === "ArrowLeft") goTo(-1);
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [lightboxOpen, goTo]);

  return (
    <div>
      <div className="relative aspect-[16/10] w-full overflow-hidden rounded-xl bg-brand-bg-subtle">
        {safeImages[active]?.url ? (
          <button
            type="button"
            onClick={() => setLightboxOpen(true)}
            className="relative block h-full w-full"
            aria-label="Ampliar foto"
          >
            <Image
              src={safeImages[active].url}
              alt={`${title} — foto ${active + 1}`}
              fill
              priority={active === 0}
              sizes="(max-width: 1024px) 100vw, 800px"
              className="object-cover"
            />
          </button>
        ) : (
          <div className="flex h-full items-center justify-center text-brand-text-secondary">Sem fotos</div>
        )}

        {safeImages.length > 1 && (
          <>
            <button
              aria-label="Foto anterior"
              onClick={() => goTo(-1)}
              className="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow"
            >
              ‹
            </button>
            <button
              aria-label="Próxima foto"
              onClick={() => goTo(1)}
              className="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow"
            >
              ›
            </button>
            <span className="absolute bottom-3 right-3 rounded-full bg-black/60 px-2 py-1 text-xs text-white">
              {active + 1} / {safeImages.length}
            </span>
          </>
        )}
      </div>

      {safeImages.length > 1 && (
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
          {safeImages.map((img, idx) => (
            <button
              key={img.id}
              onClick={() => setActive(idx)}
              aria-label={`Ver foto ${idx + 1}`}
              aria-current={idx === active}
              className={`relative h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 ${
                idx === active ? "border-brand-primary" : "border-transparent"
              }`}
            >
              <Image src={img.url} alt="" fill sizes="96px" loading="lazy" className="object-cover" />
            </button>
          ))}
        </div>
      )}

      {lightboxOpen && safeImages[active]?.url && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
          onClick={() => setLightboxOpen(false)}
        >
          <button
            aria-label="Fechar"
            className="absolute right-4 top-4 text-2xl text-white"
            onClick={() => setLightboxOpen(false)}
          >
            ✕
          </button>
          <div className="relative h-full max-h-[85vh] w-full max-w-5xl" onClick={(e) => e.stopPropagation()}>
            <Image
              src={safeImages[active].url}
              alt={`${title} — foto ${active + 1}`}
              fill
              sizes="100vw"
              className="object-contain"
            />
          </div>
          {safeImages.length > 1 && (
            <>
              <button
                aria-label="Foto anterior"
                onClick={(e) => {
                  e.stopPropagation();
                  goTo(-1);
                }}
                className="absolute left-4 top-1/2 -translate-y-1/2 text-3xl text-white"
              >
                ‹
              </button>
              <button
                aria-label="Próxima foto"
                onClick={(e) => {
                  e.stopPropagation();
                  goTo(1);
                }}
                className="absolute right-4 top-1/2 -translate-y-1/2 text-3xl text-white"
              >
                ›
              </button>
            </>
          )}
        </div>
      )}
    </div>
  );
}
