<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_wizard.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$user = require_login();
if ($user['role'] !== 'ADMIN') {
    $plan = get_effective_plan_for_actor($user);
    if ($plan['max_listings'] !== null && count_active_listings_for_actor($user) >= $plan['max_listings']) {
        redirect(base_url('anunciante/imoveis.php?limite=1'));
    }
}

$pageTitle = 'Anunciar imóvel';
require __DIR__ . '/../includes/header.php';
?>
<?php render_property_wizard(); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset_url('assets/js/property-location-map.js') ?>"></script>
<script src="<?= asset_url('assets/js/property-wizard.js') ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
