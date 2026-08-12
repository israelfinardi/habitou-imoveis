import Link from "next/link";

export function Pagination({
  page,
  totalPages,
  buildHref,
}: {
  page: number;
  totalPages: number;
  buildHref: (page: number) => string;
}) {
  if (totalPages <= 1) return null;

  const pages = Array.from({ length: totalPages }, (_, i) => i + 1).filter(
    (p) => p === 1 || p === totalPages || Math.abs(p - page) <= 2
  );

  return (
    <nav className="mt-8 flex items-center justify-center gap-1" aria-label="Paginação">
      <Link
        href={buildHref(Math.max(1, page - 1))}
        aria-disabled={page === 1}
        className={`rounded-md border border-brand-border px-3 py-1.5 text-sm ${page === 1 ? "pointer-events-none opacity-40" : "hover:border-brand-primary"}`}
      >
        Anterior
      </Link>
      {pages.map((p, idx) => (
        <span key={p} className="flex items-center">
          {idx > 0 && pages[idx - 1] !== p - 1 && <span className="px-1 text-brand-text-secondary">…</span>}
          <Link
            href={buildHref(p)}
            aria-current={p === page ? "page" : undefined}
            className={`rounded-md border px-3 py-1.5 text-sm ${
              p === page
                ? "border-brand-primary bg-brand-primary text-white"
                : "border-brand-border hover:border-brand-primary"
            }`}
          >
            {p}
          </Link>
        </span>
      ))}
      <Link
        href={buildHref(Math.min(totalPages, page + 1))}
        aria-disabled={page === totalPages}
        className={`rounded-md border border-brand-border px-3 py-1.5 text-sm ${page === totalPages ? "pointer-events-none opacity-40" : "hover:border-brand-primary"}`}
      >
        Próxima
      </Link>
    </nav>
  );
}
