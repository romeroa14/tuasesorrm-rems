#!/usr/bin/env php
<?php

/**
 * Analiza PDFs de transacciones Meta y genera reporte de conciliación.
 *
 * Uso:
 *   php scripts/analyze-meta-transactions.php
 *   php scripts/analyze-meta-transactions.php --dir=2026-06-01--2026-06-05_Transacciones
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$dir = $root . '/2026-06-01--2026-06-05_Transacciones';
$outputJson = $root . '/2026-06-01--2026-06-05_Transacciones/analisis_conciliacion.json';
$outputMd = $root . '/2026-06-01--2026-06-05_Transacciones/ANALISIS_CONCILIACION.md';

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--dir=')) {
        $dir = $root . '/' . substr($arg, 6);
    }
}

if (!is_dir($dir)) {
    fwrite(STDERR, "Directorio no encontrado: {$dir}\n");
    exit(1);
}

$pdftotext = trim((string) shell_exec('which pdftotext 2>/dev/null'));
if ($pdftotext === '') {
    fwrite(STDERR, "Se requiere pdftotext (poppler-utils)\n");
    exit(1);
}

$files = glob($dir . '/*.pdf') ?: [];
sort($files);

$transactions = [];
$campaignTotals = [];
$adTotals = [];

foreach ($files as $pdfPath) {
    $text = extractPdfText($pdfPath, $pdftotext);
    $parsed = parseTransaction($pdfPath, $text);
    $transactions[] = $parsed;

    foreach ($parsed['campaigns'] as $campaign) {
        $key = $campaign['name'];
        if (!isset($campaignTotals[$key])) {
            $campaignTotals[$key] = [
                'name' => $key,
                'total' => 0.0,
                'transactions' => [],
            ];
        }
        $campaignTotals[$key]['total'] += $campaign['amount'];
        $campaignTotals[$key]['transactions'][] = $parsed['transaction_id'];
    }

    foreach ($parsed['ads'] as $ad) {
        $adKey = mb_substr($ad['name'], 0, 80);
        if (!isset($adTotals[$adKey])) {
            $adTotals[$adKey] = ['name' => $ad['name'], 'total' => 0.0, 'count' => 0];
        }
        $adTotals[$adKey]['total'] += $ad['amount'];
        $adTotals[$adKey]['count']++;
    }
}

$paid = array_values(array_filter($transactions, fn($t) => $t['status'] === 'Pagado'));
$errors = array_values(array_filter($transactions, fn($t) => $t['status'] === 'Error'));

$paidTotal = array_sum(array_column($paid, 'amount'));
$errorsTotal = array_sum(array_column($errors, 'amount'));

// Agrupar intentos fallidos por monto+fecha (misma factura, distintas tarjetas)
$invoiceGroups = groupByInvoice($transactions);

$report = [
    'generated_at' => date('c'),
    'period' => '2026-06-01 a 2026-06-05',
    'account_id' => $transactions[0]['account_id'] ?? null,
    'summary' => [
        'total_pdfs' => count($transactions),
        'paid_count' => count($paid),
        'error_count' => count($errors),
        'paid_total_usd' => round($paidTotal, 2),
        'error_attempts_total_usd' => round($errorsTotal, 2),
        'unique_invoice_groups' => count($invoiceGroups),
        'unique_campaigns' => count($campaignTotals),
    ],
    'reconciliation' => buildReconciliation($paid, $errors, $invoiceGroups),
    'paid_transactions' => $paid,
    'invoice_groups' => $invoiceGroups,
    'all_transactions' => $transactions,
    'campaigns_by_spend' => sortByTotal($campaignTotals),
    'top_ads' => array_slice(sortByTotal($adTotals), 0, 30),
];

file_put_contents($outputJson, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$md = buildMarkdownReport($report);
file_put_contents($outputMd, $md);

echo $md;
echo "\n\n---\nJSON: {$outputJson}\n";
echo "Markdown: {$outputMd}\n";

function extractPdfText(string $pdfPath, string $pdftotext): string
{
    $escaped = escapeshellarg($pdfPath);
    return (string) shell_exec("{$pdftotext} -layout {$escaped} - 2>/dev/null");
}

/**
 * @return array<string, mixed>
 */
