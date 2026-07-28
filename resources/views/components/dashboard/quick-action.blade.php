@props(['label', 'icon' => 'activity', 'route' => '#'])

<a href="{{ $route }}" class="quick-action" aria-label="{{ $label }}">
    <span class="quick-action__icon" aria-hidden="true">
        <x-dashboard.icon :name="$icon" />
    </span>
    <span class="quick-action__label">{{ $label }}</span>
</a>
