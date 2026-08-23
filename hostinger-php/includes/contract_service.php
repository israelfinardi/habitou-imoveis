<?php
require_once __DIR__ . '/../config/database.php';

const CONTRACT_STATUS_LABEL = ['DRAFT' => 'Rascunho', 'ACTIVE' => 'Ativo', 'FINISHED' => 'Concluído', 'CANCELED' => 'Cancelado'];
const CONTRACT_TYPE_LABEL = ['SALE' => 'Compra e Venda', 'RENT' => 'Locação'];

/**
 * Tokens disponíveis num modelo de contrato (ex.: "{{cliente_nome}}") e o
 * texto de apoio mostrado no editor de modelos — mantidos juntos aqui pra
 * nunca dessincronizar a lista exibida ao usuário da lista realmente
 * suportada por contract_placeholder_map().
 */
const CONTRACT_PLACEHOLDER_HELP = [
    '{{imovel_titulo}}' => 'Título do anúncio',
    '{{imovel_codigo}}' => 'Código do imóvel',
    '{{imovel_tipo}}' => 'Tipo do imóvel (ex.: Apartamento)',
    '{{imovel_endereco}}' => 'Endereço completo',
    '{{imovel_bairro}}' => 'Bairro',
    '{{imovel_cidade}}' => 'Cidade/UF',
    '{{imovel_area}}' => 'Área total (m²)',
    '{{contrato_tipo}}' => 'Compra e Venda ou Locação',
    '{{contrato_valor}}' => 'Valor do contrato, em R$',
    '{{contrato_data_inicio}}' => 'Data de início',
    '{{contrato_data_fim}}' => 'Data de término',
    '{{contrato_data_atual}}' => 'Data de hoje',
    '{{cliente_nome}}' => 'Nome do cliente (comprador/locatário)',
    '{{cliente_email}}' => 'E-mail do cliente',
    '{{cliente_telefone}}' => 'Telefone do cliente',
    '{{cliente_documento}}' => 'CPF/RG do cliente, informado no contrato',
    '{{proprietario_nome}}' => 'Nome do proprietário do imóvel',
    '{{corretora_nome}}' => 'Nome da imobiliária',
    '{{corretor_nome}}' => 'Nome do corretor responsável',
    '{{corretor_creci}}' => 'CRECI do corretor responsável',
];

/**
 * Monta a lista de {{token}} => valor pra substituir no corpo de um modelo
 * na hora de gerar um contrato (contrato-novo.php/contrato-editar.php) —
 * também reaproveitado pra montar o PDF (includes/contract_pdf.php), já que
 * o PDF é gerado a partir do MESMO texto final salvo em contracts.body.
 */
