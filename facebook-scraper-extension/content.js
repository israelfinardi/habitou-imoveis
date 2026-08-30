// Habitou Facebook Data Extractor — content script
//
// Reads only what is already rendered in the DOM of the Facebook tab the
// user has open and is logged into. It does not log in, does not click
// through pages automatically, does not bypass any privacy setting, and
// only runs when the user presses "Extrair" in the popup. Think of it as
// a smarter "select all + copy" for the page you are already looking at.

(function () {
  const CURRENCY_RE = /R\$\s?[\d.,]+/;
  const REACTION_RE = /([\d.,]+\s?(mil|milh[oõ]es|k)?)\s*(curtidas?|reações?|reactions?|likes?)/i;
  const COMMENT_RE = /([\d.,]+\s?(mil|milh[oõ]es|k)?)\s*(coment[aá]rios?|comments?)/i;
  const SHARE_RE = /([\d.,]+\s?(mil|milh[oõ]es|k)?)\s*(compartilhamentos?|shares?)/i;

  function cleanText(el) {
    if (!el) return "";
    return el.innerText
      .replace(/ /g, " ")
      .replace(/[ \t]+\n/g, "\n")
      .replace(/\n{3,}/g, "\n\n")
      .trim();
  }

  function firstMatch(text, re) {
    const m = text && text.match(re);
    return m ? m[0].trim() : "";
  }

  function findPermalink(article) {
    const anchors = Array.from(article.querySelectorAll("a[href]"));
    const candidate = anchors.find((a) =>
      /\/posts\/|\/permalink\/|story_fbid=|\/videos\/|\/photo(\.php|\/)/.test(a.href)
    );
    return candidate ? candidate.href.split("?")[0] : "";
  }

  function findTimestamp(article) {
    const abbr = article.querySelector("abbr[title]");
    if (abbr) return abbr.getAttribute("title");
    const timeEl = article.querySelector("time[datetime]");
    if (timeEl) return timeEl.getAttribute("datetime");
    const link = Array.from(article.querySelectorAll("a[aria-label]")).find((a) =>
      /^\d|há\s|ago$|hour|hora|min|day|dia/i.test(a.getAttribute("aria-label") || "")
    );
    return link ? link.getAttribute("aria-label") : "";
  }

  function findAuthor(article) {
    const strong = article.querySelector('h2 span, h3 span, strong span, [role="link"] strong');
    if (strong && strong.innerText.trim()) return strong.innerText.trim();
    const link = article.querySelector('h2 a[role="link"], h3 a[role="link"]');
    return link ? link.innerText.trim() : "";
  }

  function findMainMessage(article) {
    const preview = article.querySelector('[data-ad-preview="message"]');
    if (preview) return cleanText(preview);
    const candidates = Array.from(article.querySelectorAll('div[dir="auto"]'))
      .map((el) => cleanText(el))
      .filter((t) => t.length > 20)
      .sort((a, b) => b.length - a.length);
    return candidates[0] || "";
  }

  function extractPosts() {
    const articles = Array.from(document.querySelectorAll('[role="article"]'));
    const seen = new Set();
    const results = [];

    articles.forEach((article) => {
      const text = cleanText(article);
      if (!text || text.length < 5) return;

      const permalink = findPermalink(article);
      const dedupeKey = permalink || text.slice(0, 120);
      if (seen.has(dedupeKey)) return;
      seen.add(dedupeKey);

      const images = Array.from(article.querySelectorAll("img"))
        .map((img) => img.src)
        .filter((src) => src && !src.startsWith("data:") && !/emoji|static\.xx/.test(src))
        .slice(0, 6);

      results.push({
        tipo: "post",
        autor: findAuthor(article),
        texto: findMainMessage(article) || text.slice(0, 500),
        data: findTimestamp(article),
        curtidas: firstMatch(text, REACTION_RE),
        comentarios: firstMatch(text, COMMENT_RE),
        compartilhamentos: firstMatch(text, SHARE_RE),
        link: permalink || location.href,
        imagens: images.join(" | "),
      });
    });

    return results;
  }

  function closestCard(el) {
    return el.closest('div[role="article"], a[role="link"]') || el.parentElement;
  }

  function extractMarketplace() {
    const results = [];

    if (/\/marketplace\/item\//.test(location.pathname)) {
      const bodyText = cleanText(document.body);
      const title = document.querySelector("h1")?.innerText.trim() || document.title;
      const price = firstMatch(bodyText, CURRENCY_RE);
      const seller =
        document.querySelector('a[href*="/marketplace/profile/"]')?.innerText.trim() || "";
      const images = Array.from(document.querySelectorAll("img"))
        .map((img) => img.src)
        .filter((src) => src && !src.startsWith("data:") && /scontent/.test(src))
        .slice(0, 8);
      const description =
        Array.from(document.querySelectorAll('div[dir="auto"]'))
          .map((el) => cleanText(el))
          .filter((t) => t.length > 40)
          .sort((a, b) => b.length - a.length)[0] || "";

      results.push({
        tipo: "marketplace_item",
        titulo: title,
        preco: price,
        vendedor: seller,
        descricao: description,
        link: location.href.split("?")[0],
        imagens: images.join(" | "),
      });
      return results;
    }

    // Marketplace listing / search grid
    const itemLinks = Array.from(
      document.querySelectorAll('a[href*="/marketplace/item/"]')
    );
    const seen = new Set();
    itemLinks.forEach((a) => {
      const href = a.href.split("?")[0];
      if (seen.has(href)) return;
      seen.add(href);

      const card = closestCard(a) || a;
      const text = cleanText(card);
      const price = firstMatch(text, CURRENCY_RE);
      const lines = text.split("\n").map((l) => l.trim()).filter(Boolean);
      const title = lines.find((l) => l !== price) || "";
      const img = card.querySelector("img");

      results.push({
        tipo: "marketplace_card",
        titulo: title,
        preco: price,
        link: href,
        imagem: img ? img.src : "",
      });
    });

    return results;
  }

  function extract() {
    const isMarketplace = /\/marketplace\//.test(location.pathname);
    const data = isMarketplace ? extractMarketplace() : extractPosts();
    return {
      url: location.href,
      pageType: isMarketplace ? "marketplace" : "feed",
      capturedAt: new Date().toISOString(),
      count: data.length,
      items: data,
    };
  }

  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message?.action === "EXTRACT_PAGE_DATA") {
      try {
        sendResponse({ ok: true, result: extract() });
      } catch (err) {
        sendResponse({ ok: false, error: String(err) });
      }
    }
    return true;
  });
})();
