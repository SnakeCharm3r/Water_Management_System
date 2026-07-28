@props(['navigation', 'user'])

<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand__mark">DA</span>
        <strong class="brand__text">DAWASA</strong>
    </a>

    <nav class="sidebar-nav">
        @foreach($navigation as $group)
            <div class="sidebar-group">
                <button type="button" class="sidebar-group__toggle" aria-expanded="true" data-collapse-toggle>
                    {{ $group['group'] }}
                    <svg class="sidebar-group__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sidebar-group__items" role="group" aria-label="{{ $group['group'] }}">
                    @foreach($group['items'] as $item)
                        @php
                            $isPlaceholder = str_starts_with($item['route'], '#');
                            $href = $isPlaceholder ? $item['route'] : route($item['route']);
                            $routePrefix = str_replace('.index', '.*', $item['route']);
                            $isActive = !$isPlaceholder && request()->routeIs($item['route'], $routePrefix);
                        @endphp
                        <a href="{{ $href }}"
                           class="sidebar-nav__link @if($isActive) sidebar-nav__link--active @endif @if($isPlaceholder) sidebar-nav__link--placeholder @endif"
                           @if($isPlaceholder) aria-disabled="true" title="Module not yet available" @endif
                           aria-current="{{ $isActive ? 'page' : 'false' }}">
                            <span class="sidebar-nav__icon" aria-hidden="true">
                                <x-dashboard.icon :name="$item['icon']" />
                            </span>
                            <span class="sidebar-nav__text">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <strong>{{ $user->full_name }}</strong>
            <span>{{ $user->roles->first()?->name ?? 'Staff' }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sign-out" type="submit" aria-label="Sign out">Sign out</button>
        </form>
    </div>
</aside>
