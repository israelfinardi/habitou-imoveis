<?php
/**
 * Endpoint público e somente leitura usado pelo card de filtros flutuante
 * para atualizar a contagem de imóveis em tempo real, sem recarregar a
 * página (ex.: "Mostrar 33 imóveis").
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$params = $_GET;
if (!empty($params['cidade_nome']) && empty($params['cidade'])) {
    $params['cidade'] = resolve_city_slug($params['cidade_nome']) ?? '__nenhuma__';
}

$pdo = db();
[$whereSql, $args] = build_property_filters($params);
$stmt = $pdo->prepare('SELECT COUNT(*) ' . PROPERTY_LIST_JOIN . ' WHERE ' . $whereSql);
$stmt->execute($args);
$count = (int) $stmt->fetchColumn();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['count' => $count]);
