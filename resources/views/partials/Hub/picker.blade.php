{{--
    A searchable select for the Hub forms: a plain <select> that resources/js/ui-controls.js turns
    into the custom dropdown (search box, keyboard, the same look on every page).

    @include('partials.Hub.picker', [
        'name' => 'county_id',          // field name, also the id (county_id / town_id / place_id cascade, resources/js/select.js)
        'label' => 'County',            // first, empty option: shown until something is picked
        'options' => $counties,         // rows with id + name (models or arrays); may be empty (filled by select.js)
        'value' => old('county_id'),    // the picked id
        'required' => true,             // optional
    ])
--}}
@php
    $value = (string) ($value ?? '');
@endphp
<select name="{{ $name }}" id="{{ $name }}"
        data-search="true"
        data-placeholder="{{ $label }}"
        data-selected="{{ $value }}"
        @if (!empty($required)) required @endif>
    <option value="">{{ $label }}</option>
    @foreach ($options ?? [] as $option)
        @php $optionId = (string) data_get($option, 'id'); @endphp
        <option value="{{ $optionId }}" @selected($optionId === $value)>{{ data_get($option, 'name') }}</option>
    @endforeach
</select>
