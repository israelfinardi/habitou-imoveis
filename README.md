# Habitou Imóveis

Portal imobiliário completo (marketplace de compra e aluguel) para Santa Catarina, construído em Next.js + TypeScript + PostgreSQL.

## Stack

- **Next.js 16** (App Router, Server Components, Server Actions)
- **TypeScript**
- **PostgreSQL** + **Prisma ORM**
- **Tailwind CSS v4**
- Autenticação própria (JWT em cookie httpOnly + bcrypt)
- **Leaflet/OpenStreetMap** para mapas
- **fast-xml-parser** para o módulo VRSync (importação/exportação de feeds)

## Requisitos

- Node.js 20+
- PostgreSQL 14+
- Um servidor com suporte a **Node.js persistente** (não funciona em hospedagem puramente estática ou apenas PHP)

## Configuração local

```bash
cp .env.example .env
# edite .env com sua string de conexão do PostgreSQL

npm install
npm run db:migrate   # cria as tabelas
npm run db:seed      # popula com dados de exemplo (imóveis, imobiliárias, planos, admin)
npm run dev
```

Acesse http://localhost:3000. Usuário administrador criado pelo seed:

- **E-mail:** admin@habitou.com.br
- **Senha:** Admin@12345

**Troque essa senha imediatamente em produção.**

## Build de produção

```bash
npm run build
npm start
```

O servidor sobe em `http://localhost:3000` (porta configurável via `PORT`).

## Variáveis de ambiente

Veja `.env.example`. As principais:

| Variável | Descrição |
|---|---|
| `DATABASE_URL` | String de conexão PostgreSQL |
| `AUTH_SECRET` | Segredo para assinar sessões (gere um valor longo e aleatório) |
| `APP_URL` | URL pública do site (usada em e-mails, sitemap, metadata) |
| `SMTP_*` | Credenciais de e-mail (recuperação de senha, contato). Se vazio, e-mails só são registrados em log |
| `CRON_SECRET` | Protege `/api/cron/vrsync` contra chamadas públicas |

## Sincronização automática de feeds VRSync

Configure um agendador externo (cron do servidor, GitHub Actions, etc.) para chamar periodicamente:

```
GET https://seu-dominio/api/cron/vrsync
Authorization: Bearer <CRON_SECRET>
```

Isso sincroniza todos os feeds ativos cujo horário de sincronização já chegou.

## Testes de regressão

```bash
npm run build && npm start &
npm run test:smoke
```

Testa via navegador real (Playwright) os fluxos: cadastro, login, painel admin, listagem/filtros,
página de imóvel, favoritos, criação/publicação de anúncio, formulário de contato, comparador e blog.

## Estrutura do projeto

```
prisma/               schema, migrations e seed (dados reais extraídos do site original)
src/
  app/                rotas (App Router) — páginas públicas, minha-conta, anunciante,
                       imobiliaria, admin, contratos, planos, API routes
  components/          componentes de UI por domínio (property, layout, compare, ui)
  lib/                 auth, validação (zod), formatação, constantes
  server/services/     regras de negócio (Prisma) por domínio
  server/vrsync/       parser XML, normalização e sincronização de feeds
  types/               tipos derivados do Prisma
```

## Implantação em VPS (ex.: Hostinger VPS / Cloud)

Este projeto **não roda em hospedagem compartilhada tradicional** (a que só serve PHP/arquivos
estáticos). É necessário um plano com Node.js persistente e PostgreSQL — por exemplo, Hostinger
VPS/Cloud com Node.js habilitado no hPanel, ou qualquer servidor Linux próprio.

1. Provisione um banco PostgreSQL (na própria VPS ou um serviço gerenciado) e anote a `DATABASE_URL`.
2. Envie o conteúdo deste pacote para o servidor (sem `node_modules`, `.next` e `.env`).
3. No servidor:
   ```bash
   cp .env.example .env   # preencha com os valores reais de produção
   npm ci
   npm run db:migrate -- --skip-generate=false
   npm run db:seed        # opcional na primeira vez
   npm run build
   npm start               # ou use pm2 / systemd para manter o processo ativo
   ```
4. Configure um proxy reverso (Nginx/Apache) apontando para a porta do Next.js (padrão 3000) e HTTPS.
5. Configure o agendador do `/api/cron/vrsync` se for usar sincronização automática de feeds.

## Status do projeto

Implementado: autenticação, CRUD de imóveis com fotos, listagem/filtros/busca/paginação, página
individual com galeria e mapa, favoritos, comparador, minha conta, área do anunciante, imobiliárias
e corretores, contratos, planos/assinaturas (sem gateway de pagamento integrado), VRSync completo
(importação, normalização, deduplicação, logs, exportação), painel administrativo, SEO
(sitemap/robots/metadata/dados estruturados), páginas institucionais.

Pendências conhecidas para uma operação 100% real:
- Integração com gateway de pagamento (assinaturas ficam como `PENDING` até confirmação manual).
- Armazenamento de imagens enviadas pelo usuário é local (`public/uploads`) — trocar por um
  provedor de objetos (S3, R2, etc.) é recomendado em produção multi-servidor.
- Conteúdo completo do blog/central de ajuda foi preservado apenas como resumos (o site original
  carrega o corpo completo dos artigos via componentes client-side não capturados na auditoria).
