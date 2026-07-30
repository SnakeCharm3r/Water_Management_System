<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['calculation_details' => 'array', 'amount' => 'decimal:2', 'quantity' => 'decimal:3'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
