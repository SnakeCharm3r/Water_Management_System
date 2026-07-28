@props(['filters', 'zones', 'billingCycles', 'tariffCategories', 'refreshedAt'])

<form method="GET" action="{{ route('dashboard') }}" class="filter-bar" role="search" aria-label="Dashboard filters">
    <div class="filter-bar__fields">
        <label class="filter-bar__field">
            <span class="sr-only">Zone</span>
            <select name="zone" aria-label="Zone filter">
                <option value="">All zones</option>
                @foreach($zones as $id => $name)
                    <option value="{{ $id }}" @selected($filters->zoneId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label class="filter-bar__field">
            <span class="sr-only">Billing cycle</span>
            <select name="billing_cycle" aria-label="Billing cycle filter">
                <option value="">All cycles</option>
                @foreach($billingCycles as $id => $name)
                    <option value="{{ $id }}" @selected($filters->billingCycleId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label class="filter-bar__field">
            <span class="sr-only">From date</span>
            <input type="date" name="date_from" value="{{ $filters->dateFrom }}" aria-label="From date" placeholder="From">
        </label>

        <label class="filter-bar__field">
            <span class="sr-only">To date</span>
            <input type="date" name="date_to" value="{{ $filters->dateTo }}" aria-label="To date" placeholder="To">
        </label>

        <label class="filter-bar__field">
            <span class="sr-only">Tariff category</span>
            <select name="tariff_category" aria-label="Tariff category filter">
                <option value="">All tariffs</option>
                @foreach($tariffCategories as $id => $name)
                    <option value="{{ $id }}" @selected($filters->tariffCategoryId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="filter-bar__actions">
        <button type="submit" class="button primary button--sm" aria-label="Apply filters">Filter</button>
        <a href="{{ route('dashboard') }}" class="button button--sm button--ghost" aria-label="Clear filters">Clear</a>
    </div>

    <span class="filter-bar__refreshed" aria-label="Last refreshed">
        Refreshed {{ $refreshedAt->format('H:i') }}
    </span>
</form>
