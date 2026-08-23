<?php
/**
 * Recebe a localização precisa (GPS do navegador, com a permissão do
 * visitante) e grava como o sinal de localização da sessão atual — ver
 * assets/js/home-recommendations.js. Endpoint público (funciona também
 * para visitante anônimo, é isso que alimenta a Fase 1/cold start do
 * algoritmo de recomendação), então em vez de CSRF a proteção contra abuso
 * é rate limit por IP: o pior que um payload forjado faz é distorcer a
 * própria recomendação de quem enviou.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/recommendation_service.php';
header('Content-Type: application/json');

rate_limit_enforce('set_location_ip', client_ip(), 30, 3600);

$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
if ($lat === false || $lat === null || $lng === false || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Coordenadas inválidas.']);
    exit;
}

rate_limit_hit('set_location_ip', client_ip());

$user = current_user();
upsert_location_signal(session_id(), $user ? (int) $user['id'] : null, $lat, $lng, 'gps');

echo json_encode(['ok' => true]);
