<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceAmountWords;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FinanceAmountWordsTest extends CIUnitTestCase
{
    public function testFormatUsdAmountUsesVenezuelanSeparators(): void
    {
        $this->assertSame('USD 187.500,00', FinanceAmountWords::formatUsdAmount(187500.0));
    }

    public function testUsdInWordsMatchesReceiptSample(): void
    {
        $words = FinanceAmountWords::usdInWords(187500.0);
        $this->assertSame(
            'CIENTO OCHENTA Y SIETE MIL QUINIENTOS DOLARES AMERICANOS',
            $words
        );
    }

    public function testIntegerToWordsHandlesZero(): void
    {
        $this->assertSame('cero', FinanceAmountWords::integerToWordsEs(0));
    }
}
