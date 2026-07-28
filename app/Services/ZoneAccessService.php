<?php

namespace App\Services;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;

class ZoneAccessService
{
    public function canAccess(User $user, ?Zone $zone): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'system-admin'])) {
            return true;
        }

        if ($zone === null || $user->zone_id === null) {
            return false;
        }

        return in_array($zone->id, $this->accessibleZoneIds($user), true);
    }

    public function accessibleZoneIds(User $user): array
    {
        if ($user->hasAnyRole(['super-admin', 'system-admin'])) {
            return Zone::query()->pluck('id')->all();
        }

        if ($user->zone_id === null) {
            return [];
        }

        $ids = [$user->zone_id];
        $frontier = [$user->zone_id];

        while ($frontier !== []) {
            $frontier = Zone::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return array_values(array_unique($ids));
    }

    public function scope(Builder $query, User $user, string $zoneColumn = 'zone_id'): Builder
    {
        if ($user->hasAnyRole(['super-admin', 'system-admin'])) {
            return $query;
        }

        return $query->whereIn($zoneColumn, $this->accessibleZoneIds($user));
    }
}
