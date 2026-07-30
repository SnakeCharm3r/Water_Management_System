<x-layouts.portal title="Zones — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Zones</h1>
                </div>
                <div class="topbar__context">
                    @can('zones.create')
                        <a href="{{ route('zones.create') }}" class="button primary button--sm">+ New Zone</a>
                    @endcan
                </div>
            </header>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('zones.index') }}" class="filter-bar">
                <div class="filter-bar__fields">
                    <div class="filter-bar__field">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search code, name, region, district…">
                    </div>
                    <div class="filter-bar__field">
                        <select name="type">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-bar__field">
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="filter-bar__field">
                        <select name="parent">
                            <option value="">All Parents</option>
                            <option value="none" @selected(request('parent') === 'none')>No Parent</option>
                            @foreach($parents as $p)
                                <option value="{{ $p->id }}" @selected(request('parent') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-bar__actions">
                    <button type="submit" class="button primary button--sm">Filter</button>
                    <a href="{{ route('zones.index') }}" class="button button--ghost button--sm">Clear</a>
                </div>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Region / District</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th>Staff</th>
                            <th>Accounts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                            <tr>
                                <td><code>{{ $zone->code }}</code></td>
                                <td class="font-medium">{{ $zone->name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $zone->zone_type)) }}</td>
                                <td>{{ $zone->region ?? '—' }} / {{ $zone->district ?? '—' }}</td>
                                <td>{{ $zone->parent?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge--{{ $zone->is_active ? 'confirmed' : 'reversed' }}">
                                        {{ $zone->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $zone->users_count }}</td>
                                <td>{{ $zone->water_accounts_count }}</td>
                                <td class="actions-cell">
                                    <a href="{{ route('zones.show', $zone) }}" class="button button--ghost button--sm">View</a>
                                    @can('zones.update', $zone)
                                        <a href="{{ route('zones.edit', $zone) }}" class="button button--ghost button--sm">Edit</a>
                                    @endcan
                                    @can('zones.assign-users', $zone)
                                        <a href="{{ route('zones.assign-staff', $zone) }}" class="button button--ghost button--sm">Staff</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-cell">No zones found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $zones->links() }}
        </main>
    </div>
</x-layouts.portal>
