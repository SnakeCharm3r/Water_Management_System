<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\ZoneOffice;
use App\Services\ZoneAccessService;

class ZoneOfficeController extends Controller
{
    public function __construct(private readonly ZoneAccessService $zoneAccess) {}

    public function show(Zone $zone, ZoneOffice $office)
    {
        $this->authorize('view', $zone);

        if ($office->zone_id !== $zone->id) {
            abort(404);
        }

        $office->load(['zone', 'manager']);

        return view('zones.offices.show', compact('zone', 'office'));
    }
}
