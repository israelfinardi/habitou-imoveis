<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';
header('Content-Type: application/json');

verify_csrf();
rate_limit_enforce('property_contact_ip', client_ip(), 20, 3600); // 20 mensagens / hora por IP

$slug = trim($_POST['slug'] ?? '');
$property = get_property_by_slug($slug);
if (!$property || $property['status'] !== 'PUBLISHED') {
    http_response_code(404);
    echo json_encode(['error' => 'Imóvel não encontrado.']);
    exit;
}

// O WhatsApp de destino é sempre recalculado aqui a partir do imóvel (nunca
// aceito do cliente) — já resolve pra quem responde pelo anúncio, seja a
// imobiliária, o corretor ou o proprietário (mesma função usada no botão
// "Conversar no WhatsApp" da página do imóvel).
$contact = property_contact_info($property);
$targetWhatsapp = $contact['whatsapp'];
if (!$targetWhatsapp) {
    http_response_code(404);
    echo json_encode(['error' => 'Este anúncio não tem WhatsApp de contato configurado.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

if (mb_strlen($name) < 2 || mb_strlen($phone) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Preencha nome, telefone e um e-mail válido.']);
    exit;
}

rate_limit_hit('property_contact_ip', client_ip());

// E-mail pro anunciante fica como registro/backup — best-effort, não bloqueia
// o fluxo principal, que agora é o WhatsApp (o anunciante pode não checar
// e-mail, mas quase sempre está no WhatsApp).
if ($contact['email']) {
    send_mail($contact['email'], 'Novo interessado no imóvel ' . $property['code'], '
        <p>Alguém demonstrou interesse no seu anúncio <strong>' . e($property['title']) . '</strong> (código ' . e($property['code']) . '):</p>
        <p><strong>Nome:</strong> ' . e($name) . '<br>
        <strong>Telefone:</strong> ' . e($phone) . '<br>
        <strong>E-mail:</strong> ' . e($email) . '</p>
        <p><a href="' . e(property_href($property)) . '">Ver anúncio</a></p>
    ');
}

$waText = "Olá! Vi o imóvel \"{$property['title']}\" (código {$property['code']}) no Habitou Imóveis e gostaria de mais informações.\n\n"
    . "Nome: {$name}\nTelefone: {$phone}\nE-mail: {$email}\n\n"
    . property_href($property);

echo json_encode(['ok' => true, 'redirect' => 'https://wa.me/55' . $targetWhatsapp . '?text=' . urlencode($waText)]);
