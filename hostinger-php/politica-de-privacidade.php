<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Política de privacidade';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-8 text-3xl font-bold">Política de privacidade</h1>
  <div class="space-y-4 text-sm leading-relaxed">
    <p>O presente documento estabelece a política de privacidade referente às condições gerais de utilização do website "Habitou Imóveis" (www.habitou.com.br).</p>
    <h2 class="pt-4 text-lg font-bold">Definições</h2>
    <p><strong>Anunciantes:</strong> são todas as pessoas que se cadastram no Habitou Imóveis e que concluem o cadastro de anúncio de um imóvel.</p>
    <p><strong>Usuários:</strong> são todas as pessoas que acessam o Habitou Imóveis, independentemente de se cadastrarem ou não.</p>
    <p><strong>Você:</strong> são os Usuários ou Anunciantes, dependendo do contexto em que esta definição é utilizada.</p>
    <h2 class="pt-4 text-lg font-bold">Qual informação é coletada de mim?</h2>
    <p>O Habitou Imóveis coleta informações pessoais fornecidas por VOCÊ. É possível visitar a maioria das seções do Habitou Imóveis sem enviar nenhuma informação pessoal identificável. Para acesso a determinados recursos (cadastro, favoritos, publicação de anúncios), poderemos solicitar nome completo, e-mail, telefone e, para anunciantes, dados profissionais (CRECI, CNPJ da imobiliária).</p>
    <h2 class="pt-4 text-lg font-bold">Coleta automática de informações</h2>
    <p>O Habitou Imóveis rastreia determinadas informações sobre VOCÊ à medida em que VOCÊ visita e utiliza nossos serviços, com o propósito de entender melhor como a plataforma é utilizada, coletadas através de cookies.</p>
    <h2 class="pt-4 text-lg font-bold">Como as minhas informações são protegidas?</h2>
    <p>Utilizamos senhas com hash criptográfico (nunca armazenadas em texto puro), conexões seguras (HTTPS) e controle de acesso por papel de usuário.</p>
    <h2 class="pt-4 text-lg font-bold">Compartilhamento de informações</h2>
    <p>Não vendemos suas informações pessoais a terceiros. Dados de contato podem ser compartilhados com o anunciante/imobiliária responsável pelo imóvel quando VOCÊ envia uma mensagem de interesse.</p>
    <h2 class="pt-4 text-lg font-bold">Seus direitos (LGPD)</h2>
    <p>Você pode solicitar a qualquer momento a confirmação, correção, portabilidade ou eliminação dos seus dados pessoais, através da nossa <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">página de contato</a>.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
