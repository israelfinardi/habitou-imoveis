import Link from "next/link";
import type { PropertyDetail } from "@/types/property";

export function ContactCard({ property }: { property: PropertyDetail }) {
  const contactName = property.agent
    ? `${property.agent.firstName} ${property.agent.lastName}`
    : property.agency
      ? property.agency.name
      : `${property.advertiser.firstName} ${property.advertiser.lastName}`;

  const phone = property.agent?.phone || property.agency?.phone || property.advertiser.phone;
  const whatsappNumber = phone?.replace(/\D/g, "");
  const message = encodeURIComponent(
    `Olá! Tenho interesse no imóvel "${property.title}" (código ${property.code}).`
  );

  return (
    <div className="rounded-xl border border-brand-border bg-white p-5">
      <p className="text-xs font-semibold uppercase text-brand-text-secondary">Anunciado por</p>
      <p className="mt-1 text-lg font-bold text-brand-text">{contactName}</p>
      {property.agent?.creci && (
        <p className="text-xs text-brand-text-secondary">CRECI {property.agent.creci}</p>
      )}
      {property.agency && (
        <Link href={`/imobiliarias/${property.agency.slug}`} className="mt-1 block text-sm text-brand-primary hover:underline">
          Ver página da imobiliária
        </Link>
      )}

      <div className="mt-4 flex flex-col gap-2">
        {whatsappNumber && (
          <a
            href={`https://wa.me/55${whatsappNumber}?text=${message}`}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-full bg-brand-green px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-green-hover"
          >
            Conversar no WhatsApp
          </a>
        )}
        <Link
          href={`/fale-conosco?imovel=${property.code}`}
          className="rounded-full border border-brand-border px-4 py-2.5 text-center text-sm font-semibold text-brand-text hover:border-brand-primary"
        >
          Enviar mensagem
        </Link>
      </div>

      <p className="mt-4 text-xs text-brand-text-secondary">Código do imóvel: {property.code}</p>
    </div>
  );
}
