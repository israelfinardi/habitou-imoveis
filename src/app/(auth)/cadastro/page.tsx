import type { Metadata } from "next";
import { CadastroForm } from "./CadastroForm";

export const metadata: Metadata = { title: "Criar conta" };

export default function CadastroPage() {
  return (
    <>
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Criar conta</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">
        Cadastre-se para favoritar imóveis e anunciar gratuitamente.
      </p>
      <CadastroForm />
    </>
  );
}
