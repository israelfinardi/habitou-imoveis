<?php
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    // Defesa em profundidade contra XSS/clickjacking, complementar ao
    // escapamento de saída (e()) já usado em toda a aplicação e ao CSRF
    // token nos formulários — nenhuma dessas duas coisas depende destes
    // cabeçalhos, mas eles reduzem o estrago de uma falha que passe batido.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/property_repo.php';
require_once __DIR__ . '/property_card.php';
require_once __DIR__ . '/category_pills.php';
require_once __DIR__ . '/account_nav.php';
require_once __DIR__ . '/subscription_service.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/recommendation_service.php';
require_once __DIR__ . '/home_blocks_service.php';
require_once __DIR__ . '/csv_export.php';
