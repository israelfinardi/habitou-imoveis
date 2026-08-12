import "server-only";
import { XMLParser, XMLValidator } from "fast-xml-parser";
import type { NormalizedListing, ParseResult, ParseIssue } from "./types";

/**
 * Parser para feeds no formato VRSync (XML). Como este ambiente não tem
 * acesso à especificação oficial de um feed específico, o parser segue a
 * convenção mais comum de feeds imobiliários brasileiros (nós em
 * português: Imovel, Titulo, Precos, Caracteristicas, Endereco, Fotos).
 * Quando a imobiliária fornecer a especificação exata do seu VRSync, ajuste
 * apenas este arquivo — o restante do sistema consome somente
 * `NormalizedListing`, então nenhuma outra camada precisa mudar.
 */

const TYPE_MAP: Record<string, NormalizedListing["propertyType"]> = {
  apartamento: "APARTMENT",
  casa: "HOUSE",
  terreno: "LAND",
  "sala/escritório": "COMMERCIAL_ROOM",
  "sala comercial": "COMMERCIAL_ROOM",
  escritorio: "COMMERCIAL_ROOM",
  loja: "STORE",
  galpao: "WAREHOUSE",
  galpão: "WAREHOUSE",
  "imóvel rural": "RURAL",
  "imovel rural": "RURAL",
  sitio: "RURAL",
  sítio: "RURAL",
  chacara: "RURAL",
  chácara: "RURAL",
  fazenda: "RURAL",
  predio: "BUILDING",
  prédio: "BUILDING",
  cobertura: "APARTMENT",
};

function mapPropertyType(raw: string | undefined): NormalizedListing["propertyType"] {
  if (!raw) return "OTHER";
  return TYPE_MAP[raw.trim().toLowerCase()] ?? "OTHER";
}

function mapListingType(raw: string | undefined): NormalizedListing["listingType"] {
  const v = (raw ?? "").trim().toLowerCase();
  if (v.includes("aluguel") || v.includes("locação") || v.includes("locacao") || v === "rent") return "RENT";
  return "SALE";
}

function toNumber(value: unknown): number | undefined {
  if (value === undefined || value === null || value === "") return undefined;
  const n = Number(String(value).replace(/\./g, "").replace(",", "."));
  return Number.isFinite(n) ? n : undefined;
}

function toArray<T>(value: T | T[] | undefined): T[] {
  if (value === undefined) return [];
  return Array.isArray(value) ? value : [value];
}

function text(value: unknown): string | undefined {
  if (value === undefined || value === null) return undefined;
  const s = String(value).trim();
  return s === "" ? undefined : s;
}

