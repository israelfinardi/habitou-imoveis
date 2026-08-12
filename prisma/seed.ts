/**
 * Seed de dados reais extraídos do site habitou.com.br (808 imóveis, 134
 * imobiliárias), usado para popular o ambiente de desenvolvimento com dados
 * realistas em vez de mocks.
 */
import path from "node:path";
import fs from "node:fs";
import { PrismaClient, type ListingType, type PropertyType } from "@prisma/client";
import { hashPassword } from "../src/lib/auth/password";
import { toSlug } from "../src/lib/slug";
import { FEATURED_CITIES } from "../src/lib/constants/cities";

const prisma = new PrismaClient();

type RawProperty = {
  externalId: string | null;
  code: string | null;
  title: string;
  price: number | null;
  type: string | null;
  transaction: string | null;
  address: string | null;
  neighborhood: string | null;
  city: string | null;
  state: string | null;
  publishedAt: string | null;
  description: string;
  agencyName: string | null;
  agencyUrl: string | null;
  sourceUrl: string | null;
  photos: string[];
  bedrooms: number | null;
  suites: number | null;
  parkingSpaces: number | null;
  area: number | null;
  sourcePath: string;
};

const TYPE_MAP: Record<string, PropertyType> = {
  "Apartamento": "APARTMENT",
  "Casa": "HOUSE",
  "Terreno": "LAND",
  "Sala/Escritório": "COMMERCIAL_ROOM",
  "Loja": "STORE",
  "Galpão": "WAREHOUSE",
  "Imóvel Rural": "RURAL",
  "Outros Imóveis": "OTHER",
};

const KNOWN_CITY_SLUGS = new Set(FEATURED_CITIES.map((c) => c.slug));
const VALID_CITIES = new Set([...FEATURED_CITIES.map((c) => c.name), "Campo Alegre"]);

function agencySlugFromUrl(url: string | null, name: string): string {
  if (url) {
    const m = url.match(/\/imobiliarias\/([^/]+)/);
    if (m) return m[1];
  }
  return toSlug(name);
}

