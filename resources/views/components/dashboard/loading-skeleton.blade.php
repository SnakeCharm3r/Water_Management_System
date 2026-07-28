@props(['lines' => 3])

<div class="skeleton" aria-hidden="true" role="presentation">
    @for($i = 0; $i < $lines; $i++)
        <div class="skeleton__line" style="width: {{ rand(60, 100) }}%"></div>
    @endfor
</div>
