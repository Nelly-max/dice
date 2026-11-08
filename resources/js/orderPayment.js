/*
|--------------------------------------------------------------------------
| M-Pesa payment page (resources/views/Payment/mpesa.blade.php)
|--------------------------------------------------------------------------
|
| Shows the customer what is happening while they pay:
|
|   STK prompt  : sent -> enter PIN -> confirming -> received (or why it failed)
|   Paybill     : waiting -> part payment received (balance) -> received
|
| The page polls route('payment.status', uuid) (StatusController). That
| endpoint reports the invoice (amount_paid, status) and the latest STK
| prompt's callback result, so a cancelled prompt, a wrong PIN or a Paybill
| payment all show up here without a reload. When the invoice is paid the
| customer is sent to the order page.
|
| Soft navigation (softnav.js): the layout re-imports this file after every
| swap, so initPaymentPage() runs again on the new page. Polling stops on
| 'softnav:before' and whenever #payment-container is no longer in the page,
| and the success redirect uses window.softNavigate when it is there.
*/

const STK_TIMEOUT_S = 90;          // M-Pesa drops an unanswered prompt after about a minute
const POLL_FAST_MS = 3000;         // while a prompt is out
const POLL_SLOW_MS = 6000;         // otherwise (Paybill, idle)
const POLL_LIMIT_MS = 15 * 60 * 1000;

// Daraja result codes -> what to tell the customer
const STK_ERRORS = {
    1: ['Not enough M-Pesa balance', 'Your M-Pesa balance is too low for this payment. Top up or use Fuliza, then send the prompt again.'],
    1001: ['Another payment is in progress', 'Your phone is busy with another M-Pesa request. Wait a moment, then try again.'],
    1019: ['The prompt expired', 'The payment prompt expired before it was approved. Send it again.'],
    1025: ['M-Pesa could not send the prompt', 'Something went wrong on M-Pesa’s side. Try again or pay with Paybill.'],
    1032: ['You cancelled the prompt', 'The payment was cancelled on your phone. Send the prompt again when you are ready.'],
    1037: ['We couldn’t reach your phone', 'Make sure your phone is on and has network, then send the prompt again.'],
    2001: ['Wrong M-Pesa PIN', 'The PIN entered was not correct. Send the prompt again and enter your M-Pesa PIN.'],
    9999: ['M-Pesa could not send the prompt', 'Something went wrong on M-Pesa’s side. Try again or pay with Paybill.'],
};

const money = (n) => 'KES ' + Math.round(Number(n) || 0).toLocaleString('en-KE');

