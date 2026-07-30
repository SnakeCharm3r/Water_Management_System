<x-layouts.portal title="Edit Staff User — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Edit: {{ $user->fname }} {{ $user->lname }}</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('staff.index') }}" class="button button--ghost button--sm">&larr; Back to List</a>
                </div>
            </header>

            <div class="form-card">
                <form method="POST" action="{{ route('staff.update', $user) }}" class="form-stack">
                    @csrf @method('PUT')

                    @if($errors->any())
                        <div class="alert" style="background:var(--red-bg);color:var(--red);">
                            <ul style="margin:0;padding-left:1.2rem;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-grid">
                        <label>
                            First Name *
                            <input type="text" name="fname" value="{{ old('fname', $user->fname) }}" required>
                        </label>
                        <label>
                            Middle Name
                            <input type="text" name="mname" value="{{ old('mname', $user->mname) }}">
                        </label>
                        <label>
                            Last Name *
                            <input type="text" name="lname" value="{{ old('lname', $user->lname) }}" required>
                        </label>
                        <label>
                            Username *
                            <input type="text" name="username" value="{{ old('username', $user->username) }}" required>
                        </label>
                        <label style="grid-column: span 2;">
                            Email *
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </label>
                        <label>
                            New Password <small>(leave blank to keep current)</small>
                            <input type="password" name="password" minlength="6">
                        </label>
                        <label>
                            Confirm New Password
                            <input type="password" name="password_confirmation">
                        </label>
                    </div>

                    @php
                        $assignedIds = $user->zones->pluck('id')->toArray();
                        $primaryId = $user->zones()->wherePivot('is_primary', true)->value('zones.id') ?? $user->zone_id;
                    @endphp

                    <div class="form-grid">
                        <label style="grid-column: span 2;">
                            Primary Zone
                            <select name="primary_zone_id">
                                <option value="">— No Zone (Head Office) —</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone['id'] }}" @selected(old('primary_zone_id', $primaryId) == $zone['id'])>{{ $zone['name'] }} ({{ $zone['type'] }})</option>
                                @endforeach
                            </select>
                            <small>Used for reporting defaults and legacy integrations.</small>
                        </label>
                    </div>

                    <fieldset class="permission-fieldset">
                        <legend>Assigned Zones</legend>
                        <div class="checkbox-grid">
                            @foreach($zones as $zone)
                                <label class="checkbox-item">
                                    <input type="checkbox" name="zone_ids[]" value="{{ $zone['id'] }}" @checked(collect(old('zone_ids', $assignedIds))->contains($zone['id']))>
                                    <span>{{ $zone['name'] }} <small>({{ $zone['type'] }})</small></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="form-grid">
                        <label>
                            Status
                            <select name="is_active">
                                <option value="1" @selected(old('is_active', $user->is_active) == true)>Active</option>
                                <option value="0" @selected(old('is_active', $user->is_active) == false)>Inactive</option>
                            </select>
                        </label>
                    </div>

                    <fieldset class="permission-fieldset">
                        <legend>Assign Role(s) *</legend>
                        <div class="checkbox-grid">
                            @foreach($roles as $role)
                                <label class="checkbox-item">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                        @checked(collect(old('roles', $user->roles->pluck('name')->toArray()))->contains($role->name))>
                                    <span>{{ ucfirst($role->name) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Update User</button>
                        <a href="{{ route('staff.index') }}" class="button button--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-layouts.portal>
