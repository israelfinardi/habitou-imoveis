<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json']);
    exit;
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf']);
    exit;
}

$action = $body['action'] ?? '';
$fields = is_array($body['fields'] ?? null) ? $body['fields'] : [];

// Só aceita chaves conhecidas do formulário — o corpo da requisição vem do
// wizard no navegador, então tratamos como entrada não confiável.
$allowedKeys = [
    'title', 'description', 'listingType', 'propertyType', 'priceSale', 'priceRent',
    'condoFee', 'iptu', 'totalArea', 'builtArea', 'bedrooms', 'suites', 'bathrooms',
    'parkingSpaces', 'features', 'cidade', 'bairro', 'street', 'number', 'complement',
    'zipCode', 'latitude', 'longitude', 'contactPhone', 'contactEmail', 'contactWhatsapp',
];
$fields = array_intersect_key($fields, array_flip($allowedKeys));

try {
    switch ($action) {
        case 'create':
            $input = array_merge([
                'title' => '', 'description' => '', 'listingType' => 'SALE', 'propertyType' => 'APARTMENT',
                'priceSale' => null, 'priceRent' => null, 'condoFee' => null, 'iptu' => null,
                'totalArea' => null, 'builtArea' => null, 'bedrooms' => null, 'suites' => null,
                'bathrooms' => null, 'parkingSpaces' => null, 'features' => [],
                'cidade' => '', 'bairro' => '', 'street' => '', 'number' => '', 'complement' => '',
                'zipCode' => '', 'latitude' => null, 'longitude' => null,
                'contactPhone' => '', 'contactEmail' => '', 'contactWhatsapp' => '',
            ], $fields);
            if (!$input['cidade'] || !$input['bairro']) {
                throw new \InvalidArgumentException('Selecione a cidade e o bairro do imóvel.');
            }
            if (!array_key_exists($input['propertyType'], PROPERTY_TYPE_LABEL)) {
                throw new \InvalidArgumentException('Tipo de imóvel inválido.');
            }
            if (!in_array($input['listingType'], ['SALE', 'RENT'], true)) {
                throw new \InvalidArgumentException('Transação inválida.');
            }
            if (trim((string) $input['title']) === '') {
                $input['title'] = trim(PROPERTY_TYPE_LABEL[$input['propertyType']] . ' no bairro ' . $input['bairro']);
            }
            $id = create_property($input, $user);
            echo json_encode(['ok' => true, 'id' => $id]);
            break;

        case 'update':
            $id = (int) ($body['id'] ?? 0);
            $property = get_property_by_id($id);
            if (!$property || !can_manage_property($user, $property)) {
                throw new \RuntimeException('Você não pode editar este imóvel.');
            }
            $input = array_merge(property_row_to_input($property), $fields);
            if (trim((string) $input['title']) === '') {
                throw new \InvalidArgumentException('Informe um título para o anúncio.');
            }
            if (mb_strlen(trim((string) $input['title'])) < 10) {
                throw new \InvalidArgumentException('O título deve ter pelo menos 10 caracteres.');
            }
            update_property($id, $input, $user);
            echo json_encode(['ok' => true]);
            break;

        case 'publish':
            $id = (int) ($body['id'] ?? 0);
            $property = get_property_by_id($id);
            if (!$property || !can_manage_property($user, $property)) {
                throw new \RuntimeException('Você não pode publicar este imóvel.');
            }
            set_property_status($id, 'publish', $user);
            $property = get_property_by_id($id);
            echo json_encode(['ok' => true, 'href' => property_href($property)]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'unknown_action']);
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
