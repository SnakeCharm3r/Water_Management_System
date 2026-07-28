<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];
    protected function casts(): array { return ['period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date', 'issued_at' => 'datetime', 'total_amount' => 'decimal:2', 'amount_paid' => 'decimal:2', 'balance_due' => 'decimal:2']; }
    public function account(): BelongsTo { return $this->belongsTo(WaterAccount::class, 'water_account_id'); }
    public function items(): HasMany { return $this->hasMany(BillItem::class); }
}
