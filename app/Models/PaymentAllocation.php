<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['allocated_amount' => 'decimal:2', 'allocated_at' => 'datetime']; }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function bill(): BelongsTo { return $this->belongsTo(Bill::class); }
}
