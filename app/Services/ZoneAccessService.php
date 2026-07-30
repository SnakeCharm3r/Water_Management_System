<?php

namespace App\Services;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class ZoneAccessService
{
    private const CACHE_TTL = 300; // seconds

    /**
     * Determine whether the user has authority-wide access to all zones.
     */
    public function hasAuthorityWideAccess(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        $permission = Permission::query()->where('name', 'zones.view-all')->where('guard_name', 'web')->first();

        return $permission !== null && $user->hasPermissionTo($permission);
    }

    /**
     * Determine whether the user can access a specific zone.
     */
    public function canAccess(User $user, ?Zone $zone): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($this->hasAuthorityWideAccess($user)) {
            return true;
        }

        if ($zone === null) {
            return false;
        }

        return in_array($zone->id, $this->accessibleZoneIds($user), true);
    }

    /**
     * Return the IDs of zones accessible to the user, including descendants.
     */
    public function accessibleZoneIds(User $user): array
    {
        if (! $user->is_active) {
            return [];
        }

        if ($this->hasAuthorityWideAccess($user)) {
            return Zone::query()->pluck('id')->all();
        }

        $cacheKey = "zone_access:{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $primaryZoneIds = $user->zones()->wherePivot('is_primary', true)->pluck('zone_id')->all();
            $assignedZoneIds = $user->zones()->pluck('zone_id')->all();

            // Fallback to legacy single-zone column if pivot is empty
            if ($assignedZoneIds === [] && $user->zone_id !== null) {
                $assignedZoneIds = [$user->zone_id];
            }

            $ids = [];
            $frontier = $assignedZoneIds;

            while ($frontier !== []) {
                $ids = [...$ids, ...$frontier];
                $frontier = Zone::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            }

            return array_values(array_unique($ids));
        });
    }

    /**
     * Return the IDs of zones directly assigned to the user (not descendants).
     */
    public function directlyAssignedZoneIds(User $user): array
    {
        $assigned = $user->zones()->pluck('zone_id')->all();

        if ($assigned === [] && $user->zone_id !== null) {
            return [$user->zone_id];
        }

        return $assigned;
    }

    /**
     * Return the user's primary zone ID, or null.
     */
    public function primaryZoneId(User $user): ?int
    {
        $primary = $user->zones()->wherePivot('is_primary', true)->value('zone_id');

        return $primary ?? $user->zone_id;
    }

    /**
     * Scope a query to records whose zone_id is in the user's accessible zones.
     */
    public function scope(Builder $query, User $user, string $zoneColumn = 'zone_id'): Builder
    {
        if ($this->hasAuthorityWideAccess($user)) {
            return $query;
        }

        return $query->whereIn($zoneColumn, $this->accessibleZoneIds($user));
    }

    /**
     * Clear zone access cache for a user.
     */
    public function clearCache(User $user): void
    {
        Cache::forget("zone_access:{$user->id}");
    }
}
