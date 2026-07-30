<form method="POST" action="{{ $zone ? route('zones.update', $zone) : route('zones.store') }}" class="form-stack">
    @csrf
    @if($zone) @method('PUT') @endif

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
            Code *
            <input type="text" name="code" value="{{ old('code', $zone?->code) }}" required maxlength="64">
        </label>
        <label>
            Name *
            <input type="text" name="name" value="{{ old('name', $zone?->name) }}" required>
        </label>
        <label>
            Type *
            <select name="zone_type" required>
                @foreach(['authority' => 'Authority', 'region' => 'Region', 'branch' => 'Branch', 'operational_zone' => 'Operational Zone', 'service_area' => 'Service Area', 'meter_reading_route' => 'Meter Reading Route', 'zone' => 'Zone'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('zone_type', $zone?->zone_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Parent Zone
            <select name="parent_id">
                <option value="">— No Parent —</option>
                @foreach($parents as $p)
                    <option value="{{ $p['id'] }}" @selected(old('parent_id', $zone?->parent_id) == $p['id'])>{{ $p['name'] }} ({{ $p['type'] }})</option>
                @endforeach
            </select>
        </label>
        <label>
            Region
            <input type="text" name="region" value="{{ old('region', $zone?->region) }}" maxlength="128">
        </label>
        <label>
            District
            <input type="text" name="district" value="{{ old('district', $zone?->district) }}" maxlength="128">
        </label>
    </div>

    <label>
        Description
        <textarea name="description" rows="3">{{ old('description', $zone?->description) }}</textarea>
    </label>

    <div class="form-actions">
        <button type="submit" class="button primary">{{ $submitLabel }}</button>
        <a href="{{ route('zones.index') }}" class="button button--ghost">Cancel</a>
    </div>
</form>
