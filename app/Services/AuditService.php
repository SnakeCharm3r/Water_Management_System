<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditService
{
    public static function log(
        string $event,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
    ): void {
        $user = Auth::user();
        $request = request();

        DB::table('audit_logs')->insert([
            'user_id' => $user?->id,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event' => $reason ? "{$event}: {$reason}" : $event,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'route' => $request?->route()?->getName() ?? $request?->path(),
            'created_at' => now(),
        ]);
    }
}
