<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/vrsync_sync.php';

$user = require_login();
if (!in_array($user['role'], ['AGENCY_ADMIN', 'AGENT', 'ADMIN'], true) || !$user['agency_id']) {
    http_response_code(403);
    exit('Acesso negado.');
}
verify_csrf();

$action = $_POST['do'] ?? '';
$pdo = db();

function assert_feed_access(array $feed, array $user): void
{
    if ($user['role'] !== 'ADMIN' && (int) $feed['agency_id'] !== (int) $user['agency_id']) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

switch ($action) {
    case 'create':
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $freq = (int) ($_POST['frequency_minutes'] ?? 1440);
        if ($name && filter_var($url, FILTER_VALIDATE_URL)) {
            $stmt = $pdo->prepare('INSERT INTO feeds (agency_id, name, url, frequency_minutes, next_sync_at) VALUES (?,?,?,?,NOW())');
            $stmt->execute([$user['agency_id'], $name, $url, $freq]);
            redirect(base_url('imobiliaria/feed.php?id=' . $pdo->lastInsertId()));
        }
        redirect(base_url('imobiliaria/feeds.php'));
        break;

    case 'sync_now':
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('SELECT * FROM feeds WHERE id = ?');
        $stmt->execute([$id]);
        $feed = $stmt->fetch();
        if ($feed) {
            assert_feed_access($feed, $user);
            run_feed_sync($id);
        }
        redirect(base_url('imobiliaria/feed.php?id=' . $id));
        break;

    case 'toggle_status':
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('SELECT * FROM feeds WHERE id = ?');
        $stmt->execute([$id]);
        $feed = $stmt->fetch();
        if ($feed) {
            assert_feed_access($feed, $user);
            $newStatus = $feed['status'] === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $pdo->prepare('UPDATE feeds SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        }
        redirect(base_url('imobiliaria/feed.php?id=' . $id));
        break;

    case 'delete':
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('SELECT * FROM feeds WHERE id = ?');
        $stmt->execute([$id]);
        $feed = $stmt->fetch();
        if ($feed) {
            assert_feed_access($feed, $user);
            $pdo->prepare('DELETE FROM feeds WHERE id = ?')->execute([$id]);
        }
        redirect(base_url('imobiliaria/feeds.php'));
        break;

    default:
        redirect(base_url('imobiliaria/feeds.php'));
}
