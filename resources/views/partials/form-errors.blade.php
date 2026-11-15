{{--
    Form errors, shown where they belong: each message sits just above its own field (placed by
    resources/js/ui-controls.js, matching the field's name; the field turns red and the first
    one is scrolled into view). This box only says how many need fixing, with a link to each,
    and lists messages that have no field on the page. Without JavaScript it lists them all.

    Use inside a form: @include('partials.form-errors')
--}}
@if ($errors->any())
    @php
        $fieldErrors = collect($errors->getMessages())->map(fn ($m) => array_values($m))->all();
        $count = count($fieldErrors);
    @endphp
    <div class="form-errors" role="alert" data-form-errors>
        <span class="form-errors-icon" aria-hidden="true">!</span>
        <div>
            <strong>{{ $count === 1 ? 'One thing needs fixing' : $count . ' things need fixing' }}</strong>
            <p class="form-errors-hint">Look for the messages in red next to each field below.</p>
            <ul class="form-errors-list">
                @foreach ($fieldErrors as $field => $messages)
                    <li data-field="{{ $field }}"><a href="#" data-error-field="{{ $field }}">{{ $messages[0] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <script type="application/json" data-form-errors-json>@json($fieldErrors)</script>
@endif
