<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FinanceCustody;
use App\Models\FinanceDailyCash;
use App\Models\FinanceExchange;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FinanceCashModelsTest extends CIUnitTestCase
{
    public function testDailyCashModel(): void
    {
        $model = new FinanceDailyCash();
        $this->assertInstanceOf(FinanceDailyCash::class, $model);
        $this->assertSame('finance_daily_cash', $model->table);
    }

    public function testCustodyModel(): void
    {
        $model = new FinanceCustody();
        $this->assertInstanceOf(FinanceCustody::class, $model);
        $this->assertSame('finance_custody', $model->table);
    }

    public function testExchangeModel(): void
    {
        $model = new FinanceExchange();
        $this->assertInstanceOf(FinanceExchange::class, $model);
        $this->assertSame('finance_exchanges', $model->table);
    }
}
