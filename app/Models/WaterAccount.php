<?php

namespace App\Models;

use App\Enums\WaterAccountStatus;
use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaterAccount extends Model
{
    use HasFactory, SoftDeletes, UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => WaterAccountStatus::class, 'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'current_balance' => 'decimal:2', 'credit_limit' => 'decimal:2'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function zone(): BelongsTo { return $this->belongsTo(Zone::class); }
    public function tariffCategory(): BelongsTo { return $this->belongsTo(TariffCategory::class); }
    public function installations(): HasMany { return $this->hasMany(MeterInstallation::class); }
    public function bills(): HasMany { return $this->hasMany(Bill::class); }
}
