{{--
    Rider application status (pending / in review / not approved / on hold): badge, the
    Sent -> Review -> Decision steps, and the team's comment (riders.comment, riders.comment_at)
    as a message bubble, at any stage, so the rider can improve their application. Shown on the Account page and at the top of the application page. Nothing for an
    approved (active) rider, or without an application.

    @include('partials.Hub.application-status', ['rider' => $rider, 'actions' => true])
--}}
@php
    $riderApp = $rider ?? null;
    $appStatus = $riderApp->account_status ?? null;
    $appState = match ($appStatus) {
        'pending' => 'waiting',
        'processing', 'business' => 'review',
        'rejected' => 'rejected',
        'suspended', 'blocked' => 'hold',
        default => null,
    };
    $appNote = trim((string) ($riderApp->comment ?? ''));
    $appNoteAt = $riderApp?->comment_at ? \Illuminate\Support\Carbon::parse($riderApp->comment_at) : $riderApp?->updated_at;
    $appSent = $riderApp?->created_at;
    $appUpdated = $riderApp?->updated_at;
    $showActions = $actions ?? true;
    $onApplication = request()->routeIs('hub.account.rider.view');
@endphp

@if ($riderApp && $appState)
<section @class(['account-detail', 'app-card', 'app-card--' . $appState]) aria-labelledby="appCardTitle">

                <header class="app-card-head">
                    <div class="app-card-icon" aria-hidden="true"><i class="fa-solid fa-motorcycle"></i></div>
                    <div>
                        <h4 class="sub-heading" id="appCardTitle">Rider application</h4>
                        <p class="app-card-sub">
                            @switch($appState)
                                @case('waiting') We have your application. Our team will review it shortly. @break
                                @case('review') Our team is checking your documents now. @break
                                @case('rejected') Your application was not approved this time. @break
                                @default Your rider account is {{ $appStatus }}.
                            @endswitch
                        </p>
                    </div>
                    <b @class(['app-pill', 'app-pill--' . $appState])>
                        @switch($appState)
                            @case('waiting') Pending @break
                            @case('review') In review @break
                            @case('rejected') Not approved @break
                            @default {{ ucfirst($appStatus) }}
                        @endswitch
                    </b>
                </header>

                {{-- progress: sent -> review -> decision --}}
                @if ($appState !== 'hold')
                    <ol class="app-steps" aria-label="Application progress">
                        <li class="is-done">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <strong>Sent</strong>
                            @if ($appSent)<time datetime="{{ $appSent->toIso8601String() }}">{{ $appSent->format('d M') }}</time>@endif
                        </li>
                        <li @class(['is-done' => $appState === 'rejected', 'is-now' => in_array($appState, ['waiting', 'review'])])>
                            <i class="fa-solid {{ $appState === 'rejected' ? 'fa-check' : 'fa-magnifying-glass' }}" aria-hidden="true"></i>
                            <strong>Review</strong>
                            <em>{{ $appState === 'waiting' ? 'Up next' : ($appState === 'review' ? 'In progress' : 'Done') }}</em>
                        </li>
                        <li @class(['is-failed' => $appState === 'rejected'])>
                            <i class="fa-solid {{ $appState === 'rejected' ? 'fa-xmark' : 'fa-flag-checkered' }}" aria-hidden="true"></i>
                            <strong>{{ $appState === 'rejected' ? 'Not approved' : 'Approved' }}</strong>
                            @if ($appState === 'rejected' && $appUpdated)<time datetime="{{ $appUpdated->toIso8601String() }}">{{ $appUpdated->format('d M') }}</time>@else<em>Then you can deliver</em>@endif
                        </li>
                    </ol>
                @endif

                {{-- the team's comment: what to improve (any stage), or why it is on hold --}}
                @if ($appNote !== '' || in_array($appState, ['rejected', 'hold']))
                    <figure class="app-note">
                        <div class="app-note-avatar" aria-hidden="true">SM</div>
                        <div class="app-note-bubble">
                            <figcaption>
                                <strong>Smart Market team @if ($appNote !== '')<b class="app-note-tag">COMMENT</b>@endif</strong>
                                @if ($appNoteAt)<time datetime="{{ $appNoteAt->toIso8601String() }}">{{ $appNoteAt->diffForHumans() }}</time>@endif
                            </figcaption>
                            <blockquote>
                                @if ($appNote !== '')
                                    {!! nl2br(e($appNote)) !!}
                                @elseif ($appState === 'rejected')
                                    We couldn't approve your application with the details sent. Check that your documents are clear and up to date, then send it again.
                                @else
                                    Contact us to find out more and to get your account back.
                                @endif
                            </blockquote>
                        </div>
                    </figure>
                @endif

                @if ($showActions)
                <div class="app-actions">
                    @unless ($onApplication)
                    <a class="app-btn" href="{{ route('hub.account.rider.view') }}">
                        <i class="fa-regular fa-file-lines" aria-hidden="true"></i> View application
                    </a>
                    @endunless
                    @if ($appState === 'rejected')
                        <a class="app-btn app-btn--primary" href="{{ route('hub.account.rider.edit') }}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i> Update and send again
                        </a>
                    @elseif ($appNote !== '' && in_array($appState, ['waiting', 'review']))
                        <a class="app-btn app-btn--primary" href="{{ route('hub.account.rider.edit') }}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i> Update my application
                        </a>
                    @elseif ($appState === 'hold')
                        <a class="app-btn app-btn--primary" href="{{ url('/help') }}">
                            <i class="fa-regular fa-life-ring" aria-hidden="true"></i> Contact support
                        </a>
                    @endif
                </div>
                @endif

            </section>
@endif
