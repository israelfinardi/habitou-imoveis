<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Política de privacidade';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-8 text-3xl font-bold">Política de privacidade</h1>
  <div class="space-y-4 text-sm leading-relaxed">
    <p>O presente documento estabelece a política de privacidade referente às condições gerais de utilização do website "Habitou Imóveis" (www.habitou.com.br), em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD).</p>

    <h2 class="pt-4 text-lg font-bold">Definições</h2>
    <p><strong>Anunciantes:</strong> são todas as pessoas que se cadastram no Habitou Imóveis e que concluem o cadastro de anúncio de um imóvel.</p>
    <p><strong>Usuários:</strong> são todas as pessoas que acessam o Habitou Imóveis, independentemente de se cadastrarem ou não.</p>
    <p><strong>Você:</strong> são os Usuários ou Anunciantes, dependendo do contexto em que esta definição é utilizada.</p>

    <h2 class="pt-4 text-lg font-bold">1. Coleta de Dados</h2>
    <p>Coletamos dados pessoais fornecidos voluntariamente por VOCÊ nos seguintes momentos: (a) ao criar uma conta no portal (nome, e-mail, telefone e, para Anunciantes, dados profissionais como CRECI e CNPJ da imobiliária); e (b) ao preencher um formulário de interesse em um imóvel específico (Nome, E-mail e Telefone/WhatsApp). É possível visitar a maioria das seções do Habitou Imóveis sem enviar nenhuma informação pessoal identificável.</p>

    <h2 class="pt-4 text-lg font-bold">2. Finalidade e Compartilhamento</h2>
    <p>Ao preencher o formulário de contato de um imóvel específico, o Usuário concorda expressa e inequivocamente que seus dados (Nome, E-mail e Telefone) sejam transferidos e compartilhados de forma direta e automática com o corretor ou a imobiliária responsável por aquele anúncio, para fins de atendimento comercial referente ao imóvel de interesse.</p>
    <p>A partir desse momento, o Anunciante passa a ser o Controlador dos dados compartilhados para fins de atendimento comercial, isentando o Habitou Imóveis de responsabilidade sobre o uso posterior dessas informações pelo Anunciante — inclusive quanto à forma, frequência e conteúdo dos contatos que o Anunciante venha a fazer com o Usuário.</p>
    <p>O Habitou Imóveis não vende dados pessoais a terceiros e não compartilha seus dados fora do contexto acima descrito, exceto quando exigido por lei ou ordem judicial.</p>

    <h2 class="pt-4 text-lg font-bold">3. Cookies e Navegação</h2>
    <p>Utilizamos cookies essenciais de infraestrutura, necessários para o funcionamento da plataforma (login, sessão, favoritos), e ferramentas de análise estatística anônima para melhorar o desempenho das páginas e entender o tráfego de visitantes por região. Esses dados de navegação, quando agregados, não identificam VOCÊ individualmente.</p>

    <h2 class="pt-4 text-lg font-bold">4. Segurança da Informação</h2>
    <p>Utilizamos senhas com hash criptográfico (nunca armazenadas em texto puro), conexões seguras (HTTPS) e controle de acesso por papel de usuário para proteger os dados armazenados em nossa base.</p>

    <h2 class="pt-4 text-lg font-bold">5. Direitos do Titular (LGPD)</h2>
    <p>O Usuário pode solicitar a qualquer momento, através da nossa <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">página de contato</a>, a confirmação, correção, portabilidade ou exclusão dos seus dados pessoais da nossa base de registros de leads. No entanto, o Usuário compreende que, uma vez compartilhados com um Anunciante conforme o item 2 acima, deverá solicitar a exclusão desses dados diretamente ao Anunciante que já o tiver contatado, já que o Habitou Imóveis deixa de ter controle sobre a cópia que o Anunciante recebeu.</p>

    <p class="pt-6 text-xs text-brand-text-secondary">Esta Política de Privacidade deve ser lida em conjunto com os nossos <a href="<?= base_url('termos-de-uso.php') ?>" class="text-brand-primary hover:underline">Termos de uso</a>. Dúvidas podem ser enviadas através da nossa <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">página de contato</a>.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
