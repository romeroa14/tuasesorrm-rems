#!/usr/bin/env php
<?php

/**
 * Exporta usuarios del inbox de Instagram a Excel.
 *
 * Recorre inbox/<usuario_id>/message_1.json, extrae el nombre del usuario
 * y detecta números de teléfono compartidos por el usuario en la conversación.
 *
 * Uso:
 *   php scripts/export-inbox-users-excel.php
 *   php scripts/export-inbox-users-excel.php --output=inbox/usuarios_inbox.xlsx
 *   php scripts/export-inbox-users-excel.php --inbox=/ruta/al/inbox
 */

declare(strict_types=1);

const BUSINESS_SENDERS = [
    'realtors rm',
    'asesores rm',
    'usuario de instagram',
];

const PHONE_PATTERN = '/(?<!\d)(?:\+58[\s.-]?)?0?(4(?:12|14|16|24|26|22)[\s.\-]?\d{3}[\s.\-]?\d{4}|2(?:12|41|61|71|81|91)[\s.\-]?\d{3}[\s.\-]?\d{4})(?!\d)/';

$options = parseOptions($argv);
$inboxDir = $options['inbox'];
$outputPath = $options['output'];

if (!is_dir($inboxDir)) {
    fwrite(STDERR, "No existe el directorio inbox: {$inboxDir}\n");
    exit(1);
}

$rows = [];
$stats = [
    'folders' => 0,
    'with_json' => 0,
    'with_phone' => 0,
    'errors' => 0,
];

foreach (glob($inboxDir . '/*', GLOB_ONLYDIR) ?: [] as $folderPath) {
    $stats['folders']++;
    $folderName = basename($folderPath);
    $jsonPath = $folderPath . '/message_1.json';

    if (!is_file($jsonPath)) {
        $rows[] = [
            'usuario' => usernameFromFolder($folderName),
            'carpeta' => $folderName,
            'telefono' => '',
            'nota' => 'Sin archivo message_1.json',
        ];
        continue;
    }

    $stats['with_json']++;

    try {
        $conversation = loadConversation($jsonPath);
        $userName = extractUserName($conversation, $folderName);
        $phone = extractUserPhone($conversation);

        if ($phone !== '') {
            $stats['with_phone']++;
        }

        $rows[] = [
            'usuario' => $userName,
            'carpeta' => $folderName,
            'telefono' => $phone,
            'nota' => '',
        ];
    } catch (Throwable $e) {
        $stats['errors']++;
        $rows[] = [
            'usuario' => usernameFromFolder($folderName),
            'carpeta' => $folderName,
            'telefono' => '',
            'nota' => 'Error: ' . $e->getMessage(),
        ];
    }
}

usort($rows, static fn(array $a, array $b): int => strcasecmp($a['usuario'], $b['usuario']));

writeXlsx(
    $outputPath,
    ['Usuario', 'Carpeta', 'Teléfono', 'Nota'],
    array_map(
        static fn(array $row): array => [$row['usuario'], $row['carpeta'], $row['telefono'], $row['nota']],
        $rows
    )
);

echo "Excel generado: {$outputPath}\n";
echo "Carpetas: {$stats['folders']}\n";
echo "Con conversación JSON: {$stats['with_json']}\n";
echo "Con teléfono detectado: {$stats['with_phone']}\n";

if ($stats['errors'] > 0) {
    echo "Errores al leer JSON: {$stats['errors']}\n";
}

/**
 * @return array<string, string>
 */
function parseOptions(array $argv): array
{
    $root = dirname(__DIR__);
    $inbox = $root . '/inbox';
    $output = $root . '/inbox/usuarios_inbox.xlsx';

    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--inbox=')) {
            $inbox = substr($arg, 8);
            continue;
        }

        if (str_starts_with($arg, '--output=')) {
            $output = substr($arg, 9);
            continue;
        }

        if ($arg === '--help' || $arg === '-h') {
            echo "Uso: php scripts/export-inbox-users-excel.php [--inbox=PATH] [--output=PATH]\n";
            exit(0);
        }
    }

    return [
        'inbox' => $inbox,
        'output' => $output,
    ];
}

/**
 * @return array<string, mixed>
 */
function loadConversation(string $jsonPath): array
{
    $raw = file_get_contents($jsonPath);
    if ($raw === false) {
        throw new RuntimeException('No se pudo leer el archivo');
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('JSON inválido');
    }

    return $data;
}

function usernameFromFolder(string $folderName): string
{
    if (preg_match('/^(.+)_\d+$/', $folderName, $matches) === 1) {
        return $matches[1];
    }

    return $folderName;
}

/**
 * @param array<string, mixed> $conversation
 */
