import type { Metadata } from "next";
import { listActivePlans, getCurrentSubscription } from "@/server/services/subscription-service";
import { getCurrentUser } from "@/lib/auth/session";
import { formatCurrencyBRL } from "@/lib/format";
import { SubscribeButton } from "./SubscribeButton";

export const metadata: Metadata = {
  title: "Planos",
  description: "Conheça os planos do Habitou Imóveis para anunciantes e imobiliárias.",
};

export default async function PlanosPage() {
  const [plans, user] = await Promise.all([listActivePlans(), getCurrentUser()]);
  const currentSubscription = user ? await getCurrentSubscription(user) : null;

  return (
    <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 className="mb-2 text-center text-3xl font-bold text-brand-text">Planos</h1>
      <p className="mb-10 text-center text-brand-text-secondary">Escolha o plano ideal para anunciar seus imóveis.</p>

      <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
        {plans.map((plan) => {
          const isCurrent = currentSubscription?.planId === plan.id;
          const features = Array.isArray(plan.features) ? (plan.features as string[]) : [];
          return (
            <div key={plan.id} className="flex flex-col rounded-2xl border border-brand-border bg-white p-6">
              <h2 className="text-lg font-bold text-brand-text">{plan.name}</h2>
              <p className="mt-1 text-sm text-brand-text-secondary">{plan.description}</p>
              <p className="mt-4 text-3xl font-bold text-brand-primary">
                {formatCurrencyBRL(Number(plan.price))}
                <span className="text-sm font-normal text-brand-text-secondary">/mês</span>
              </p>
              <ul className="my-6 flex-1 space-y-2 text-sm text-brand-text">
                {features.map((f) => (
                  <li key={f} className="flex items-start gap-2">
                    <span className="text-brand-green">✓</span> {f}
                  </li>
                ))}
              </ul>
              <SubscribeButton
                planId={plan.id}
                authenticated={!!user}
                isCurrent={isCurrent}
                currentSubscriptionId={isCurrent ? currentSubscription?.id : undefined}
              />
              {isCurrent && (
                <p className="mt-2 text-center text-xs text-brand-text-secondary">
                  Status: {currentSubscription?.status === "ACTIVE" ? "ativo" : "aguardando confirmação de pagamento"}
                </p>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
