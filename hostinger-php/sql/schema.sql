-- Habitou Imóveis — schema SQLite (compatível com hospedagem compartilhada,
-- ex.: Hostinger). Um único arquivo de banco, sem servidor separado nem
-- credenciais — criado automaticamente pelo próprio site na primeira
-- requisição. Equivalente funcional ao schema Prisma usado na versão
-- Next.js do projeto.

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------
-- Usuários e autenticação
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS agencies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  cnpj VARCHAR(32) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(32) NULL,
  whatsapp VARCHAR(32) NULL,
  address VARCHAR(255) NULL,
  zip_code VARCHAR(16) NULL,
  city VARCHAR(120) NULL,
  state VARCHAR(60) NULL,
  service_area VARCHAR(255) NULL,
  logo_url VARCHAR(500) NULL,
  description TEXT NULL,
  website VARCHAR(255) NULL,
  status TEXT NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(32) NULL,
  whatsapp VARCHAR(32) NULL,
  website VARCHAR(255) NULL,
  service_area VARCHAR(255) NULL,
  bio TEXT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role TEXT NOT NULL DEFAULT 'USER',
  status TEXT NOT NULL DEFAULT 'ACTIVE',
  avatar_url VARCHAR(500) NULL,
  creci VARCHAR(32) NULL,
  agency_id INTEGER NULL,
  notify_email VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  CONSTRAINT fk_users_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_users_agency ON users (agency_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

-- ---------------------------------------------------------------------
-- Segurança: histórico de tentativas de login (bloqueio progressivo de
-- conta) e contador genérico de limite de requisições por IP (rate
-- limiting em recuperação de senha, webhooks, etc).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(255) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  success INTEGER NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts (email, created_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts (ip, created_at);

CREATE TABLE IF NOT EXISTS rate_limit_hits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  bucket VARCHAR(60) NOT NULL,
  rkey VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_rate_limit_hits ON rate_limit_hits (bucket, rkey, created_at);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_prt_user ON password_reset_tokens (user_id);

-- ---------------------------------------------------------------------
-- Localização
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  state VARCHAR(60) NOT NULL,
  state_code CHAR(2) NOT NULL,
  region VARCHAR(60) NULL,
  country VARCHAR(60) NOT NULL DEFAULT 'Brasil',
  latitude DECIMAL(10,6) NULL,
  longitude DECIMAL(10,6) NULL,
  description TEXT NULL,
  hero_image_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_cities_state ON cities (state);

CREATE TABLE IF NOT EXISTS neighborhoods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  city_id INTEGER NOT NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uniq_city_slug UNIQUE (city_id, slug),
  CONSTRAINT fk_neighborhood_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Planos e assinaturas
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS plans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  billing_period TEXT NOT NULL DEFAULT 'MONTHLY',
  max_listings INTEGER NULL,
  features TEXT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  mp_plan_id VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_plans_mp_plan_id ON plans (mp_plan_id);

CREATE TABLE IF NOT EXISTS subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  agency_id INTEGER NULL,
  plan_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'PENDING',
  started_at DATETIME NULL,
  expires_at DATETIME NULL,
  canceled_at DATETIME NULL,
  external_id VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_sub_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
);
CREATE INDEX IF NOT EXISTS idx_sub_user ON subscriptions (user_id);
CREATE INDEX IF NOT EXISTS idx_sub_agency ON subscriptions (agency_id);

-- ---------------------------------------------------------------------
-- VRSync — feeds (precisa existir antes de properties por causa da FK)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS feeds (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  agency_id INTEGER NOT NULL,
  name VARCHAR(160) NOT NULL,
  url TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'ACTIVE',
  frequency_minutes INTEGER NOT NULL DEFAULT 1440,
  last_sync_at DATETIME NULL,
  next_sync_at DATETIME NULL,
  last_run_status TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feed_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_feed_agency ON feeds (agency_id);

CREATE TABLE IF NOT EXISTS feed_sync_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  feed_id INTEGER NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  status TEXT NOT NULL DEFAULT 'RUNNING',
  total_found INTEGER NOT NULL DEFAULT 0,
  total_created INTEGER NOT NULL DEFAULT 0,
  total_updated INTEGER NOT NULL DEFAULT 0,
  total_deactivated INTEGER NOT NULL DEFAULT 0,
  total_unchanged INTEGER NOT NULL DEFAULT 0,
  total_errors INTEGER NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  CONSTRAINT fk_log_feed FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_log_feed ON feed_sync_logs (feed_id);

-- ---------------------------------------------------------------------
-- Imóveis
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS properties (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  external_code VARCHAR(64) NULL,
  origin TEXT NOT NULL DEFAULT 'MANUAL',
  source_feed_id INTEGER NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  listing_type TEXT NOT NULL,
  property_type TEXT NOT NULL,
  price_sale DECIMAL(14,2) NULL,
  price_rent DECIMAL(14,2) NULL,
  condo_fee DECIMAL(14,2) NULL,
  iptu DECIMAL(14,2) NULL,
  total_area FLOAT NULL,
  built_area FLOAT NULL,
  bedrooms INTEGER NULL,
  suites INTEGER NULL,
  bathrooms INTEGER NULL,
  parking_spaces INTEGER NULL,
  features TEXT NULL,
  status TEXT NOT NULL DEFAULT 'DRAFT',
  is_featured INTEGER NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  deactivated_at DATETIME NULL,
  expires_at DATETIME NULL,
  city_id INTEGER NOT NULL,
  neighborhood_id INTEGER NULL,
  street VARCHAR(255) NULL,
  number VARCHAR(32) NULL,
  complement VARCHAR(120) NULL,
  zip_code VARCHAR(16) NULL,
  latitude DECIMAL(10,6) NULL,
  longitude DECIMAL(10,6) NULL,
  contact_phone VARCHAR(32) NULL,
  contact_email VARCHAR(255) NULL,
  contact_whatsapp VARCHAR(32) NULL,
  advertiser_id INTEGER NOT NULL,
  owner_id INTEGER NULL,
  agent_id INTEGER NULL,
  agency_id INTEGER NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prop_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_prop_neighborhood FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_advertiser FOREIGN KEY (advertiser_id) REFERENCES users(id),
  CONSTRAINT fk_prop_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_feed FOREIGN KEY (source_feed_id) REFERENCES feeds(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_listing ON properties (city_id, listing_type, property_type, status);
CREATE INDEX IF NOT EXISTS idx_prop_neighborhood ON properties (neighborhood_id);
CREATE INDEX IF NOT EXISTS idx_prop_price_sale ON properties (price_sale);
CREATE INDEX IF NOT EXISTS idx_prop_price_rent ON properties (price_rent);
CREATE INDEX IF NOT EXISTS idx_prop_status_published ON properties (status, published_at);
CREATE INDEX IF NOT EXISTS idx_prop_advertiser ON properties (advertiser_id);
CREATE INDEX IF NOT EXISTS idx_prop_agency ON properties (agency_id);
CREATE INDEX IF NOT EXISTS idx_prop_source ON properties (origin, source_feed_id, external_code);

CREATE TABLE IF NOT EXISTS property_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  property_id INTEGER NOT NULL,
  url VARCHAR(500) NOT NULL,
  `order` INTEGER NOT NULL DEFAULT 0,
  is_primary INTEGER NOT NULL DEFAULT 0,
  width INTEGER NULL,
  height INTEGER NULL,
  size_bytes INTEGER NULL,
  origin TEXT NOT NULL DEFAULT 'MANUAL',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_img_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_img_property_order ON property_images (property_id, `order`);

CREATE TABLE IF NOT EXISTS favorites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  property_id INTEGER NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uniq_user_property UNIQUE (user_id, property_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fav_property ON favorites (property_id);

-- ---------------------------------------------------------------------
-- Recomendações por e-mail: histórico de buscas (sinal de interesse, junto
-- com favorites) e throttle do disparo diário (cron/property_recommendations.php).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS search_history (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  city_id INTEGER NULL,
  listing_type TEXT NULL,
  property_type TEXT NULL,
  min_price DECIMAL(14,2) NULL,
  max_price DECIMAL(14,2) NULL,
  bedrooms INTEGER NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_search_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_search_history_user ON search_history (user_id, created_at);

CREATE TABLE IF NOT EXISTS recommendation_email_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  last_sent_at DATETIME NULL,
  last_property_ids TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rec_email_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Contratos
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS contracts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  property_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'DRAFT',
  owner_id INTEGER NULL,
  advertiser_id INTEGER NULL,
  buyer_id INTEGER NULL,
  tenant_id INTEGER NULL,
  agent_id INTEGER NULL,
  agency_id INTEGER NULL,
  value DECIMAL(14,2) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  documents TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_contract_property FOREIGN KEY (property_id) REFERENCES properties(id),
  CONSTRAINT fk_contract_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_advertiser FOREIGN KEY (advertiser_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_tenant FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_contract_property ON contracts (property_id);
CREATE INDEX IF NOT EXISTS idx_contract_status ON contracts (status);

CREATE TABLE IF NOT EXISTS contract_history (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  contract_id INTEGER NOT NULL,
  status TEXT NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_history_contract ON contract_history (contract_id);

-- ---------------------------------------------------------------------
-- Conteúdo (blog / central de ajuda) e contato
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS articles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  kind TEXT NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NOT NULL,
  content TEXT NULL,
  category VARCHAR(120) NOT NULL,
  author_name VARCHAR(120) NOT NULL,
  author_role VARCHAR(160) NULL,
  read_minutes INTEGER NULL,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_articles_kind_category ON articles (kind, category);

CREATE TABLE IF NOT EXISTS contact_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NULL,
  subject VARCHAR(160) NULL,
  message TEXT NOT NULL,
  user_id INTEGER NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
