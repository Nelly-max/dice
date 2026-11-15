{{--
    Hub notifications (the bell in the top bar): the latest messages for this customer from
    customer_notifications, e.g. a comment from the team on their rider application. Opening
    the panel marks them read (POST hub.notifications.read); each opens where it points
    (hub.notifications.open). A new one also pops up once as a toast.
--}}
@php
    $notes = collect();
    $unread = 0;
    try {
        $cid = auth('customer')->id();
        if ($cid) {
            $notes = \App\Models\Customer\CustomerNotification::where('customer_id', $cid)->latest()->limit(20)->get();
            $unread = \App\Models\Customer\CustomerNotification::where('customer_id', $cid)->whereNull('read_at')->count();
        }
    } catch (\Throwable $e) {
        // table not migrated yet: an empty bell
    }
    $latestUnread = $notes->first(fn ($n) => !$n->read_at);
@endphp

<div class="hub-notif" id="hubNotif" hidden
     data-unread="{{ $unread }}"
     data-read-url="{{ route('hub.notifications.read') }}"
     @if ($latestUnread)
         data-toast-id="{{ $latestUnread->id }}"
         data-toast-title="{{ $latestUnread->title }}"
         data-toast-body="{{ \Illuminate\Support\Str::limit((string) $latestUnread->body, 120) }}"
     @endif
     role="dialog" aria-label="Notifications">
    <div class="hub-notif-head">
        <strong>Notifications</strong>
        <button type="button" class="hub-notif-x" data-notif-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>

    @if ($notes->isEmpty())
        <div class="hub-notif-empty">
            <i class="fa-regular fa-bell" aria-hidden="true"></i>
            <strong>You're all caught up</strong>
            <small>Updates about your applications, deliveries and payouts show here.</small>
        </div>
    @else
        <ul class="hub-notif-list">
            @foreach ($notes as $n)
                <li>
                    <a href="{{ route('hub.notifications.open', $n->id) }}" @class(['hub-notif-item', 'is-unread' => !$n->read_at])>
                        <i class="hub-notif-icon {{ $n->icon ?: 'fa-regular fa-bell' }}" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $n->title }}</strong>
                            @if ($n->body)<p>{{ \Illuminate\Support\Str::limit($n->body, 140) }}</p>@endif
                            <time datetime="{{ $n->created_at?->toIso8601String() }}">{{ $n->created_at?->diffForHumans() }}</time>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<script>
(() => {
    const panel = document.getElementById('hubNotif');
    if (!panel || panel.dataset.wired) return;
    panel.dataset.wired = '1';
    const bell = document.querySelector('[data-hub-bell]');
    const counter = bell?.querySelector('.counter');
    let unread = Number(panel.dataset.unread || 0);
    const paint = () => { if (counter) counter.textContent = unread > 0 ? (unread > 9 ? '9+' : String(unread)) : ''; bell?.setAttribute('aria-label', unread ? `Notifications, ${unread} new` : 'Notifications'); };
    paint();

    const close = () => { panel.hidden = true; bell?.setAttribute('aria-expanded', 'false'); };
    const open = () => {
        panel.hidden = false;
        bell?.setAttribute('aria-expanded', 'true');
        if (unread > 0) {
            fetch(panel.dataset.readUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(() => { unread = 0; paint(); }).catch(() => {});
        }
    };
    bell?.addEventListener('click', (e) => { e.stopPropagation(); panel.hidden ? open() : close(); });
    panel.querySelector('[data-notif-close]')?.addEventListener('click', close);
    document.addEventListener('click', (e) => { if (!panel.hidden && !panel.contains(e.target) && !bell?.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) close(); });

    // a new message pops up once
    const id = panel.dataset.toastId;
    if (id) {
        let seen = null;
        try { seen = localStorage.getItem('hubNotifToast'); } catch (e) {}
        if (seen !== id) {
            try { localStorage.setItem('hubNotifToast', id); } catch (e) {}
            const show = () => window.notify?.info?.(panel.dataset.toastBody || panel.dataset.toastTitle, { title: panel.dataset.toastTitle, duration: 7000 });
            window.notify ? show() : window.addEventListener('load', show, { once: true });
        }
    }
})();
</script>
