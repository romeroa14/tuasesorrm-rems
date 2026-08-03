<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FinanceWalletTransfer extends Model
{
    protected $table            = 'finance_wallet_transfers';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'company_id', 'from_wallet_id', 'to_wallet_id', 'amount', 'currency_id',
        'transfer_date', 'description', 'finance_movement_id', 'created_by',
    ];
    protected $useTimestamps = true;
}
