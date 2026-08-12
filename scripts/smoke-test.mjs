/**
 * Teste de regressão ponta a ponta (fluxo real via navegador).
 * Requer o servidor rodando em http://localhost:3000 (`npm run build && npm start`
 * ou `npm run dev`) e o banco populado (`npm run db:seed`).
 *
 *   node scripts/smoke-test.mjs
 */
import { chromium } from "playwright";
import { existsSync, globSync } from "node:fs";

const BASE = process.env.SMOKE_BASE_URL || "http://localhost:3000";
const results = [];

function log(name, ok, detail) {
  results.push({ name, ok, detail });
  console.log(`${ok ? "OK  " : "FAIL"} ${name}${detail ? " — " + detail : ""}`);
}

function resolveChromiumExecutable() {
  const browsersPath = process.env.PLAYWRIGHT_BROWSERS_PATH;
  if (!browsersPath || !existsSync(browsersPath)) return undefined;
  const matches = globSync(`${browsersPath}/chromium-*/chrome-linux/chrome`);
  return matches[0];
}

const browser = await chromium.launch({ executablePath: resolveChromiumExecutable() });
const context = await browser.newContext();
const page = await context.newPage();

try {
  // 1. Homepage loads with real data
  await page.goto(`${BASE}/`, { waitUntil: "networkidle" });
  const heroVisible = await page.locator("text=Encontre o seu imóvel").isVisible();
  log("Homepage renderiza hero", heroVisible);

  // 2. Register a new user
  const email = `teste.smoke.${Date.now()}@example.com`;
  await page.goto(`${BASE}/cadastro`);
  await page.fill('input[name="firstName"]', "Teste");
  await page.fill('input[name="lastName"]', "Smoke");
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', "SenhaForte@123");
  await page.fill('input[name="passwordConfirmation"]', "SenhaForte@123");
  await page.click('button[type="submit"]');
  await page.waitForURL(/minha-conta/, { timeout: 10000 });
  log("Cadastro cria conta e redireciona para Minha Conta", page.url().includes("/minha-conta"));

  // 3. Logout
  await page.goto(`${BASE}/minha-conta`);
  const logoutBtn = page.locator('button:has-text("Sair")').first();
  if (await logoutBtn.count() > 0) {
    await logoutBtn.click();
    await page.waitForTimeout(1000);
  }

  // 4. Login as admin
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="email"]', "admin@habitou.com.br");
  await page.fill('input[name="password"]', "Admin@12345");
  await page.click('button[type="submit"]');
  await page.waitForURL(/minha-conta/, { timeout: 10000 });
  log("Login admin funciona", page.url().includes("/minha-conta"));

  // 5. Admin panel accessible
  await page.goto(`${BASE}/admin`, { waitUntil: "networkidle" });
  const adminVisible = await page.locator('h1:has-text("Visão geral")').isVisible();
  log("Painel admin acessível", adminVisible);

  await page.goto(`${BASE}/admin/usuarios`, { waitUntil: "networkidle" });
  const usersVisible = await page.locator("table").isVisible();
  log("Admin > usuários lista", usersVisible);

  // 6. Listing + filters
  await page.goto(`${BASE}/florianopolis/comprar/apartamento`, { waitUntil: "networkidle" });
  const listingVisible = await page.locator("text=imóve").first().isVisible();
  log("Listagem por cidade/tipo carrega", listingVisible);

  // 7. Property detail page + favorite toggle
  const firstCard = page.locator("article a").first();
  const href = await firstCard.getAttribute("href");
  if (href) {
    await page.goto(`${BASE}${href}`, { waitUntil: "networkidle" });
    const galleryVisible = await page.locator("img").first().isVisible();
    log("Página de imóvel carrega galeria", galleryVisible, href);

    const favBtn = page.locator('button[aria-label*="favoritos"]').first();
    if (await favBtn.count() > 0) {
      const before = await favBtn.getAttribute("aria-pressed");
      await favBtn.click();
      await page.waitForTimeout(800);
      const after = await favBtn.getAttribute("aria-pressed");
      log("Favoritar imóvel alterna estado", before !== after, `${before} -> ${after}`);
    }
  }

  // 8. Anunciante: create property
  await page.goto(`${BASE}/anunciante/imoveis/novo`, { waitUntil: "networkidle" });
  await page.fill('input[name="title"]', "Apartamento de teste smoke 2 quartos");
  await page.fill('textarea[name="description"]', "Descrição de teste gerada pelo smoke test automatizado.");
  await page.selectOption('select[name="cidade"]', "florianopolis");
  await page.fill('input[name="bairro"]', "Centro");
  await page.fill('input[name="totalArea"]', "60");
  await page.fill('input[name="bedrooms"]', "2");
  await page.click('button[type="submit"]');
  await page.waitForURL(/anunciante\/imoveis\/.+\/editar/, { timeout: 10000 });
  log("Anunciante cria imóvel", /editar/.test(page.url()), page.url());

  // 9. Publish it
  await page.goto(`${BASE}/anunciante/imoveis`, { waitUntil: "networkidle" });
  const publishBtn = page.locator('button:has-text("Publicar")').first();
  if (await publishBtn.count() > 0) {
    await publishBtn.click();
    await page.waitForTimeout(1000);
    log("Botão publicar imóvel disponível e clicável", true);
  } else {
    log("Botão publicar imóvel disponível e clicável", false, "não encontrado");
  }

  // 10. Contact form
  await page.goto(`${BASE}/fale-conosco`, { waitUntil: "networkidle" });
  await page.fill('input[name="name"]', "Smoke Test");
  await page.fill('input[name="email"]', email);
  await page.selectOption('select[name="subject"]', "Outro");
  await page.fill('textarea[name="message"]', "Mensagem de teste automatizado do smoke test.");
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1500);
  const successVisible = await page.locator("text=Mensagem enviada").isVisible();
  log("Formulário de contato envia mensagem", successVisible);

  // 11. Compare
  await page.goto(`${BASE}/comparar`, { waitUntil: "networkidle" });
  const compareVisible = await page.locator("text=Comparar imóveis").isVisible();
  log("Página de comparação carrega", compareVisible);

  // 12. Blog
  await page.goto(`${BASE}/blog`, { waitUntil: "networkidle" });
  const blogVisible = await page.locator("text=mercado imobiliário de Santa Catarina").isVisible();
  log("Blog carrega", blogVisible);

} catch (err) {
  log("ERRO INESPERADO", false, err.message);
} finally {
  await browser.close();
}

const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} passaram.`);
if (failed.length > 0) {
  console.log("Falhas:", failed.map((f) => f.name).join(", "));
  process.exit(1);
}
