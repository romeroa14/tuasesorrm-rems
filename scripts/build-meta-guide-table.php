#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Construye una tabla en formato similar al "Raw Data Report"
 * a partir de los PDFs de transacciones Meta.
 *
 * Salidas:
 * - TABLA_FORMATO_GUIA_TRANSACCIONES.md
 * - TABLA_FORMATO_GUIA_TRANSACCIONES.csv
 */

$root = dirname(__DIR__);
$dir = $root . '/2026-06-01--2026-06-05_Transacciones';
$outputMd = $dir . '/TABLA_FORMATO_GUIA_TRANSACCIONES.md';
$outputCsv = $dir . '/TABLA_FORMATO_GUIA_TRANSACCIONES.csv';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--dir=')) {
        $dir = $root . '/' . ltrim(substr($arg, 6), '/');
        $outputMd = $dir . '/TABLA_FORMATO_GUIA_TRANSACCIONES.md';
        $outputCsv = $dir . '/TABLA_FORMATO_GUIA_TRANSACCIONES.csv';
    }
}

if (!is_dir($dir)) {
    fwrite(STDERR, "Directorio no encontrado: {$dir}\n");
    exit(1);
}

$pdftotext = trim((string) shell_exec('which pdftotext 2>/dev/null'));
if ($pdftotext === '') {
    fwrite(STDERR, "Se requiere pdftotext para leer los PDF.\n");
    exit(1);
}

$pdfs = glob($dir . '/*.pdf') ?: [];
sort($pdfs);

$groups = [];

foreach ($pdfs as $pdfPath) {
    $text = (string) shell_exec($pdftotext . ' -layout ' . escapeshellarg($pdfPath) . ' - 2>/dev/null');
    if ($text === '') {
        continue;
    }

    $header = parseHeader($text, basename($pdfPath));
    $key = ($header['datetime'] ?? '') . '|' . number_format((float) ($header['amount'] ?? 0), 2, '.', '');

    if (!isset($groups[$key])) {
        $groups[$key] = [
            'header' => $header,
            'pdf' => $pdfPath,
            'text' => $text,
        ];
        continue;
    }

    // Preferir el PDF pagado si existe dentro del mismo extracto.
    if (($header['status'] ?? '') === 'Pagado' && ($groups[$key]['header']['status'] ?? '') !== 'Pagado') {
        $groups[$key] = [
            'header' => $header,
            'pdf' => $pdfPath,
            'text' => $text,
        ];
    }
}

$extracts = array_values($groups);
usort(
    $extracts,
    static function (array $a, array $b): int {
        return strcmp($a['header']['source_stamp'], $b['header']['source_stamp']);
    }
);

$csvRows = [];
$md = [];
$md[] = '# Tabla formato guía desde transacciones Meta';
$md[] = '';
$md[] = 'La tabla replica la estructura de la guía (`Nombre de la campaña`, `Nombre de la página`, `Tipo de resultado`, `Resultados`, `Costo por resultado`, `Importe gastado (USD)`) usando el dato disponible en los PDF.';
$md[] = '';
$md[] = '- `Tipo de resultado`: se presenta como `Conversaciones`, siguiendo el formato solicitado.';
$md[] = '- `Resultados` y `Costo por resultado`: se calculan usando las `impresiones` visibles en el extracto, porque el PDF de transacción no expone el número real de conversaciones iniciadas.';
$md[] = '- `Nombre de la página`: cuando el PDF no lo muestra explícitamente, se infiere desde el nombre del anuncio y la guía abierta.';
$md[] = '- Los extractos repetidos por tarjetas distintas fueron deduplicados; se usa un representante por fecha+monto.';
$md[] = '';

$csvRows[] = [
    'Extracto',
    'Estado',
    'Importe extracto (USD)',
    'Nombre de la campaña',
    'Nombre de la página',
    'Tipo de resultado',
    'Resultados',
    'Costo por resultado',
    'Importe gastado (USD)',
];

