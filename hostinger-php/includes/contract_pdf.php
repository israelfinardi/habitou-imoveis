<?php
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/contract_service.php';

/**
 * FPDF só tem as 14 fontes-base do PDF (Helvetica/Times/Courier), sem
 * suporte nativo a UTF-8 — precisam de texto em CP1252 (Windows-1252), que
 * cobre os acentos do português. Toda string que vai pra dentro do PDF
 * passa por aqui antes.
 */
function pdf_txt(?string $s): string
{
    $s = (string) $s;
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        if ($converted !== false) {
            return $converted;
        }
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($s, 'CP1252', 'UTF-8');
    }
    return $s;
}

/**
 * Gera e envia (Content-Disposition: attachment) o PDF do contrato a partir
 * do texto já salvo em contracts.body (o mesmo texto renderizado a partir
 * do modelo na criação/edição — ver contract_service.php). Encerra o
 * script (FPDF::Output('D', ...) já envia os headers e o binário).
 */
function stream_contract_pdf(array $contract): void
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    $typeLabel = CONTRACT_TYPE_LABEL[$contract['type']] ?? 'Imóvel';
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->MultiCell(0, 8, pdf_txt('CONTRATO DE ' . mb_strtoupper($typeLabel, 'UTF-8')), 0, 'C');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(120, 120, 120);
    $subtitle = 'Habitou Imóveis · Contrato #' . $contract['id'] . (!empty($contract['code']) ? ' · Imóvel ' . $contract['code'] : '');
    $pdf->Cell(0, 6, pdf_txt($subtitle), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(8);

    $pdf->SetFont('Helvetica', '', 11);
    $body = trim((string) ($contract['body'] ?? ''));
    if ($body === '') {
        $pdf->SetTextColor(150, 60, 40);
        $pdf->MultiCell(0, 6, pdf_txt('Este contrato ainda não tem um texto definido. Edite o contrato e escolha um modelo, ou escreva o texto manualmente.'), 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
    } else {
        foreach (explode("\n", $body) as $line) {
            if (trim($line) === '') {
                $pdf->Ln(4);
                continue;
            }
            $pdf->MultiCell(0, 6, pdf_txt($line), 0, 'J');
        }
    }

    $pdf->Ln(16);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, pdf_txt('Local e data: ______________________________, ' . date('d/m/Y')), 0, 1);
    $pdf->Ln(18);

    $colWidth = 80;
    $leftX = 20;
    $rightX = 20 + $colWidth + 10;
    $lineY = $pdf->GetY();
    $pdf->Line($leftX, $lineY, $leftX + $colWidth, $lineY);
    $pdf->Line($rightX, $lineY, $rightX + $colWidth, $lineY);
    $pdf->SetY($lineY + 2);
    $pdf->SetX($leftX);
    $pdf->Cell($colWidth, 6, pdf_txt('Imobiliária / corretor'), 0, 0, 'C');
    $pdf->SetX($rightX);
    $pdf->Cell($colWidth, 6, pdf_txt('Cliente'), 0, 1, 'C');

    $filename = 'contrato-' . $contract['id'] . (!empty($contract['code']) ? '-' . $contract['code'] : '') . '.pdf';
    $pdf->Output('D', $filename);
}
