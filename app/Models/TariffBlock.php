<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffBlock extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['from_quantity' => 'decimal:3', 'to_quantity' => 'decimal:3', 'rate_per_unit' => 'decimal:6'];
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(TariffRate::class, 'tariff_rate_id');
    }
}