async function main() {
  console.log("Lendo dados extraídos...");
  const propertiesRaw: RawProperty[] = JSON.parse(
    fs.readFileSync(path.join(__dirname, "seed-data/properties.json"), "utf-8")
  );

  const properties = propertiesRaw.filter(
    (p) => p.city && VALID_CITIES.has(p.city) && p.type && TYPE_MAP[p.type]
  );
  console.log(`${properties.length}/${propertiesRaw.length} imóveis válidos para importar.`);

  // ---- Planos ----------------------------------------------------------
  console.log("Criando planos...");
  const plans = await Promise.all(
    [
      { name: "Básico", slug: "basico", price: 0, maxListings: 3, billingPeriod: "MONTHLY" as const, description: "Ideal para anunciantes individuais.", features: ["Até 3 anúncios ativos", "Suporte por e-mail"] },
      { name: "Profissional", slug: "profissional", price: 99.9, maxListings: 30, billingPeriod: "MONTHLY" as const, description: "Para corretores autônomos.", features: ["Até 30 anúncios ativos", "Destaque nos resultados", "Suporte prioritário"] },
      { name: "Imobiliária", slug: "imobiliaria", price: 349.9, maxListings: null, billingPeriod: "MONTHLY" as const, description: "Para imobiliárias com sincronização VRSync.", features: ["Anúncios ilimitados", "Sincronização automática de feeds (VRSync)", "Múltiplos corretores", "Painel administrativo"] },
    ].map((p) =>
      prisma.plan.upsert({
        where: { slug: p.slug },
        update: {},
        create: {
          name: p.name,
          slug: p.slug,
          description: p.description,
          price: p.price,
          billingPeriod: p.billingPeriod,
          maxListings: p.maxListings,
          features: p.features,
        },
      })
    )
  );
  const agencyPlan = plans.find((p) => p.slug === "imobiliaria")!;

  // ---- Administrador -----------------------------------------------------
  console.log("Criando usuário administrador...");
  const adminPasswordHash = await hashPassword("Admin@12345");
  await prisma.user.upsert({
    where: { email: "admin@habitou.com.br" },
    update: {},
    create: {
      firstName: "Administrador",
      lastName: "Habitou",
      email: "admin@habitou.com.br",
      passwordHash: adminPasswordHash,
      role: "ADMIN",
      status: "ACTIVE",
    },
  });

  // ---- Cidades e bairros ---------------------------------------------
  console.log("Criando cidades e bairros...");
  const cityByName = new Map<string, { id: string }>();
  for (const city of FEATURED_CITIES) {
    const record = await prisma.city.upsert({
      where: { slug: city.slug },
      update: {},
      create: {
        name: city.name,
        slug: city.slug,
        state: city.state,
        stateCode: city.stateCode,
        region: "Santa Catarina",
        latitude: city.latitude,
        longitude: city.longitude,
      },
    });
    cityByName.set(city.name, record);
  }
  if (!cityByName.has("Campo Alegre")) {
    const record = await prisma.city.upsert({
      where: { slug: "campo-alegre" },
      update: {},
      create: {
        name: "Campo Alegre",
        slug: "campo-alegre",
        state: "Santa Catarina",
        stateCode: "SC",
        latitude: -26.3853,
        longitude: -49.2444,
      },
    });
    cityByName.set("Campo Alegre", record);
  }

  const neighborhoodCache = new Map<string, { id: string }>();
  async function getNeighborhood(cityId: string, name: string) {
    const key = `${cityId}:${name}`;
    if (neighborhoodCache.has(key)) return neighborhoodCache.get(key)!;
    const slug = toSlug(name) || "sem-bairro";
    const record = await prisma.neighborhood.upsert({
      where: { cityId_slug: { cityId, slug } },
      update: {},
      create: { cityId, name, slug },
    });
    neighborhoodCache.set(key, record);
    return record;
  }

  // ---- Imobiliárias e usuários-anunciantes -----------------------------
  console.log("Criando imobiliárias...");
  const agencyByName = new Map<string, { id: string; advertiserUserId: string }>();
  const distinctAgencies = new Map<string, string | null>();
  for (const p of properties) {
    if (p.agencyName && !distinctAgencies.has(p.agencyName)) {
      distinctAgencies.set(p.agencyName, p.agencyUrl);
    }
  }

  let agencyIndex = 0;
  for (const [name, url] of distinctAgencies) {
    agencyIndex += 1;
    const slug = agencySlugFromUrl(url, name);
    const agency = await prisma.agency.upsert({
      where: { slug },
      update: {},
      create: {
        name,
        slug,
        status: "ACTIVE",
        description: `${name} é parceira Habitou Imóveis, com anúncios verificados em Santa Catarina.`,
      },
    });

    const email = `contato+${slug}@habitou.com.br`.slice(0, 254);
    const passwordHash = await hashPassword("Imobiliaria@123");
    const advertiserUser = await prisma.user.upsert({
      where: { email },
      update: { agencyId: agency.id },
      create: {
        firstName: name.split(" ")[0] || "Imobiliária",
        lastName: "Parceira",
        email,
        passwordHash,
        role: "AGENCY_ADMIN",
        status: "ACTIVE",
        agencyId: agency.id,
      },
    });

    agencyByName.set(name, { id: agency.id, advertiserUserId: advertiserUser.id });
  }
  console.log(`${agencyByName.size} imobiliárias criadas.`);

  // Cria um feed VRSync de demonstração para a imobiliária com mais anúncios.
  const topAgencyEntry = [...distinctAgencies.keys()][0];
  let demoFeedId: string | null = null;
  if (topAgencyEntry) {
    const agencyInfo = agencyByName.get(topAgencyEntry);
    if (agencyInfo) {
      const feed = await prisma.feed.create({
        data: {
          agencyId: agencyInfo.id,
          name: "Feed principal (VRSync)",
          url: "https://exemplo-crm.com.br/feeds/vrsync.xml",
          status: "ACTIVE",
          frequencyMinutes: 1440,
          lastSyncAt: new Date(),
          lastRunStatus: "SUCCESS",
        },
      });
      demoFeedId = feed.id;
      await prisma.feedSyncLog.create({
        data: {
          feedId: feed.id,
          startedAt: new Date(Date.now() - 1000 * 60 * 5),
          finishedAt: new Date(),
          status: "SUCCESS",
          totalFound: 0,
          totalCreated: 0,
          totalUpdated: 0,
          totalUnchanged: 0,
          totalDeactivated: 0,
          totalErrors: 0,
        },
      });
    }
  }

  // ---- Imóveis -----------------------------------------------------------
  console.log("Criando imóveis (isso pode levar um tempo)...");
  let created = 0;
  let codeSeq = 1000;

  for (const p of properties) {
    const propertyType = TYPE_MAP[p.type!];
    const listingType: ListingType = p.transaction === "Locação" ? "RENT" : "SALE";
    const city = cityByName.get(p.city!)!;
    const neighborhood = p.neighborhood ? await getNeighborhood(city.id, p.neighborhood) : null;
    const agencyInfo = p.agencyName ? agencyByName.get(p.agencyName) : undefined;

    const slug = path.basename(p.sourcePath, ".md");
    const existing = await prisma.property.findUnique({ where: { slug } });
    if (existing) continue;

    codeSeq += 1;
    const isTopAgency = p.agencyName === topAgencyEntry;

    await prisma.property.create({
      data: {
        code: `HB-${codeSeq}`,
        externalCode: p.code ?? p.externalId ?? undefined,
        origin: isTopAgency ? "VRSYNC" : "MANUAL",
        sourceFeedId: isTopAgency ? demoFeedId : null,
        title: p.title,
        slug,
        description: p.description || null,
        listingType,
        propertyType,
        priceSale: listingType === "SALE" ? p.price ?? undefined : undefined,
        priceRent: listingType === "RENT" ? p.price ?? undefined : undefined,
        totalArea: p.area ?? undefined,
        bedrooms: p.bedrooms ?? undefined,
        suites: p.suites ?? undefined,
        parkingSpaces: p.parkingSpaces ?? undefined,
        status: "PUBLISHED",
        publishedAt: p.publishedAt ? new Date(p.publishedAt) : new Date(),
        cityId: city.id,
        neighborhoodId: neighborhood?.id,
        street: p.address ?? undefined,
        advertiserId: agencyInfo?.advertiserUserId ?? (await getFallbackAdvertiser()),
        agencyId: agencyInfo?.id,
        images: {
          create: p.photos.slice(0, 20).map((url, idx) => ({
            url,
            order: idx,
            isPrimary: idx === 0,
            origin: isTopAgency ? "VRSYNC" : "MANUAL",
          })),
        },
      },
    });
    created += 1;
    if (created % 100 === 0) console.log(`  ${created} imóveis criados...`);
  }

  console.log(`Concluído: ${created} imóveis criados.`);
}

let fallbackAdvertiserId: string | null = null;
async function getFallbackAdvertiser() {
  if (fallbackAdvertiserId) return fallbackAdvertiserId;
  const email = "anunciante-demo@habitou.com.br";
  const user = await prisma.user.upsert({
    where: { email },
    update: {},
    create: {
      firstName: "Anunciante",
      lastName: "Demo",
      email,
      passwordHash: await hashPassword("Anunciante@123"),
      role: "ADVERTISER",
      status: "ACTIVE",
    },
  });
  fallbackAdvertiserId = user.id;
  return user.id;
}

main()
  .catch((err) => {
    console.error(err);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
