import type { Metadata } from "next";
import { requireUser } from "@/lib/auth/guards";
import { DadosForm } from "./DadosForm";

export const metadata: Metadata = { title: "Meus dados" };

export default async function DadosPage() {
  const user = await requireUser();
  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold text-brand-text">Meus dados</h1>
      <DadosForm user={user} />
    </div>
  );
}
