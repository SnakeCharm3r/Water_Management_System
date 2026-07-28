@props(['data'])

<div class="chart-panel">
    <div class="chart-panel__header">
        <h3>Billing vs Collections</h3>
        <span class="chart-panel__sub">Last 6 months</span>
    </div>

    @if(collect($data)->every(fn ($m) => $m['billed'] == 0 && $m['collected'] == 0))
        <x-dashboard.empty-state message="No billing or collection data for this period." />
    @else
        <div class="chart-panel__canvas" id="billingChart"
             data-chart='@json($data)'
             aria-label="Bar chart showing billed versus collected amounts for each of the last six months">
        </div>
        <div class="chart-panel__legend">
            <span class="chart-panel__legend-item chart-panel__legend-item--billed">Billed</span>
            <span class="chart-panel__legend-item chart-panel__legend-item--collected">Collected</span>
        </div>
    @endif
</div>
