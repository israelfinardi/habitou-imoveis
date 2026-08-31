<?php
/**
 * Importador "melhor esforço" de anúncios do Facebook Marketplace: o
 * usuário cola o link no wizard de anúncio, e tentamos extrair título,
 * descrição, uma foto de capa e um preço estimado a partir das tags Open
 * Graph da página (as mesmas que o próprio Facebook usa para gerar preview
 * de link no WhatsApp/Messenger — por isso costumam estar disponíveis
 * mesmo sem login, ao contrário do resto da página).
 *
 * Isso NÃO é uma integração oficial: o Marketplace não tem API pública pra
 * isso, e boa parte dos anúncios exige login pra mostrar o conteúdo
 * completo — então essa função frequentemente vai falhar ou trazer só uma
 * prévia parcial. Cada falha devolve uma mensagem clara pro usuário
 * completar manualmente; nunca trava o fluxo do wizard.
 */

class FacebookImportError extends \RuntimeException {}

/** Só aceita links do próprio domínio do Facebook — nunca busca uma URL arbitrária (defesa contra SSRF). */
function facebook_import_validate_marketplace_url(string $url): string
{
    $url = trim($url);
    $parts = parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
        throw new FacebookImportError('Link inválido.');
    }
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        throw new FacebookImportError('Link inválido.');
    }
    $host = strtolower($parts['host']);
    $allowedHosts = ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'];
    if (!in_array($host, $allowedHosts, true)) {
        throw new FacebookImportError('Cole um link de um anúncio do Facebook Marketplace (facebook.com/marketplace/...).');
    }
    if (!str_contains($parts['path'] ?? '', '/marketplace/')) {
        throw new FacebookImportError('Esse link não parece ser de um anúncio do Marketplace.');
    }
    return $url;
}

/** Baixa a página com um user-agent identificado (nunca finge ser outro serviço) e limite de tamanho/tempo. */
function facebook_import_fetch_html(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; HabitouImoveisBot/1.0; +https://habitou.com.br)',
        CURLOPT_HTTPHEADER => ['Accept-Language: pt-BR,pt;q=0.9'],
        CURLOPT_RANGE => '0-2097152', // no máximo ~2MB de HTML — a página inteira não interessa, só o <head>.
    ]);
    $html = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html === false || $html === '') {
        throw new FacebookImportError('Não foi possível acessar esse link agora (' . ($error ?: "HTTP $status") . '). Tente novamente ou preencha manualmente.');
    }
    if ($status >= 400) {
        throw new FacebookImportError('O Facebook recusou o acesso a esse anúncio (HTTP ' . $status . '). Preencha os campos manualmente.');
    }
    return $html;
}

function facebook_import_extract_meta(string $html): array
{
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $meta = function (string $property) use ($xpath): ?string {
        $nodes = $xpath->query('//meta[@property="' . $property . '"]/@content');
        return $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : null;
    };
    $metaAll = function (string $property) use ($xpath): array {
        $nodes = $xpath->query('//meta[@property="' . $property . '"]/@content');
        $out = [];
        foreach ($nodes as $node) {
            $v = trim($node->nodeValue);
            if ($v !== '') {
                $out[] = $v;
            }
        }
        return $out;
    };

    return [
        'title' => $meta('og:title'),
        'description' => $meta('og:description'),
        'images' => $metaAll('og:image'),
    ];
}

/**
 * Extrai um preço em R$ de texto livre (título/descrição do anúncio) — não
 * usa xml_import_num() de includes/xml_import_parser.php porque aquela
 * função assume um campo estruturado vindo de um CRM (onde um único ponto
 * já é a casa decimal, ex.: "290000.00"); aqui o texto é escrito por gente,
 * no formato brasileiro de exibição ("R$ 350.000" = trezentos e cinquenta
 * mil, com o ponto separando milhar, não casa decimal).
 */
