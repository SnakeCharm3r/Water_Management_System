<x-layouts.portal title="Create Role — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Create Role</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('roles.index') }}" class="button button--ghost button--sm">&larr; Back to Roles</a>
                </div>
            </header>

            <div class="form-card">
                <form method="POST" action="{{ route('roles.store') }}" class="form-stack">
                    @csrf

                    @if($errors->any())
                        <div class="alert" style="background:var(--red-bg);color:var(--red);">
                            <ul style="margin:0;padding-left:1.2rem;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <label>
                        Role Name *
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. finance-officer">
                    </label>

                    <fieldset class="permission-fieldset">
                        <legend>Assign Permissions *</legend>
                        <div class="permission-select-actions" style="margin-bottom:.75rem;display:flex;gap:.5rem;">
                            <button type="button" class="button button--ghost button--sm" onclick="document.querySelectorAll('.perm-cb').forEach(c=>c.checked=true)">Select All</button>
                            <button type="button" class="button button--ghost button--sm" onclick="document.querySelectorAll('.perm-cb').forEach(c=>c.checked=false)">Deselect All</button>
                        </div>
                        @foreach($grouped as $group => $perms)
                            <div class="permission-group">
                                <h3 class="permission-group__title">{{ ucfirst(str_replace('-', ' ', $group)) }}</h3>
                                <div class="checkbox-grid">
                                    @foreach($perms as $perm)
                                        <label class="checkbox-item">
                                            <input type="checkbox" class="perm-cb" name="permissions[]" value="{{ $perm->name }}"
                                                @checked(is_array(old('permissions')) && in_array($perm->name, old('permissions')))>
                                            <span>{{ $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Create Role</button>
                        <a href="{{ route('roles.index') }}" class="button button--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-layouts.portal>
