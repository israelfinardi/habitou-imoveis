<?php
/**
 * Copie este arquivo para config.php e preencha com os dados reais do seu
 * banco na Hostinger (hPanel > Bancos de Dados > MySQL Databases).
 * NUNCA suba o config.php de produção para um repositório público.
 */

// --- Banco de dados ---------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'u000000000_habitou');
define('DB_USER', 'u000000000_habitou');
define('DB_PASS', 'troque-esta-senha');
define('DB_CHARSET', 'utf8mb4');

// --- Aplicação -----------------------------------------------------------
define('APP_URL', 'https://www.habitou.com.br');   // sem barra no final
define('APP_NAME', 'Habitou Imóveis');

// Gere uma string longa e aleatória (ex.: comando `openssl rand -hex 32`)
define('AUTH_SECRET', 'troque-por-um-valor-aleatorio-longo');

// Protege /cron/vrsync_sync.php contra chamadas públicas não autorizadas
define('CRON_SECRET', 'troque-por-outro-valor-aleatorio');

// --- E-mail (SMTP) — usado em recuperação de senha e contato -----------
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'no-reply@habitou.com.br');
define('SMTP_FROM_NAME', 'Habitou Imóveis');

// --- Uploads -------------------------------------------------------------
define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024); // 8MB

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // mantenha desligado em produção
ini_set('log_errors', '1');
