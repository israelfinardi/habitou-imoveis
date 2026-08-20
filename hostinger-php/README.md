# Habitou Imóveis — versão PHP/SQLite (Hostinger)

Portal imobiliário completo (compra, aluguel, anunciantes, imobiliárias, painel
administrativo) em **PHP puro + SQLite**, sem dependência de Node.js e sem passo de build —
feito para rodar em hospedagem compartilhada como a Hostinger.

## Instalação: literalmente só enviar os arquivos

Não existe passo de banco de dados. Não existe instalador para visitar. O site cria e popula
o próprio banco (um único arquivo SQLite) sozinho, na primeira vez que alguém acessa qualquer
página.

1. No hPanel: **Gerenciador de Arquivos** → entre em `public_html` (ou no domínio/subdomínio
   desejado) → envie o `.zip` deste pacote → clique com o botão direito → **Extrair**.
2. Acesse `https://seudominio.com.br/`.

Pronto — é isso. Na primeira visita, o site detecta que ainda não tem banco, cria as tabelas
e popula um catálogo de exemplo (leva menos de 2 segundos). Os segredos de segurança
(`AUTH_SECRET`/`CRON_SECRET`, usados para proteger `limpar-dados.php` e os crons agendados) são
gerados sozinhos nesse momento e guardados em `data/.secrets.php` — arquivo que fica fora do
alcance do navegador (bloqueado por `.htaccess`, igual ao restante da pasta `data/`).

Login de administrador criado automaticamente:

- **E-mail:** admin@habitou.com.br
- **Senha:** Admin@12345

**Troque essa senha assim que entrar** (Minha conta → Alterar senha).

## O que você precisa no seu plano

- **PHP 8.0+** com extensões: `pdo_sqlite`, `mbstring`, `simplexml`, `gd` (praticamente todo
  plano de hospedagem da Hostinger já vem com isso — não precisa nem existe MySQL aqui).
- **Não precisa de Node.js, Composer, SSH, banco de dados separado nem passo de build.**

## Apagar os dados de demonstração

O primeiro acesso já popula o banco com um catálogo de exemplo (imóveis, imobiliárias,
cidades e bairros) só para você conferir que tudo funciona. Quando estiver pronto para
colocar dados reais no ar, apague tudo de uma vez. Pegue o token no painel administrativo
(**Administração → Token de manutenção**, na página inicial do admin) e acesse:

```
https://seudominio.com.br/limpar-dados.php?token=SEU_TOKEN
```

A página mostra um aviso e o link exato de confirmação (com `&confirmar=sim`) — é preciso
visitar esse segundo link pra realmente apagar. Isso remove todos os imóveis, imobiliárias,
cidades, bairros, contratos e as contas de demonstração criadas pelo seed
(imobiliárias parceiras de exemplo e o anunciante demo). **A conta `admin@habitou.com.br` e
qualquer conta real que você já tenha criado não são apagadas.** Não pode ser desfeito —
apague o arquivo `limpar-dados.php` do servidor depois de usar.

Depois de limpar, o cadastro de imóveis continua funcionando normalmente: qualquer cidade do
Brasil pode ser escolhida no formulário (não é preciso recriar cidades manualmente — elas são
criadas automaticamente na primeira vez que alguém anuncia um imóvel nelas).

## Atualizando os arquivos no futuro

Quando eu (ou você) mandar uma nova versão do sistema, é só sobrescrever os arquivos PHP —
**nunca sobrescreva a pasta `data/`** (é lá que ficam o banco SQLite e os segredos gerados).
Sobrescrever só o código PHP não apaga nem altera nada do que já está cadastrado.

## E-mail (recuperação de senha, formulário de contato)

Por padrão o sistema tenta usar a função `mail()` nativa do PHP, que costuma funcionar na
Hostinger sem configuração adicional. Se quiser usar SMTP (recomendado para entregabilidade),
edite `config/config.php` e preencha `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` — a
Hostinger fornece essas credenciais em **E-mails → Contas de E-mail**.

## Estrutura do projeto

```
config/           configuração — bloqueado por .htaccess
includes/         funções compartilhadas, autenticação, serviços de domínio
sql/schema.sql    schema completo do banco (SQLite), aplicado automaticamente
includes/seed.php popula os dados de demonstração, chamado automaticamente no 1º acesso
data/             banco SQLite, segredos e uploads de dados — tudo bloqueado por .htaccess
actions/          endpoints que processam formulários (POST)
anunciante/        área do anunciante (CRUD de imóveis, fotos)
imobiliaria/       painel da imobiliária (perfil)
admin/             painel administrativo
cron/              scripts para agendamento (expiração de anúncios, recomendações)
uploads/           fotos enviadas pelos anunciantes (gravável pelo PHP)
assets/            CSS/JS/imagens/dados (cidades-br.json)
limpar-dados.php   apaga o catálogo de demonstração (apagar depois de usar)
*.php (raiz)       páginas públicas (home, busca, imóvel, login, blog, etc.)
```

