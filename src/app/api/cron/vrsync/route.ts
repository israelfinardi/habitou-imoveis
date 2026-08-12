import { NextResponse } from "next/server";
import { prisma } from "@/lib/db";
import { runFeedSync } from "@/server/vrsync/sync-service";

export const dynamic = "force-dynamic";
export const maxDuration = 300;

/**
 * Executa a sincronização automática dos feeds VRSync cujo próximo horário
 * já chegou. Pensado para ser chamado por um agendador externo (ex.: Vercel
 * Cron, cron do servidor, GitHub Actions) apontando para esta rota.
 * Protegido por CRON_SECRET para não ser acionado publicamente.
 */
export async function GET(request: Request) {
  const secret = process.env.CRON_SECRET;
  if (secret) {
    const auth = request.headers.get("authorization");
    if (auth !== `Bearer ${secret}`) {
      return NextResponse.json({ error: "unauthorized" }, { status: 401 });
    }
  }

  const dueFeeds = await prisma.feed.findMany({
    where: {
      status: "ACTIVE",
      OR: [{ nextSyncAt: null }, { nextSyncAt: { lte: new Date() } }],
    },
    select: { id: true, name: true },
  });

  const results = [];
  for (const feed of dueFeeds) {
    try {
      const log = await runFeedSync(feed.id);
      results.push({ feedId: feed.id, name: feed.name, status: log.status });
    } catch (err) {
      results.push({ feedId: feed.id, name: feed.name, status: "ERROR", error: (err as Error).message });
    }
  }

  return NextResponse.json({ processed: results.length, results });
}
