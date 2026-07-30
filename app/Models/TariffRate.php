<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffRate extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'minimum_charge' => 'decimal:2', 'fixed_charge' => 'decimal:2', 'unit_rate' => 'decimal:6', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TariffCategory::class, 'tariff_category_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TariffBlock::class);
    }
}
