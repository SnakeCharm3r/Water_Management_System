<x-layouts.portal title="Edit Zone — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Edit Zone: {{ $zone->name }}</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('zones.index') }}" class="button button--ghost button--sm">&larr; Back to Zones</a>
                </div>
            </header>

            <div class="form-card">
                @include('zones.form', ['zone' => $zone, 'parents' => $parents, 'submitLabel' => 'Update Zone'])
            </div>
        </main>
    </div>
</x-layouts.portal>
