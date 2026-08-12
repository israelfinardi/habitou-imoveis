# Habitou Imóveis — versão PHP/MySQL (Hostinger)

Portal imobiliário completo (compra, aluguel, anunciantes, imobiliárias, VRSync, painel
administrativo) em **PHP puro + MySQL**, sem dependência de Node.js e sem passo de build —
feito para rodar em hospedagem compartilhada como a Hostinger.

## O que você precisa no seu plano

- **PHP 8.0+** com extensões: `pdo_mysql`, `mbstring`, `simplexml`, `gd` (praticamente todo
  plano de hospedagem da Hostinger já vem com isso).
- **Um banco de dados MySQL** (hPanel → Bancos de Dados → MySQL Databases).
- **Não precisa de Node.js, Composer, SSH nem passo de build.** É só enviar os arquivos.

## Passo a passo (Hostinger, sem usar terminal)

### 1. Criar o banco de dados

No hPanel: **Bancos de Dados → MySQL Databases** → crie um banco e um usuário, anote:
nome do banco, usuário e senha (algo como `u123456789_habitou`).

### 2. Enviar os arquivos

No hPanel: **Gerenciador de Arquivos** → entre em `public_html` (ou no domínio/subdomínio
desejado) → envie o `.zip` deste pacote → clique com o botão direito → **Extrair**.

### 3. Configurar a conexão com o banco

Dentro da pasta enviada, copie `config/config.example.php` para `config/config.php` e edite
com os dados reais do passo 1:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_habitou');
define('DB_USER', 'u123456789_habitou');
define('DB_PASS', 'sua-senha-do-banco');

define('APP_URL', 'https://seudominio.com.br');
define('AUTH_SECRET', '...'); // gere uma string aleatória longa
define('CRON_SECRET', '...'); // outra string aleatória
```

Isso pode ser feito direto no **Editor de Código** do Gerenciador de Arquivos da Hostinger,
sem precisar baixar/reenviar nada.

### 4. Instalar as tabelas e os dados

Abra no navegador (troque pelo seu domínio e pelo `AUTH_SECRET` que você definiu):

```
https://seudominio.com.br/instalar.php?token=SEU_AUTH_SECRET
```

Isso cria as tabelas e popula o catálogo com os dados de exemplo (imóveis, imobiliárias,
planos, blog). Pode levar 1–2 minutos — se a página parecer travar ou der erro de tempo
limite, **recarregue a mesma URL**: o processo é seguro para rodar mais de uma vez, ele
continua de onde parou sem duplicar nada.

Ao final, você verá a confirmação com o login do administrador:

- **E-mail:** admin@habitou.com.br
- **Senha:** Admin@12345

**Troque essa senha imediatamente e depois apague o arquivo `instalar.php` do servidor**
(pelo Gerenciador de Arquivos). Ele fica protegido por senha, mas não custa nada removê-lo
depois de usado.

### 5. Pronto

Acesse `https://seudominio.com.br/` — o site já está no ar, com banco de dados real.

## Apagar os dados de demonstração

O instalador popula o banco com um catálogo de exemplo (imóveis, imobiliárias, cidades e
bairros) só para você conferir que tudo funciona. Quando estiver pronto para colocar dados
reais no ar, apague tudo de uma vez acessando (troque pelo seu domínio e pelo `AUTH_SECRET`):

```
https://seudominio.com.br/limpar-dados.php?token=SEU_AUTH_SECRET
```

A página mostra um aviso e o link exato de confirmação (com `&confirmar=sim`) — é preciso
visitar esse segundo link pra realmente apagar. Isso remove todos os imóveis, imobiliárias,
cidades, bairros, contratos, feeds VRSync e as contas de demonstração criadas pelo seed
(imobiliárias parceiras de exemplo e o anunciante demo). **A conta `admin@habitou.com.br` e
qualquer conta real que você já tenha criado não são apagadas.** Não pode ser desfeito —
apague o arquivo `limpar-dados.php` do servidor depois de usar, assim como fez com o
`instalar.php`.

Depois de limpar, o cadastro de imóveis continua funcionando normalmente: qualquer cidade do
Brasil pode ser escolhida no formulário (não é preciso recriar as cidades manualmente — elas
são criadas automaticamente na primeira vez que alguém anuncia um imóvel nelas).

## Sincronização automática de feeds VRSync (opcional)

No hPanel: **Avançado → Cron Jobs**, crie uma tarefa (ex.: a cada hora) executando:

```
php /home/SEU_USUARIO/public_html/cron/vrsync_sync.php
```

Isso sincroniza automaticamente os feeds VRSync das imobiliárias cadastradas.

## E-mail (recuperação de senha, formulário de contato)

Por padrão o sistema tenta usar a função `mail()` nativa do PHP, que costuma funcionar na
Hostinger sem configuração adicional. Se quiser usar SMTP (recomendado para entregabilidade),
preencha `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` em `config/config.php` — a
Hostinger fornece essas credenciais em **E-mails → Contas de E-mail**.

