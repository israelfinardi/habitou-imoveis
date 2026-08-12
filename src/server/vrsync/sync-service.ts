import "server-only";
import { prisma } from "@/lib/db";
import { toSlug, buildPropertySlug } from "@/lib/slug";
import { parseVRSyncXml } from "./parser";
import type { NormalizedListing } from "./types";

const FETCH_TIMEOUT_MS = 20_000;
const MAX_FEED_SIZE_BYTES = 50 * 1024 * 1024; // 50MB

export class VRSyncFetchError extends Error {}

async function fetchFeedXml(url: string): Promise<string> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

  try {
    const res = await fetch(url, {
      signal: controller.signal,
      headers: { Accept: "application/xml,text/xml" },
    });
    if (!res.ok) {
      throw new VRSyncFetchError(`Feed respondeu com status ${res.status}.`);
    }
    const contentLength = Number(res.headers.get("content-length") ?? 0);
    if (contentLength && contentLength > MAX_FEED_SIZE_BYTES) {
      throw new VRSyncFetchError("Feed excede o tamanho máximo permitido (50MB).");
    }
    return await res.text();
  } catch (err) {
    if (err instanceof VRSyncFetchError) throw err;
    if (err instanceof Error && err.name === "AbortError") {
      throw new VRSyncFetchError("Tempo limite excedido ao baixar o feed.");
    }
    throw new VRSyncFetchError(`Falha ao acessar o feed: ${(err as Error).message}`);
  } finally {
    clearTimeout(timeout);
  }
}

async function resolveCityAndNeighborhood(listing: NormalizedListing) {
  const citySlug = toSlug(listing.address!.city);
  const city = await prisma.city.upsert({
    where: { slug: citySlug },
    update: {},
    create: {
      name: listing.address!.city,
      slug: citySlug,
      state: listing.address!.state,
      stateCode: listing.address!.state.length === 2 ? listing.address!.state.toUpperCase() : listing.address!.state.slice(0, 2).toUpperCase(),
    },
  });

  let neighborhood = null;
  if (listing.address!.neighborhood) {
    const slug = toSlug(listing.address!.neighborhood) || "sem-bairro";
    neighborhood = await prisma.neighborhood.upsert({
      where: { cityId_slug: { cityId: city.id, slug } },
      update: {},
      create: { cityId: city.id, name: listing.address!.neighborhood, slug },
    });
  }

  return { city, neighborhood };
}

async function nextCode(): Promise<string> {
  const last = await prisma.property.findFirst({ orderBy: { createdAt: "desc" }, select: { code: true } });
  const lastNum = last?.code?.match(/(\d+)$/)?.[1];
  return `HB-${lastNum ? Number(lastNum) + 1 : 1000}`;
}

