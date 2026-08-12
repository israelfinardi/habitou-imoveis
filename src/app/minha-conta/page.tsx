import type { Metadata } from "next";
import Link from "next/link";
import { requireUser } from "@/lib/auth/guards";
import { prisma } from "@/lib/db";

export const metadata: Metadata = { title: "Minha conta" };

export default async function MinhaContaPage() {
  const user = await requireUser();

  const [propertyCount, favoriteCount, subscription] = await Promise.all([
    prisma.property.count({ where: { advertiserId: user.id } }),
    prisma.favorite.count({ where: { userId: user.id } }),
    prisma.subscription.findFirst({
      where: { userId: user.id, status: "ACTIVE" },
      include: { plan: true },
    }),
  ]);

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Olá, {user.firstName}!</h1>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Link href="/anunciante/imoveis" className="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
          <p className="text-2xl font-bold text-brand-text">{propertyCount}</p>
          <p className="text-sm text-brand-text-secondary">Meus anúncios</p>
        </Link>
        <Link href="/minha-conta/favoritos" className="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
          <p className="text-2xl font-bold text-brand-text">{favoriteCount}</p>
          <p className="text-sm text-brand-text-secondary">Imóveis favoritos</p>
        </Link>
        <div className="rounded-xl border border-brand-border bg-white p-5">
          <p className="text-2xl font-bold text-brand-text">{subscription?.plan.name ?? "Nenhum"}</p>
          <p className="text-sm text-brand-text-secondary">Plano atual</p>
          {!subscription && (
            <Link href="/como-anunciar" className="mt-1 inline-block text-xs font-medium text-brand-primary hover:underline">
              Ver planos
            </Link>
          )}
        </div>
      </div>

      <div className="mt-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 className="mb-3 text-sm font-semibold text-brand-text">Dados da conta</h2>
        <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-brand-text-secondary">Nome</dt>
            <dd className="text-brand-text">{user.firstName} {user.lastName}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">E-mail</dt>
            <dd className="text-brand-text">{user.email}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Telefone</dt>
            <dd className="text-brand-text">{user.phone || "—"}</dd>
          </div>
          <div>
            <dt className="text-brand-text-secondary">Tipo de conta</dt>
            <dd className="text-brand-text">{user.role}</dd>
          </div>
        </dl>
        <Link href="/minha-conta/dados" className="mt-4 inline-block text-sm font-medium text-brand-primary hover:underline">
          Editar dados
        </Link>
      </div>
    </div>
  );
}