function initPaymentPage() {
    const root = document.getElementById('payment-container');
    if (!root || root.dataset.payReady) return;
    root.dataset.payReady = '1';

    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => [...root.querySelectorAll(sel)];

    const btn = document.getElementById('stkPushBtn');
    const phoneInput = document.getElementById('mpesa_phone');
    const invoiceId = document.getElementById('invoice_id')?.value || btn?.dataset.invoiceId;
    const phoneError = document.getElementById('px-phone-error');
    const statusUrl = root.dataset.statusUrl || `/payment/status/${root.dataset.invoiceUuid}`;
    const stkUrl = btn?.dataset.stkUrl || '/payment/mpesa/stk-push';

    const track = $('[data-pay-track]');
    const title = $('[data-pay-title]');
    const text = $('[data-pay-text]');
    const stages = $('[data-pay-stages]');
    const timer = $('[data-pay-timer]');
    const actions = $('[data-pay-actions]');
    const icon = $('.px-track-icon i');

    const state = {
        method: 'stk',
        requestId: null,        // CheckoutRequestID of the prompt we sent
        sentAt: 0,
        paid: Number(root.dataset.paid) || 0,
        total: Number(root.dataset.total) || 0,
        finished: false,
        timedOut: false,
        startedAt: Date.now(),
        pollTimer: null,
        tickTimer: null,
    };

    /* ---------------- tracker ---------------- */
    const ICONS = {
        idle: 'fa-hourglass-half',
        waiting: 'fa-hourglass-half',
        sending: 'fa-paper-plane',
        pin: 'fa-mobile-screen-button',
        confirm: 'fa-shield-halved',
        partial: 'fa-circle-half-stroke',
        success: 'fa-circle-check',
        error: 'fa-triangle-exclamation',
    };

    function setTrack(kind, heading, message) {
        track.dataset.state = kind;
        title.textContent = heading;
        text.textContent = message;
        icon.className = 'fa-solid ' + (ICONS[kind] || ICONS.idle);
    }

    // stage: sent | pin | confirm | done ; failed marks the active one red
    function setStage(active, failed = false) {
        const order = ['sent', 'pin', 'confirm', 'done'];
        const at = order.indexOf(active);
        stages.hidden = false;
        stages.querySelectorAll('[data-stage]').forEach((li) => {
            const i = order.indexOf(li.dataset.stage);
            li.classList.toggle('is-done', i < at || (active === 'done' && !failed));
            li.classList.toggle('is-active', i === at && !(active === 'done' && !failed));
            li.classList.toggle('is-failed', failed && i === at);
        });
    }

    function startTimer() {
        clearInterval(state.tickTimer);
        timer.hidden = false;
        const tick = () => {
            const s = Math.floor((Date.now() - state.sentAt) / 1000);
            timer.textContent = `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
            if (s >= STK_TIMEOUT_S && !state.timedOut && state.requestId) {
                state.timedOut = true;
                setTrack('error', 'No answer from your phone yet',
                    'If you approved the payment, we\'ll still pick it up here. If you didn\'t get the prompt, send it again or pay with Paybill.');
                actions.hidden = false;
            }
        };
        tick();
        state.tickTimer = setInterval(tick, 1000);
    }

    function stopTimer() {
        clearInterval(state.tickTimer);
        timer.hidden = true;
    }

    function updateBalance(paid) {
        const balance = Math.max(0, state.total - paid);
        root.querySelectorAll('[data-pay-balance]').forEach((el) => { el.textContent = money(balance); });
        $('[data-copy-balance]')?.setAttribute('data-copy', String(Math.ceil(balance)));
        const part = $('[data-pay-part]');
        if (part && paid > 0) {
            part.hidden = false;
            const received = part.querySelector('[data-pay-received]');
            if (received) received.textContent = money(paid);
        }
    }

    function idleMessage() {
        if (state.method === 'paybill') {
            setTrack('waiting', 'Waiting for your Paybill payment',
                'Send the payment from your phone. We\'ll confirm it here automatically – no need to refresh.');
            stages.hidden = true;
        } else {
            setTrack('idle', 'Waiting for your payment', 'Enter your M-Pesa number and tap Send prompt.');
            stages.hidden = true;
        }
        actions.hidden = true;
    }

    /* ---------------- tabs ---------------- */
    function showMethod(method) {
        state.method = method;
        $$('[data-pay-tab]').forEach((tab) => {
            if (tab.getAttribute('role') !== 'tab') return;
            const on = tab.dataset.payTab === method;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
        });
        $$('[data-pay-panel]').forEach((panel) => { panel.hidden = panel.dataset.payPanel !== method; });

        // a prompt that is still out keeps the tracker; otherwise show the method's waiting text
        if (!state.finished && !(state.requestId && track.dataset.state !== 'error')) {
            stopTimer();
            idleMessage();
        }
    }

    root.addEventListener('click', (e) => {
        const tab = e.target.closest('[data-pay-tab]');
        if (tab) showMethod(tab.dataset.payTab);
    });

    root.querySelector('[role="tablist"]')?.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        const next = state.method === 'stk' ? 'paybill' : 'stk';
        showMethod(next);
        root.querySelector(`[role="tab"][data-pay-tab="${next}"]`)?.focus();
    });

    /* ---------------- copy buttons ---------------- */
    root.addEventListener('click', async (e) => {
        const copyBtn = e.target.closest('[data-copy]');
        if (!copyBtn) return;
        const value = copyBtn.dataset.copy;
        try {
            await navigator.clipboard.writeText(value);
        } catch {
            const tmp = document.createElement('textarea');
            tmp.value = value;
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); } catch { /* ignore */ }
            tmp.remove();
        }
        const old = copyBtn.innerHTML;
        copyBtn.classList.add('is-copied');
        copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Copied';
        setTimeout(() => { copyBtn.classList.remove('is-copied'); copyBtn.innerHTML = old; }, 1600);
    });

    /* ---------------- send the STK prompt ---------------- */
    const PHONE_RE = /^(?:\+254|254|0)?7\d{8}$/;

    function showPhoneError(message) {
        if (!phoneError) return;
        phoneError.textContent = message || '';
        phoneError.hidden = !message;
        phoneInput?.classList.toggle('is-invalid', !!message);
    }

    async function sendPrompt() {
        const phone = (phoneInput?.value || '').replace(/[\s-]/g, '');

        if (!PHONE_RE.test(phone)) {
            showPhoneError('Enter a Safaricom number like 0712 345 678.');
            phoneInput?.focus();
            return;
        }
        showPhoneError('');

        btn.disabled = true;
        btn.classList.add('is-loading');
        state.requestId = null;
        state.timedOut = false;
        actions.hidden = true;
        stopTimer();

        setTrack('sending', 'Sending the prompt…', `Asking M-Pesa to send a payment prompt to ${phone}.`);
        setStage('sent');
        track.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        try {
            const response = await fetch(stkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ invoice_id: invoiceId, phone }),
            });

            let data = {};
            try { data = await response.json(); } catch { /* not JSON */ }

            if (response.status === 422) {
                const msg = data.errors?.phone?.[0] || data.message || 'Check the number and try again.';
                if (data.errors?.phone) showPhoneError('Enter a Safaricom number like 0712 345 678.');
                throw Object.assign(new Error(msg), { friendly: true });
            }

            if (!response.ok || !data.success) {
                console.error('STK push failed:', response.status, data);
                throw new Error('We couldn\'t reach M-Pesa just now. Try again in a moment, or pay with Paybill.');
            }

            state.requestId = data.data?.CheckoutRequestID || 'sent';
            state.sentAt = Date.now();

            const note = stages.querySelector('[data-stage="sent"] [data-stage-note]');
            if (note) note.textContent = `To ${phone}`;

            setTrack('pin', 'Check your phone', 'Enter your M-Pesa PIN on the prompt to approve the payment.');
            setStage('pin');
            startTimer();
            poll(true);

        } catch (error) {
            setTrack('error', 'The prompt was not sent', error.message);
            setStage('sent', true);
            actions.hidden = false;
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-loading');
        }
    }

    btn?.addEventListener('click', sendPrompt);
    phoneInput?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); sendPrompt(); } });
    phoneInput?.addEventListener('input', () => showPhoneError(''));
    $('[data-pay-retry]')?.addEventListener('click', () => { showMethod('stk'); sendPrompt(); });

    /* ---------------- status polling ---------------- */
    let inFlight = false;

    // leaving the page (soft navigation or not): stop everything
    const alive = () => root.isConnected && !state.finished;
    function stop() {
        state.finished = true;
        clearTimeout(state.pollTimer);
        clearInterval(state.tickTimer);
    }
    document.addEventListener('softnav:before', stop);

    function schedule() {
        clearTimeout(state.pollTimer);
        if (!alive()) return;
        if (Date.now() - state.startedAt > POLL_LIMIT_MS) {
            setTrack('error', 'Still waiting for your payment',
                'We stopped checking automatically. If you have paid, tap "I\'ve paid – check now" or reload this page.');
            stages.hidden = true;
            return;
        }
        const fast = state.requestId && !state.timedOut;
        state.pollTimer = setTimeout(() => poll(), fast ? POLL_FAST_MS : POLL_SLOW_MS);
    }

    function finish(data) {
        state.finished = true;
        clearTimeout(state.pollTimer);
        stopTimer();
        actions.hidden = true;
        if (state.requestId) setStage('done');
        setTrack('success', 'Payment received!',
            data.redirect ? 'Thank you. Taking you to your order…' : 'Thank you. Your order is being placed.');
        if (btn) btn.disabled = true;
        window.notify?.success?.('Payment received', { title: 'M-Pesa' });
        if (data.redirect) {
            setTimeout(() => {
                if (!root.isConnected) return;
                if (window.softNavigate) window.softNavigate(data.redirect, { replace: true });
                else window.location.replace(data.redirect);
            }, 1400);
        }
    }

    async function poll(manual = false) {
        if (inFlight || !alive()) return;
        inFlight = true;

        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('status ' + response.status);
            const data = await response.json();
            if (!root.isConnected) return;

            if (data.total) state.total = Number(data.total);

            if (data.paid) {
                finish(data);
                return;
            }

            // money arrived but not all of it (Paybill part payment, or an STK for less)
            const paidNow = Number(data.amount_paid) || 0;
            if (paidNow > state.paid) {
                state.paid = paidNow;
                updateBalance(paidNow);
                stopTimer();
                state.requestId = null;
                setTrack('partial', `Received ${money(paidNow)}`,
                    `Pay the balance of ${money(Math.max(0, state.total - paidNow))} to place your order.`);
                stages.hidden = true;
                actions.hidden = true;
            }

            // our prompt's result, from the M-Pesa callback
            const stk = data.stk;
            if (state.requestId && stk && (state.requestId === 'sent' || stk.checkout_request_id === state.requestId)) {
                if (stk.state === 'success') {
                    stopTimer();
                    setTrack('confirm', 'Payment approved', 'M-Pesa confirmed it. Placing your order…');
                    setStage('confirm');
                } else if (stk.state === 'failed') {
                    stopTimer();
                    const [head, body] = STK_ERRORS[Number(stk.code)] || ['The payment didn\'t go through', 'Send the prompt again, or pay with Paybill.'];
                    setTrack('error', head, body);
                    setStage('pin', true);
                    actions.hidden = false;
                    state.requestId = null;
                }
            }

            if (manual === 'check' && track.dataset.state !== 'partial') {
                setTrack('waiting', 'Not received yet',
                    'M-Pesa can take a minute to confirm. We\'ll keep checking and update this page.');
            }
        } catch (error) {
            console.warn('Payment status check failed:', error);
        } finally {
            inFlight = false;
            schedule();
        }
    }

    const checkBtn = $('[data-pay-check]');
    checkBtn?.addEventListener('click', async () => {
        checkBtn.classList.add('is-loading');
        clearTimeout(state.pollTimer);
        await poll('check');
        setTimeout(() => checkBtn.classList.remove('is-loading'), 400);
    });

    // check straight away when the customer comes back from the M-Pesa app
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && alive()) {
            clearTimeout(state.pollTimer);
            poll();
        }
    });

    idleMessage();
    poll();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPaymentPage);
else initPaymentPage();
