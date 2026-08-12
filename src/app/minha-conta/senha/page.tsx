import type { Metadata } from "next";
import { SenhaForm } from "./SenhaForm";

export const metadata: Metadata = { title: "Alterar senha" };

export default function SenhaPage() {
  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Alterar senha</h1>
      <SenhaForm />
    </div>
  );
}
