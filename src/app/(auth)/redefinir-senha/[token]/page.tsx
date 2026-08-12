import type { Metadata } from "next";
import { RedefinirSenhaForm } from "./RedefinirSenhaForm";

export const metadata: Metadata = { title: "Redefinir senha" };

export default async function RedefinirSenhaPage({
  params,
}: {
  params: Promise<{ token: string }>;
}) {
  const { token } = await params;
  return (
    <>
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Redefinir senha</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">Escolha uma nova senha para sua conta.</p>
      <RedefinirSenhaForm token={token} />
    </>
  );
}
