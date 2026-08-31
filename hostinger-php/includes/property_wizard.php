<?php
/**
 * Wizard de "Anunciar imóvel" em várias etapas, uma pergunta por tela —
 * mesmo padrão do fluxo "Configure seu anúncio" do Airbnb (barra de
 * progresso no rodapé, voltar/avançar, uma etapa focada por vez), mas com
 * o conteúdo de cada etapa mapeado para o nosso modelo real de dados
 * (venda/aluguel de imóvel via corretor/imobiliária/proprietário — não
 * reserva por diária). Por isso ficam de fora pedaços do fluxo original
 * que só fazem sentido pra hospedagem por diária (reserva instantânea,
 * desconto semanal/mensal, ajuste de fim de semana, calendário, "primeiro
 * hóspede"): não têm nenhum dado real por trás no nosso banco, e simular
 * esses toggles sem função real seria só decoração enganosa.
 *
 * Todas as etapas ficam no DOM da mesma página; assets/js/property-wizard.js
 * controla qual aparece, valida cada uma antes de liberar "Avançar" e só
 * fala com o servidor nos pontos que já existem hoje: cria o rascunho
 * (actions/property_draft.php) ao chegar na etapa de fotos, atualiza campo
 * a campo nas etapas seguintes, e publica no fim.
 */
