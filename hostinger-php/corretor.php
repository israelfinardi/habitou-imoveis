<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT u.*, a.name AS agency_name, a.slug AS agency_slug FROM users u LEFT JOIN agencies a ON a.id = u.agency_id
    WHERE u.id = ? AND u.role IN ('AGENT','AGENCY_ADMIN') AND u.status = 'ACTIVE'");
$stmt->execute([$id]);
$agent = $stmt->fetch();
if (!$agent) {
    http_response_code(404);
    $pageTitle = 'Corretor não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Corretor não encontrado</h1></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$result = list_properties([], ['p.agent_id = ?' => $id]);
$agentName = trim($agent['first_name'] . ' ' . $agent['last_name']);
$memberSince = $agent['created_at'] ? date('Y', strtotime($agent['created_at'])) : null;

$pageTitle = $agentName . ' — Corretor';
$ogImage = $agent['avatar_url'] ?? null;
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-10 sm:px-6 lg:px-8">
  <h1 class="mb-6 text-2xl font-bold">Sobre <?= e($agent['first_name']) ?></h1>
  <div class="grid grid-cols-1 gap-10 lg:grid-cols-[300px_1fr]">
    <div class="rounded-2xl border border-brand-border p-6 text-center lg:sticky lg:top-24">
      <?php if (!empty($agent['avatar_url'])): ?>
        <img src="<?= e($agent['avatar_url']) ?>" alt="" class="mx-auto h-24 w-24 rounded-full object-cover">
      <?php else: ?>
        <span class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-brand-primary text-3xl font-semibold text-white"><?= e(mb_strtoupper(mb_substr($agent['first_name'], 0, 1))) ?></span>
      <?php endif; ?>
      <p class="mt-3 text-xl font-bold"><?= e($agentName) ?></p>
      <p class="text-sm text-brand-text-secondary">Corretor(a) de imóveis</p>
      <div class="mt-5 grid grid-cols-2 divide-x divide-brand-border border-y border-brand-border py-4">
        <div><p class="text-lg font-bold"><?= $result['total'] ?></p><p class="text-xs text-brand-text-secondary">imóveis publicados</p></div>
        <div><p class="text-lg font-bold"><?= e($memberSince ?: '—') ?></p><p class="text-xs text-brand-text-secondary">no Habitou desde</p></div>
      </div>
      <div class="mt-5 flex flex-col gap-2">
        <?php if (!empty($agent['whatsapp']) || !empty($agent['phone'])): ?>
          <a href="https://wa.me/55<?= e(preg_replace('/\D/', '', $agent['whatsapp'] ?: $agent['phone'])) ?>" target="_blank" rel="noopener noreferrer" class="rounded-full bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Conversar no WhatsApp</a>
        <?php endif; ?>
        <?php if (!empty($agent['email'])): ?><a href="mailto:<?= e($agent['email']) ?>" class="rounded-full border border-brand-border px-4 py-2.5 text-sm font-semibold hover:border-brand-primary">E-mail</a><?php endif; ?>
      </div>
    </div>

    <div>
      <div class="flex flex-col gap-3 border-b border-brand-border pb-8">
        <?php if ($agent['agency_name']): ?>
          <div class="flex items-center gap-3">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-brand-text-secondary"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
            <p class="text-sm">Trabalha na <a href="<?= base_url('imobiliaria.php?slug=' . $agent['agency_slug']) ?>" class="font-semibold text-brand-primary hover:underline"><?= e($agent['agency_name']) ?></a></p>
          </div>
        <?php endif; ?>
        <?php if ($agent['creci']): ?>
          <div class="flex items-center gap-3">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-brand-green"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            <p class="text-sm">CRECI <?= e($agent['creci']) ?> verificado</p>
          </div>
        <?php endif; ?>
        <?php if (!empty($agent['service_area'])): ?>
          <div class="flex items-center gap-3">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-brand-text-secondary"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <p class="text-sm">Atua em <?= e($agent['service_area']) ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($agent['website'])): ?>
          <div class="flex items-center gap-3">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-brand-text-secondary"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
            <a href="<?= e($agent['website']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-brand-primary hover:underline"><?= e($agent['website']) ?></a>
          </div>
        <?php endif; ?>
        <?php if (!empty($agent['bio'])): ?><p class="mt-1 max-w-2xl text-sm leading-relaxed text-brand-text-secondary"><?= nl2br(e($agent['bio'])) ?></p><?php endif; ?>
      </div>

      <h2 class="mb-4 mt-8 text-lg font-bold">Imóveis de <?= e($agent['first_name']) ?> (<?= $result['total'] ?>)</h2>
      <?php render_property_grid($result['items'], $favoriteIds); ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
