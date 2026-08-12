<?php
require_once __DIR__ . '/../config/database.php';

function create_contract(array $actor, array $input): int
{
    $property = get_property_by_id((int) $input['propertyId']);
    if (!$property) {
        throw new \RuntimeException('Imóvel não encontrado.');
    }
    $canCreate = $actor['role'] === 'ADMIN' || (int) $property['advertiser_id'] === (int) $actor['id']
        || (!empty($property['agency_id']) && (int) $actor['agency_id'] === (int) $property['agency_id']);
    if (!$canCreate) {
        throw new \RuntimeException('Você não pode criar contratos para este imóvel.');
    }

    $type = $property['listing_type'] === 'RENT' ? 'RENT' : 'SALE';
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO contracts (property_id, type, status, owner_id, advertiser_id, agent_id, agency_id, value, start_date, end_date)
        VALUES (?,?,"DRAFT",?,?,?,?,?,?,?)');
    $stmt->execute([
        $property['id'], $type, $property['owner_id'], $property['advertiser_id'], $property['agent_id'], $property['agency_id'],
        $input['value'] ?: null, $input['startDate'] ?: null, $input['endDate'] ?: null,
    ]);
    $contractId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,"DRAFT",?)')
        ->execute([$contractId, $input['note'] ?: 'Contrato criado.']);
    return $contractId;
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
    $stmt = db()->prepare('SELECT c.*, p.title AS property_title FROM contracts c JOIN properties p ON p.id = c.property_id WHERE c.id = ?');
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

    return $contract;
}

function update_contract_status(int $id, string $status, ?string $note, array $actor): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE id = ?');
    $stmt->execute([$id]);
    $contract = $stmt->fetch();
    if (!$contract) {
        throw new \RuntimeException('Contrato não encontrado.');
    }
    $authorized = $actor['role'] === 'ADMIN' || $contract['advertiser_id'] == $actor['id'] || (!empty($contract['agency_id']) && $contract['agency_id'] == ($actor['agency_id'] ?? null));
    if (!$authorized) {
        throw new \RuntimeException('Você não pode alterar este contrato.');
    }
    $pdo->prepare('UPDATE contracts SET status = ? WHERE id = ?')->execute([$status, $id]);
    $pdo->prepare('INSERT INTO contract_history (contract_id, status, note) VALUES (?,?,?)')->execute([$id, $status, $note]);
}
