<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceWallet extends Model
{
    protected $table            = 'finance_wallets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'company_id', 'code', 'name', 'wallet_type', 'currency_id', 'balance', 'active', 'notes',
    ];
    protected $useTimestamps = true;
}
