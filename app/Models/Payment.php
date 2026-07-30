<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payment_date' => 'datetime', 'confirmed_at' => 'datetime', 'reversed_at' => 'datetime', 'amount' => 'decimal:2', 'raw_callback' => 'array'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WaterAccount::class, 'water_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
