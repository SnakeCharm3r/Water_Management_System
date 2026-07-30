<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedgerEntry extends Model
{
    use UsesPublicUuid;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'debit_amount' => 'decimal:2', 'credit_amount' => 'decimal:2', 'running_balance' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WaterAccount::class, 'water_account_id');
    }
}
