<x-layouts.portal title="Zone: {{ $zone->name }} — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>{{ $zone->name }}</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('zones.index') }}" class="button button--ghost button--sm">&larr; Back to Zones</a>
                </div>
            </header>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            <div class="dashboard-panels">
                <section class="form-card">
                    <h2 class="section-title">Details</h2>
                    <dl class="detail-list">
                        <dt>Code</dt><dd><code>{{ $zone->code }}</code></dd>
                        <dt>Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $zone->zone_type)) }}</dd>
                        <dt>Region</dt><dd>{{ $zone->region ?? '—' }}</dd>
                        <dt>District</dt><dd>{{ $zone->district ?? '—' }}</dd>
                        <dt>Parent</dt><dd>{{ $zone->parent?->name ?? '—' }}</dd>
                        <dt>Status</dt><dd><span class="badge badge--{{ $zone->is_active ? 'confirmed' : 'reversed' }}">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span></dd>
                        <dt>Description</dt><dd>{{ $zone->description ?? '—' }}</dd>
                    </dl>
                    <div class="form-actions" style="margin-top:1rem;">
                        @can('zones.update', $zone)
                            <a href="{{ route('zones.edit', $zone) }}" class="button primary button--sm">Edit Zone</a>
                        @endcan
                        @can('zones.deactivate', $zone)
                            <form method="POST" action="{{ route('zones.toggle-status', $zone) }}" class="inline-form">
                                @csrf @method('PATCH')
                                <button type="submit" class="button button--ghost button--sm" style="color:var(--red);">{{ $zone->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        @endcan
                    </div>
                </section>

                <section class="form-card">
                    <h2 class="section-title">Child Zones</h2>
                    @if($zone->children->isNotEmpty())
                        <ul class="plain-list">
                            @foreach($zone->children as $child)
                                <li><a href="{{ route('zones.show', $child) }}">{{ $child->name }}</a> <span class="badge badge--muted">{{ $child->zone_type }}</span></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="empty-cell">No child zones.</p>
                    @endif
                </section>

                <section class="form-card" style="grid-column: span 2;">
                    <h2 class="section-title">Assigned Staff</h2>
                    @if($zone->users->isNotEmpty())
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr><th>Name</th><th>Username</th><th>Role</th><th>Primary</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($zone->users as $u)
                                        <tr>
                                            <td>{{ $u->full_name }}</td>
                                            <td><code>{{ $u->username }}</code></td>
                                            <td>{{ $u->roles->first()?->name ?? '—' }}</td>
                                            <td>{{ $u->pivot->is_primary ? 'Yes' : 'No' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="empty-cell">No staff assigned.</p>
                    @endif
                    @can('zones.assign-users', $zone)
                        <a href="{{ route('zones.assign-staff', $zone) }}" class="button primary button--sm" style="margin-top:.75rem;">Assign Staff</a>
                    @endcan
                </section>

                <section class="form-card" style="grid-column: span 2;">
                    <h2 class="section-title">Zone Offices & Locations</h2>
                    @if($zone->offices->isNotEmpty())
                        <div class="office-grid">
                            @foreach($zone->offices as $office)
                                <article class="office-card">
                                    <div class="office-card__header">
                                        <h3>{{ $office->name }}</h3>
                                        @if($office->is_main_office)
                                            <span class="badge badge--amber">Main Office</span>
                                        @endif
                                        <span class="badge badge--{{ $office->is_active ? 'confirmed' : 'reversed' }}">{{ $office->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                    <dl class="office-card__details">
                                        <dt>Address</dt><dd>{{ $office->address ?? '—' }}</dd>
                                        <dt>Phone</dt><dd>{{ $office->phone ?? '—' }}</dd>
                                        <dt>Email</dt><dd>{{ $office->email ?? '—' }}</dd>
                                        <dt>Office Hours</dt>
                                        <dd>{{ $office->opening_time?->format('H:i') }} – {{ $office->closing_time?->format('H:i') }}</dd>
                                        <dt>Line Manager</dt>
                                        <dd>{{ $office->manager?->full_name ?? '—' }}</dd>
                                        <dt>Coordinates</dt>
                                        <dd>
                                            @if($office->hasCoordinates())
                                                Lat {{ number_format($office->latitude, 5) }}, Lng {{ number_format($office->longitude, 5) }}
                                                @if($office->easting && $office->northing)
                                                    <br><small>Easting {{ number_format($office->easting, 2) }}, Northing {{ number_format($office->northing, 2) }} {{ $office->utm_zone ? '(UTM '.$office->utm_zone.')' : '' }}</small>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </dl>
                                    <a href="{{ route('zones.offices.show', [$zone, $office]) }}" class="button button--ghost button--sm">View Office Details</a>
                                </article>
                            @endforeach
                        </div>

                        @php
                            $mappedOffices = $zone->offices->filter(fn ($o) => $o->hasCoordinates())->values();
                        @endphp
                        @if($mappedOffices->isNotEmpty())
                            <div class="map-panel">
                                <div id="zone-office-map" class="map-container" data-lat="{{ $mappedOffices->first()->latitude }}" data-lng="{{ $mappedOffices->first()->longitude }}"></div>
                            </div>
                            <script id="office-markers" type="application/json">
                                {!! json_encode($mappedOffices->map(fn ($o) => [
                                    'name' => $o->name,
                                    'lat' => (float) $o->latitude,
                                    'lng' => (float) $o->longitude,
                                    'address' => $o->address,
                                    'url' => route('zones.offices.show', [$zone, $o]),
                                ])) !!}
                            </script>
                        @endif
                    @else
                        <p class="empty-cell">No offices recorded for this zone.</p>
                    @endif
                </section>
            </div>
        </main>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('zone-office-map');
                if (!container) return;

                const markers = JSON.parse(document.getElementById('office-markers').textContent);
                const map = L.map('zone-office-map').setView([container.dataset.lat, container.dataset.lng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                const bounds = [];
                markers.forEach(function (m) {
                    const marker = L.marker([m.lat, m.lng]).addTo(map);
                    const popup = '<strong>' + m.name + '</strong>' + (m.address ? '<br>' + m.address : '') + '<br><a href="' + m.url + '">View details</a>';
                    marker.bindPopup(popup);
                    bounds.push([m.lat, m.lng]);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                }
            });
        </script>
    @endpush
</x-layouts.portal>
