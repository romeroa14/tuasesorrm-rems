<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\FinanceQuota;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FinanceQuotaTest extends CIUnitTestCase
{
    public function testModelCanInstantiate(): void
    {
        $model = new FinanceQuota();
        $this->assertInstanceOf(FinanceQuota::class, $model);
    }

    public function testAllowedFields(): void
    {
        $model = new FinanceQuota();
        $expected = [
            'type', 'name', 'receipt_date', 'delivery_date',
            'currency', 'exchange_rate', 'receipt_number', 'amount', 'notes',
        ];

        $this->assertSame($expected, $model->allowedFields);
    }

    public function testTableName(): void
    {
        $model = new FinanceQuota();
        $this->assertSame('finance_quotas', $model->table);
    }
}
