<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Builds the "phone numbers obtained" export: one row per number with who the
 * client is, who attended them and which account the chat came in through.
 */
final class CrmPhoneExport
{
    private const HEADER = ['Telefono', 'Nombre', 'Usuario Instagram', 'Atendido por', 'Cuenta', 'Fecha', 'Origen', 'Conversacion'];

    /**
     * @param list<array<string, mixed>>  $conversations rows with id, created_at, lead_name, lead_phone, instagram_username, external_username, recipient_ig_username
     * @param list<array<string, mixed>>  $inbound       client messages: conversation_id, content, created_at (in reading order)
     * @param array<int, list<string>>    $attribution   conversation id => advisors, from the selected report mode
     *
     * @return list<array<string, string>>
     */
    public static function rows(array $conversations, array $inbound, array $attribution): array
    {
        $byId  = array_column($conversations, null, 'id');
        $found = [];

        $add = static function (string $phone, array $c, string $date, string $source) use (&$found, $attribution): void {
            if (isset($found[$phone])) {
                return;
            }
            $handle  = trim((string) ($c['instagram_username'] ?? '')) ?: trim((string) ($c['external_username'] ?? ''));
            $name    = trim((string) ($c['lead_name'] ?? ''));
            $advisor = $attribution[(int) $c['id']] ?? [];

            $found[$phone] = [
                'phone'        => $phone,
                'name'         => ($name === '' || str_starts_with($name, 'Instagram User')) && $handle !== '' ? $handle : $name,
                'instagram'    => $handle,
                'attended_by'  => $advisor === [] ? 'Sin identificar' : implode(' / ', $advisor),
                'account'      => (string) ($c['recipient_ig_username'] ?? ''),
                'date'         => $date,
                'source'       => $source,
                'conversation' => (string) $c['id'],
            ];
        };

        foreach ($inbound as $m) {
            $c = $byId[$m['conversation_id']] ?? null;
            if ($c === null) {
                continue;
            }
            foreach (PhoneExtractor::extract((string) $m['content']) as $phone) {
                $add($phone, $c, (string) $m['created_at'], 'chat');
            }
        }
        foreach ($conversations as $c) {
            $raw = trim((string) ($c['lead_phone'] ?? ''));
            foreach ($raw === '' ? [] : PhoneExtractor::extract($raw) as $phone) {
                $add($phone, $c, (string) $c['created_at'], 'lead');
            }
        }

        return array_values($found);
    }

    /** @param list<array<string, string>> $rows */
    public static function csv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, self::HEADER);
        foreach ($rows as $row) {
            fputcsv($fh, array_map([self::class, 'safe'], array_values($row)));
        }
        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /**
     * Minimal Office Open XML workbook (one sheet, text cells), built with ZipArchive
     * so the project needs no spreadsheet library. Every cell is a string, so long
     * phone numbers never turn into scientific notation.
     *
     * @param list<array<string, string>> $rows
     */
    public static function xlsx(array $rows): string
    {
        $esc   = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col   = static fn (int $i): string => chr(65 + $i);
        $cells = static function (array $values, int $r, int $style) use ($esc, $col): string {
            $out = '<row r="' . $r . '">';
            foreach (array_values($values) as $i => $v) {
                $out .= '<c r="' . $col($i) . $r . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t>' . $esc((string) $v) . '</t></is></c>';
            }

            return $out . '</row>';
        };

        $sheetRows = $cells(self::HEADER, 1, 1);
        foreach (array_values($rows) as $i => $row) {
            $sheetRows .= $cells($row, $i + 2, 0);
        }
        $widths = [16, 28, 22, 26, 20, 20, 10, 14];
        $cols   = '';
        foreach ($widths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $parts = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Numeros" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>' . $cols . '</cols><sheetData>' . $sheetRows . '</sheetData><autoFilter ref="A1:H' . (count($rows) + 1) . '"/></worksheet>',
        ];

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip  = new \ZipArchive();
        $zip->open($file, \ZipArchive::OVERWRITE);
        foreach ($parts as $name => $xml) {
            $zip->addFromString($name, $xml);
        }
        $zip->close();
        $binary = (string) file_get_contents($file);
        unlink($file);

        return $binary;
    }

    /** Neutralise spreadsheet formula injection. */
    private static function safe(string $value): string
    {
        return $value !== '' && strpbrk($value[0], "=+-@\t\r") !== false ? "'" . $value : $value;
    }
}