function parseTransaction(string $pdfPath, string $text): array
{
    $basename = basename($pdfPath, '.pdf');
    $text = normalizeSpaces($text);

    preg_match('/Transacción n\.º\s+([0-9]+-[0-9]+)/u', $basename, $idMatch);
    $transactionId = $idMatch[1] ?? '';

    preg_match('/Identificador de la cuenta:\s*([0-9]+)/u', $text, $accMatch);
    preg_match('/Fecha de nota de pago[^\n]*\n\s*([^\n]+)/u', $text, $dateMatch);
    preg_match('/Método de pago\s+(Pagado|Error)/u', $text, $statusMatch);
    preg_match('/\$([0-9.,]+)/u', $text, $amountMatch);
    preg_match('/Número de referencia:\s*([A-Z0-9]+)/u', $text, $refMatch);
    preg_match('/(Visa|Mastercard)[^$]*····\s*([0-9]+)/u', $text, $cardMatch);
    preg_match('/(Realizaste este pago manual|Se solicitó un pago manual[^.]*)/u', $text, $tipoMatch);

    $amount = parseAmount($amountMatch[1] ?? '0');
    $campaigns = parseCampaigns($text);
    $ads = parseAds($text);

    return [
        'file' => basename($pdfPath),
        'transaction_id' => $transactionId,
        'datetime' => trim($dateMatch[1] ?? ''),
        'status' => $statusMatch[1] ?? 'Desconocido',
        'amount' => $amount,
        'reference' => $refMatch[1] ?? '',
        'card' => isset($cardMatch[1]) ? $cardMatch[1] . ' ****' . $cardMatch[2] : '',
        'payment_note' => trim($tipoMatch[1] ?? ''),
        'account_id' => $accMatch[1] ?? '',
        'campaigns' => $campaigns,
        'ads' => $ads,
        'campaigns_total' => round(array_sum(array_column($campaigns, 'amount')), 2),
        'ads_total' => round(array_sum(array_column($ads, 'amount')), 2),
    ];
}

/**
 * @return list<array{name: string, amount: float, period: string, ads: list<array{name: string, amount: float, impressions: string}>}>
 */
function parseCampaigns(string $text): array
{
    $campaigns = [];
    $parts = preg_split('/\n\s*Campañas\s*\n/u', $text, 2);
    if (count($parts) < 2) {
        return $campaigns;
    }

    $body = $parts[1];
    // Cortar footer Meta Ireland
    $body = preg_split('/Meta Platforms Ireland/u', $body)[0] ?? $body;

    // Campaña principal: NOMBRE + monto + periodo
    preg_match_all(
        '/\n\s*([A-Z0-9][A-Z0-9\s\-]+(?:JUNIO|MAYO|JUN|MAY)[0-9\s\-]*)\s*\n\s*\$([0-9.,]+)\s*\n\s*De\s+([^\n]+)/u',
        $body,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $m) {
        $name = trim($m[1]);
        if (strlen($name) < 5 || preg_match('/Publicación|Instagram/i', $name)) {
            continue;
        }
        $campaigns[] = [
            'name' => $name,
            'amount' => parseAmount($m[2]),
            'period' => trim($m[3]),
            'ads' => [],
        ];
    }

    // Si no encontró campaña con patrón, buscar TUASESOR*
    if ($campaigns === []) {
        if (preg_match('/(TUASESOR[A-Z0-9\s\-]+)\s+\$([0-9.,]+)/u', $body, $m)) {
            $campaigns[] = [
                'name' => trim($m[1]),
                'amount' => parseAmount($m[2]),
                'period' => '',
                'ads' => [],
            ];
        }
    }

    return $campaigns;
}

/**
 * @return list<array{name: string, amount: float, impressions: string}>
 */