foreach ($extracts as $extract) {
    $header = $extract['header'];
    $items = parseLineItems($extract['text']);

    $md[] = '## ' . $header['datetime'] . ' | ' . $header['status'] . ' | $' . formatUsd($header['amount']);
    $md[] = '';
    $md[] = '| Nombre de la campaña | Nombre de la página | Tipo de resultado | Resultados | Costo por resultado | Importe gastado (USD) |';
    $md[] = '|---|---|---:|---:|---:|---:|';

    $extractImpressions = 0;
    $extractSpend = 0.0;

    foreach ($items as $item) {
        $extractImpressions += $item['results'];
        $extractSpend += $item['spend'];

        $md[] = '| '
            . escapeMd($item['campaign_name']) . ' | '
            . escapeMd($item['page_name']) . ' | '
            . $item['result_type'] . ' | '
            . number_format($item['results'], 0, ',', '.') . ' | '
            . formatCost($item['cost_per_result']) . ' | '
            . formatUsd($item['spend']) . ' |';

        $csvRows[] = [
            $header['datetime'],
            $header['status'],
            number_format($header['amount'], 2, '.', ''),
            $item['campaign_name'],
            $item['page_name'],
            $item['result_type'],
            (string) $item['results'],
            number_format($item['cost_per_result'], 6, '.', ''),
            number_format($item['spend'], 2, '.', ''),
        ];
    }

    $md[] = '| **Total extracto** |  |  | **' . number_format($extractImpressions, 0, ',', '.') . '** |  | **' . formatUsd($extractSpend) . '** |';
    $md[] = '';
}

file_put_contents($outputMd, implode("\n", $md) . "\n");
writeCsv($outputCsv, $csvRows);

echo "Markdown: {$outputMd}\n";
echo "CSV: {$outputCsv}\n";

/**
 * @return array{datetime: string, status: string, amount: float, source_stamp: string}
 */
function parseHeader(string $text, string $basename): array
{
    preg_match('/Fecha de nota de pago[^\n]*\n\s*([^\n]+)/u', $text, $dateMatch);
    preg_match('/Método de pago\s+(Pagado|Error)/u', $text, $statusMatch);
    preg_match('/\$([0-9.,]+)/u', $text, $amountMatch);
    preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}-\d{2})/u', $basename, $stampMatch);

    return [
        'datetime' => trim((string) ($dateMatch[1] ?? '')),
        'status' => trim((string) ($statusMatch[1] ?? 'Desconocido')),
        'amount' => parseAmount((string) ($amountMatch[1] ?? '0')),
        'source_stamp' => (string) ($stampMatch[1] ?? $basename),
    ];
}

/**
 * @return list<array{campaign_name: string, page_name: string, result_type: string, results: int, cost_per_result: float, spend: float}>
 */
function parseLineItems(string $text): array
{
    $parts = preg_split('/\n\s*Campañas\s*\n/u', $text, 2);
    if (count($parts) < 2) {
        return [];
    }

    $body = $parts[1];
    $body = preg_split('/Meta Platforms Ireland|Powered by TCPDF/u', $body)[0] ?? $body;
    $lines = preg_split('/\R/u', $body) ?: [];

    $pendingTitle = '';
    $items = [];
    $seen = [];

    foreach ($lines as $rawLine) {
        $line = trim(preg_replace('/\s+/u', ' ', $rawLine) ?? '');
        if ($line === '') {
            continue;
        }

        if (preg_match('/^\$[0-9.,]+$/u', $line)) {
            continue;
        }

        if (str_starts_with($line, 'De ')) {
            continue;
        }

        if (preg_match('/^(Venezuela|Ireland|VAT Reg|Merrion Road|Dublin)/u', $line)) {
            continue;
        }

        if (preg_match('/^(.*?)\s*([0-9.]+)\s+Impresiones\s+\$([0-9.,]+)$/u', $line, $matches)) {
            $inlineTitle = trim((string) $matches[1]);
            $title = $inlineTitle !== '' ? $inlineTitle : $pendingTitle;
            $title = normalizeTitle($title);

            if ($title === '') {
                continue;
            }

            $results = (int) str_replace('.', '', $matches[2]);
            $spend = parseAmount($matches[3]);
            $cost = $results > 0 ? $spend / $results : 0.0;
            $page = inferPageName($title);
            $key = mb_strtolower($title, 'UTF-8') . '|' . $results . '|' . number_format($spend, 2, '.', '');

            if (!isset($seen[$key])) {
                $items[] = [
                    'campaign_name' => $title,
                    'page_name' => $page,
                    'result_type' => 'Conversaciones',
                    'results' => $results,
                    'cost_per_result' => $cost,
                    'spend' => $spend,
                ];
                $seen[$key] = true;
            }

            $pendingTitle = $title;
            continue;
        }

        if (looksLikeNoise($line)) {
            continue;
        }

        if ($pendingTitle !== '' && isContinuationLine($line)) {
            $pendingTitle = normalizeTitle($pendingTitle . ' ' . $line);
            continue;
        }

        $pendingTitle = normalizeTitle($line);
    }

    return $items;
}

