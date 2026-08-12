<?php
require_once __DIR__ . '/includes/bootstrap.php';
$sections = ['Links Eletrônicos e Referências ao Habitou Imóveis', 'Facilidades Oferecidas pelo Habitou Imóveis', 'Responsabilidades do Habitou Imóveis', 'Conteúdo do Habitou Imóveis', 'Conteúdo Incluído por Você no Habitou Imóveis', 'Direitos Autorais, Propriedade Intelectual e Marcas', 'Política de Privacidade'];
$pageTitle = 'Termos de uso';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-8 text-3xl font-bold">Termos de uso</h1>
  <div class="space-y-4 text-sm leading-relaxed">
    <p>O presente termo regula as condições gerais de utilização do website "Habitou Imóveis" (www.habitou.com.br), plataforma destinada a corretores e imobiliárias registrados junto ao CRECI-SC para divulgação de imóveis em Santa Catarina.</p>
    <h2 class="pt-4 text-lg font-bold">Definições</h2>
    <p>Para os fins destes Termos de Uso, as seguintes definições serão adotadas:</p>
    <p><strong>Termos de uso:</strong> são os termos contidos nesta página.</p>
    <p><strong>Habitou Imóveis:</strong> website com endereço http://www.habitou.com.br.</p>
    <p><strong>Você:</strong> é qualquer pessoa que acessa ou utiliza o Habitou Imóveis.</p>
    <h2 class="pt-4 text-lg font-bold">Leitura e Aceitação</h2>
    <p>Antes de acessar ou utilizar qualquer parte do Habitou Imóveis, VOCÊ deverá ler com atenção estes Termos. O uso do Habitou Imóveis, ou acesso a este, implicará que VOCÊ leu, concordou e aceitou cumprir com os termos, condições e advertências aqui estabelecidos.</p>
    <h2 class="pt-4 text-lg font-bold">1. Limitações e Uso Adequado</h2>
    <p>O acesso e a utilização do Habitou Imóveis são oferecidos a VOCÊ unicamente para o seu uso pessoal e não comercial. Entre outros fatores, VOCÊ concorda que não poderá: modificar, copiar, distribuir, transmitir, exibir, reproduzir, publicar, licenciar, criar trabalhos derivados ou vender qualquer informação, software ou banco de dados obtidos pelo Habitou Imóveis; enviar informações falsas, enganosas, ofensivas ou que violem a lei; efetuar cadastro utilizando informações falsas ou de terceiros; transmitir códigos maliciosos ou vírus; acessar áreas restritas sem autorização; ou realizar qualquer atividade que viole direitos de propriedade intelectual.</p>
    <?php foreach ($sections as $i => $title): ?>
      <h2 class="pt-4 text-lg font-bold"><?= $i + 2 ?>. <?= e($title) ?></h2>
    <?php endforeach; ?>
    <h2 class="pt-4 text-lg font-bold">Termos Gerais</h2>
    <p>O Habitou Imóveis, de tempos em tempos, poderá modificar estes Termos de Uso, sem aviso prévio. VOCÊ entende e aceita que deverá rever estes Termos de Uso periodicamente.</p>
    <p class="pt-6 text-xs text-brand-text-secondary">Dúvidas sobre estes termos podem ser enviadas através da nossa <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">página de contato</a>.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
