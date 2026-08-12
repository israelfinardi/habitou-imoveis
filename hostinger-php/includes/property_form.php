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

    <div class="grid grid-cols-2 gap-3">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Cidade</label>
        <input type="text" name="cidade" class="js-city-picker w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
               list="cidades-datalist-imovel" autocomplete="off" required
               placeholder="Digite o nome da cidade..." value="<?= $val('city_label') ?>">
        <datalist id="cidades-datalist-imovel"></datalist>
        <p class="mt-1 text-xs text-brand-text-secondary">Qualquer cidade do Brasil. Digite e escolha uma opção da lista (formato "Cidade (UF)").</p>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Bairro</label>
        <input name="bairro" required value="<?= $val('neighborhood_name') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Rua</label><input name="street" value="<?= $val('street') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Número</label><input name="number" value="<?= $val('number') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Complemento</label><input name="complement" value="<?= $val('complement') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">CEP</label><input name="zipCode" value="<?= $val('zip_code') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Latitude (opcional)</label><input type="number" step="0.000001" name="latitude" value="<?= $val('latitude') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Longitude (opcional)</label><input type="number" step="0.000001" name="longitude" value="<?= $val('longitude') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
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
    ];
}