function contract_placeholder_map(array $property, array $contract, ?array $client): array
{
    $pdo = db();
    $addressParts = array_filter([$property['street'] ?? null, $property['number'] ?? null, $property['complement'] ?? null]);

    // $property tanto pode vir "cru" de get_property_by_id() (só tem os IDs)
    // quanto já com neighborhood_name/city_name/state_code juntados (como
    // get_contract() devolve) — resolve pelos IDs quando eles não vierem
    // prontos, pra contract_placeholder_map() funcionar com os dois formatos.
    $neighborhoodName = $property['neighborhood_name'] ?? null;
    if ($neighborhoodName === null && !empty($property['neighborhood_id'])) {
        $stmt = $pdo->prepare('SELECT name FROM neighborhoods WHERE id = ?');
        $stmt->execute([$property['neighborhood_id']]);
        $neighborhoodName = $stmt->fetchColumn() ?: null;
    }
    $cityName = $property['city_name'] ?? null;
    $stateCode = $property['state_code'] ?? null;
    if ($cityName === null && !empty($property['city_id'])) {
        $stmt = $pdo->prepare('SELECT name, state_code FROM cities WHERE id = ?');
        $stmt->execute([$property['city_id']]);
        if ($cityRow = $stmt->fetch()) {
            $cityName = $cityRow['name'];
            $stateCode = $cityRow['state_code'];
        }
    }
    $cityLabel = trim(($cityName ?? '') . ($stateCode ? ' / ' . $stateCode : ''));

    $advertiser = null;
    if (!empty($contract['advertiser_id'])) {
        $stmt = $pdo->prepare('SELECT first_name, last_name, creci FROM users WHERE id = ?');
        $stmt->execute([$contract['advertiser_id']]);
        $advertiser = $stmt->fetch();
    }
    $owner = null;
    if (!empty($contract['owner_id'])) {
        $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
        $stmt->execute([$contract['owner_id']]);
        $owner = $stmt->fetch();
    }
    $agencyName = null;
    if (!empty($contract['agency_id'])) {
        $stmt = $pdo->prepare('SELECT name FROM agencies WHERE id = ?');
        $stmt->execute([$contract['agency_id']]);
        $agencyName = $stmt->fetchColumn() ?: null;
    }

    return [
        '{{imovel_titulo}}' => $property['title'] ?? '',
        '{{imovel_codigo}}' => $property['code'] ?? '',
        '{{imovel_tipo}}' => PROPERTY_TYPE_LABEL[$property['property_type'] ?? ''] ?? '',
        '{{imovel_endereco}}' => implode(', ', $addressParts) ?: '—',
        '{{imovel_bairro}}' => $neighborhoodName ?? '—',
        '{{imovel_cidade}}' => $cityLabel ?: '—',
        '{{imovel_area}}' => !empty($property['total_area']) ? number_format((float) $property['total_area'], 0, ',', '.') . ' m²' : '—',
        '{{contrato_tipo}}' => CONTRACT_TYPE_LABEL[$contract['type'] ?? 'SALE'] ?? '',
        '{{contrato_valor}}' => !empty($contract['value']) ? format_currency_brl($contract['value']) : '—',
        '{{contrato_data_inicio}}' => !empty($contract['start_date']) ? format_date($contract['start_date']) : '—',
        '{{contrato_data_fim}}' => !empty($contract['end_date']) ? format_date($contract['end_date']) : '—',
        '{{contrato_data_atual}}' => format_date(date('Y-m-d')),
        '{{cliente_nome}}' => $client ? trim($client['first_name'] . ' ' . $client['last_name']) : '—',
        '{{cliente_email}}' => $client['email'] ?? '—',
        '{{cliente_telefone}}' => $client['phone'] ?? $client['whatsapp'] ?? '—',
        '{{cliente_documento}}' => $contract['client_document'] ?? '—',
        '{{proprietario_nome}}' => $owner ? trim($owner['first_name'] . ' ' . $owner['last_name']) : ($advertiser ? trim($advertiser['first_name'] . ' ' . $advertiser['last_name']) : '—'),
        '{{corretora_nome}}' => $agencyName ?? '—',
        '{{corretor_nome}}' => $advertiser ? trim($advertiser['first_name'] . ' ' . $advertiser['last_name']) : '—',
        '{{corretor_creci}}' => $advertiser['creci'] ?? '—',
    ];
}

function render_contract_body(string $templateBody, array $property, array $contract, ?array $client): string
{
    return strtr($templateBody, contract_placeholder_map($property, $contract, $client));
}

// ---------------------------------------------------------------------
// Modelos de contrato
// ---------------------------------------------------------------------

/**
 * Modelos visíveis pro usuário: globais (redigidos pelo admin), os da
 * própria imobiliária, e os pessoais dele — nunca os pessoais de outra
 * pessoa nem os de outra imobiliária.
 */
