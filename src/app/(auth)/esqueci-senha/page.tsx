import type { Metadata } from "next";
import { EsqueciSenhaForm } from "./EsqueciSenhaForm";

export const metadata: Metadata = { title: "Esqueci minha senha" };

export default function EsqueciSenhaPage() {
  return (
    <>
      <h1 className="mb-1 text-2xl font-bold text-brand-text">Esqueci minha senha</h1>
      <p className="mb-6 text-sm text-brand-text-secondary">
        Informe o e-mail da sua conta. Enviaremos um link para redefinir sua senha.
      </p>
      <EsqueciSenhaForm />
    </>
  );
}
