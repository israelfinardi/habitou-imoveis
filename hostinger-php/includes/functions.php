<?php

function slugify(string $text): string
{
    $text = trim($text);
    // remove acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text);
    return $text ?: 'sem-nome';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Extrai cidade/estado (nome por extenso) do valor do .js-city-picker
 * (formato "Cidade (UF)" produzido pelo autocomplete de
 * assets/js/location-picker.js) — usado para preencher users.city/state e
 * agencies.city/state. Não confundir com property_mutations.php's
 * parse_city_label(), que resolve slug de cidade pra busca de imóveis e tem
 * assinatura/retorno diferentes.
 * @return array{city: ?string, state: ?string}
 */
function parse_city_state_label(string $cityLabel): array
{
    if (!preg_match('/^(.+?)\s*\(([A-Za-z]{2})\)\s*$/u', trim($cityLabel), $m)) {
        return ['city' => null, 'state' => null];
    }
    return ['city' => trim($m[1]), 'state' => BRAZIL_STATES[mb_strtoupper($m[2])] ?? null];
}

function format_currency_brl($value): string
{
    if ($value === null || $value === '') {
        return 'Consulte';
    }
    return 'R$ ' . number_format((float) $value, 0, ',', '.');
}

function format_price_short($value, bool $isRent = false): string
{
    if ($value === null || $value === '') {
        return 'Consulte';
    }
    $value = (float) $value;
    if ($isRent) {
        return 'R$ ' . number_format($value, 0, ',', '.') . '/mês';
    }
    if ($value >= 1000000) {
        return 'R$ ' . number_format($value / 1000000, 1, ',', '.') . ' mi';
    }
    if ($value >= 1000) {
        return 'R$ ' . number_format($value / 1000, 0, ',', '.') . ' mil';
    }
    return 'R$ ' . number_format($value, 0, ',', '.');
}

function format_area($value): string
{
    if (!$value) {
        return '';
    }
    return number_format((float) $value, 0, ',', '.') . ' m²';
}

function format_date($value): string
{
    if (!$value) {
        return '';
    }
    $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
    return $ts ? date('d/m/Y', $ts) : '';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function base_url(string $path = ''): string
{
    $base = rtrim(defined('APP_URL') ? APP_URL : '', '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Como base_url(), mas para JS/CSS: acrescenta ?v=<mtime do arquivo> para
 * invalidar o cache do navegador automaticamente sempre que o arquivo mudar
 * (sem isso, um deploy novo pode continuar rodando o JS antigo em cache).
 */
function asset_url(string $path): string
{
    $file = __DIR__ . '/../' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : time();
    return base_url($path) . '?v=' . $version;
}

function current_url_with(array $overrides): string
{
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    unset($params['pagina']);
    $qs = http_build_query($params);
    return $_SERVER['PHP_SELF'] . ($qs ? '?' . $qs : '');
}

/**
 * Alterna um valor dentro de um parâmetro de array da querystring (ex.:
 * caracteristicas[]) preservando os demais filtros já aplicados — usado
 * pelos chips de filtro rápido (estilo Airbnb) de imoveis.php.
 */
function toggle_array_param_url(string $path, array $get, string $param, string $value): string
{
    $current = (array) ($get[$param] ?? []);
    if (in_array($value, $current, true)) {
        $current = array_values(array_diff($current, [$value]));
    } else {
        $current[] = $value;
    }
    $params = $get;
    if ($current) {
        $params[$param] = $current;
    } else {
        unset($params[$param]);
    }
    unset($params['pagina']);
    $qs = http_build_query($params);
    return base_url($path . ($qs ? '?' . $qs : ''));
}

function json_decode_safe(?string $value, $default = [])
{
    if (!$value) {
        return $default;
    }
    $decoded = json_decode($value, true);
    return $decoded === null ? $default : $decoded;
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** Gera um código interno sequencial simples (HB-1000, HB-1001, ...). */
function next_property_code(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT code FROM properties ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $num = 1000;
    if ($last && preg_match('/(\d+)$/', $last, $m)) {
        $num = (int) $m[1] + 1;
    }
    return 'HB-' . $num;
}