function list_contract_templates_for_user(array $user, ?string $transactionType = null, bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM contract_templates WHERE (is_global = 1 OR created_by = :uid' . (!empty($user['agency_id']) ? ' OR agency_id = :aid' : '') . ')';
    $params = ['uid' => $user['id']];
    if (!empty($user['agency_id'])) {
        $params['aid'] = $user['agency_id'];
    }
    if (!$includeInactive) {
        $sql .= ' AND is_active = 1';
    }
    if ($transactionType) {
        $sql .= ' AND transaction_type = :type';
        $params['type'] = $transactionType;
    }
    $sql .= ' ORDER BY name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_contract_template(int $id, array $user): ?array
{
    $stmt = db()->prepare('SELECT * FROM contract_templates WHERE id = ?');
    $stmt->execute([$id]);
    $template = $stmt->fetch();
    if (!$template) {
        return null;
    }
    $visible = !empty($template['is_global']) || (int) $template['created_by'] === (int) $user['id']
        || (!empty($template['agency_id']) && $template['agency_id'] == ($user['agency_id'] ?? null));
    return $visible ? $template : null;
}

function create_contract_template(array $user, array $input): int
{
    $name = trim((string) ($input['name'] ?? ''));
    $body = trim((string) ($input['body'] ?? ''));
    $type = in_array($input['transactionType'] ?? '', ['SALE', 'RENT'], true) ? $input['transactionType'] : 'SALE';
    if ($name === '' || $body === '') {
        throw new \RuntimeException('Preencha o nome e o texto do modelo.');
    }
    $isGlobal = $user['role'] === 'ADMIN' && !empty($input['isGlobal']) ? 1 : 0;
    $agencyId = (!$isGlobal && !empty($user['agency_id']) && in_array($user['role'], AGENCY_ROLES, true) && !empty($input['scopeAgency'])) ? $user['agency_id'] : null;

    $stmt = db()->prepare('INSERT INTO contract_templates (name, transaction_type, body, is_global, agency_id, created_by)
        VALUES (?,?,?,?,?,?)');
    $stmt->execute([$name, $type, $body, $isGlobal, $agencyId, $user['id']]);
    return (int) db()->lastInsertId();
}

function update_contract_template(int $id, array $user, array $input): void
{
    $template = get_contract_template($id, $user);
    if (!$template || !can_manage_contract_template($user, $template)) {
        throw new \RuntimeException('Você não pode editar este modelo.');
    }
    $name = trim((string) ($input['name'] ?? ''));
    $body = trim((string) ($input['body'] ?? ''));
    $type = in_array($input['transactionType'] ?? '', ['SALE', 'RENT'], true) ? $input['transactionType'] : $template['transaction_type'];
    if ($name === '' || $body === '') {
        throw new \RuntimeException('Preencha o nome e o texto do modelo.');
    }
    $isGlobal = $user['role'] === 'ADMIN' ? (!empty($input['isGlobal']) ? 1 : 0) : (int) $template['is_global'];

    db()->prepare('UPDATE contract_templates SET name = ?, transaction_type = ?, body = ?, is_global = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([$name, $type, $body, $isGlobal, $id]);
}

function set_contract_template_active(int $id, array $user, bool $active): void
{
    $template = get_contract_template($id, $user);
    if (!$template || !can_manage_contract_template($user, $template)) {
        throw new \RuntimeException('Você não pode alterar este modelo.');
    }
    db()->prepare('UPDATE contract_templates SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$active ? 1 : 0, $id]);
}

// ---------------------------------------------------------------------
// Contratos
// ---------------------------------------------------------------------

/** Busca o cliente (comprador/locatário) pelo e-mail — precisa já ter conta. */
function find_client_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, phone, whatsapp FROM users WHERE email = ? AND status = "ACTIVE"');
    $stmt->execute([trim($email)]);
    return $stmt->fetch() ?: null;
}

function create_contract(array $actor, array $input): int
{
    $property = get_property_by_id((int) $input['propertyId']);
    if (!$property) {
        throw new \RuntimeException('Imóvel não encontrado.');
    }
    if (!can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode criar contratos para este imóvel.');
    }

    $type = $property['listing_type'] === 'RENT' ? 'RENT' : 'SALE';

    $client = null;
    if (!empty($input['clientEmail'])) {
        $client = find_client_by_email($input['clientEmail']);
        if (!$client) {
            throw new \RuntimeException('Não encontramos nenhum cliente cadastrado com esse e-mail. Peça para o cliente criar uma conta em ' . base_url('cadastro.php') . ' primeiro.');
        }
    }

    $templateId = !empty($input['templateId']) ? (int) $input['templateId'] : null;
    $body = trim((string) ($input['body'] ?? ''));
    if ($templateId && $body === '') {
        $template = get_contract_template($templateId, $actor);
        if ($template) {
            $draftContract = [
                'type' => $type, 'value' => $input['value'] ?? null, 'start_date' => $input['startDate'] ?? null,
                'end_date' => $input['endDate'] ?? null, 'client_document' => $input['clientDocument'] ?? null,
                'advertiser_id' => $property['advertiser_id'], 'owner_id' => $property['owner_id'], 'agency_id' => $property['agency_id'],
            ];
            $body = render_contract_body($template['body'], $property, $draftContract, $client);
        }
    }

    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO contracts
        (property_id, type, status, owner_id, advertiser_id, buyer_id, tenant_id, agent_id, agency_id, value, start_date, end_date, template_id, body, client_document)
        VALUES (?,?,"DRAFT",?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $property['id'], $type, $property['owner_id'], $property['advertiser_id'],
        $type === 'SALE' ? ($client['id'] ?? null) : null, $type === 'RENT' ? ($client['id'] ?? null) : null,
        $property['agent_id'], $property['agency_id'],
        $input['value'] ?: null, $input['startDate'] ?: null, $input['endDate'] ?: null,
        $templateId, $body ?: null, $input['clientDocument'] ?: null,
    ]);
    $contractId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,"DRAFT",?)')
        ->execute([$contractId, $input['note'] ?: 'Contrato criado.']);
    return $contractId;
}

function update_contract(int $id, array $actor, array $input): void
{
    $stmt = db()->prepare('SELECT * FROM contracts WHERE id = ?');
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    if (!$contract) {
        throw new \RuntimeException('Contrato não encontrado.');
    }
    if (!can_manage_contract($actor, $contract)) {
        throw new \RuntimeException('Você não pode editar este contrato.');
    }
    if (in_array($contract['status'], ['FINISHED', 'CANCELED'], true)) {
        throw new \RuntimeException('Este contrato está ' . mb_strtolower(CONTRACT_STATUS_LABEL[$contract['status']]) . ' e não pode mais ser editado.');
    }

    $client = null;
    if (!empty($input['clientEmail'])) {
        $client = find_client_by_email($input['clientEmail']);
        if (!$client) {
            throw new \RuntimeException('Não encontramos nenhum cliente cadastrado com esse e-mail. Peça para o cliente criar uma conta em ' . base_url('cadastro.php') . ' primeiro.');
        }
    }

    $pdo = db();
    $pdo->prepare('UPDATE contracts SET value = ?, start_date = ?, end_date = ?, body = ?, client_document = ?,
        buyer_id = ?, tenant_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([
            $input['value'] ?: null, $input['startDate'] ?: null, $input['endDate'] ?: null,
            trim((string) ($input['body'] ?? '')) ?: null, $input['clientDocument'] ?: null,
            $contract['type'] === 'SALE' ? ($client['id'] ?? $contract['buyer_id']) : null,
            $contract['type'] === 'RENT' ? ($client['id'] ?? $contract['tenant_id']) : null,
            $id,
        ]);
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,?,?)')
        ->execute([$id, $contract['status'], 'Contrato editado.']);
}

function list_contracts_for_user(array $actor): array
{
    $pdo = db();
    if ($actor['role'] === 'ADMIN') {
        $stmt = $pdo->query('SELECT c.*, p.title AS property_title FROM contracts c JOIN properties p ON p.id = c.property_id ORDER BY c.updated_at DESC');
        return $stmt->fetchAll();
    }
    $stmt = $pdo->prepare('SELECT c.*, p.title AS property_title FROM contracts c JOIN properties p ON p.id = c.property_id
        WHERE c.owner_id = :uid OR c.advertiser_id = :uid OR c.buyer_id = :uid OR c.tenant_id = :uid OR c.agent_id = :uid OR c.agency_id = :aid
        ORDER BY c.updated_at DESC');
    $stmt->execute(['uid' => $actor['id'], 'aid' => $actor['agency_id'] ?? 0]);
    return $stmt->fetchAll();
}

function get_contract(int $id, array $actor): ?array
{
    $stmt = db()->prepare('SELECT c.*, p.title AS property_title, p.code, p.property_type, p.street, p.number, p.complement,
            p.neighborhood_id, p.total_area, n.name AS neighborhood_name, ci.name AS city_name, ci.state_code
        FROM contracts c
        JOIN properties p ON p.id = c.property_id
        LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
        JOIN cities ci ON ci.id = p.city_id
        WHERE c.id = ?');
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    if (!$contract) {
        return null;
    }
    $authorized = $actor['role'] === 'ADMIN'
        || in_array($actor['id'], [$contract['owner_id'], $contract['advertiser_id'], $contract['buyer_id'], $contract['tenant_id'], $contract['agent_id']], true)
        || (!empty($contract['agency_id']) && $contract['agency_id'] == ($actor['agency_id'] ?? null));
    if (!$authorized) {
        throw new \RuntimeException('Você não tem acesso a este contrato.');
    }

    $histStmt = db()->prepare('SELECT * FROM contract_history WHERE contract_id = ? ORDER BY created_at DESC');
    $histStmt->execute([$id]);
    $contract['history'] = $histStmt->fetchAll();

    $clientId = $contract['type'] === 'SALE' ? $contract['buyer_id'] : $contract['tenant_id'];
    $contract['client'] = null;
    if ($clientId) {
        $stmt = db()->prepare('SELECT id, first_name, last_name, email, phone, whatsapp FROM users WHERE id = ?');
        $stmt->execute([$clientId]);
        $contract['client'] = $stmt->fetch() ?: null;
    }

    return $contract;
}

function update_contract_status(int $id, string $status, ?string $note, array $actor): void
{
    if (!array_key_exists($status, CONTRACT_STATUS_LABEL)) {
        throw new \RuntimeException('Status inválido.');
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE id = ?');
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    if (!$contract) {
        throw new \RuntimeException('Contrato não encontrado.');
    }
    if (!can_manage_contract($actor, $contract)) {
        throw new \RuntimeException('Você não pode alterar este contrato.');
    }
    $pdo->prepare('UPDATE contracts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$status, $id]);
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,?,?)')->execute([$id, $status, $note]);
}

/** Registra o PDF assinado enviado pelo cliente/corretor — ver actions/upload_contract_document.php. */
function set_contract_signed_document(int $id, array $actor, string $url): void
{
    $stmt = db()->prepare('SELECT * FROM contracts WHERE id = ?');
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    if (!$contract) {
        throw new \RuntimeException('Contrato não encontrado.');
    }
    if (!can_view_contract($actor, $contract)) {
        throw new \RuntimeException('Você não tem acesso a este contrato.');
    }
    $pdo = db();
    $pdo->prepare('UPDATE contracts SET signed_document_url = ?, signed_document_uploaded_at = CURRENT_TIMESTAMP,
        signed_document_uploaded_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([$url, $actor['id'], $id]);
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,?,?)')
        ->execute([$id, $contract['status'], 'Documento assinado enviado por ' . trim($actor['first_name'] . ' ' . $actor['last_name']) . '.']);
}
