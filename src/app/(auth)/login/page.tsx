import type { Metadata } from "next";
import { LoginForm } from "./LoginForm";

export const metadata: Metadata = { title: "Entrar" };

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ redefinida?: string }>;
}) {
  const params = await searchParams;
  return (
    <>
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Entrar</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">
        Acesse sua conta para gerenciar anúncios e favoritos.
      </p>
      <LoginForm redeemed={params.redefinida === "1"} />
    </>
  );
}
