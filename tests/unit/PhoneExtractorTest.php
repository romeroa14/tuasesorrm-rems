<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\PhoneExtractor;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PhoneExtractorTest extends CIUnitTestCase
{
    public function testNormalizesInternationalFormat(): void
    {
        $this->assertSame(['584141234567'], PhoneExtractor::extract('Mi numero es +58 414-1234567 gracias'));
    }

    public function testNormalizesLocalFormatWithSpaces(): void
    {
        $this->assertSame(['584123456789'], PhoneExtractor::extract('escribeme al 0412 345 6789'));
    }

    public function testDeduplicatesAcrossFragments(): void
    {
        $this->assertSame(
            ['584141234567'],
            PhoneExtractor::extract('0414-1234567', 'si, 04141234567')
        );
    }

    public function testReturnsEmptyWhenThereIsNoNumber(): void
    {
        $this->assertSame([], PhoneExtractor::extract('no tengo numero ahora', 'busco apartamento en 2 habitaciones'));
    }
}
