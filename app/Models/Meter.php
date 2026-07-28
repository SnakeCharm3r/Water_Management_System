<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meter extends Model
{
    use SoftDeletes, UsesPublicUuid;

    protected $guarded = [];
    protected function casts(): array { return ['status' => MeterStatus::class, 'manufactured_at' => 'date', 'commissioned_at' => 'date']; }
    public function installations(): HasMany { return $this->hasMany(MeterInstallation::class); }
}
