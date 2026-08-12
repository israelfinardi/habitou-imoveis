"use client";

import Link from "next/link";
import { useState } from "react";
import type { SafeUser } from "@/lib/auth/session";
import { FEATURED_CITIES } from "@/lib/constants/cities";
import { logoutAction } from "@/app/(auth)/actions";

export function MobileMenu({ user }: { user: SafeUser | null }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="lg:hidden">
      <button
        aria-label="Abrir menu"
        onClick={() => setOpen(true)}
        className="flex h-9 w-9 items-center justify-center rounded-md border border-brand-border"
      >
        <svg width="18" height="14" viewBox="0 0 18 14" fill="none" aria-hidden="true">
          <path d="M0 1h18M0 7h18M0 13h18" stroke="currentColor" strokeWidth="1.5" />
        </svg>
      </button>

      {open && (
        <div className="fixed inset-0 z-50 flex">
          <div className="fixed inset-0 bg-black/40" onClick={() => setOpen(false)} />
          <div className="relative ml-auto flex h-full w-80 max-w-[85vw] flex-col overflow-y-auto bg-white p-5">
            <div className="mb-4 flex items-center justify-between">
              <span className="text-lg font-bold text-brand-primary">Habitou Imóveis</span>
              <button onClick={() => setOpen(false)} aria-label="Fechar menu" className="p-2">
                ✕
              </button>
            </div>

            {user ? (
              <div className="mb-4 rounded-lg bg-brand-bg-subtle p-3 text-sm">
                <p className="font-semibold">{user.firstName} {user.lastName}</p>
                <p className="text-brand-text-secondary">{user.email}</p>
              </div>
            ) : (
              <div className="mb-4 flex gap-2">
                <Link href="/login" onClick={() => setOpen(false)} className="flex-1 rounded-full border border-brand-border py-2 text-center text-sm font-medium">
                  Entrar
                </Link>
                <Link href="/cadastro" onClick={() => setOpen(false)} className="flex-1 rounded-full bg-brand-primary py-2 text-center text-sm font-medium text-white">
                  Cadastre-se
                </Link>
              </div>
            )}

            <p className="mb-2 mt-2 text-xs font-semibold uppercase text-brand-text-secondary">Comprar</p>
            {FEATURED_CITIES.map((c) => (
              <Link key={`c-${c.slug}`} href={`/${c.slug}/comprar`} onClick={() => setOpen(false)} className="py-1.5 text-sm">
                {c.name}
              </Link>
            ))}

            <p className="mb-2 mt-3 text-xs font-semibold uppercase text-brand-text-secondary">Alugar</p>
            {FEATURED_CITIES.map((c) => (
              <Link key={`a-${c.slug}`} href={`/${c.slug}/alugar`} onClick={() => setOpen(false)} className="py-1.5 text-sm">
                {c.name}
              </Link>
            ))}

            <div className="mt-4 flex flex-col gap-1 border-t border-brand-border pt-4">
              <Link href="/imobiliarias" onClick={() => setOpen(false)} className="py-1.5 text-sm">Imobiliárias e corretores</Link>
              <Link href="/como-anunciar" onClick={() => setOpen(false)} className="py-1.5 text-sm">Como anunciar</Link>
              <Link href="/quem-somos" onClick={() => setOpen(false)} className="py-1.5 text-sm">Sobre nós</Link>
              <Link href="/anunciante/imoveis/novo" onClick={() => setOpen(false)} className="mt-2 rounded-full bg-brand-primary px-4 py-2 text-center text-sm font-semibold text-white">
                Anunciar imóvel
              </Link>
              {user && (
                <>
                  <Link href="/minha-conta" onClick={() => setOpen(false)} className="mt-2 py-1.5 text-sm">Minha conta</Link>
                  <Link href="/minha-conta/favoritos" onClick={() => setOpen(false)} className="py-1.5 text-sm">Favoritos</Link>
                  <form action={logoutAction}>
                    <button type="submit" className="py-1.5 text-left text-sm text-brand-text-secondary">Sair</button>
                  </form>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