function parseAds(string $text): array
{
    $ads = [];
    $parts = preg_split('/\n\s*Campañas\s*\n/u', $text, 2);
    if (count($parts) < 2) {
        return $ads;
    }

    $body = $parts[1];
    $body = preg_split('/Meta Platforms Ireland/u', $body)[0] ?? $body;

    // Líneas de anuncio con impresiones y monto
    preg_match_all(
        '/(?:Publicación(?: de Instagram)?|TUASESOR[^\n]*)\s*:?\s*([^\n$]{5,120})\s+([0-9.,]+)\s+Impresiones\s+\$([0-9.,]+)/u',
        $body,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $m) {
        $name = trim(preg_replace('/\s+/', ' ', $m[1]));
        $ads[] = [
            'name' => $name,
            'impressions' => $m[2],
            'amount' => parseAmount($m[3]),
        ];
    }

    // Anuncios con monto en línea anterior (layout alternativo)
    preg_match_all(
        '/(Publicación(?: de Instagram)?[^$\n]{10,100})\s*\n\s*\$([0-9.,]+)/u',
        $body,
        $altMatches,
        PREG_SET_ORDER
    );

    foreach ($altMatches as $m) {
        $name = trim(preg_replace('/\s+/', ' ', $m[1]));
        $amount = parseAmount($m[2]);
        $exists = false;
        foreach ($ads as $ad) {
            if (similar_text($ad['name'], $name) > 80 && abs($ad['amount'] - $amount) < 0.01) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $ads[] = ['name' => $name, 'impressions' => '', 'amount' => $amount];
        }
    }

    return $ads;
}

/**
 * @param list<array<string, mixed>> $transactions
 * @return list<array<string, mixed>>
 */
function groupByInvoice(array $transactions): array
{
    $groups = [];

    foreach ($transactions as $tx) {
        $key = $tx['datetime'] . '|' . number_format($tx['amount'], 2);
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'datetime' => $tx['datetime'],
                'amount' => $tx['amount'],
                'statuses' => [],
                'transaction_ids' => [],
                'references' => [],
                'cards' => [],
                'campaigns' => $tx['campaigns'],
                'paid_count' => 0,
                'error_count' => 0,
            ];
        }
        $groups[$key]['statuses'][] = $tx['status'];
        $groups[$key]['transaction_ids'][] = $tx['transaction_id'];
        if ($tx['reference'] !== '') {
            $groups[$key]['references'][] = $tx['reference'];
        }
        if ($tx['card'] !== '') {
            $groups[$key]['cards'][] = $tx['card'];
        }
        if ($tx['status'] === 'Pagado') {
            $groups[$key]['paid_count']++;
        } else {
            $groups[$key]['error_count']++;
        }
    }

    return array_values($groups);
}

/**
 * @param list<array<string, mixed>> $paid
 * @param list<array<string, mixed>> $errors
 * @param list<array<string, mixed>> $invoiceGroups
 * @return array<string, mixed>
 */
function buildReconciliation(array $paid, array $errors, array $invoiceGroups): array
{
    $uniqueInvoicesPaid = [];
    $uniqueInvoicesPending = [];

    foreach ($invoiceGroups as $g) {
        if ($g['paid_count'] > 0) {
            $uniqueInvoicesPaid[] = $g;
        } else {
            $uniqueInvoicesPending[] = $g;
        }
    }

    $paidUniqueTotal = array_sum(array_column($uniqueInvoicesPaid, 'amount'));
    $pendingUniqueTotal = array_sum(array_column($uniqueInvoicesPending, 'amount'));

    return [
        'pagado_efectivo' => [
            'transacciones' => count($paid),
            'monto_usd' => round(array_sum(array_column($paid, 'amount')), 2),
            'detalle' => array_map(fn($t) => [
                'id' => $t['transaction_id'],
                'fecha' => $t['datetime'],
                'monto' => $t['amount'],
                'referencia' => $t['reference'],
            ], $paid),
        ],
        'intentos_fallidos_error' => [
            'transacciones' => count($errors),
            'monto_sumado_intentos' => round(array_sum(array_column($errors, 'amount')), 2),
            'nota' => 'Suma de todos los PDF con estado Error; muchos son reintentos de la misma factura con distinta tarjeta.',
        ],
        'facturas_unicas' => [
            'pagadas' => count($uniqueInvoicesPaid),
            'monto_pagado_unico_usd' => round($paidUniqueTotal, 2),
            'pendientes_o_sin_cobro' => count($uniqueInvoicesPending),
            'monto_pendiente_unico_usd' => round($pendingUniqueTotal, 2),
        ],
        'conciliacion' => [
            'total_cobrado_real' => round($paidUniqueTotal, 2),
            'total_pendiente_estimado' => round($pendingUniqueTotal, 2),
            'diferencia_acumulado_vs_pagado' => round($pendingUniqueTotal, 2),
            'formula' => 'Acumulado pendiente = facturas únicas sin estado Pagado. Pagado real = facturas únicas con al menos 1 PDF Pagado.',
        ],
    ];
}

