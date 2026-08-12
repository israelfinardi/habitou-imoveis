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
