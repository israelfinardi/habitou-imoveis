"use client";

import Link from "next/link";
import { useState, useRef, useEffect } from "react";
import type { SafeUser } from "@/lib/auth/session";
import { logoutAction } from "@/app/(auth)/actions";

export function UserMenu({ user }: { user: SafeUser | null }) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function onClick(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    document.addEventListener("click", onClick);
    return () => document.removeEventListener("click", onClick);
  }, []);

  if (!user) {
    return (
      <div className="flex items-center gap-2">
        <Link
          href="/login"
          className="rounded-full border border-brand-border px-4 py-2 text-sm font-medium text-brand-text hover:border-brand-primary hover:text-brand-primary"
        >
          Entrar
        </Link>
        <Link
          href="/cadastro"
          className="rounded-full px-4 py-2 text-sm font-medium text-brand-text hover:text-brand-primary"
        >
          Cadastre-se
        </Link>
      </div>
    );
  }

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((v) => !v)}
        className="flex items-center gap-2 rounded-full border border-brand-border px-3 py-1.5 text-sm font-medium text-brand-text hover:border-brand-primary"
      >
        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white">
          {user.firstName.charAt(0).toUpperCase()}
        </span>
        {user.firstName}
      </button>
      {open && (
        <div className="absolute right-0 top-full z-30 mt-2 w-56 rounded-lg border border-brand-border bg-white p-2 shadow-lg">
          <Link href="/minha-conta" className="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">
            Minha conta
          </Link>
          <Link href="/minha-conta/anuncios" className="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">
            Meus anúncios
          </Link>
          <Link href="/minha-conta/favoritos" className="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">
            Favoritos
          </Link>
          {(user.role === "AGENCY_ADMIN" || user.role === "AGENT") && (
            <Link href="/imobiliaria" className="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">
              Painel da imobiliária
            </Link>
          )}
          {user.role === "ADMIN" && (
            <Link href="/admin" className="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">
              Administração
            </Link>
          )}
          <form action={logoutAction}>
            <button
              type="submit"
              className="mt-1 block w-full rounded-md border-t border-brand-border px-3 py-2 text-left text-sm text-brand-text-secondary hover:bg-brand-bg-subtle"
            >
              Sair
            </button>
          </form>
        </div>
      )}
    </div>
  );
}
