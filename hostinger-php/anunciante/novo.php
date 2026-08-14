<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_wizard.php';

$user = require_login();

$pageTitle = 'Anunciar imóvel';
require __DIR__ . '/../includes/header.php';
?>
<?php render_property_wizard(); ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset_url('assets/js/property-location-map.js') ?>"></script>
<script src="<?= asset_url('assets/js/property-wizard.js') ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
