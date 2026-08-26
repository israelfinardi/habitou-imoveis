<?php
require_once __DIR__ . '/includes/bootstrap.php';

$articles = db()->query('SELECT * FROM articles WHERE kind = "GUIDE" ORDER BY published_at DESC')->fetchAll();
$categories = db()->query('SELECT category, COUNT(*) AS c FROM articles WHERE kind = "GUIDE" GROUP BY category')->fetchAll();

$faqGroups = [
    'Como o Habitou Imóveis funciona' => [
        ['Como funciona o Habitou Imóveis?', 'O Habitou Imóveis é um portal de buscas: corretores, imobiliárias e proprietários publicam anúncios de imóveis à venda ou para locação, e qualquer visitante pode buscar por cidade, tipo de imóvel, faixa de preço e outras características, sem precisar se cadastrar. Nós não somos uma imobiliária nem intermediamos a negociação — só aproximamos quem procura de quem anuncia. Veja os detalhes nos <a href="' . base_url('termos-de-uso.php') . '" class="text-brand-primary hover:underline">Termos de uso</a>.'],
        ['O Habitou Imóveis cobra comissão sobre a venda ou o aluguel?', 'Não. O Habitou Imóveis não recebe comissão sobre nenhuma negociação. Nossa única fonte de receita são os planos de assinatura pagos pelos Anunciantes para publicar mais imóveis simultaneamente.'],
        ['Preciso criar uma conta para buscar imóveis?', 'Não. A busca, os filtros e a visualização de anúncios funcionam sem cadastro. Você só precisa de uma conta para favoritar imóveis, anunciar um imóvel ou gerenciar um perfil de corretor/imobiliária.'],
    ],
    'Cadastro e tipos de conta' => [
        ['Quais tipos de conta existem?', 'Três: Comprador/anunciante particular (para buscar, favoritar e, se quiser, anunciar um imóvel próprio sem CRECI), Corretor autônomo (exige número de CRECI) e Imobiliária (exige nome e CNPJ da empresa).'],
        ['Sou proprietário e quero anunciar meu imóvel sem corretor — posso?', 'Sim. Ao se cadastrar, escolha a opção "Comprador/anunciante" e publique seu imóvel diretamente pelo painel, informando que você é o proprietário. Não é necessário CRECI para anunciar dessa forma.'],
        ['O Habitou Imóveis verifica o CRECI ou o CNPJ informado no cadastro?', 'O CRECI e o CNPJ são autodeclarados no momento do cadastro. O portal pode verificá-los junto aos órgãos competentes, mas a responsabilidade pela veracidade dessas informações é sempre do Anunciante. Por segurança, sempre peça ao corretor ou à imobiliária para confirmar o registro ativo diretamente com o CRECI do seu estado antes de fechar negócio.'],
        ['Como excluo minha conta?', 'Envie um pedido pela nossa <a href="' . base_url('fale-conosco.php') . '" class="text-brand-primary hover:underline">página de contato</a> informando o e-mail cadastrado. Veja como tratamos essa solicitação na <a href="' . base_url('politica-de-privacidade.php') . '" class="text-brand-primary hover:underline">Política de privacidade</a>.'],
        ['Esqueci minha senha, e agora?', 'Na tela de login, clique em "Esqueci minha senha" e siga as instruções enviadas para o seu e-mail cadastrado.'],
    ],
    'Anunciar um imóvel' => [
        ['Como anuncio um imóvel?', 'Crie uma conta (ou entre na sua), clique em "Anunciar imóvel" e preencha o passo a passo: tipo de imóvel, endereço (com localização no mapa), características, fotos e preço. O anúncio fica disponível assim que publicado, respeitando o limite de anúncios simultâneos do seu plano.'],
        ['Quantos imóveis posso anunciar de graça?', 'Existe um limite gratuito de anúncios simultâneos. Para publicar mais imóveis ao mesmo tempo, é necessário assinar um dos planos pagos na página de <a href="' . base_url('planos.php') . '" class="text-brand-primary hover:underline">Planos</a>.'],
        ['Sou imobiliária com muitos imóveis — preciso cadastrar um por um?', 'Não necessariamente. Imobiliárias podem importar toda a carteira de uma vez através de um arquivo XML no formato usado pelo mercado imobiliário (o que chamamos internamente de VRSync), disponível no painel da conta.'],
        ['Posso editar ou remover um anúncio depois de publicado?', 'Sim, a qualquer momento pelo painel "Meus anúncios". É sua responsabilidade manter os anúncios atualizados e remover ou pausar os de imóveis já vendidos ou alugados.'],
        ['Quem é responsável pelas informações e fotos do anúncio?', 'O Anunciante que o publicou (ou, no caso de importação por XML, a imobiliária responsável pelo arquivo) é o único responsável pela veracidade, legalidade e atualização do conteúdo publicado — o Habitou Imóveis não revisa manualmente cada anúncio antes da publicação.'],
    ],
    'Planos e pagamento' => [
        ['Como funciona a cobrança dos planos?', 'A cobrança é recorrente (mensal, semestral ou anual, dependendo do plano escolhido) e processada com segurança pelo Mercado Pago. Detalhes completos estão nos <a href="' . base_url('termos-assinatura.php') . '" class="text-brand-primary hover:underline">Termos do contrato de assinatura</a>.'],
        ['Como cancelo minha assinatura?', 'A qualquer momento, pelo painel da conta, na área de Planos — sem multa e sem fidelidade. O cancelamento interrompe a cobrança do próximo ciclo; o ciclo já pago continua ativo até o fim do período.'],
        ['O que acontece se eu atrasar o pagamento do plano?', 'Após 5 dias de inadimplência, seus anúncios são suspensos automaticamente (deixam de aparecer nas buscas). Os dados continuam salvos no painel até a regularização do pagamento ou o cancelamento definitivo da conta.'],
        ['O Habitou Imóveis armazena o número do meu cartão de crédito?', 'Não. Todo o pagamento é processado diretamente pelo Mercado Pago — não temos acesso nem armazenamos dados de cartão.'],
    ],
    'Contato e negociação' => [
        ['Como falo com o anunciante de um imóvel?', 'Na página do imóvel, use o botão "Conversar no WhatsApp" ou o formulário "Enviar mensagem". Em ambos os casos, você é redirecionado para uma conversa de WhatsApp com o número informado pelo Anunciante (imobiliária, corretor ou proprietário), já com uma mensagem inicial pronta.'],
        ['Por que fui redirecionado para o WhatsApp em vez de mandar uma mensagem pelo site?', 'Para agilizar o contato: a conversa acontece diretamente no WhatsApp de quem responde pelo anúncio, sem intermediação nem atraso. Isso significa que, a partir do envio, a conversa passa a ser de responsabilidade das partes envolvidas — veja o Artigo 7 dos <a href="' . base_url('termos-de-uso.php') . '" class="text-brand-primary hover:underline">Termos de uso</a>.'],
        ['O Habitou Imóveis participa da negociação, visita ou contrato?', 'Não. O portal só aproxima Usuário e Anunciante. Toda comunicação, agendamento de visita, verificação de documentação, assinatura de contrato e pagamento acontece diretamente entre as partes, fora da plataforma.'],
        ['Como funciona o "Fale conosco" do site?', 'É o canal para falar diretamente com o time do Habitou Imóveis (dúvidas, suporte, parcerias). Ao enviar, você também é redirecionado para uma conversa de WhatsApp com a nossa equipe, com a mensagem já preenchida.'],
    ],
    'Segurança e fraude' => [
        ['O Habitou Imóveis pede PIX, depósito ou transferência para reservar um imóvel?', 'Nunca. O portal jamais solicita pagamento de qualquer tipo. Se alguém pedir isso em nome do Habitou Imóveis, é uma fraude — denuncie imediatamente pela nossa <a href="' . base_url('fale-conosco.php') . '" class="text-brand-primary hover:underline">página de contato</a>.'],
        ['Como me proteger de golpes ao negociar um imóvel?', 'Exija a apresentação do CRECI do corretor (quando aplicável) e confira a matrícula e a situação legal do imóvel diretamente no Cartório de Registro de Imóveis antes de qualquer pagamento, sinal ou assinatura de contrato. Desconfie de preços muito abaixo do mercado e de quem evita chamadas de vídeo ou visitas presenciais.'],
        ['Encontrei um anúncio suspeito ou com informação falsa, o que faço?', 'Reporte pela nossa <a href="' . base_url('fale-conosco.php') . '" class="text-brand-primary hover:underline">página de contato</a>. Anunciantes com denúncias de fraude, informação falsa ou comportamento suspeito podem ter seus anúncios removidos e a conta suspensa ou banida.'],
    ],
    'Seus dados e privacidade (LGPD)' => [
        ['Quais dados o Habitou Imóveis coleta sobre mim?', 'Depende do uso: dados de navegação (cookies) para quem só busca imóveis; nome, e-mail, telefone e senha para quem cria conta; CRECI/CNPJ para corretores e imobiliárias; e os dados digitados nos formulários de contato ou "Fale conosco". Veja a lista completa na <a href="' . base_url('politica-de-privacidade.php') . '" class="text-brand-primary hover:underline">Política de privacidade</a>.'],
        ['Meus dados são compartilhados com o anunciante do imóvel?', 'Sim — mas só os que você mesmo digita no formulário de contato daquele imóvel (nome, telefone e e-mail), e só para montar a mensagem de WhatsApp que você decide enviar. A partir daí, o Anunciante passa a ser responsável por esses dados.'],
        ['Posso pedir para excluir meus dados?', 'Sim, a qualquer momento, pela nossa <a href="' . base_url('fale-conosco.php') . '" class="text-brand-primary hover:underline">página de contato</a>. Note que, se seus dados já foram compartilhados com um Anunciante via WhatsApp, a exclusão na base do Anunciante precisa ser pedida a ele diretamente.'],
        ['Como uso a localização para recomendar imóveis?', 'Se você autorizar o navegador a compartilhar sua localização, ou buscar por uma cidade específica, usamos essa informação (e o histórico de imóveis que você visualiza) só para sugerir imóveis relevantes na página inicial — nunca para outra finalidade.'],
    ],
];