function facebook_import_guess_price(string $text): ?float
{
    if (!preg_match('/R\$\s?([\d.,]+)/u', $text, $match)) {
        return null;
    }
    $raw = $match[1];
    if (str_contains($raw, ',')) {
        // Tem vírgula: ponto é sempre separador de milhar, vírgula é a casa decimal.
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (preg_match('/\.\d{3}$/', $raw)) {
        // Só ponto, com exatamente 3 dígitos depois do último — preço em reais
        // não tem 3 casas decimais, então isso é separador de milhar ("350.000").
        $raw = str_replace('.', '', $raw);
    }
    // Só ponto com 1-2 dígitos depois (ex.: "850.00") já é decimal — mantém como está.
    return is_numeric($raw) ? (float) $raw : null;
}

/**
 * Reconhece "à venda" x "para alugar" em texto livre — precisa cobrir tanto
 * o substantivo (aluguel, locação) quanto o verbo (alugar, alugo), que é
 * como a maioria dos anúncios do Marketplace realmente escreve isso
 * ("Casa para alugar"), diferente do valor mais formal/fixo que um CRM
 * estruturado costuma exportar num XML.
 */
function facebook_import_guess_listing_type(string $text): string
{
    $v = mb_strtolower($text);
    $rentPatterns = ['alugar', 'aluguel', 'alugo', 'locação', 'locacao', 'temporada', 'for rent'];
    foreach ($rentPatterns as $pattern) {
        if (str_contains($v, $pattern)) {
            return 'RENT';
        }
    }
    return 'SALE';
}

/**
 * @return array{title:?string, description:?string, priceGuess:?float, listingTypeGuess:string, photoUrls:string[]}
 */
function facebook_import_scrape_listing(string $url): array
{
    $url = facebook_import_validate_marketplace_url($url);
    $html = facebook_import_fetch_html($url);
    $meta = facebook_import_extract_meta($html);

    if (!$meta['title'] && !$meta['description']) {
        // Sem nenhuma tag Open Graph: quase sempre significa que caiu numa
        // tela de login/checkpoint em vez do anúncio de verdade.
        throw new FacebookImportError('Não conseguimos ler os dados desse anúncio — o Facebook provavelmente está exigindo login para essa página. Preencha os campos manualmente.');
    }

    $combinedText = trim(($meta['title'] ?? '') . ' ' . ($meta['description'] ?? ''));
    $priceGuess = facebook_import_guess_price($combinedText);
    $listingTypeGuess = facebook_import_guess_listing_type($combinedText);

    // Facebook costuma repetir og:image (mesma foto em tamanhos diferentes) —
    // mantém só URLs únicas, e nunca mais que um punhado por importação.
    $photoUrls = array_slice(array_values(array_unique($meta['images'])), 0, 10);

    return [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'priceGuess' => $priceGuess,
        'listingTypeGuess' => $listingTypeGuess,
        'photoUrls' => $photoUrls,
    ];
}

/** Só aceita imagens hospedadas no CDN do próprio Facebook — nunca baixa uma URL arbitrária. */
function facebook_import_validate_photo_url(string $url): string
{
    $parts = parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        throw new FacebookImportError('URL de foto inválida.');
    }
    $host = strtolower($parts['host']);
    $isFacebookCdn = str_ends_with($host, '.fbcdn.net') || str_ends_with($host, '.facebook.com') || $host === 'facebook.com';
    if (!$isFacebookCdn) {
        throw new FacebookImportError('Essa foto não parece vir do Facebook.');
    }
    return $url;
}

/** Baixa uma foto do CDN do Facebook, com limite de tamanho — devolve [tmpPath, mimeType] ou lança FacebookImportError. */
function facebook_import_download_photo(string $url): array
{
    $url = facebook_import_validate_photo_url($url);
    $tmpPath = tempnam(sys_get_temp_dir(), 'fbimg_');
    $fh = fopen($tmpPath, 'wb');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; HabitouImoveisBot/1.0; +https://habitou.com.br)',
        // Aborta o download assim que passar do limite — não baixa o arquivo
        // inteiro só pra descartar depois.
        CURLOPT_NOPROGRESS => false,
        CURLOPT_PROGRESSFUNCTION => function ($resource, $expectedDown, $actualDown) {
            return $actualDown > UPLOAD_MAX_BYTES ? 1 : 0;
        },
    ]);
    $ok = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    fclose($fh);

    $size = is_file($tmpPath) ? filesize($tmpPath) : 0;
    if (!$ok || $status >= 400 || $size === 0) {
        @unlink($tmpPath);
        throw new FacebookImportError('Não foi possível baixar essa foto.');
    }
    if ($size > UPLOAD_MAX_BYTES) {
        @unlink($tmpPath);
        throw new FacebookImportError('Foto maior que o limite permitido (8MB).');
    }

    $mime = mime_content_type($tmpPath) ?: $contentType;
    return [$tmpPath, $mime];
}