## Segurança

- Senhas com hash `bcrypt` (`password_hash`), nunca em texto puro.
- Proteção CSRF em todos os formulários (`csrf_field()` / `verify_csrf()`).
- Upload de fotos valida extensão, MIME real (`mime_content_type`) e tamanho (máx. 8MB);
  a pasta `uploads/` bloqueia execução de PHP via `.htaccess`.
- `config/`, `includes/`, `sql/` e `data/` são bloqueados para acesso direto pelo navegador —
  isso inclui o próprio arquivo do banco (`data/database.sqlite`) e os segredos gerados
  (`data/.secrets.php`), que nunca ficam expostos ao navegador.
- Permissões: um anunciante só edita seus próprios imóveis; imobiliária só edita imóveis da
  própria equipe; administrador tem acesso total.

## Cadastro de corretores e imobiliárias

Em `/cadastro.php` a pessoa escolhe o tipo de conta: comprador/anunciante, corretor autônomo
(CRECI) ou imobiliária. Ao se cadastrar como imobiliária, o sistema já cria a conta ativa
(pode anunciar na hora), mas o **perfil público** da imobiliária fica com status `PENDING`
até o administrador aprovar em `/admin/imobiliarias.php`.

## Mapa interativo na busca

As páginas de listagem/filtros (`/imoveis.php` e `/cidade.php`) mostram um mapa lateral
(Leaflet) com pinos de preço, sincronizado por hover com os cards da lista — no celular ele
vira uma tela cheia acionada pelo botão "Ver no mapa". Imóveis sem latitude/longitude próprias
usam o centro da cidade como localização aproximada (mesmo comportamento já usado na página
individual do imóvel).

## Localização nacional (cadastro de imóvel)

O campo "Cidade" do formulário de anúncio (`/anunciante/novo.php` e `/anunciante/editar.php`)
não é uma lista fixa — é uma busca com todas as ~5.600 cidades do Brasil
(`assets/data/cidades-br.json`). A cidade escolhida é criada no banco automaticamente na
primeira vez que é usada, então não é preciso cadastrar cidades manualmente em lugar nenhum.
As 6 cidades em destaque de Santa Catarina continuam usando os mesmos endereços
(`/cidade.php?slug=...`) de sempre, mesmo depois de rodar o `limpar-dados.php`.

## Barra superior (busca de cidade, transação e filtros)

O topo do site (presente em toda página) tem uma pílula de cidade com busca instantânea (mesma
lista nacional de cidades), um seletor Todos/Comprar/Alugar e um atalho para os filtros. A
paleta de cores usa laranja `#D95D39` e azul-marinho `#1A2E44`.

## Testes de regressão

Este pacote foi testado localmente (PHP 8.4) cobrindo: provisionamento automático do banco a
partir de um estado limpo (extração simulada do zip), cadastro (incluindo corretor e
imobiliária, com aprovação de imobiliária pelo admin), login, listagem com filtros reais no
banco e mapa interativo lateral, página de imóvel com galeria/mapa, favoritos, criação e
publicação de imóvel pelo anunciante (incluindo criação de cidade nova em tempo real), upload
de fotos, painel administrativo, `limpar-dados.php` de ponta a ponta (com o site
continuando a funcionar normalmente depois), e requisições simultâneas (sem erro de banco
travado) — tudo funcionando antes do empacotamento.

## Limitações conhecidas

- **SQLite é single-writer**: leituras simultâneas não têm problema (é o caso comum: gente
  navegando o catálogo), mas duas escritas ao mesmo tempo (dois cadastros no mesmíssimo
  instante, por exemplo) esperam uma pela outra em vez de rodar em paralelo — para o volume de
  tráfego de um portal regional isso não chega a ser perceptível. Se o site crescer muito e
  isso virar gargalo real, dá pra migrar para MySQL depois.
- Sem gateway de pagamento integrado: assinaturas ficam como `PENDING` até confirmação
  manual pelo administrador em `/admin/assinaturas.php`.
- Uploads ficam no disco do próprio servidor (`uploads/`) — em hospedagem compartilhada isso
  é normal e funciona bem; para múltiplos servidores, trocar por um serviço de objetos (S3 etc.).
- O visual usa Tailwind CSS via CDN (sem passo de build) — funciona perfeitamente, mas para
  produção de alta escala um build local do Tailwind reduziria o peso da página.
