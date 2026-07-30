<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;
use App\Services\ZoneAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ZonePolicy
{
    use HandlesAuthorization;

    public function __construct(private readonly ZoneAccessService $zoneAccess) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->can('zones.view');
    }

    public function view(User $user, Zone $zone): bool
    {
        return $user->is_active && $user->can('zones.view') && $this->zoneAccess->canAccess($user, $zone);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->can('zones.create');
    }

    public function update(User $user, Zone $zone): bool
    {
        return $user->is_active && $user->can('zones.update') && $this->zoneAccess->canAccess($user, $zone);
    }

    public function deactivate(User $user, Zone $zone): bool
    {
        return $user->is_active && $user->can('zones.deactivate') && $this->zoneAccess->canAccess($user, $zone);
    }

    public function assignUsers(User $user, Zone $zone): bool
    {
        return $user->is_active && $user->can('zones.assign-users') && $this->zoneAccess->canAccess($user, $zone);
    }

    public function reassignRecords(User $user, Zone $zone): bool
    {
        return $user->is_active && $user->can('zones.reassign-records') && $this->zoneAccess->canAccess($user, $zone);
    }
}
