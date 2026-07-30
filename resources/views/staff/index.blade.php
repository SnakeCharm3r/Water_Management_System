<x-layouts.portal title="Staff Users — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Staff Users</h1>
                </div>
                <div class="topbar__context">
                    @can('staff-users.create')
                        <a href="{{ route('staff.create') }}" class="button primary button--sm">+ New User</a>
                    @endcan
                </div>
            </header>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert" style="background:var(--red-bg);color:var(--red);">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('staff.index') }}" class="filter-bar">
                <div class="filter-bar__fields">
                    <div class="filter-bar__field">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, username, email…">
                    </div>
                    <div class="filter-bar__field">
                        <select name="role">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ ucfirst($role->name) }}</option>
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
                        <select name="zone">
                            <option value="">All Zones</option>
                            @foreach($zones as $z)
                                <option value="{{ $z['id'] }}" @selected(request('zone') == $z['id'])>{{ $z['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-bar__actions">
                    <button type="submit" class="button primary button--sm">Filter</button>
                    <a href="{{ route('staff.index') }}" class="button button--ghost button--sm">Clear</a>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role(s)</th>
                            <th>Zones</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="font-medium">{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}</td>
                                <td><code>{{ $user->username }}</code></td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge badge--info">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @forelse($user->zones as $z)
                                        <span class="badge {{ $z->pivot->is_primary ? 'badge--amber' : 'badge--muted' }}">{{ $z->name }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge badge--{{ $user->is_active ? 'confirmed' : 'reversed' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    @can('staff-users.update')
                                        <a href="{{ route('staff.edit', $user) }}" class="button button--ghost button--sm">Edit</a>
                                    @endcan
                                    @can('staff-users.manage')
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('staff.destroy', $user) }}" class="inline-form" onsubmit="return confirm('Delete user {{ $user->username }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="button button--ghost button--sm" style="color:var(--red);">Delete</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="empty-cell">No staff users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </main>
    </div>
</x-layouts.portal>
