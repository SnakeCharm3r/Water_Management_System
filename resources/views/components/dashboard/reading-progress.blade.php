@props(['data'])

<div class="reading-progress">
    <div class="reading-progress__header">
        <h3>Meter Reading Progress</h3>
        @if($data['cycle'])
            <span class="reading-progress__cycle">{{ $data['cycle']->name ?? 'Cycle #' . $data['cycle']->id }}</span>
        @endif
    </div>

    @if(!$data['cycle'])
        <x-dashboard.empty-state message="No active billing cycle found." />
    @else
        <div class="reading-progress__bar-wrap">
            <div class="reading-progress__bar" style="width: {{ min($data['pct'], 100) }}%" role="progressbar" aria-valuenow="{{ $data['pct'] }}" aria-valuemin="0" aria-valuemax="100">
                {{ $data['pct'] }}%
            </div>
        </div>
        <div class="reading-progress__stats">
            <div><strong>{{ number_format($data['total']) }}</strong><span>Expected</span></div>
            <div class="reading-progress__stat--green"><strong>{{ number_format($data['completed']) }}</strong><span>Completed</span></div>
            <div class="reading-progress__stat--amber"><strong>{{ number_format($data['submitted']) }}</strong><span>Submitted</span></div>
            <div class="reading-progress__stat--red"><strong>{{ number_format($data['rejected']) }}</strong><span>Rejected</span></div>
            <div><strong>{{ number_format($data['remaining']) }}</strong><span>Remaining</span></div>
        </div>
        <a href="#readings" class="reading-progress__link">Go to meter-reading workspace</a>
    @endif
</div>
