@props(['field'])

@error($field)
    <p class="field-error">{{ $message }}</p>
@enderror
