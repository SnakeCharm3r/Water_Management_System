<?php

namespace App\Models;

use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TariffCategory extends Model
{
    use HasFactory, SoftDeletes, UsesPublicUuid;

    protected $guarded = [];

    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function waterAccounts(): HasMany { return $this->hasMany(WaterAccount::class); }
    public function rates(): HasMany { return $this->hasMany(TariffRate::class); }
}
