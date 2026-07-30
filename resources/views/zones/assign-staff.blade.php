<x-layouts.portal title="Assign Staff to Zone: {{ $zone->name }} — DAWASA">
    <div class="portal-shell" data-sidebar>
        <x-dashboard.sidebar :navigation="app(\App\Services\DashboardStatisticsService::class)->navigation(auth()->user())" :user="auth()->user()" />

        <main class="portal-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <div class="topbar__greeting">
                    <p class="eyebrow">Administration</p>
                    <h1>Assign Staff: {{ $zone->name }}</h1>
                </div>
                <div class="topbar__context">
                    <a href="{{ route('zones.show', $zone) }}" class="button button--ghost button--sm">&larr; Back to Zone</a>
                </div>
            </header>

            <div class="form-card">
                <form method="POST" action="{{ route('zones.assign-staff.update', $zone) }}" class="form-stack">
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

                    @php
                        $assigned = $zone->users()->pluck('users.id')->toArray();
                        $primary = $zone->users()->wherePivot('is_primary', true)->value('users.id');
                    @endphp

                    <fieldset class="permission-fieldset">
                        <legend>Assigned Staff</legend>
                        @if($users->isEmpty())
                            <p class="empty-cell">No active staff users available.</p>
                        @else
                            <div class="checkbox-grid">
                                @foreach($users as $u)
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" @checked(in_array($u->id, old('user_ids', $assigned)))>
                                        <span>{{ $u->full_name }} <small>({{ $u->username }})</small></span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>

                    <label>
                        Primary Staff Member
                        <select name="primary_user_id">
                            <option value="">— None —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(old('primary_user_id', $primary) == $u->id)>{{ $u->full_name }}</option>
                            @endforeach
                        </select>
                        <small>Select one assigned staff member to be primary for this zone.</small>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Save Assignments</button>
                        <a href="{{ route('zones.show', $zone) }}" class="button button--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-layouts.portal>
