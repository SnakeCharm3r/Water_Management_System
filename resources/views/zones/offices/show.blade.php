<x-layouts.portal title="Office: {{ $office->name }} — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration &middot; {{ $zone->name }}</p>
                    <h1>{{ $office->name }}</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('zones.show', $zone) }}" class="button button--ghost button--sm">&larr; Back to Zone</a>
                </div>
            </header>

            <div class="dashboard-panels">
                <section class="form-card" style="grid-column: span 1;">
                    <h2 class="section-title">Office Details</h2>
                    <dl class="detail-list">
                        <dt>Zone</dt><dd>{{ $zone->name }}</dd>
                        <dt>Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $office->office_type)) }}</dd>
                        <dt>Address</dt><dd>{{ $office->address ?? '—' }}</dd>
                        <dt>Phone</dt><dd>{{ $office->phone ?? '—' }}</dd>
                        <dt>Email</dt><dd>{{ $office->email ?? '—' }}</dd>
                        <dt>Opening Hours</dt>
                        <dd>{{ $office->opening_time?->format('H:i') }} – {{ $office->closing_time?->format('H:i') }}</dd>
                        <dt>Opening Days</dt>
                        <dd>{{ is_array($office->opening_days) ? implode(', ', $office->opening_days) : '—' }}</dd>
                        <dt>Line Manager</dt>
                        <dd>{{ $office->manager?->full_name ?? '—' }}</dd>
                        <dt>Status</dt>
                        <dd><span class="badge badge--{{ $office->is_active ? 'confirmed' : 'reversed' }}">{{ $office->is_active ? 'Active' : 'Inactive' }}</span></dd>
                    </dl>
                </section>

                <section class="form-card" style="grid-column: span 1;">
                    <h2 class="section-title">Location Coordinates</h2>
                    <dl class="detail-list">
                        <dt>Latitude</dt><dd>{{ $office->latitude !== null ? number_format($office->latitude, 7) : '—' }}</dd>
                        <dt>Longitude</dt><dd>{{ $office->longitude !== null ? number_format($office->longitude, 7) : '—' }}</dd>
                        <dt>Easting</dt><dd>{{ $office->easting !== null ? number_format($office->easting, 2) : '—' }}</dd>
                        <dt>Northing</dt><dd>{{ $office->northing !== null ? number_format($office->northing, 2) : '—' }}</dd>
                        <dt>UTM Zone</dt><dd>{{ $office->utm_zone ?? '—' }}</dd>
                    </dl>
                    @if($office->hasCoordinates())
                        <div class="map-panel" style="margin-top: 1rem;">
                            <div id="office-detail-map" class="map-container" data-lat="{{ $office->latitude }}" data-lng="{{ $office->longitude }}"></div>
                        </div>
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
                const container = document.getElementById('office-detail-map');
                if (!container) return;

                const lat = parseFloat(container.dataset.lat);
                const lng = parseFloat(container.dataset.lng);
                const map = L.map('office-detail-map').setView([lat, lng], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([lat, lng]).addTo(map).bindPopup('<strong>{{ $office->name }}</strong>').openPopup();
            });
        </script>
    @endpush
</x-layouts.portal>
