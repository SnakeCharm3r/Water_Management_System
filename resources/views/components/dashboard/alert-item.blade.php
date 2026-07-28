@props(['label', 'count' => 0, 'severity' => 'blue', 'description' => '', 'link' => null])

<div class="alert-item alert-item--{{ $severity }}" role="status">
    <div class="alert-item__badge" aria-label="{{ $severity }} severity">{{ $count }}</div>
    <div class="alert-item__body">
        <strong class="alert-item__title">{{ $label }}</strong>
        <span class="alert-item__desc">{{ $description }}</span>
    </div>
    @if($link)
        <a href="{{ $link }}" class="alert-item__link" aria-label="View {{ $label }}">View</a>
    @endif
</div>
