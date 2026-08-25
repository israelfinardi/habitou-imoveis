<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Termos do contrato de assinatura';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-8 text-3xl font-bold">Termos do contrato de assinatura</h1>
  <div class="space-y-4 text-sm leading-relaxed">
    <p>Este documento complementa os <a href="<?= base_url('termos-de-uso.php') ?>" class="text-brand-primary hover:underline">Termos de uso</a> do Habitou Imóveis e se aplica especificamente ao Anunciante (corretor autônomo ou imobiliária) que contrata um dos planos pagos de assinatura oferecidos na página de <a href="<?= base_url('planos.php') ?>" class="text-brand-primary hover:underline">Planos</a>, referentes aos serviços de tecnologia, hospedagem de anúncios e publicidade online prestados pelo portal.</p>

    <h2 class="pt-4 text-lg font-bold">1. Objeto</h2>
    <p>O plano contratado dá ao Anunciante direito de publicar e manter ativo, simultaneamente, o número de anúncios de imóveis previsto no plano escolhido, além dos demais recursos de destaque e visibilidade descritos na página de Planos no momento da contratação.</p>

    <h2 class="pt-4 text-lg font-bold">2. Cobrança e Recorrência</h2>
    <p>Ao assinar um plano, o Anunciante concorda com a cobrança recorrente no formato escolhido (mensal, semestral ou anual), processada com segurança através do Mercado Pago. A cobrança se repete automaticamente a cada ciclo até que a assinatura seja cancelada.</p>

    <h2 class="pt-4 text-lg font-bold">3. Cancelamento</h2>
    <p>O cancelamento pode ser feito a qualquer momento pelo painel do Anunciante, na área de Planos, interrompendo a cobrança do próximo ciclo. Não há fidelidade mínima. Valores já pagos referentes ao ciclo em curso não são reembolsados proporcionalmente, salvo disposição legal em contrário.</p>

    <h2 class="pt-4 text-lg font-bold">4. Inadimplência e Suspensão</h2>
    <p>O atraso no pagamento do plano de assinatura resultará na suspensão automática da exibição dos anúncios do Anunciante após 5 (cinco) dias de inadimplência, permanecendo os dados salvos no painel até a regularização ou o cancelamento definitivo da conta.</p>

    <h2 class="pt-4 text-lg font-bold">5. Nota Fiscal</h2>
    <p>A nota fiscal referente aos serviços de tecnologia e publicidade prestados pelo Habitou Imóveis é emitida contra o CPF ou CNPJ informado pelo Anunciante no momento da contratação do plano.</p>

    <h2 class="pt-4 text-lg font-bold">6. Isenção de Responsabilidade pela Negociação</h2>
    <p>A assinatura de um plano contrata exclusivamente os serviços de tecnologia, hospedagem e publicidade descritos acima. Ela não representa, em nenhuma hipótese, intermediação, corretagem ou qualquer forma de participação do Habitou Imóveis nas negociações realizadas entre o Anunciante e os Usuários interessados em seus imóveis, conforme detalhado nos <a href="<?= base_url('termos-de-uso.php') ?>" class="text-brand-primary hover:underline">Termos de uso</a>.</p>

    <p class="pt-6 text-xs text-brand-text-secondary">Dúvidas sobre cobrança ou sobre o seu plano podem ser enviadas através da nossa <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">página de contato</a>.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