function extractUserName(array $conversation, string $folderName): string
{
    $title = trim((string) ($conversation['title'] ?? ''));
    if ($title !== '' && !isBusinessSender($title)) {
        return fixEncoding($title);
    }

    foreach ($conversation['participants'] ?? [] as $participant) {
        $name = trim((string) ($participant['name'] ?? ''));
        if ($name !== '' && !isBusinessSender($name)) {
            return fixEncoding($name);
        }
    }

    return usernameFromFolder($folderName);
}

/**
 * @param array<string, mixed> $conversation
 */
function extractUserPhone(array $conversation): string
{
    $userSenders = [];

    foreach ($conversation['participants'] ?? [] as $participant) {
        $name = trim((string) ($participant['name'] ?? ''));
        if ($name !== '' && !isBusinessSender($name)) {
            $userSenders[] = normalizeSender($name);
        }
    }

    if ($userSenders === []) {
        $userSenders[] = normalizeSender((string) ($conversation['title'] ?? ''));
    }

    $messages = $conversation['messages'] ?? [];
    usort(
        $messages,
        static fn(array $a, array $b): int => ((int) ($a['timestamp_ms'] ?? 0)) <=> ((int) ($b['timestamp_ms'] ?? 0))
    );

    foreach ($messages as $message) {
        $sender = normalizeSender((string) ($message['sender_name'] ?? ''));
        if ($sender === '' || isBusinessSender($sender)) {
            continue;
        }

        if ($userSenders !== [] && !in_array($sender, $userSenders, true)) {
            continue;
        }

        $content = fixEncoding((string) ($message['content'] ?? ''));
        if ($content === '') {
            continue;
        }

        $phone = findPhoneInText($content);
        if ($phone !== '') {
            return $phone;
        }
    }

    return '';
}

function findPhoneInText(string $text): string
{
    if (preg_match(PHONE_PATTERN, $text, $matches) !== 1) {
        return '';
    }

    return normalizePhone($matches[1]);
}

function normalizePhone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '58') && strlen($digits) >= 12) {
        $digits = '0' . substr($digits, 2);
    }

    if (!str_starts_with($digits, '0')) {
        $digits = '0' . $digits;
    }

    return $digits;
}

function isBusinessSender(string $name): bool
{
    $normalized = normalizeSender($name);

    foreach (BUSINESS_SENDERS as $businessName) {
        if ($normalized === $businessName || str_contains($normalized, $businessName)) {
            return true;
        }
    }

    return false;
}

function normalizeSender(string $name): string
{
    return mb_strtolower(trim(fixEncoding($name)), 'UTF-8');
}

function fixEncoding(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (preg_match('/Ã|Â|ð/u', $value) === 1) {
        $fixed = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);
        if ($fixed !== false && mb_check_encoding($fixed, 'UTF-8')) {
            return $fixed;
        }
    }

    if (preg_match('//u', $value) === 1) {
        return $value;
    }

    $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
    if ($converted !== false && $converted !== '') {
        return $converted;
    }

    $legacy = @utf8_encode($value);

    return $legacy !== false ? $legacy : $value;
}

/**
 * @param list<string> $headers
 * @param list<list<string>> $rows
 */
function writeXlsx(string $outputPath, array $headers, array $rows): void
{
    $outputDir = dirname($outputPath);
    if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
        throw new RuntimeException("No se pudo crear el directorio: {$outputDir}");
    }

    $sharedStrings = [];
    $sharedIndex = static function ($value) use (&$sharedStrings): int {
        $value = (string) $value;
        if (!array_key_exists($value, $sharedStrings)) {
            $sharedStrings[$value] = count($sharedStrings);
        }

        return $sharedStrings[$value];
    };

    $sheetRows = [];
    $allRows = array_merge([$headers], $rows);

    foreach ($allRows as $rowIndex => $rowValues) {
        $cells = [];
        foreach ($rowValues as $columnIndex => $value) {
            $column = columnName($columnIndex);
            $rowNumber = $rowIndex + 1;
            $stringIndex = $sharedIndex($value);
            $cells[] = '<c r="' . $column . $rowNumber . '" t="s"><v>' . $stringIndex . '</v></c>';
        }

        $sheetRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
    }

    $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
        . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';

    foreach (array_keys($sharedStrings) as $string) {
        $sharedXml .= '<si><t>' . xmlEscape($string) . '</t></si>';
    }
    $sharedXml .= '</sst>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetData>' . implode('', $sheetRows) . '</sheetData></worksheet>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Usuarios" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        . '</Types>';

    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        . '</Relationships>';

    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("No se pudo crear el archivo Excel: {$outputPath}");
    }

    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $relsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->close();
}

function columnName(int $index): string
{
    $index++;
    $name = '';

    while ($index > 0) {
        $remainder = ($index - 1) % 26;
        $name = chr(65 + $remainder) . $name;
        $index = intdiv($index - 1, 26);
    }

    return $name;
}

function xmlEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
