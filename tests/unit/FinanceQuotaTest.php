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

    public function testAllowedFieldsIncludesPeriodColumns(): void
    {
        $model = new FinanceQuota();
        $fields = $model->allowedFields;

        $this->assertContains('period_month', $fields);
        $this->assertContains('period_year', $fields);
        $this->assertContains('payment_date', $fields);
        $this->assertContains('lead_id', $fields);
        $this->assertContains('installment_id', $fields);
    }

    public function testTableName(): void
    {
        $model = new FinanceQuota();
        $this->assertSame('finance_quotas', $model->table);
    }
}
