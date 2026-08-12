-- Habitou Imóveis — schema MySQL/MariaDB (compatível com hospedagem
-- compartilhada, ex.: Hostinger). Equivalente funcional ao schema Prisma
-- usado na versão Next.js do projeto.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Usuários e autenticação
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS agencies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  cnpj VARCHAR(32) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(32) NULL,
  address VARCHAR(255) NULL,
  zip_code VARCHAR(16) NULL,
  city VARCHAR(120) NULL,
  state VARCHAR(60) NULL,
  logo_url VARCHAR(500) NULL,
  description TEXT NULL,
  website VARCHAR(255) NULL,
  status ENUM('ACTIVE','INACTIVE','PENDING') NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(32) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('USER','ADVERTISER','OWNER','AGENT','AGENCY_ADMIN','ADMIN') NOT NULL DEFAULT 'USER',
  status ENUM('ACTIVE','INACTIVE','SUSPENDED','PENDING') NOT NULL DEFAULT 'ACTIVE',
  avatar_url VARCHAR(500) NULL,
  creci VARCHAR(32) NULL,
  agency_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  INDEX (agency_id),
  INDEX (role),
  CONSTRAINT fk_users_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Localização
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cities (
  id INT AUTO_INCREMENT PRIMARY KEY,
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
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS neighborhoods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  city_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(180) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_city_slug (city_id, slug),
  CONSTRAINT fk_neighborhood_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Planos e assinaturas
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  billing_period ENUM('MONTHLY','YEARLY') NOT NULL DEFAULT 'MONTHLY',
  max_listings INT NULL,
  features JSON NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  agency_id INT NULL,
  plan_id INT NOT NULL,
  status ENUM('ACTIVE','PENDING','CANCELED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  started_at DATETIME NULL,
  expires_at DATETIME NULL,
  canceled_at DATETIME NULL,
  external_id VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (user_id),
  INDEX (agency_id),
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_sub_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- VRSync — feeds (precisa existir antes de properties por causa da FK)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS feeds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agency_id INT NOT NULL,
  name VARCHAR(160) NOT NULL,
  url VARCHAR(500) NOT NULL,
  status ENUM('ACTIVE','INACTIVE','ERROR') NOT NULL DEFAULT 'ACTIVE',
  frequency_minutes INT NOT NULL DEFAULT 1440,
  last_sync_at DATETIME NULL,
  next_sync_at DATETIME NULL,
  last_run_status ENUM('RUNNING','SUCCESS','ERROR') NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (agency_id),
  CONSTRAINT fk_feed_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feed_sync_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  feed_id INT NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  status ENUM('RUNNING','SUCCESS','ERROR') NOT NULL DEFAULT 'RUNNING',
  total_found INT NOT NULL DEFAULT 0,
  total_created INT NOT NULL DEFAULT 0,
  total_updated INT NOT NULL DEFAULT 0,
  total_deactivated INT NOT NULL DEFAULT 0,
  total_unchanged INT NOT NULL DEFAULT 0,
  total_errors INT NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  INDEX (feed_id),
  CONSTRAINT fk_log_feed FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Imóveis
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL UNIQUE,
  external_code VARCHAR(64) NULL,
  origin ENUM('MANUAL','VRSYNC') NOT NULL DEFAULT 'MANUAL',
  source_feed_id INT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  listing_type ENUM('SALE','RENT') NOT NULL,
  property_type ENUM('APARTMENT','HOUSE','LAND','COMMERCIAL_ROOM','STORE','WAREHOUSE','RURAL','BUILDING','OTHER') NOT NULL,
  price_sale DECIMAL(14,2) NULL,
  price_rent DECIMAL(14,2) NULL,
  condo_fee DECIMAL(14,2) NULL,
  iptu DECIMAL(14,2) NULL,
  total_area FLOAT NULL,
  built_area FLOAT NULL,
  bedrooms INT NULL,
  suites INT NULL,
  bathrooms INT NULL,
  parking_spaces INT NULL,
  features JSON NULL,
  status ENUM('DRAFT','PUBLISHED','PAUSED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME NULL,
  deactivated_at DATETIME NULL,
  city_id INT NOT NULL,
  neighborhood_id INT NULL,
  street VARCHAR(255) NULL,
  number VARCHAR(32) NULL,
  complement VARCHAR(120) NULL,
  zip_code VARCHAR(16) NULL,
  latitude DECIMAL(10,6) NULL,
  longitude DECIMAL(10,6) NULL,
  advertiser_id INT NOT NULL,
  owner_id INT NULL,
  agent_id INT NULL,
  agency_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_title_description (title, description),
  INDEX idx_listing (city_id, listing_type, property_type, status),
  INDEX (neighborhood_id),
  INDEX (price_sale),
  INDEX (price_rent),
  INDEX (status, published_at),
  INDEX (advertiser_id),
  INDEX (agency_id),
  INDEX idx_source (origin, source_feed_id, external_code),
  CONSTRAINT fk_prop_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_prop_neighborhood FOREIGN KEY (neighborhood_id) REFERENCES neighborhoods(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_advertiser FOREIGN KEY (advertiser_id) REFERENCES users(id),
  CONSTRAINT fk_prop_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
  CONSTRAINT fk_prop_feed FOREIGN KEY (source_feed_id) REFERENCES feeds(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS property_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  url VARCHAR(500) NOT NULL,
  `order` INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  width INT NULL,
  height INT NULL,
  size_bytes INT NULL,
  origin ENUM('MANUAL','VRSYNC') NOT NULL DEFAULT 'MANUAL',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (property_id, `order`),
  CONSTRAINT fk_img_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  property_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_property (user_id, property_id),
  INDEX (property_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Contratos
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS contracts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  type ENUM('SALE','RENT') NOT NULL,
  status ENUM('DRAFT','ACTIVE','FINISHED','CANCELED') NOT NULL DEFAULT 'DRAFT',
  owner_id INT NULL,
  advertiser_id INT NULL,
  buyer_id INT NULL,
  tenant_id INT NULL,
  agent_id INT NULL,
  agency_id INT NULL,
  value DECIMAL(14,2) NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  documents JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (property_id),
  INDEX (status),
  CONSTRAINT fk_contract_property FOREIGN KEY (property_id) REFERENCES properties(id),
  CONSTRAINT fk_contract_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_advertiser FOREIGN KEY (advertiser_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_tenant FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_agent FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_contract_agency FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT NOT NULL,
  status ENUM('DRAFT','ACTIVE','FINISHED','CANCELED') NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (contract_id),
  CONSTRAINT fk_history_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Conteúdo (blog / central de ajuda) e contato
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kind ENUM('BLOG','GUIDE') NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT NOT NULL,
  content TEXT NULL,
  category VARCHAR(120) NOT NULL,
  author_name VARCHAR(120) NOT NULL,
  author_role VARCHAR(160) NULL,
  read_minutes INT NULL,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (kind, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NULL,
  subject VARCHAR(160) NULL,
  message TEXT NOT NULL,
  user_id INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
