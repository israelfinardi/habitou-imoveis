<?php
/**
 * Exportação de listagens em CSV — usado por toda página que lista algo
 * (meus anúncios, favoritos, planos, usuários, imóveis, imobiliárias,
 * assinaturas...). Cada página monta suas próprias colunas e
 * linhas a partir dos dados que já carregou pra exibir na tela, então o CSV
 * sempre bate com o que está sendo mostrado (incluindo filtros ativos).
 *
 * @param array<string,string> $columns  [chave_da_linha => "Cabeçalho da coluna"]
 * @param array<int,array<string,mixed>> $rows
 */
function export_csv(string $filename, array $columns, array $rows): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    // BOM UTF-8: sem isso o Excel no Windows abre acentos quebrados.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_values($columns), ';');
    foreach ($rows as $row) {
        $line = [];
        foreach (array_keys($columns) as $key) {
            $value = $row[$key] ?? '';
            $line[] = is_bool($value) ? ($value ? 'Sim' : 'Não') : (string) $value;
        }
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

/** Botão padrão "Exportar CSV" — preserva a query string atual (filtros/busca) e acrescenta export=csv. */
function render_csv_export_button(): void
{
    $params = $_GET;
    $params['export'] = 'csv';
    $href = '?' . http_build_query($params);
    echo '<a href="' . e($href) . '" class="inline-flex items-center gap-1.5 rounded-full border border-brand-border px-4 py-2 text-sm font-semibold text-brand-text hover:border-brand-primary">'
        . '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg>'
        . 'Exportar CSV</a>';
}
