<?php
function render_property_form(array $defaults = [], array $errors = []): void
{
    $val = fn($key, $def = '') => e((string) ($defaults[$key] ?? $def));
    $features = $defaults['features'] ?? [];
    ?>
    <div class="mb-4">
      <label class="mb-1 block text-sm font-medium">Título do anúncio</label>
      <input name="title" required value="<?= $val('title') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
    </div>
    <div class="mb-4">
      <label class="mb-1 block text-sm font-medium">Descrição</label>
      <textarea name="description" rows="5" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"><?= $val('description') ?></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Transação</label>
        <select name="listingType" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <option value="SALE" <?= ($defaults['listing_type'] ?? 'SALE') === 'SALE' ? 'selected' : '' ?>>Venda</option>
          <option value="RENT" <?= ($defaults['listing_type'] ?? '') === 'RENT' ? 'selected' : '' ?>>Aluguel</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Tipo de imóvel</label>
        <select name="propertyType" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php foreach (PROPERTY_TYPE_LABEL as $type => $label): ?>
            <option value="<?= e($type) ?>" <?= ($defaults['property_type'] ?? 'APARTMENT') === $type ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Preço venda (R$)</label><input type="number" step="0.01" name="priceSale" value="<?= $val('price_sale') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Preço aluguel (R$)</label><input type="number" step="0.01" name="priceRent" value="<?= $val('price_rent') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Condomínio (R$)</label><input type="number" step="0.01" name="condoFee" value="<?= $val('condo_fee') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">IPTU (R$)</label><input type="number" step="0.01" name="iptu" value="<?= $val('iptu') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Área total (m²)</label><input type="number" step="0.01" name="totalArea" value="<?= $val('total_area') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Área construída (m²)</label><input type="number" step="0.01" name="builtArea" value="<?= $val('built_area') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Quartos</label><input type="number" name="bedrooms" value="<?= $val('bedrooms') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Suítes</label><input type="number" name="suites" value="<?= $val('suites') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Banheiros</label><input type="number" name="bathrooms" value="<?= $val('bathrooms') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Vagas</label><input type="number" name="parkingSpaces" value="<?= $val('parking_spaces') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
      <?php foreach (COMMON_FEATURES as $f): ?>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="features[]" value="<?= e($f) ?>" <?= in_array($f, $features, true) ? 'checked' : '' ?>> <?= e($f) ?></label>
      <?php endforeach; ?>
    </div>

    <div class="mb-4">
      <label class="mb-1 block text-sm font-medium">Localização no mapa</label>
      <p class="mb-2 text-xs text-brand-text-secondary">Clique no mapa (ou arraste o pino) no local exato do imóvel — cidade, bairro e rua abaixo são preenchidos automaticamente.</p>
      <div class="mb-2 flex gap-2">
        <input type="text" id="property-map-search" placeholder="Buscar endereço para localizar no mapa..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <button type="button" id="property-map-search-btn" class="shrink-0 rounded-lg border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">Buscar</button>
      </div>
      <div id="property-map" class="h-80 w-full rounded-xl border border-brand-border"></div>
      <p id="property-map-status" class="mt-2 text-xs text-brand-text-secondary">Nenhum ponto marcado ainda.</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Cidade</label>
        <input type="text" name="cidade" id="js-cidade-input" class="js-city-picker w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
               list="cidades-datalist-imovel" autocomplete="off" required
               placeholder="Digite o nome da cidade..." value="<?= $val('city_label') ?>">
        <datalist id="cidades-datalist-imovel"></datalist>
        <p class="mt-1 text-xs text-brand-text-secondary">Qualquer cidade do Brasil. Preenchido automaticamente ao marcar o mapa, ou digite e escolha uma opção da lista (formato "Cidade (UF)").</p>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Bairro</label>
        <input name="bairro" id="js-bairro-input" required value="<?= $val('neighborhood_name') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Rua</label><input name="street" id="js-street-input" value="<?= $val('street') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Número</label><input name="number" value="<?= $val('number') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Complemento</label><input name="complement" value="<?= $val('complement') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">CEP</label><input name="zipCode" id="js-zip-input" value="<?= $val('zip_code') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Latitude</label><input type="number" step="0.000001" name="latitude" id="js-lat-input" value="<?= $val('latitude') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Longitude</label><input type="number" step="0.000001" name="longitude" id="js-lng-input" value="<?= $val('longitude') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= asset_url('assets/js/property-location-map.js') ?>"></script>

    <div class="mt-2 border-t border-brand-border pt-4">
      <label class="mb-1 block text-sm font-medium">Contato deste anúncio</label>
      <p class="mb-3 text-xs text-brand-text-secondary">Opcional — deixe em branco para usar o telefone, e-mail e WhatsApp do seu perfil. Preencha só se quiser que este anúncio específico use um contato diferente.</p>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Telefone</label><input name="contactPhone" value="<?= $val('contact_phone') ?>" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">WhatsApp</label><input name="contactWhatsapp" value="<?= $val('contact_whatsapp') ?>" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">E-mail</label><input type="email" name="contactEmail" value="<?= $val('contact_email') ?>" placeholder="contato@..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      </div>
    </div>
    <?php
}

function parse_property_form(array $post): array
{
    return [
        'title' => trim($post['title'] ?? ''),
        'description' => trim($post['description'] ?? ''),
        'listingType' => in_array($post['listingType'] ?? '', ['SALE', 'RENT'], true) ? $post['listingType'] : 'SALE',
        'propertyType' => array_key_exists($post['propertyType'] ?? '', PROPERTY_TYPE_LABEL) ? $post['propertyType'] : 'APARTMENT',
        'priceSale' => $post['priceSale'] !== '' ? (float) $post['priceSale'] : null,
        'priceRent' => $post['priceRent'] !== '' ? (float) $post['priceRent'] : null,
        'condoFee' => $post['condoFee'] !== '' ? (float) $post['condoFee'] : null,
        'iptu' => $post['iptu'] !== '' ? (float) $post['iptu'] : null,
        'totalArea' => $post['totalArea'] !== '' ? (float) $post['totalArea'] : null,
        'builtArea' => $post['builtArea'] !== '' ? (float) $post['builtArea'] : null,
        'bedrooms' => $post['bedrooms'] !== '' ? (int) $post['bedrooms'] : null,
        'suites' => $post['suites'] !== '' ? (int) $post['suites'] : null,
        'bathrooms' => $post['bathrooms'] !== '' ? (int) $post['bathrooms'] : null,
        'parkingSpaces' => $post['parkingSpaces'] !== '' ? (int) $post['parkingSpaces'] : null,
        'features' => $post['features'] ?? [],
        'cidade' => trim($post['cidade'] ?? ''),
        'bairro' => trim($post['bairro'] ?? ''),
        'street' => trim($post['street'] ?? ''),
        'number' => trim($post['number'] ?? ''),
        'complement' => trim($post['complement'] ?? ''),
        'zipCode' => trim($post['zipCode'] ?? ''),
        'latitude' => $post['latitude'] !== '' ? (float) $post['latitude'] : null,
        'longitude' => $post['longitude'] !== '' ? (float) $post['longitude'] : null,
        'contactPhone' => trim($post['contactPhone'] ?? ''),
        'contactEmail' => trim($post['contactEmail'] ?? ''),
        'contactWhatsapp' => trim($post['contactWhatsapp'] ?? ''),
    ];
}