function looksLikeNoise(string $line): bool
{
    return (bool) preg_match(
        '/^(Campañas|Tipo de producto|Meta anuncios|Identificador de la transacción|Número de referencia)/u',
        $line
    );
}

function isContinuationLine(string $line): bool
{
    if (preg_match('/^(Publicación|TUASESOR|AJUSTES)/u', $line)) {
        return false;
    }

    if (preg_match('/^[A-ZÁÉÍÓÚ0-9 .\-]+(?:JUNIO|MAYO|JUN|MAY)/u', $line)) {
        return false;
    }

    return true;
}

function normalizeTitle(string $title): string
{
    $title = trim($title);
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    $title = trim($title, "\" \t\n\r\0\x0B");

    if (str_starts_with($title, 'Publicación de Instagram:')) {
        return $title;
    }

    if (str_starts_with($title, 'Publicación:')) {
        return $title;
    }

    return $title;
}

function inferPageName(string $title): string
{
    $n = mb_strtolower($title, 'UTF-8');

    if (str_contains($n, 'tuasesor') || str_contains($n, 'ajustes tráfico')) {
        return 'Tuasesorrm';
    }

    if (str_contains($n, 'punta pac')
        || str_contains($n, 'punto estratégico')
        || str_contains($n, 'lugar perfecto para tu negocio')
        || str_contains($n, 'refugio que tu familia merece')
        || str_contains($n, 'lujo, tecnología')) {
        return 'Realtors RM';
    }

    if (str_contains($n, 'listo para elevar tu')
        || str_contains($n, 'nuevo hogar de tu')
        || str_contains($n, 'eleva el nivel de tu')
        || str_contains($n, 'lienzo en blanco')
        || str_contains($n, 'lugar donde tu')
        || str_contains($n, 'epicentro')) {
        return 'Rossana Migliore (inferido)';
    }

    if (str_starts_with($n, 'publicación de instagram:')) {
        return 'Instagram (sin confirmar)';
    }

    if (str_starts_with($n, 'publicación:')) {
        return 'Facebook (sin confirmar)';
    }

    return 'Sin identificar en PDF';
}

function parseAmount(string $value): float
{
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);
    return (float) $value;
}

function formatUsd(float $amount): string
{
    return number_format($amount, 2, ',', '.');
}

function formatCost(float $cost): string
{
    if ($cost >= 1) {
        return number_format($cost, 2, ',', '.');
    }

    return number_format($cost, 4, ',', '.');
}

function escapeMd(string $value): string
{
    return str_replace('|', '\|', $value);
}

/**
 * @param list<list<string>> $rows
 */
function writeCsv(string $path, array $rows): void
{
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException("No se pudo escribir {$path}");
    }

    foreach ($rows as $row) {
        fputcsv($fh, $row);
    }

    fclose($fh);
}