function render_property_wizard(): void
{
    ?>
    <div id="wizard" class="flex min-h-[calc(100vh-64px)] flex-col" data-csrf="<?= e(csrf_token()) ?>">
      <header class="flex shrink-0 items-center justify-between border-b border-brand-border px-4 py-3 sm:px-8">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="flex h-9 w-9 items-center justify-center rounded-full hover:bg-brand-bg-subtle" aria-label="Sair">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </a>
        <span id="wizard-step-label" class="text-xs font-semibold text-brand-text-secondary"></span>
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="text-xs font-semibold underline hover:text-brand-primary">Salvar e sair</a>
      </header>

      <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6 sm:py-16">

          <!-- 1. Endereço ---------------------------------------------------->
          <section class="wizard-step" data-step="endereco">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 1 de 5 · Sobre o imóvel</p>
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Qual é o endereço do imóvel?</h1>
            <p class="mb-6 text-brand-text-secondary">Busque o endereço, ou clique/arraste o pino no mapa — cidade, bairro, rua e CEP são preenchidos automaticamente. Você também pode preencher tudo manualmente.</p>

            <div class="mb-6 rounded-xl border border-dashed border-brand-border bg-brand-bg-subtle p-4">
              <p class="mb-1 text-sm font-semibold">Já tem esse imóvel anunciado no Facebook Marketplace?</p>
              <p class="mb-3 text-xs text-brand-text-secondary">Cole o link do anúncio — tentamos importar título, descrição, preço e fotos automaticamente. Nem sempre funciona (o Facebook às vezes exige login para mostrar o anúncio), e você sempre pode revisar e completar tudo antes de publicar.</p>
              <div class="flex gap-2">
                <input type="url" id="wz-fb-import-url" placeholder="https://www.facebook.com/marketplace/item/..." class="w-full rounded-lg border border-brand-border px-3 py-2.5 text-sm">
                <button type="button" id="wz-fb-import-btn" class="shrink-0 rounded-lg bg-brand-text px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90">Importar</button>
              </div>
              <p id="wz-fb-import-status" class="mt-2 text-xs text-brand-text-secondary"></p>
            </div>

            <div class="mb-4 flex gap-2">
              <input type="text" id="wz-address-search" placeholder="Buscar endereço (rua, bairro, cidade)..." class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm">
              <button type="button" id="wz-address-search-btn" class="shrink-0 rounded-xl border border-brand-border px-4 text-sm font-semibold hover:border-brand-primary">Buscar</button>
            </div>
            <p id="wz-address-status" class="mb-4 text-xs text-brand-text-secondary"></p>

            <div class="mb-6">
              <p class="mb-2 text-xs font-medium text-brand-text-secondary">Ou clique no mapa (ou arraste o pino) para marcar o local exato</p>
              <div id="property-map" class="h-72 w-full rounded-xl border border-brand-border"></div>
              <p id="property-map-status" class="mt-2 text-xs text-brand-text-secondary">Nenhum ponto marcado ainda.</p>
            </div>

            <div class="mb-4">
              <label class="mb-1 block text-sm font-medium">Cidade</label>
              <input type="text" id="js-cidade-input" class="js-city-picker w-full rounded-xl border border-brand-border px-4 py-3 text-sm" list="cidades-datalist-wizard" autocomplete="off" placeholder="Digite o nome da cidade...">
              <datalist id="cidades-datalist-wizard"></datalist>
            </div>
            <div class="mb-4">
              <label class="mb-1 block text-sm font-medium">Bairro</label>
              <input type="text" id="js-bairro-input" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div class="mb-4 sm:col-span-2"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Rua</label><input type="text" id="js-street-input" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm"></div>
              <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Número</label><input type="text" id="js-number-input" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm"></div>
              <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Complemento</label><input type="text" id="js-complement-input" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm"></div>
            </div>
            <div class="mb-4 max-w-[200px]"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">CEP</label><input type="text" id="js-zip-input" class="w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm"></div>
            <input type="hidden" id="js-lat-input" value="">
            <input type="hidden" id="js-lng-input" value="">
          </section>

          <!-- 2. Tipo de imóvel -------------------------------------------->
          <section class="wizard-step" data-step="tipo">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 1 de 5 · Sobre o imóvel</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight">Qual desses descreve melhor o seu imóvel?</h1>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <?php foreach (PROPERTY_TYPE_SLUG as $type => $slug): ?>
                <label class="filtro-tipo-card">
                  <input type="radio" name="wz-tipo" value="<?= e($type) ?>" class="wz-field" data-field="propertyType">
                  <span>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= CATEGORY_ICONS[$type] ?></svg>
                    <em><?= e(PROPERTY_TYPE_LABEL[$type]) ?></em>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </section>

          <!-- 3. Transação ---------------------------------------------------->
          <section class="wizard-step" data-step="transacao">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 1 de 5 · Sobre o imóvel</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight">O que você quer fazer com esse imóvel?</h1>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <label class="wizard-choice-card">
                <input type="radio" name="wz-transacao" value="SALE" class="wz-field" data-field="listingType" checked>
                <span>
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  <strong>Vender</strong>
                  <em>Anunciar para venda</em>
                </span>
              </label>
              <label class="wizard-choice-card">
                <input type="radio" name="wz-transacao" value="RENT" class="wz-field" data-field="listingType">
                <span>
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                  <strong>Alugar</strong>
                  <em>Anunciar para locação</em>
                </span>
              </label>
            </div>
          </section>

          <!-- 4. Detalhes ---------------------------------------------------->
          <section class="wizard-step" data-step="detalhes">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 1 de 5 · Sobre o imóvel</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight">Compartilhe alguns detalhes básicos sobre o imóvel</h1>
            <div class="flex flex-col divide-y divide-brand-border rounded-xl border border-brand-border px-4">
              <?php
              $steppers = ['bedrooms' => 'Quartos', 'suites' => 'Suítes', 'bathrooms' => 'Banheiros', 'parkingSpaces' => 'Vagas de garagem'];
              foreach ($steppers as $field => $label):
              ?>
                <div class="wz-stepper flex items-center justify-between py-4" data-field="<?= e($field) ?>">
                  <span class="text-sm font-medium"><?= e($label) ?></span>
                  <div class="flex items-center gap-4">
                    <button type="button" class="wz-stepper-dec flex h-8 w-8 items-center justify-center rounded-full border border-brand-border text-lg leading-none hover:border-brand-primary disabled:cursor-not-allowed disabled:opacity-30" disabled>&minus;</button>
                    <span class="wz-stepper-value w-4 shrink-0 text-center text-sm">0</span>
                    <button type="button" class="wz-stepper-inc flex h-8 w-8 items-center justify-center rounded-full border border-brand-border text-lg leading-none hover:border-brand-primary">+</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
              <div><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Área total (m²)</label><input type="number" min="0" step="0.01" id="wz-totalArea" class="wz-field w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm" data-field="totalArea"></div>
              <div><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Área construída (m²)</label><input type="number" min="0" step="0.01" id="wz-builtArea" class="wz-field w-full rounded-xl border border-brand-border px-3 py-2.5 text-sm" data-field="builtArea"></div>
            </div>
          </section>

          <!-- 5. Comodidades ---------------------------------------------------->
          <section class="wizard-step" data-step="comodidades">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 2 de 5 · Destaque o imóvel</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight">Quais comodidades o imóvel oferece?</h1>
            <?php foreach (FEATURE_GROUPS as $group => $items): ?>
              <div class="mb-6">
                <h3 class="mb-3 text-sm font-semibold text-brand-text-secondary"><?= e($group) ?></h3>
                <div class="flex flex-wrap gap-2">
                  <?php foreach ($items as $item): ?>
                    <label class="filtro-pill">
                      <input type="checkbox" name="wz-feature" value="<?= e($item) ?>" class="wz-field" data-field="features">
                      <span><?= e($item) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </section>

          <!-- 6. Fotos ---------------------------------------------------------->
          <section class="wizard-step" data-step="fotos">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 2 de 5 · Destaque o imóvel</p>
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Adicione fotos do imóvel</h1>
            <p class="mb-6 text-brand-text-secondary">Você precisa de pelo menos 5 fotos para publicar. Arraste para reordenar — a primeira é a foto de capa.</p>
            <div id="wz-photo-drop" class="mb-4 flex min-h-[160px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-brand-border p-6 text-center hover:border-brand-primary">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-brand-text-secondary"><path d="M12 16V4M6 10l6-6 6 6"/><path d="M4 20h16"/></svg>
              <span class="text-sm font-semibold">Arraste as fotos aqui ou clique para escolher</span>
              <span class="text-xs text-brand-text-secondary">JPG, PNG ou WEBP, até 8MB cada</span>
              <input type="file" id="wz-photo-input" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
            </div>
            <p id="wz-photo-status" class="mb-3 text-xs text-brand-text-secondary"></p>
            <div id="wz-photo-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
          </section>

          <!-- 7. Título ---------------------------------------------------------->
          <section class="wizard-step" data-step="titulo">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 3 de 5 · Título e descrição</p>
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Agora, vamos dar um título ao anúncio</h1>
            <p class="mb-6 text-brand-text-secondary">Títulos curtos e diretos funcionam melhor. Você pode alterar depois.</p>
            <textarea id="wz-title" maxlength="120" rows="3" class="wz-field w-full resize-none rounded-xl border border-brand-border px-4 py-3 text-lg" data-field="title" placeholder="Ex.: Apartamento 2 quartos com vista mar no Centro"></textarea>
            <p class="mt-1 text-right text-xs text-brand-text-secondary"><span id="wz-title-count">0</span>/120 · mínimo 10 caracteres</p>
          </section>

          <!-- 8. Descrição ---------------------------------------------------->
          <section class="wizard-step" data-step="descricao">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 3 de 5 · Título e descrição</p>
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Crie sua descrição</h1>
            <p class="mb-6 text-brand-text-secondary">Explique o que faz esse imóvel especial: cômodos, acabamentos, vista, proximidade de comércio, transporte.</p>
            <textarea id="wz-description" rows="8" class="wz-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm" data-field="description" placeholder="Descreva o imóvel..."></textarea>
          </section>

          <!-- 9. Preço ---------------------------------------------------------->
          <section class="wizard-step" data-step="preco">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 4 de 5 · Preço</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight">Agora, defina o preço</h1>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div id="wz-price-sale-wrap" class="hidden">
                <label class="mb-1 block text-sm font-medium">Preço de venda</label>
                <div class="flex items-center rounded-xl border border-brand-border px-4 py-3">
                  <span class="mr-1 text-sm text-brand-text-secondary">R$</span>
                  <input type="number" min="0" step="0.01" id="wz-priceSale" class="wz-field w-full border-0 p-0 text-lg outline-none" data-field="priceSale">
                </div>
              </div>
              <div id="wz-price-rent-wrap" class="hidden">
                <label class="mb-1 block text-sm font-medium">Preço de aluguel (mensal)</label>
                <div class="flex items-center rounded-xl border border-brand-border px-4 py-3">
                  <span class="mr-1 text-sm text-brand-text-secondary">R$</span>
                  <input type="number" min="0" step="0.01" id="wz-priceRent" class="wz-field w-full border-0 p-0 text-lg outline-none" data-field="priceRent">
                </div>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Condomínio</label>
                <div class="flex items-center rounded-xl border border-brand-border px-4 py-3">
                  <span class="mr-1 text-sm text-brand-text-secondary">R$</span>
                  <input type="number" min="0" step="0.01" id="wz-condoFee" class="wz-field w-full border-0 p-0 text-lg outline-none" data-field="condoFee">
                </div>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">IPTU (anual)</label>
                <div class="flex items-center rounded-xl border border-brand-border px-4 py-3">
                  <span class="mr-1 text-sm text-brand-text-secondary">R$</span>
                  <input type="number" min="0" step="0.01" id="wz-iptu" class="wz-field w-full border-0 p-0 text-lg outline-none" data-field="iptu">
                </div>
              </div>
            </div>
          </section>

          <!-- 10. Contato ---------------------------------------------------------->
          <section class="wizard-step" data-step="contato">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-primary">Etapa 5 de 5 · Contato e revisão</p>
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Contato deste anúncio</h1>
            <p class="mb-6 text-brand-text-secondary">Opcional — deixe em branco para usar o telefone, e-mail e WhatsApp do seu perfil. Preencha só se este anúncio específico deve usar um contato diferente.</p>
            <div class="mb-4"><label class="mb-1 block text-sm font-medium">Telefone</label><input type="text" id="wz-contactPhone" class="wz-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm" data-field="contactPhone" placeholder="(00) 00000-0000"></div>
            <div class="mb-4"><label class="mb-1 block text-sm font-medium">WhatsApp</label><input type="text" id="wz-contactWhatsapp" class="wz-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm" data-field="contactWhatsapp" placeholder="(00) 00000-0000"></div>
            <div class="mb-4"><label class="mb-1 block text-sm font-medium">E-mail</label><input type="email" id="wz-contactEmail" class="wz-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm" data-field="contactEmail" placeholder="contato@..."></div>
          </section>

          <!-- 11. Revisar e publicar ---------------------------------------------->
          <section class="wizard-step" data-step="revisar">
            <h1 class="mb-2 text-3xl font-extrabold leading-tight">Eba! É hora de publicar o anúncio</h1>
            <p class="mb-6 text-brand-text-secondary">Confira as informações antes de publicar. Depois de publicado, o imóvel aparece na busca e nos filtros do site.</p>
            <div id="wz-review-card" class="overflow-hidden rounded-2xl border border-brand-border"></div>
          </section>

        </div>
      </div>

      <footer class="shrink-0 border-t border-brand-border px-4 py-4 sm:px-8">
        <div class="mx-auto flex w-full max-w-2xl items-center justify-between">
          <button type="button" id="wizard-back" class="rounded-lg px-4 py-2.5 text-sm font-semibold underline hover:text-brand-primary disabled:opacity-0">Voltar</button>
          <button type="button" id="wizard-next" class="rounded-xl bg-brand-text px-6 py-3 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">Avançar</button>
        </div>
        <p id="wizard-error" class="mx-auto mt-3 hidden w-full max-w-2xl rounded-lg bg-red-50 px-3 py-2 text-center text-sm text-red-700"></p>
      </footer>
    </div>

    <style>
    .wizard-step{display:none}
    .wizard-step.is-active{display:block;animation:wz-fade .25s ease}
    @keyframes wz-fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
    .wizard-choice-card{display:block;cursor:pointer}
    .wizard-choice-card input{position:absolute;opacity:0;width:0;height:0}
    .wizard-choice-card span{display:flex;flex-direction:column;gap:8px;border:1px solid #DDDDDD;border-radius:16px;padding:22px;transition:.15s}
    .wizard-choice-card strong{font-size:16px}
    .wizard-choice-card em{font-style:normal;font-size:13px;color:#717171}
    .wizard-choice-card:hover span{border-color:#222222}
    .wizard-choice-card input:checked + span{border-color:#222222;border-width:2px;background:#F7F7F7}
    .wz-photo-tile{position:relative;aspect-ratio:4/3;overflow:hidden;border-radius:12px;border:1px solid #DDDDDD;cursor:grab}
    .wz-photo-tile img{width:100%;height:100%;object-fit:cover}
    .wz-photo-tile.dragging{opacity:.4}
    .wz-photo-cover-badge{position:absolute;left:6px;top:6px;border-radius:999px;background:#222;color:#fff;font-size:10px;font-weight:700;padding:3px 8px}
    .wz-photo-remove{position:absolute;right:6px;top:6px;display:flex;height:24px;width:24px;align-items:center;justify-content:center;border-radius:999px;background:rgba(0,0,0,.6);color:#fff}
    </style>
    <?php
}
