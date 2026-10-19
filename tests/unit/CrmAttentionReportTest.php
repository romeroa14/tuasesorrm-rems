<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\CrmAttentionReport;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CrmAttentionReportTest extends CIUnitTestCase
{
    private function report(): CrmAttentionReport
    {
        return new CrmAttentionReport([
            'Luis Rojas'        => ['luis rojas', 'luis'],
            'Jefferson Ramirez' => ['jefferson ramirez', 'jefferson'],
            'Barbara Migliore'  => ['barbara migliore', 'barbara'],
            'Valery Mendoza'    => ['valery mendoza', 'valery'],
            'Alejandro Flores'  => ['alejandro flores', 'alejandro'],
        ]);
    }

    public function testDetectsAdvisorFromSelfIntroduction(): void
    {
        $this->assertSame(
            ['Luis Rojas'],
            $this->report()->advisorsIn('Buenos días! Por acá le habla Luis Rojas de parte del equipo de asesores')
        );
        $this->assertSame(
            ['Jefferson Ramirez'],
            $this->report()->advisorsIn('Hola buenas tarde por acá Jefferson de Asesores RM')
        );
    }

    public function testIgnoresAccentsWhenMatchingNames(): void
    {
        $this->assertSame(
            ['Barbara Migliore'],
            $this->report()->advisorsIn('Hola buenas tardes, un placer Bárbara Migliore por acá')
        );
    }

    public function testDoesNotMatchANameUsedToAddressTheLead(): void
    {
        $this->assertSame([], $this->report()->advisorsIn('Hola Luis, ¿en qué zona está buscando?'));
    }

    public function testSummarizesAttentionPerAdvisor(): void
    {
        $summary = $this->report()->summarize([
            ['id' => 1, 'human_replied' => true, 'outbound_texts' => ['Por acá le habla Luis Rojas', 'Perfecto']],
            ['id' => 2, 'human_replied' => true, 'outbound_texts' => ['Soy Valery, ¿cómo está?', 'Le escribe Alejandro Flores']],
            ['id' => 3, 'human_replied' => true, 'outbound_texts' => ['¿En qué zona busca?']],
            ['id' => 4, 'human_replied' => false, 'outbound_texts' => []],
        ]);

        $this->assertSame(4, $summary['total']);
        $this->assertSame(2, $summary['attended']);
        $this->assertSame(2, $summary['unattended']);
        $this->assertSame(1, $summary['shared']);
        $this->assertSame(1, $summary['unidentified_human']);

        $luis = $summary['advisors']['Luis Rojas'];
        $this->assertSame([1, 1, 1, 0], [$luis['total'], $luis['first'], $luis['exclusive'], $luis['shared']]);
        $this->assertSame(50.0, $luis['share_pct']);

        $valery = $summary['advisors']['Valery Mendoza'];
        $this->assertSame([1, 1, 0, 1], [$valery['total'], $valery['first'], $valery['exclusive'], $valery['shared']]);

        $alejandro = $summary['advisors']['Alejandro Flores'];
        $this->assertSame([1, 0, 0, 1], [$alejandro['total'], $alejandro['first'], $alejandro['exclusive'], $alejandro['shared']]);
    }

    public function testEmptyInputGivesZeroedSummary(): void
    {
        $summary = $this->report()->summarize([]);

        $this->assertSame(0, $summary['total']);
        $this->assertSame(0, $summary['attended']);
        $this->assertSame(0.0, $summary['advisors']['Luis Rojas']['share_pct']);
    }
}
