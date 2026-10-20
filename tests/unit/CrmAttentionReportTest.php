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
    /**
     * @return list<array{id: int, human_replied: bool, outbound_texts: list<string>}>
     */
    private function conv(int $id, string ...$texts): array
    {
        return [['id' => $id, 'human_replied' => $texts !== [], 'outbound_texts' => $texts]];
    }

    private function namesIn(string $text): array
    {
        $summary = (new CrmAttentionReport())->summarizeConversations($this->conv(1, $text));

        return $summary['conversations'][1];
    }

    public function testDetectsSignatureWithPipe(): void
    {
        $this->assertSame(['Valery Mendoza'], $this->namesIn("Hola cómo está?\nValery Mendoza | Asesores RM"));
    }

    public function testDetectsTeamIntroductionAndSkipsGreetingWords(): void
    {
        $this->assertSame(['Odra Cedeño'], $this->namesIn('Buenas Noches Odra Cedeño del Equipo de Asesores RM un placer atenderle'));
    }

    public function testDetectsSpokenIntroduction(): void
    {
        $this->assertSame(['Luis Rojas'], $this->namesIn('Buenos días! Por acá le habla Luis Rojas de parte del equipo de asesores'));
        $this->assertSame(['Bárbara Migliore'], $this->namesIn('Hola buenas tardes, un placer Bárbara Migliore por acá'));
    }

    public function testDoesNotTreatTheLeadNameAsAdvisor(): void
    {
        $this->assertSame([], $this->namesIn('Hola Luis, ¿en qué zona está buscando?'));
    }

    public function testMergesFirstNameAndFullNameOfTheSameAdvisor(): void
    {
        $summary = (new CrmAttentionReport())->summarizeConversations(array_merge(
            $this->conv(1, 'Soy Alejandro, ¿cómo está?'),
            $this->conv(2, 'Alejandro Flores del Equipo de Asesores RM'),
        ));

        $this->assertSame(['Alejandro Flores'], $summary['conversations'][1]);
        $this->assertSame(2, $summary['advisors'][0]['total']);
        $this->assertSame('Alejandro Flores', $summary['advisors'][0]['advisor']);
    }

    public function testFoldsARepeatedSignatureIntoOneAdvisor(): void
    {
        $summary = (new CrmAttentionReport())->summarizeConversations(array_merge(
            $this->conv(1, 'Alejandro Flores del Equipo de Asesores RM'),
            $this->conv(2, 'Alejandro Flores del Equipo de Asesores RM'),
            $this->conv(3, "Saludos\nFlores Alejandro Flores del Equipo de Asesores RM"),
        ));

        $this->assertSame(['Alejandro Flores'], array_column($summary['advisors'], 'advisor'));
        $this->assertSame(3, $summary['advisors'][0]['total']);
    }

    public function testSummarizesAttentionPerAdvisor(): void
    {
        $summary = (new CrmAttentionReport())->summarizeConversations(array_merge(
            $this->conv(1, 'Por acá le habla Luis Rojas', 'Perfecto'),
            $this->conv(2, 'Soy Valery Mendoza', 'Le escribe Alejandro Flores'),
            $this->conv(3, '¿En qué zona busca?'),
            $this->conv(4),
        ));

        $this->assertSame(4, $summary['total']);
        $this->assertSame(2, $summary['attended']);
        $this->assertSame(2, $summary['unattended']);
        $this->assertSame(1, $summary['shared']);
        $this->assertSame(1, $summary['unidentified_human']);

        $rows = array_column($summary['advisors'], null, 'advisor');
        $this->assertSame([1, 1, 1, 0, 50.0], array_values(array_diff_key($rows['Luis Rojas'], ['advisor' => 1])));
        $this->assertSame([1, 1, 0, 1, 50.0], array_values(array_diff_key($rows['Valery Mendoza'], ['advisor' => 1])));
        $this->assertSame([1, 0, 0, 1, 50.0], array_values(array_diff_key($rows['Alejandro Flores'], ['advisor' => 1])));
    }

    public function testCrmModeUsesTheUsersWhoRepliedFromTheCrm(): void
    {
        $summary = (new CrmAttentionReport())->summarizeParticipants([
            ['id' => 1, 'human_replied' => true, 'participants' => ['Valery Mendoza']],
            ['id' => 2, 'human_replied' => true, 'participants' => ['Luis Rojas', 'Valery Mendoza']],
            ['id' => 3, 'human_replied' => true, 'participants' => []],
            ['id' => 4, 'human_replied' => false, 'participants' => []],
        ]);

        $this->assertSame([4, 2, 2, 1, 1], [$summary['total'], $summary['attended'], $summary['unattended'], $summary['shared'], $summary['unidentified_human']]);
        $rows = array_column($summary['advisors'], null, 'advisor');
        $this->assertSame(2, $rows['Valery Mendoza']['total']);
        $this->assertSame(1, $rows['Luis Rojas']['first']);
    }

    public function testEmptyInputGivesZeroedSummary(): void
    {
        $summary = (new CrmAttentionReport())->summarizeConversations([]);

        $this->assertSame([0, 0, 0], [$summary['total'], $summary['attended'], $summary['shared']]);
        $this->assertSame([], $summary['advisors']);
    }
}
