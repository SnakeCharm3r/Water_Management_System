<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait UsesPublicUuid
{
    protected static function bootUsesPublicUuid(): void
    {
        static::creating(function (self $model): void {
            $model->public_uuid ??= (string) Str::uuid();
        });
    }
}
