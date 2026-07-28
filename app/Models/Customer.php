<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Concerns\UsesPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, UsesPublicUuid;

    protected $guarded = [];
    protected $appends = ['display_name'];

    protected function casts(): array
    {
        return ['customer_type' => CustomerType::class, 'status' => CustomerStatus::class, 'source_updated_at' => 'datetime', 'synced_at' => 'datetime'];
    }

    public function waterAccounts(): HasMany { return $this->hasMany(WaterAccount::class); }

    public function getDisplayNameAttribute(): string
    {
        return $this->customer_type === CustomerType::Individual
            ? trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name])))
            : (string) $this->business_name;
    }
}