function parseAmount(string $value): float
{
    $value = str_replace(['$', ' '], '', $value);
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);

    return (float) $value;
}

function normalizeSpaces(string $text): string
{
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;

    return $text;
}

/**
 * @param array<string, array{name: string, total: float}> $items
 * @return list<array<string, mixed>>
 */
function sortByTotal(array $items): array
{
    $list = array_values($items);
    usort($list, fn($a, $b) => $b['total'] <=> $a['total']);

    return $list;
}

/**
 * @param array<string, mixed> $report
 */
function buildMarkdownReport(array $report): string
{
    $s = $report['summary'];
    $r = $report['reconciliation'];

    $md = "# Análisis de transacciones Meta (1–5 jun 2026)\n\n";
    $md .= "**Cuenta:** {$report['account_id']}  \n";
    $md .= "**Generado:** {$report['generated_at']}\n\n";

    $md .= "## Resumen ejecutivo\n\n";
    $md .= "| Métrica | Valor |\n|---------|-------|\n";
    $md .= "| PDFs analizados | {$s['total_pdfs']} |\n";
    $md .= "| Transacciones **Pagado** | {$s['paid_count']} |\n";
    $md .= "| Transacciones **Error** (fallidas) | {$s['error_count']} |\n";
    $md .= "| **Total cobrado real** (únicas pagadas) | **\$" . number_format($r['facturas_unicas']['monto_pagado_unico_usd'], 2, ',', '.') . "** |\n";
    $md .= "| **Pendiente / no cobrado** (únicas sin pago) | **\$" . number_format($r['facturas_unicas']['monto_pendiente_unico_usd'], 2, ',', '.') . "** |\n";
    $md .= "| Suma bruta intentos Error (inflada por reintentos) | \$" . number_format($r['intentos_fallidos_error']['monto_sumado_intentos'], 2, ',', '.') . " |\n";
    $md .= "| Grupos de factura únicos | {$s['unique_invoice_groups']} |\n\n";

    $md .= "## Conciliación: pagado vs acumulado pendiente\n\n";
    $md .= $r['conciliacion']['formula'] . "\n\n";
    $md .= "- **Pagado efectivo:** \$" . number_format($r['pagado_efectivo']['monto_usd'], 2, ',', '.') . " ({$r['pagado_efectivo']['transacciones']} transacción/es)\n";
    $md .= "- **Pendiente (facturas únicas sin cobro):** \$" . number_format($r['facturas_unicas']['monto_pendiente_unico_usd'], 2, ',', '.') . " ({$r['facturas_unicas']['pendientes_o_sin_cobro']} factura/s)\n";
    $md .= "- **Diferencia acumulado − pagado:** \$" . number_format($r['conciliacion']['diferencia_acumulado_vs_pagado'], 2, ',', '.') . "\n\n";
    $md .= "> **Importante:** Los \$" . number_format($r['facturas_unicas']['monto_pendiente_unico_usd'], 2, ',', '.') . " suman facturas históricas que Meta fue reemitiendo (\$41,99 → \$335,90 → \$593,21 → \$600,65). **No se suman entre sí.** El saldo pendiente real es el **último extracto: \$600,65** (4 jun 2026).\n";
    $md .= "> Exposición total estimada = pagado + último pendiente = **\$" . number_format($r['pagado_efectivo']['monto_usd'] + getLatestPendingAmount($report['invoice_groups']), 2, ',', '.') . "**\n\n";

    $md .= "### Transacciones efectivamente pagadas\n\n";
    $md .= "| Fecha | Monto | Referencia | ID transacción |\n";
    $md .= "|-------|-------|------------|----------------|\n";
    foreach ($r['pagado_efectivo']['detalle'] as $p) {
        $md .= "| {$p['fecha']} | \$" . number_format($p['monto'], 2, ',', '.') . " | {$p['referencia']} | `{$p['id']}` |\n";
    }
    $md .= "\n";

    $md .= "### Grupos de factura (fecha + monto)\n\n";
    $md .= "| Fecha | Monto | Pagados | Errores | Campaña principal |\n";
    $md .= "|-------|-------|---------|---------|-------------------|\n";
    foreach ($report['invoice_groups'] as $g) {
        $camp = $g['campaigns'][0]['name'] ?? '—';
        $md .= "| {$g['datetime']} | \$" . number_format($g['amount'], 2, ',', '.') . " | {$g['paid_count']} | {$g['error_count']} | {$camp} |\n";
    }
    $md .= "\n";

    $md .= "## Campañas que consumieron presupuesto\n\n";
    $md .= "| Campaña | Total en extractos (USD) | Apariciones |\n";
    $md .= "|---------|--------------------------|-------------|\n";
    foreach ($report['campaigns_by_spend'] as $c) {
        $count = count(array_unique($c['transactions'] ?? []));
        $md .= "| {$c['name']} | \$" . number_format($c['total'], 2, ',', '.') . " | {$count} |\n";
    }
    $md .= "\n";

    $md .= "## Top anuncios por gasto (acumulado en todos los PDF)\n\n";
    $md .= "| Anuncio | Gasto total | PDFs |\n";
    $md .= "|---------|-------------|------|\n";
    foreach ($report['top_ads'] as $a) {
        $md .= "| " . mb_substr($a['name'], 0, 60) . "… | \$" . number_format($a['total'], 2, ',', '.') . " | {$a['count']} |\n";
    }
    $md .= "\n";

    $md .= "## Detalle por transacción\n\n";
    foreach ($report['all_transactions'] as $tx) {
        $md .= "### {$tx['file']}\n\n";
        $md .= "- **Estado:** {$tx['status']}\n";
        $md .= "- **Monto:** \$" . number_format($tx['amount'], 2, ',', '.') . "\n";
        $md .= "- **Fecha:** {$tx['datetime']}\n";
        $md .= "- **Tarjeta:** {$tx['card']}\n";
        $md .= "- **Referencia:** {$tx['reference']}\n";
        if (!empty($tx['campaigns'])) {
            $md .= "- **Campañas:**\n";
            foreach ($tx['campaigns'] as $c) {
                $md .= "  - {$c['name']}: \$" . number_format($c['amount'], 2, ',', '.') . " ({$c['period']})\n";
            }
        }
        if (!empty($tx['ads'])) {
            $md .= "- **Anuncios ({$tx['ads_total']} USD en líneas):** " . count($tx['ads']) . " ítems\n";
        }
        $md .= "\n";
    }

    return $md;
}

/**
 * @param list<array<string, mixed>> $invoiceGroups
 */
function getLatestPendingAmount(array $invoiceGroups): float
{
    $latest = 0.0;
    $latestDate = '';

    foreach ($invoiceGroups as $g) {
        if (($g['paid_count'] ?? 0) > 0) {
            continue;
        }
        if ($latestDate === '' || strcmp((string) $g['datetime'], $latestDate) > 0) {
            $latestDate = (string) $g['datetime'];
            $latest = (float) $g['amount'];
        }
    }

    return $latest;
}
