<?php

namespace App\Models;

use App\Enums\MeterInstallationStatus;
use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeterInstallation extends Model
{
    use SoftDeletes, UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['installation_date' => 'date', 'removal_date' => 'date', 'initial_reading' => 'decimal:3', 'final_reading' => 'decimal:3', 'meter_multiplier' => 'decimal:4', 'is_active' => 'boolean', 'status' => MeterInstallationStatus::class];
    }

    public function waterAccount(): BelongsTo
    {
        return $this->belongsTo(WaterAccount::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }
}