export async function runFeedSync(feedId: string) {
  const feed = await prisma.feed.findUniqueOrThrow({ where: { id: feedId } });

  const log = await prisma.feedSyncLog.create({
    data: { feedId, status: "RUNNING" },
  });

  let totalFound = 0;
  let totalCreated = 0;
  let totalUpdated = 0;
  let totalUnchanged = 0;
  let totalDeactivated = 0;
  let totalErrors = 0;
  let errorMessage: string | undefined;

  try {
    const xml = await fetchFeedXml(feed.url);
    const { listings, issues } = parseVRSyncXml(xml);
    totalFound = listings.length;
    totalErrors = issues.length;

    const seenExternalIds = new Set<string>();

    for (const listing of listings) {
      seenExternalIds.add(listing.externalId);
      try {
        const { city, neighborhood } = await resolveCityAndNeighborhood(listing);

        const existing = await prisma.property.findFirst({
          where: { origin: "VRSYNC", sourceFeedId: feedId, externalCode: listing.externalId },
        });

        const data = {
          title: listing.title,
          description: listing.description,
          listingType: listing.listingType,
          propertyType: listing.propertyType,
          priceSale: listing.priceSale,
          priceRent: listing.priceRent,
          condoFee: listing.condoFee,
          iptu: listing.iptu,
          totalArea: listing.totalArea,
          builtArea: listing.builtArea,
          bedrooms: listing.bedrooms,
          suites: listing.suites,
          bathrooms: listing.bathrooms,
          parkingSpaces: listing.parkingSpaces,
          features: listing.features,
          status: listing.active ? ("PUBLISHED" as const) : ("PAUSED" as const),
          publishedAt: listing.active ? new Date() : undefined,
          cityId: city.id,
          neighborhoodId: neighborhood?.id,
          street: listing.address?.street,
          number: listing.address?.number,
          zipCode: listing.address?.zipCode,
          latitude: listing.address?.latitude,
          longitude: listing.address?.longitude,
          agencyId: feed.agencyId,
        };

        if (existing) {
          await prisma.property.update({ where: { id: existing.id }, data });

          const existingUrls = new Set((await prisma.propertyImage.findMany({ where: { propertyId: existing.id }, select: { url: true } })).map((i) => i.url));
          const newPhotos = listing.photos.filter((url) => !existingUrls.has(url));
          if (newPhotos.length > 0) {
            const maxOrder = await prisma.propertyImage.count({ where: { propertyId: existing.id } });
            await prisma.propertyImage.createMany({
              data: newPhotos.map((url, idx) => ({
                propertyId: existing.id,
                url,
                order: maxOrder + idx,
                isPrimary: maxOrder === 0 && idx === 0,
                origin: "VRSYNC" as const,
              })),
            });
          }
          totalUpdated += 1;
        } else {
          const advertiser = await ensureFeedAdvertiser(feed.agencyId);
          const suffix = listing.externalId;
          const slug = buildPropertySlug({
            title: listing.title,
            city: city.name,
            neighborhood: neighborhood?.name,
            suffix,
          });

          const created = await prisma.property.create({
            data: {
              ...data,
              code: await nextCode(),
              externalCode: listing.externalId,
              origin: "VRSYNC",
              sourceFeedId: feedId,
              slug,
              advertiserId: advertiser.id,
              images: {
                create: listing.photos.map((url, idx) => ({
                  url,
                  order: idx,
                  isPrimary: idx === 0,
                  origin: "VRSYNC" as const,
                })),
              },
            },
          });
          void created;
          totalCreated += 1;
        }
      } catch (err) {
        totalErrors += 1;
        console.error(`[vrsync] erro ao processar imóvel ${listing.externalId}:`, err);
      }
    }

    // Desativa imóveis deste feed que não vieram nesta sincronização.
    const staleProperties = await prisma.property.findMany({
      where: {
        origin: "VRSYNC",
        sourceFeedId: feedId,
        status: { not: "ARCHIVED" },
        externalCode: { notIn: [...seenExternalIds] },
      },
      select: { id: true },
    });
    if (staleProperties.length > 0) {
      await prisma.property.updateMany({
        where: { id: { in: staleProperties.map((p) => p.id) } },
        data: { status: "ARCHIVED", deactivatedAt: new Date() },
      });
      totalDeactivated = staleProperties.length;
    }

    totalUnchanged = totalFound - totalCreated - totalUpdated;

    await prisma.feedSyncLog.update({
      where: { id: log.id },
      data: {
        finishedAt: new Date(),
        status: totalErrors > 0 && totalCreated === 0 && totalUpdated === 0 ? "ERROR" : "SUCCESS",
        totalFound,
        totalCreated,
        totalUpdated,
        totalUnchanged: Math.max(0, totalUnchanged),
        totalDeactivated,
        totalErrors,
        errorMessage: issues.slice(0, 5).map((i) => i.message).join(" | ") || undefined,
      },
    });

    await prisma.feed.update({
      where: { id: feedId },
      data: {
        lastSyncAt: new Date(),
        nextSyncAt: new Date(Date.now() + feed.frequencyMinutes * 60_000),
        lastRunStatus: totalErrors > 0 && totalCreated === 0 && totalUpdated === 0 ? "ERROR" : "SUCCESS",
      },
    });
  } catch (err) {
    errorMessage = err instanceof Error ? err.message : "Erro desconhecido.";
    await prisma.feedSyncLog.update({
      where: { id: log.id },
      data: { finishedAt: new Date(), status: "ERROR", errorMessage, totalErrors: totalErrors + 1 },
    });
    await prisma.feed.update({
      where: { id: feedId },
      data: { lastSyncAt: new Date(), lastRunStatus: "ERROR" },
    });
  }

  return prisma.feedSyncLog.findUniqueOrThrow({ where: { id: log.id } });
}

async function ensureFeedAdvertiser(agencyId: string) {
  const agency = await prisma.agency.findUniqueOrThrow({ where: { id: agencyId } });
  const existing = await prisma.user.findFirst({ where: { agencyId, role: "AGENCY_ADMIN" } });
  if (existing) return existing;

  const { hashPassword } = await import("@/lib/auth/password");
  const email = `vrsync+${agency.slug}@habitou.com.br`;
  return prisma.user.upsert({
    where: { email },
    update: {},
    create: {
      firstName: agency.name.split(" ")[0] || "Imobiliária",
      lastName: "VRSync",
      email,
      passwordHash: await hashPassword(crypto.randomUUID()),
      role: "AGENCY_ADMIN",
      status: "ACTIVE",
      agencyId,
    },
  });
}