export function parseVRSyncXml(xml: string): ParseResult {
  const issues: ParseIssue[] = [];

  const validation = XMLValidator.validate(xml);
  if (validation !== true) {
    return { listings: [], issues: [{ message: `XML inválido: ${validation.err.msg}` }] };
  }

  const parser = new XMLParser({
    ignoreAttributes: true,
    trimValues: true,
    parseTagValue: false,
  });

  let doc: Record<string, unknown>;
  try {
    doc = parser.parse(xml);
  } catch (err) {
    return { listings: [], issues: [{ message: `Falha ao interpretar XML: ${(err as Error).message}` }] };
  }

  const root = (doc.Carga ?? doc.carga ?? doc) as Record<string, unknown>;
  const imoveisNode = (root?.Imoveis ?? root?.imoveis ?? root?.Listings) as Record<string, unknown> | undefined;
  const rawItems = toArray(
    (imoveisNode?.Imovel ?? imoveisNode?.imovel ?? imoveisNode?.Listing ?? (Array.isArray(imoveisNode) ? imoveisNode : undefined)) as
      | Record<string, unknown>
      | Record<string, unknown>[]
      | undefined
  );

  if (rawItems.length === 0) {
    issues.push({ message: "Nenhum imóvel encontrado no feed (estrutura Carga/Imoveis/Imovel esperada)." });
  }

  const listings: NormalizedListing[] = [];

  for (const item of rawItems) {
    const externalId = text(item.CodigoImovel ?? item.Codigo ?? item.Id ?? item.codigo);
    if (!externalId) {
      issues.push({ message: "Imóvel ignorado: sem código externo (CodigoImovel)." });
      continue;
    }

    const title = text(item.Titulo ?? item.titulo ?? item.Title);
    if (!title) {
      issues.push({ externalId, message: "Imóvel ignorado: sem título." });
      continue;
    }

    const precos = (item.Precos ?? item.precos ?? {}) as Record<string, unknown>;
    const caracteristicas = (item.Caracteristicas ?? item.caracteristicas ?? {}) as Record<string, unknown>;
    const endereco = (item.Endereco ?? item.endereco ?? {}) as Record<string, unknown>;
    const fotosNode = (item.Fotos ?? item.fotos ?? {}) as Record<string, unknown>;

    const cidade = text(endereco.Cidade ?? endereco.cidade);
    const estado = text(endereco.Estado ?? endereco.estado ?? endereco.UF);
    if (!cidade || !estado) {
      issues.push({ externalId, message: "Imóvel ignorado: endereço sem cidade/estado." });
      continue;
    }

    const statusRaw = text(item.Status ?? item.status)?.toLowerCase();
    const active = statusRaw ? !["inativo", "removido", "inactive", "removed"].includes(statusRaw) : true;

    const photos = toArray(fotosNode.Foto ?? fotosNode.foto ?? fotosNode.Photo)
      .map((p) => text(p))
      .filter((p): p is string => !!p && /^https?:\/\//.test(p));

    const features = toArray(caracteristicas.Comodidade ?? caracteristicas.comodidade ?? caracteristicas.Feature)
      .map((f) => text(f))
      .filter((f): f is string => !!f);

    listings.push({
      externalId,
      externalCode: text(item.CodigoReferencia ?? item.Referencia) ?? externalId,
      title,
      description: text(item.Descricao ?? item.descricao),
      listingType: mapListingType(text(item.Finalidade ?? item.finalidade)),
      propertyType: mapPropertyType(text(item.TipoImovel ?? item.tipoImovel ?? item.Tipo)),
      priceSale: toNumber(precos.PrecoVenda ?? precos.precoVenda),
      priceRent: toNumber(precos.PrecoLocacao ?? precos.precoLocacao ?? precos.PrecoAluguel),
      condoFee: toNumber(precos.PrecoCondominio ?? precos.precoCondominio),
      iptu: toNumber(precos.PrecoIptu ?? precos.precoIptu),
      totalArea: toNumber(caracteristicas.AreaTotal ?? caracteristicas.areaTotal),
      builtArea: toNumber(caracteristicas.AreaConstruida ?? caracteristicas.areaConstruida),
      bedrooms: toNumber(caracteristicas.Quartos ?? caracteristicas.quartos ?? caracteristicas.Dormitorios),
      suites: toNumber(caracteristicas.Suites ?? caracteristicas.suites),
      bathrooms: toNumber(caracteristicas.Banheiros ?? caracteristicas.banheiros),
      parkingSpaces: toNumber(caracteristicas.Vagas ?? caracteristicas.vagas),
      features,
      active,
      address: {
        street: text(endereco.Logradouro ?? endereco.logradouro ?? endereco.Rua),
        number: text(endereco.Numero ?? endereco.numero),
        neighborhood: text(endereco.Bairro ?? endereco.bairro),
        city: cidade,
        state: estado,
        zipCode: text(endereco.CEP ?? endereco.cep),
        latitude: toNumber(endereco.Latitude ?? endereco.latitude),
        longitude: toNumber(endereco.Longitude ?? endereco.longitude),
      },
      photos,
    });
  }

  return { listings, issues };
}