$pageTitle = 'Central de ajuda';
$pageDescription = 'Perguntas frequentes e guias sobre como usar o Habitou Imóveis: cadastro, anúncios, planos, contato via WhatsApp, segurança e privacidade.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-2 text-3xl font-bold">Como podemos te ajudar?</h1>
  <p class="mb-8 text-brand-text-secondary">Não encontrou o que procura? <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">Fale com a gente</a>.</p>

  <?php if ($articles): ?>
    <div class="mb-8 flex flex-wrap gap-2">
      <?php foreach ($categories as $c): ?><span class="rounded-full border border-brand-border px-3 py-1.5 text-sm"><?= e($c['category']) ?> (<?= $c['c'] ?>)</span><?php endforeach; ?>
    </div>
    <h2 class="mb-4 text-lg font-bold">Últimos artigos</h2>
    <div class="mb-12 space-y-3">
      <?php foreach ($articles as $a): ?>
        <a href="<?= base_url('guia.php?slug=' . $a['slug']) ?>" class="block rounded-xl border border-brand-border bg-white p-4 hover:border-brand-primary">
          <span class="text-xs font-medium text-brand-text-secondary"><?= e($a['category']) ?></span>
          <p class="font-semibold"><?= e($a['title']) ?></p>
          <p class="mt-1 text-sm text-brand-text-secondary"><?= e($a['excerpt']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2 class="mb-1 text-2xl font-bold">Perguntas frequentes</h2>
  <p class="mb-6 text-sm text-brand-text-secondary">As dúvidas mais comuns sobre como o Habitou Imóveis funciona, de ponta a ponta.</p>

  <div class="space-y-8">
    <?php foreach ($faqGroups as $group => $items): ?>
      <div>
        <h3 class="mb-3 text-base font-bold text-brand-primary"><?= e($group) ?></h3>
        <div class="divide-y divide-brand-border rounded-xl border border-brand-border bg-white">
          <?php foreach ($items as [$question, $answer]): ?>
            <details class="group px-4 py-3 [&_summary::-webkit-details-marker]:hidden">
              <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-brand-text">
                <?= e($question) ?>
                <svg class="h-4 w-4 shrink-0 text-brand-text-secondary transition-transform group-open:rotate-45" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </summary>
              <p class="mt-2 text-sm leading-relaxed text-brand-text-secondary"><?= $answer /* já vem com links controlados internamente */ ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
