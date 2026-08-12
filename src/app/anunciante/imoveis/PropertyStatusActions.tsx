"use client";

import { useTransition } from "react";
import Link from "next/link";
import {
  setPropertyStatusAction,
  deletePropertyAction,
  duplicatePropertyAction,
} from "@/app/anunciante/actions";
import type { PropertyStatus } from "@prisma/client";

export function PropertyStatusActions({ propertyId, status }: { propertyId: string; status: PropertyStatus }) {
  const [pending, startTransition] = useTransition();

  return (
    <div className="flex flex-wrap items-center gap-2 text-xs">
      <Link href={`/anunciante/imoveis/${propertyId}/editar`} className="text-brand-primary hover:underline">
        Editar
      </Link>
      {status !== "PUBLISHED" && (
        <button
          disabled={pending}
          onClick={() => startTransition(() => setPropertyStatusAction(propertyId, "publish"))}
          className="text-brand-green-hover hover:underline disabled:opacity-50"
        >
          Publicar
        </button>
      )}
      {status === "PUBLISHED" && (
        <button
          disabled={pending}
          onClick={() => startTransition(() => setPropertyStatusAction(propertyId, "pause"))}
          className="text-brand-text-secondary hover:underline disabled:opacity-50"
        >
          Pausar
        </button>
      )}
      {status !== "ARCHIVED" && (
        <button
          disabled={pending}
          onClick={() => startTransition(() => setPropertyStatusAction(propertyId, "archive"))}
          className="text-brand-text-secondary hover:underline disabled:opacity-50"
        >
          Arquivar
        </button>
      )}
      <button
        disabled={pending}
        onClick={() => startTransition(() => duplicatePropertyAction(propertyId))}
        className="text-brand-text-secondary hover:underline disabled:opacity-50"
      >
        Duplicar
      </button>
      <Link href={`/contratos/novo?imovelId=${propertyId}`} className="text-brand-text-secondary hover:underline">
        Criar contrato
      </Link>
      <button
        disabled={pending}
        onClick={() => {
          if (confirm("Excluir este imóvel permanentemente?")) {
            startTransition(() => deletePropertyAction(propertyId));
          }
        }}
        className="text-red-600 hover:underline disabled:opacity-50"
      >
        Excluir
      </button>
    </div>
  );
}
