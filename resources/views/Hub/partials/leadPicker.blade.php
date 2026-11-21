{{-- Add a lead (Hub/leadForm): now the shared Hub picker, a <select> styled by resources/js/ui-controls.js.
     Params kept: $id (unused), $name, $label, $options, $value. --}}
@include('partials.Hub.picker', ['name' => $name, 'label' => $label, 'options' => $options, 'value' => $value, 'required' => $name === 'county_id'])
