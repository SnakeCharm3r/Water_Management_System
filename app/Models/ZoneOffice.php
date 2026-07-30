<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZoneOffice extends Model
{
    use HasFactory, SoftDeletes, UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'easting' => 'decimal:2',
            'northing' => 'decimal:2',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'opening_days' => 'array',
            'is_main_office' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function coordinates(): array
    {
        return [$this->latitude, $this->longitude];
    }

    public function getOpeningDaysAttribute($value): array
    {
        if ($value === null) {
            return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        }

        return is_array($value) ? $value : json_decode($value, true);
    }
}
