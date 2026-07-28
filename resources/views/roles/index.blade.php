.<x-layouts.portal title="Roles & Permissions — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Roles &amp; Permissions</h1>
                </div>
                <div class="topbar__context">
                    @can('roles.manage')
                        <a href="{{ route('roles.create') }}" class="button primary button--sm">+ New Role</a>
                    @endcan
                </div>
            </header>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert" style="background:var(--red-bg);color:var(--red);">{{ session('error') }}</div>
            @endif

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Users</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="font-medium">{{ ucfirst($role->name) }}</td>
                                <td>
                                    <span class="badge badge--info">{{ $role->users_count }}</span>
                                </td>
                                <td>
                                    <div class="permission-tags">
                                        @foreach($role->permissions->sortBy('name') as $perm)
                                            <span class="badge badge--muted">{{ $perm->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="actions-cell">
                                    @can('roles.manage')
                                        <a href="{{ route('roles.edit', $role) }}" class="button button--ghost button--sm">Edit</a>
                                        @if($role->users_count === 0)
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline-form" onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="button button--ghost button--sm" style="color:var(--red);">Delete</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-cell">No roles defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</x-layouts.portal>
