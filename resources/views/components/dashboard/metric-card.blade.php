@props(['label', 'value', 'change' => null, 'description' => '', 'icon' => 'activity', 'link' => '#', 'isCurrency' => false])

<a href="{{ $link }}" class="metric-card" aria-label="{{ $label }}: {{ $isCurrency ? 'TZS ' . number_format($value) : number_format($value) }}">
    <div class="metric-card__header">
        <span class="metric-card__icon" aria-hidden="true">
            <x-dashboard.icon :name="$icon" />
        </span>
        <span class="metric-card__label">{{ $label }}</span>
    </div>
    <div class="metric-card__value">
        @if($isCurrency)
            <span class="metric-card__currency">TZS</span> {{ number_format($value) }}
        @else
            {{ number_format($value) }}
        @endif
    </div>
    <div class="metric-card__footer">
        @if($change !== null)
            <span class="metric-card__change {{ $change >= 0 ? 'metric-card__change--up' : 'metric-card__change--down' }}" aria-label="{{ abs($change) }}% {{ $change >= 0 ? 'increase' : 'decrease' }}">
                {{ $change >= 0 ? '+' : '' }}{{ $change }}%
            </span>
        @endif
        <span class="metric-card__desc">{{ $description }}</span>
    </div>
</a>
