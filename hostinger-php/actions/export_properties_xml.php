<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
require_once __DIR__ . '/../includes/xml_export.php';

$user = require_login();
$properties = attach_xml_export_data(list_properties_for_advertiser($user));

$xml = build_properties_xml($properties);

header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: attachment; filename="meus-imoveis-habitou.xml"');
header('Content-Length: ' . strlen($xml));
echo $xml;
