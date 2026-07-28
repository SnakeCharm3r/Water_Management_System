<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingCycle extends Model
{
    use UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'reading_start_date' => 'date', 'reading_end_date' => 'date', 'issue_date' => 'date', 'due_date' => 'date', 'generated_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function readings(): HasMany { return $this->hasMany(MeterReading::class); }
    public function bills(): HasMany { return $this->hasMany(Bill::class); }
}