## Estrutura do projeto

```
config/           configuração (banco, e-mail, segredos) — bloqueado por .htaccess
includes/         funções compartilhadas, autenticação, serviços de domínio
sql/schema.sql    schema completo do banco (MySQL)
data/seed.php     popula o banco com os dados reais extraídos do site original
actions/          endpoints que processam formulários (POST)
anunciante/        área do anunciante (CRUD de imóveis, fotos)
imobiliaria/       painel da imobiliária (feeds VRSync)
admin/             painel administrativo
feeds/vrsync.php   exportação pública do catálogo em XML (VRSync)
cron/              scripts para agendamento (sincronização automática)
uploads/           fotos enviadas pelos anunciantes (gravável pelo PHP)
assets/            CSS/JS/imagens/dados (cidades-br.json)
instalar.php       instalador web (apagar depois de usar)
limpar-dados.php   apaga o catálogo de demonstração (apagar depois de usar)
*.php (raiz)       páginas públicas (home, busca, imóvel, login, blog, etc.)
```

## Segurança

- Senhas com hash `bcrypt` (`password_hash`), nunca em texto puro.
- Proteção CSRF em todos os formulários (`csrf_field()` / `verify_csrf()`).
- Upload de fotos valida extensão, MIME real (`mime_content_type`) e tamanho (máx. 8MB);
  a pasta `uploads/` bloqueia execução de PHP via `.htaccess`.
- `config/`, `includes/`, `sql/` e `data/` são bloqueados para acesso direto pelo navegador.
- Permissões: um anunciante só edita seus próprios imóveis; imobiliária só edita imóveis da
  própria equipe; administrador tem acesso total.

## Cadastro de corretores e imobiliárias

Em `/cadastro.php` a pessoa escolhe o tipo de conta: comprador/anunciante, corretor autônomo
(CRECI) ou imobiliária. Ao se cadastrar como imobiliária, o sistema já cria a conta ativa
(pode anunciar e configurar feeds VRSync na hora), mas o **perfil público** da imobiliária
fica com status `PENDING` até o administrador aprovar em `/admin/imobiliarias.php`.

## Mapa interativo na busca

As páginas de listagem/filtros (`/imoveis.php` e `/cidade.php`) mostram um mapa lateral
(Leaflet) com pinos de preço, sincronizado por hover com os cards da lista — no celular ele
vira uma tela cheia acionada pelo botão "Ver no mapa". Imóveis sem latitude/longitude próprias
usam o centro da cidade como localização aproximada (mesmo comportamento já usado na página
individual do imóvel).

## Localização nacional (cadastro de imóvel)

O campo "Cidade" do formulário de anúncio (`/anunciante/novo.php` e `/anunciante/editar.php`)
não é mais uma lista fixa — é uma busca com todas as ~5.600 cidades do Brasil
(`assets/data/cidades-br.json`). A cidade escolhida é criada no banco automaticamente na
primeira vez que é usada (`get_or_create_city()`), então não é preciso cadastrar cidades
manualmente em lugar nenhum. As 6 cidades em destaque de Santa Catarina continuam usando os
mesmos endereços (`/cidade.php?slug=...`) de sempre, mesmo depois de rodar o `limpar-dados.php`.

## Barra superior (busca de cidade, transação e filtros)

O topo do site (presente em toda página) tem uma pílula de cidade com busca instantânea (mesma
lista nacional de cidades), um seletor Todos/Comprar/Alugar e um atalho para os filtros —
inspirados no layout que você enviou como referência. A paleta de cores também foi atualizada
para acompanhar esse modelo (laranja `#D95D39`, azul-marinho `#1A2E44`).

## Testes de regressão

Este pacote foi testado localmente (PHP 8.4 + MariaDB) cobrindo: cadastro (incluindo corretor
e imobiliária, com aprovação de imobiliária pelo admin), login, listagem com filtros reais no
banco e mapa interativo lateral, página de imóvel com galeria/mapa, favoritos, criação e
publicação de imóvel pelo anunciante, upload de fotos, VRSync (importação com deduplicação,
exportação, roundtrip completo), painel administrativo e formulário de contato — todos
funcionando ponta a ponta antes do empacotamento.

## Limitações conhecidas

- Sem gateway de pagamento integrado: assinaturas ficam como `PENDING` até confirmação
  manual pelo administrador em `/admin/assinaturas.php`.
- Uploads ficam no disco do próprio servidor (`uploads/`) — em hospedagem compartilhada isso
  é normal e funciona bem; para múltiplos servidores, trocar por um serviço de objetos (S3 etc.).
- O visual usa Tailwind CSS via CDN (sem passo de build) — funciona perfeitamente, mas para
  produção de alta escala um build local do Tailwind reduziria o peso da página.
