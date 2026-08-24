<?php
/**
 * Modal global de "Entrar ou cadastrar-se" — estilo Airbnb: um único botão
 * no header abre o modal por cima da página atual (fundo desfocado), e uma
 * etapa por tela em vez do formulário inteiro de uma vez (mesma ideia do
 * wizard de anúncio em includes/property_wizard.php, só que compacto e em
 * modal em vez de página cheia).
 *
 * É UM ÚNICO <form>, cujo `action` é trocado via JS (assets/js/auth-modal.js)
 * pra login.php ou cadastro.php dependendo do que a primeira etapa descobre
 * (actions/auth_check_email.php diz se aquele e-mail já tem conta) — no
 * fim, o submit é um POST normal (não AJAX), processado pelo mesmo código
 * que já existia, então toda validação de servidor (senha, CRECI, CNPJ,
 * captcha, rate limit) continua valendo exatamente como antes.
 *
 * Os IDs aqui usam o prefixo "authm-" de propósito: como esse modal fica
 * incluído em toda página (inclusive login.php/cadastro.php, que mantêm o
 * formulário de página inteira original como fallback sem JS), não pode
 * colidir com os ids que aquelas páginas já usam.
 */
function render_auth_modal(): void
{
    ?>
    <div id="authm-overlay" class="fixed inset-0 z-[300] hidden items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="authm-title">
      <div id="authm-backdrop" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
      <div id="authm-panel" class="relative flex max-h-[92vh] w-full max-w-[480px] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="relative flex shrink-0 items-center justify-center border-b border-brand-border px-4 py-4">
          <button type="button" id="authm-back" class="absolute left-3 flex h-9 w-9 items-center justify-center rounded-full hover:bg-brand-bg-subtle" aria-label="Voltar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <span id="authm-title" class="text-sm font-semibold">Entrar ou cadastrar-se</span>
          <button type="button" id="authm-close" class="absolute right-3 flex h-9 w-9 items-center justify-center rounded-full hover:bg-brand-bg-subtle" aria-label="Fechar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
          <form id="authm-form" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" id="authm-redirect" value="">
            <!-- Único campo realmente enviado como "password" — login e cadastro
                 usam a mesma chave no POST, então os dois inputs visíveis (etapas
                 login-password e signup-password) nunca têm name="password" direto;
                 o JS espelha o valor do que estiver ativo pra cá antes de enviar. -->
            <input type="hidden" name="password" id="authm-password-sync" value="">

            <p id="authm-error" class="mb-4 hidden rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"></p>

            <!-- 1. E-mail --------------------------------------------------->
            <section class="authm-step" data-step="entry">
              <h2 class="mb-5 text-2xl font-bold">Bem-vindo(a)</h2>
              <label class="mb-1 block text-sm font-medium">E-mail</label>
              <input type="email" id="authm-email" name="email" required autocomplete="email" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="voce@email.com">
              <p id="authm-email-hint" class="mt-2 text-xs text-brand-text-secondary">Vamos verificar se você já tem conta pra te levar direto pra etapa certa.</p>
            </section>

            <!-- 2. Login: senha ----------------------------------------------->
            <section class="authm-step" data-step="login-password">
              <h2 class="mb-1 text-2xl font-bold">Digite sua senha</h2>
              <p id="authm-login-email-label" class="mb-5 text-sm text-brand-text-secondary"></p>
              <label class="mb-1 block text-sm font-medium">Senha</label>
              <input type="password" id="authm-login-password" autocomplete="current-password" class="authm-password-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
              <a href="<?= base_url('esqueci-senha.php') ?>" class="mt-2 inline-block text-xs font-semibold text-brand-primary hover:underline">Esqueci minha senha</a>
              <div class="authm-captcha-slot mt-4"></div>
            </section>

            <!-- 3. Cadastro: nome ----------------------------------------------->
            <section class="authm-step" data-step="signup-name">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Qual é o seu nome?</h2>
              <div class="mb-4">
                <label class="mb-1 block text-sm font-medium">Nome</label>
                <input type="text" name="firstName" autocomplete="given-name" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Sobrenome</label>
                <input type="text" name="lastName" autocomplete="family-name" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
              </div>
            </section>

            <!-- 4. Cadastro: senha ----------------------------------------------->
            <section class="authm-step" data-step="signup-password">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Crie uma senha</h2>
              <div class="mb-4">
                <label class="mb-1 block text-sm font-medium">Senha</label>
                <input type="password" id="authm-signup-password" autocomplete="new-password" class="authm-password-field w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
                <p class="mt-1 text-xs text-brand-text-secondary">Pelo menos 8 caracteres.</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Confirmar senha</label>
                <input type="password" name="passwordConfirmation" autocomplete="new-password" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
              </div>
            </section>

            <!-- 5. Cadastro: tipo de conta --------------------------------------->
            <section class="authm-step" data-step="signup-type">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Como você quer usar a Habitou?</h2>
              <div class="flex flex-col gap-2.5">
                <label class="authm-choice">
                  <input type="radio" name="accountType" value="" class="authm-account-type" checked>
                  <span><strong>Comprador / anunciante</strong><em>Quero buscar imóveis ou anunciar como pessoa física</em></span>
                </label>
                <label class="authm-choice">
                  <input type="radio" name="accountType" value="corretor" class="authm-account-type">
                  <span><strong>Corretor autônomo</strong><em>Tenho CRECI e anuncio por conta própria</em></span>
                </label>
                <label class="authm-choice">
                  <input type="radio" name="accountType" value="imobiliaria" class="authm-account-type">
                  <span><strong>Imobiliária</strong><em>Vou cadastrar a imobiliária e seus anúncios</em></span>
                </label>
              </div>
            </section>

            <!-- 6. Cadastro: CRECI (corretor) ------------------------------------>
            <section class="authm-step" data-step="signup-creci">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-2 text-2xl font-bold">Qual é o seu CRECI?</h2>
              <p class="mb-5 text-sm text-brand-text-secondary">Usado pra mostrar o selo de corretor verificado no seu perfil.</p>
              <input type="text" name="creci" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="Ex.: 12345-F">
            </section>

            <!-- 7. Cadastro: imobiliária ------------------------------------------>
            <section class="authm-step" data-step="signup-agency">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Sobre a imobiliária</h2>
              <div class="mb-4">
                <label class="mb-1 block text-sm font-medium">Nome da imobiliária</label>
                <input type="text" name="agencyName" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
              </div>
              <div class="mb-4 grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-sm font-medium">CNPJ</label>
                  <input type="text" name="cnpj" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Telefone comercial</label>
                  <input type="text" name="agencyPhone" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none">
                </div>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Cidade de atuação</label>
                <input type="text" name="agencyCity" class="js-city-picker w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" list="authm-cidades-imobiliaria" autocomplete="off" placeholder="Digite o nome da cidade...">
                <datalist id="authm-cidades-imobiliaria"></datalist>
              </div>
              <p class="mt-3 text-xs text-brand-text-secondary">O perfil público da imobiliária fica em análise até a aprovação do administrador do site.</p>
            </section>

            <!-- 8. Cadastro: telefone -------------------------------------------->
            <section class="authm-step" data-step="signup-phone">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Seu telefone (WhatsApp)</h2>
              <input type="tel" name="phone" autocomplete="tel" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="(47) 99999-9999">
              <div class="authm-hide-if-imobiliaria mt-4">
                <label class="mb-1 block text-sm font-medium">CNPJ <span class="font-normal text-brand-text-secondary">(opcional)</span></label>
                <input type="text" name="personalCnpj" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="Se você anuncia como pessoa jurídica">
              </div>
            </section>

            <!-- 9. Cadastro: localização ------------------------------------------>
            <section class="authm-step" data-step="signup-location">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-2 text-2xl font-bold">Onde você está?</h2>
              <p class="mb-5 text-sm text-brand-text-secondary">Opcional — ajuda a personalizar sua experiência.</p>
              <div class="mb-4 grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-sm font-medium">CEP</label>
                  <input type="text" name="zipCode" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="00000-000">
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium">Cidade</label>
                  <input type="text" name="cityLabel" class="js-city-picker w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" list="authm-cidades-pessoal" autocomplete="off" placeholder="Digite o nome da cidade...">
                  <datalist id="authm-cidades-pessoal"></datalist>
                </div>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Endereço</label>
                <input type="text" name="address" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="Rua, número, bairro">
              </div>
            </section>

            <!-- 10. Cadastro: redes sociais ---------------------------------------->
            <section class="authm-step" data-step="signup-social">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-2 text-2xl font-bold">Suas redes sociais</h2>
              <p class="mb-5 text-sm text-brand-text-secondary">Opcional — aparece no seu perfil público.</p>
              <div class="mb-4">
                <label class="mb-1 block text-sm font-medium">Instagram</label>
                <input type="text" name="instagram" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="@usuario">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Facebook</label>
                <input type="text" name="facebook" class="w-full rounded-xl border border-brand-border px-4 py-3 text-sm focus:border-brand-primary focus:outline-none" placeholder="facebook.com/usuario">
              </div>
            </section>

            <!-- 11. Cadastro: foto -------------------------------------------------->
            <section class="authm-step" data-step="signup-avatar">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-2 text-2xl font-bold">Adicione uma foto de perfil</h2>
              <p class="mb-5 text-sm text-brand-text-secondary">Opcional — deixa seu perfil mais confiável para quem for falar com você.</p>
              <div class="flex items-center gap-4">
                <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
                  <span id="authm-avatar-fallback" class="flex h-full w-full items-center justify-center text-2xl font-semibold text-brand-primary">?</span>
                  <img id="authm-avatar-preview" src="" class="hidden h-full w-full object-cover" alt="">
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">
                  <span>Escolher foto</span>
                  <input type="file" name="avatar" id="authm-avatar-input" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
              </div>
            </section>

            <!-- 12. Cadastro: revisar e criar -------------------------------------->
            <section class="authm-step" data-step="signup-review">
              <p class="authm-step-eyebrow">Criar conta</p>
              <h2 class="mb-5 text-2xl font-bold">Tudo pronto!</h2>
              <p class="mb-5 text-sm text-brand-text-secondary">Ao criar sua conta, você concorda com os <a href="<?= base_url('termos-de-uso.php') ?>" target="_blank" class="font-medium text-brand-primary hover:underline">Termos de uso</a> e a <a href="<?= base_url('politica-de-privacidade.php') ?>" target="_blank" class="font-medium text-brand-primary hover:underline">Política de privacidade</a>.</p>
              <div class="authm-captcha-slot"></div>
            </section>

            <!-- Widget do captcha existe uma única vez no DOM (evita instanciar o
                 Turnstile duas vezes) — o JS move este bloco pro .authm-captcha-slot
                 da etapa ativa (login-password ou signup-review) quando necessário. -->
            <div id="authm-captcha-widget" class="hidden"><?= render_captcha_widget() ?></div>
          </form>
        </div>

        <div class="shrink-0 border-t border-brand-border px-6 py-4">
          <div class="flex items-center justify-between gap-3">
            <button type="button" id="authm-skip" class="hidden text-sm font-semibold text-brand-text-secondary hover:underline">Pular por enquanto</button>
            <button type="button" id="authm-next" class="ml-auto rounded-xl bg-brand-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-primary-hover disabled:cursor-not-allowed disabled:opacity-50">Continuar</button>
          </div>
        </div>
      </div>
    </div>

    <style>
    .authm-step{display:none}
    .authm-step.is-active{display:block;animation:authm-fade .2s ease}
    @keyframes authm-fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
    .authm-step-eyebrow{margin-bottom:6px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#C1502E}
    .authm-choice{display:block;cursor:pointer}
    .authm-choice input{position:absolute;opacity:0;width:0;height:0}
    .authm-choice span{display:flex;flex-direction:column;gap:3px;border:1px solid #DDDDDD;border-radius:14px;padding:14px 16px;transition:.15s}
    .authm-choice strong{font-size:14.5px}
    .authm-choice em{font-style:normal;font-size:12.5px;color:#717171}
    .authm-choice:hover span{border-color:#222222}
    .authm-choice input:checked + span{border-color:#222222;border-width:2px;background:#F7F7F7}
    body.authm-open{overflow:hidden}
    </style>
    <?php
}
