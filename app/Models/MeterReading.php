<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reading_date' => 'date', 'previous_reading' => 'decimal:3', 'current_reading' => 'decimal:3', 'consumption' => 'decimal:3', 'submitted_at' => 'datetime', 'verified_at' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function installation(): BelongsTo { return $this->belongsTo(MeterInstallation::class, 'meter_installation_id'); }
    public function cycle(): BelongsTo { return $this->belongsTo(BillingCycle::class, 'billing_cycle_id'); }
    public function previous(): BelongsTo { return $this->belongsTo(self::class, 'previous_reading_id'); }
}
