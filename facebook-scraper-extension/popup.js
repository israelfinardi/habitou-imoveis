const extractBtn = document.getElementById("extract-btn");
const statusEl = document.getElementById("status");
const notFacebookEl = document.getElementById("not-facebook");
const resultPanel = document.getElementById("result-panel");
const resultCountEl = document.getElementById("result-count");
const resultTypeEl = document.getElementById("result-type");
const previewEl = document.getElementById("preview");
const exportCsvBtn = document.getElementById("export-csv");
const exportJsonBtn = document.getElementById("export-json");
const copyJsonBtn = document.getElementById("copy-json");

let lastResult = null;

function setStatus(text) {
  statusEl.textContent = text || "";
}

async function getActiveTab() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  return tab;
}

function isFacebookUrl(url) {
  try {
    return new URL(url).hostname.endsWith("facebook.com");
  } catch {
    return false;
  }
}

function renderPreview(items) {
  previewEl.innerHTML = "";
  items.slice(0, 25).forEach((item) => {
    const div = document.createElement("div");
    div.className = "item";
    const title = item.titulo || item.autor || item.tipo;
    const body = item.texto || item.descricao || item.preco || "";
    const strong = document.createElement("strong");
    strong.textContent = title || "(sem título)";
    const p = document.createElement("p");
    p.textContent = body.length > 220 ? body.slice(0, 220) + "…" : body;
    div.appendChild(strong);
    if (body) div.appendChild(p);
    previewEl.appendChild(div);
  });
  if (items.length > 25) {
    const more = document.createElement("div");
    more.className = "item";
    more.textContent = `+ ${items.length - 25} itens (veja no arquivo exportado)`;
    previewEl.appendChild(more);
  }
}

function toCsv(items) {
  if (!items.length) return "";
  const headers = Array.from(
    items.reduce((set, item) => {
      Object.keys(item).forEach((k) => set.add(k));
      return set;
    }, new Set())
  );
  const escape = (val) => {
    const str = (val ?? "").toString().replace(/"/g, '""');
    return `"${str}"`;
  };
  const lines = [headers.join(",")];
  items.forEach((item) => {
    lines.push(headers.map((h) => escape(item[h])).join(","));
  });
  return lines.join("\n");
}

function download(filename, content, mime) {
  const blob = new Blob([content], { type: mime });
  const url = URL.createObjectURL(blob);
  chrome.downloads.download({ url, filename, saveAs: true }, () => {
    setTimeout(() => URL.revokeObjectURL(url), 10000);
  });
}

function timestampSlug() {
  return new Date().toISOString().replace(/[:.]/g, "-");
}

extractBtn.addEventListener("click", async () => {
  setStatus("Extraindo…");
  resultPanel.hidden = true;
  const tab = await getActiveTab();
  if (!tab || !isFacebookUrl(tab.url)) {
    notFacebookEl.hidden = false;
    setStatus("");
    return;
  }
  notFacebookEl.hidden = true;

  chrome.tabs.sendMessage(tab.id, { action: "EXTRACT_PAGE_DATA" }, (response) => {
    if (chrome.runtime.lastError || !response) {
      setStatus(
        "Não foi possível ler a página. Recarregue a aba do Facebook e tente novamente."
      );
      return;
    }
    if (!response.ok) {
      setStatus("Erro ao extrair: " + response.error);
      return;
    }

    lastResult = response.result;
    chrome.storage.local.set({ lastExtraction: lastResult });

    setStatus("");
    resultCountEl.textContent = `${lastResult.count} itens encontrados`;
    resultTypeEl.textContent = ` · ${lastResult.pageType}`;
    renderPreview(lastResult.items);
    resultPanel.hidden = false;
  });
});

exportCsvBtn.addEventListener("click", () => {
  if (!lastResult) return;
  download(
    `facebook-dados-${timestampSlug()}.csv`,
    toCsv(lastResult.items),
    "text/csv;charset=utf-8"
  );
});

exportJsonBtn.addEventListener("click", () => {
  if (!lastResult) return;
  download(
    `facebook-dados-${timestampSlug()}.json`,
    JSON.stringify(lastResult, null, 2),
    "application/json"
  );
});

copyJsonBtn.addEventListener("click", async () => {
  if (!lastResult) return;
  await navigator.clipboard.writeText(JSON.stringify(lastResult, null, 2));
  setStatus("JSON copiado para a área de transferência.");
});

(async function init() {
  const tab = await getActiveTab();
  if (tab && !isFacebookUrl(tab.url)) {
    notFacebookEl.hidden = false;
  }
  const stored = await chrome.storage.local.get("lastExtraction");
  if (stored.lastExtraction) {
    lastResult = stored.lastExtraction;
    resultCountEl.textContent = `${lastResult.count} itens encontrados`;
    resultTypeEl.textContent = ` · ${lastResult.pageType} (última extração)`;
    renderPreview(lastResult.items);
    resultPanel.hidden = false;
  }
})();
