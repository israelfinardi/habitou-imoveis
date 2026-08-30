# Habitou Facebook Data Extractor

Extensão do Chrome (Manifest V3) que extrai, para CSV/JSON, os dados **já
visíveis na página do Facebook que você está navegando** — feed, grupo,
página de negócio ou Marketplace — para você analisar depois numa planilha
ou importar no seu próprio sistema.

## O que ela faz

- Roda apenas quando você clica em **"Extrair desta página"** — não navega,
  não rola a página, não clica em nada sozinha e não roda em segundo plano.
- Lê o DOM da aba ativa (a sessão já logada do seu navegador), do mesmo jeito
  que um "Salvar página" faria. Não usa a API do Facebook, não guarda nem
  envia sua senha, e não envia dados para nenhum servidor — tudo fica local
  no seu computador.
- Detecta o tipo de página e extrai campos relevantes:
  - **Feed / grupo / página**: autor, texto do post, data, curtidas,
    comentários, compartilhamentos, link permanente e imagens.
  - **Marketplace (item)**: título, preço, vendedor, descrição, link e fotos.
  - **Marketplace (busca/listagem)**: título, preço, link e foto de cada
    card.
- Exporta o resultado em **CSV** ou **JSON**, ou copia o JSON para a área de
  transferência.

## Aviso importante (leia antes de usar)

O Facebook proíbe scraping automatizado em seus
[Termos de Uso](https://www.facebook.com/terms) e pode suspender contas que
façam coleta em massa. Esta ferramenta foi desenhada para uso **manual,
pontual e pessoal** (analisar a concorrência, organizar leads de um grupo
que você administra, etc.), não para automação em massa. Recomendações:

- Não a use em loop/automatizada para varrer centenas de páginas.
- Não colete dados sensíveis de terceiros sem base legal (LGPD) — prefira
  focar em dados públicos e nas suas próprias páginas/grupos.
- O Facebook muda o HTML da interface com frequência; se algum campo vier
  vazio, é provável que os seletores em `content.js` precisem de ajuste.

## Instalação (modo desenvolvedor)

1. Abra `chrome://extensions` no Chrome (ou Edge/Brave).
2. Ative o **"Modo do desenvolvedor"** (canto superior direito).
3. Clique em **"Carregar sem compactação"** (Load unpacked).
4. Selecione a pasta `facebook-scraper-extension/` deste repositório.
5. O ícone da extensão aparece na barra de ferramentas.

## Uso

1. Abra uma aba do Facebook e vá até o post, grupo ou anúncio do
   Marketplace que você quer analisar.
2. Clique no ícone da extensão e depois em **"Extrair desta página"**.
3. Veja a prévia dos itens encontrados e exporte em CSV (Excel/Sheets) ou
   JSON.

## Estrutura

```
facebook-scraper-extension/
├── manifest.json     # configuração da extensão (MV3)
├── content.js        # lê o DOM da página do Facebook aberta
├── background.js     # service worker (mantém a última extração em cache)
├── popup.html/.css/.js  # interface de extração e exportação
└── icons/            # ícones da extensão
```

## Limitações conhecidas

- O Facebook usa classes CSS geradas dinamicamente; a extração usa atributos
  mais estáveis (`role`, `aria-label`, `data-ad-preview`, `abbr[title]`),
  mas mudanças no site podem exigir atualização dos seletores.
- Conteúdo que exige "Ver mais" ou rolagem adicional só é capturado depois
  que você mesmo expandir/rolar a página, já que a extensão não interage
  com a página sozinha.
