<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\CrmPhoneExport;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CrmPhoneExportTest extends CIUnitTestCase
{
    private function conversations(): array
    {
        return [
            ['id' => 1, 'created_at' => '2026-10-16 10:00:00', 'lead_name' => 'María Pérez', 'lead_phone' => '', 'instagram_username' => '@mariap', 'external_username' => '', 'recipient_ig_username' => 'realtorsrm'],
            ['id' => 2, 'created_at' => '2026-10-17 09:00:00', 'lead_name' => 'Instagram User 123456', 'lead_phone' => '04121112233', 'instagram_username' => '@juan_rm', 'external_username' => '', 'recipient_ig_username' => 'tuasesorrm'],
        ];
    }

    public function testBuildsOneRowPerNumberWithNameAdvisorAndAccount(): void
    {
        $rows = CrmPhoneExport::rows(
            $this->conversations(),
            [
                ['conversation_id' => 1, 'content' => 'mi numero es 0414-1234567', 'created_at' => '2026-10-16 10:05:00'],
                ['conversation_id' => 1, 'content' => 'repito: 04141234567', 'created_at' => '2026-10-16 10:06:00'],
            ],
            [1 => ['Valery Mendoza'], 2 => ['Luis Rojas', 'Odra Cedeño']]
        );

        $this->assertSame(['584141234567', '584121112233'], array_column($rows, 'phone'));
        $this->assertSame([
            'phone'       => '584141234567',
            'name'        => 'María Pérez',
            'instagram'   => '@mariap',
            'attended_by' => 'Valery Mendoza',
            'account'     => 'realtorsrm',
            'date'        => '2026-10-16 10:05:00',
            'source'      => 'chat',
            'conversation' => '1',
        ], $rows[0]);
    }

    public function testUsesInstagramHandleWhenTheNameIsAPlaceholder(): void
    {
        $rows = CrmPhoneExport::rows($this->conversations(), [], [2 => ['Luis Rojas', 'Odra Cedeño']]);

        $this->assertSame('@juan_rm', $rows[0]['name']);
        $this->assertSame('Luis Rojas / Odra Cedeño', $rows[0]['attended_by']);
        $this->assertSame('lead', $rows[0]['source']);
    }

    public function testMarksUnattributedNumbers(): void
    {
        $rows = CrmPhoneExport::rows($this->conversations(), [], []);

        $this->assertSame('Sin identificar', $rows[0]['attended_by']);
    }

    public function testCsvHasTheExpectedHeaderAndNeutralisesFormulas(): void
    {
        $csv = CrmPhoneExport::csv([[
            'phone' => '584141234567', 'name' => '=HYPERLINK("x")', 'instagram' => '@a', 'attended_by' => 'Valery Mendoza',
            'account' => 'realtorsrm', 'date' => '2026-10-16 10:05:00', 'source' => 'chat', 'conversation' => '1',
        ]]);

        $lines = preg_split('/\r?\n/', trim($csv, "\xEF\xBB\xBF\n"));
        $this->assertSame('Telefono,Nombre,"Usuario Instagram","Atendido por",Cuenta,Fecha,Origen,Conversacion', $lines[0]);
        $this->assertStringContainsString("'=HYPERLINK", $lines[1]);
    }

    public function testXlsxIsAValidWorkbookWithHeaderAndTextCells(): void
    {
        $binary = CrmPhoneExport::xlsx([[
            'phone' => '584141234567', 'name' => 'María & Pérez <VIP>', 'instagram' => '@mariap', 'attended_by' => 'Valery Mendoza',
            'account' => 'realtorsrm', 'date' => '2026-10-16 10:05:00', 'source' => 'chat', 'conversation' => '1',
        ]]);

        $this->assertSame("PK\x03\x04", substr($binary, 0, 4));

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $binary);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file) === true);
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $part) {
            $this->assertNotFalse($zip->locateName($part), $part);
        }
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($file);

        $xml = simplexml_load_string($sheet);
        $this->assertNotFalse($xml, 'sheet1.xml must be well-formed XML');
        $this->assertStringContainsString('<t>Telefono</t>', $sheet);
        $this->assertStringContainsString('<t>Atendido por</t>', $sheet);
        $this->assertStringContainsString('<t>584141234567</t>', $sheet);
        $this->assertStringContainsString('María &amp; Pérez &lt;VIP&gt;', $sheet);
    }
}
