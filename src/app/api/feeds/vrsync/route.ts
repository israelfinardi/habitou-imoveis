import { XMLBuilder } from "fast-xml-parser";
import { prisma } from "@/lib/db";
import { PROPERTY_TYPE_LABEL, LISTING_TYPE_LABEL } from "@/lib/constants/property";

export const dynamic = "force-dynamic";

/**
 * Exporta o catálogo (imóveis publicados) no mesmo formato XML aceito pelo
 * importador VRSync (ver src/server/vrsync/parser.ts), permitindo que outro
 * portal ou sistema consuma o catálogo do Habitou Imóveis.
 *
 * Parâmetros opcionais:
 *   ?imobiliaria=<slug>   restringe a uma imobiliária
 */
export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const agencySlug = searchParams.get("imobiliaria") ?? undefined;

  const properties = await prisma.property.findMany({
    where: {
      status: "PUBLISHED",
      ...(agencySlug ? { agency: { slug: agencySlug } } : {}),
    },
    include: {
      city: true,
      neighborhood: true,
      images: { orderBy: { order: "asc" } },
    },
    orderBy: { publishedAt: "desc" },
    take: 5000,
  });

  const payload = {
    Carga: {
      DataGeracao: new Date().toISOString(),
      Imoveis: {
        Imovel: properties.map((p) => ({
          CodigoImovel: p.externalCode ?? p.code,
          CodigoReferencia: p.code,
          Titulo: p.title,
          Descricao: p.description ?? "",
          TipoImovel: PROPERTY_TYPE_LABEL[p.propertyType],
          Finalidade: LISTING_TYPE_LABEL[p.listingType],
          Status: "Ativo",
          Precos: {
            PrecoVenda: p.priceSale?.toString() ?? "",
            PrecoLocacao: p.priceRent?.toString() ?? "",
            PrecoCondominio: p.condoFee?.toString() ?? "",
            PrecoIptu: p.iptu?.toString() ?? "",
          },
          Caracteristicas: {
            AreaTotal: p.totalArea?.toString() ?? "",
            AreaConstruida: p.builtArea?.toString() ?? "",
            Quartos: p.bedrooms?.toString() ?? "",
            Suites: p.suites?.toString() ?? "",
            Banheiros: p.bathrooms?.toString() ?? "",
            Vagas: p.parkingSpaces?.toString() ?? "",
            Comodidade: p.features,
          },
          Endereco: {
            Logradouro: p.street ?? "",
            Numero: p.number ?? "",
            Bairro: p.neighborhood?.name ?? "",
            Cidade: p.city.name,
            Estado: p.city.stateCode,
            CEP: p.zipCode ?? "",
            Latitude: p.latitude?.toString() ?? "",
            Longitude: p.longitude?.toString() ?? "",
          },
          Fotos: { Foto: p.images.map((img) => img.url) },
        })),
      },
    },
  };

  const builder = new XMLBuilder({ format: true, ignoreAttributes: true });
  const xml = `<?xml version="1.0" encoding="UTF-8"?>\n${builder.build(payload)}`;

  return new Response(xml, {
    headers: {
      "Content-Type": "application/xml; charset=utf-8",
      "Cache-Control": "public, max-age=1800",
    },
  });
}
