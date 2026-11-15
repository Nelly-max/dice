{{-- Booking pieces (Airbnb stay card, Home City viewing form, booking page, Hub → Bookings).
     Kept here so no asset rebuild is needed; uses the subdivision tokens (--accent …). --}}
<style>
.ab-book-form { margin: 0; }
.ab-book-error { display: flex; gap: .45rem; align-items: flex-start; margin: -.35rem 0 .7rem; color: #e11d48; font-size: .82rem; font-weight: 700; }
.ab-book-small { margin: .6rem 0 0; color: var(--ink-soft); font-size: .78rem; text-align: center; }
body[data-subdivision] .ab-dates textarea { width: 100%; min-height: 4.2rem; margin: 0; padding: .6rem .7rem; border: 1px solid var(--line); border-radius: .75rem; background: var(--surface-tint); color: var(--ink); font: 700 .9rem "Nunito", system-ui, sans-serif; resize: vertical; }
.ab-dates span small { font-weight: 700; text-transform: none; letter-spacing: 0; opacity: .8; }
.ab-who { margin: 0 0 .9rem; border: 1px solid var(--line); border-radius: .9rem; }
.ab-who summary { display: flex; align-items: center; gap: .5rem; padding: .7rem .85rem; cursor: pointer; list-style: none; font-size: .88rem; font-weight: 800; color: var(--ink); }
.ab-who summary::-webkit-details-marker { display: none; }
.ab-who summary i { color: var(--accent); }
.ab-who summary em { margin-left: auto; font-style: normal; font-size: .78rem; color: var(--accent-strong); }
.ab-who[open] summary em { display: none; }
.ab-who .ab-dates { margin: 0; padding: 0 .85rem .85rem; }
.hc-viewing { margin: 1rem 0 0; padding: 1rem 0 0; border-top: 1px dashed var(--line); }
.hc-viewing h3 { display: flex; align-items: center; gap: .5rem; margin: 0 0 .8rem; font-size: 1rem; font-weight: 900; color: var(--ink); }
.hc-viewing h3 i { color: var(--accent); }
@media (max-height: 920px), (max-width: 900px) { .hc-lister:has(form) { position: static; } }

/* booking page */
.bk-page { max-width: 980px; margin: 0 auto; padding: 1.2rem 0 3rem; }
.bk-head { display: flex; flex-wrap: wrap; align-items: center; gap: .8rem 1rem; margin: 0 0 1rem; }
.bk-head h1 { margin: 0; font-size: 1.5rem; font-weight: 900; color: var(--ink); letter-spacing: -.01em; }
.bk-head p { flex-basis: 100%; margin: 0; color: var(--ink-soft); font-weight: 700; }
.bk-state { --clr: var(--accent); display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .8rem; border-radius: 99px; font-size: .8rem; font-weight: 900; color: var(--clr); background: color-mix(in srgb, var(--clr) 13%, transparent); }
.bk-state::before { content: ''; width: .5rem; height: .5rem; border-radius: 50%; background: currentColor; }
.bk-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 1rem; align-items: start; }
.bk-grid .sv-panel + .sv-panel { margin-top: 1rem; }
.bk-panel-title { display: flex; align-items: center; gap: .5rem; margin: 0 0 .9rem; font-size: 1rem; font-weight: 900; color: var(--ink); }
.bk-panel-title i { color: var(--accent); }
.bk-steps { display: grid; gap: 0; margin: 0; padding: 0; list-style: none; }
.bk-steps li { position: relative; display: grid; grid-template-columns: 1.9rem minmax(0, 1fr); gap: .7rem; padding: 0 0 1rem; }
.bk-steps li::before { content: ''; position: absolute; left: .9rem; top: 1.9rem; bottom: 0; width: 2px; background: var(--line); }
.bk-steps li:last-child { padding-bottom: 0; } .bk-steps li:last-child::before { display: none; }
.bk-steps li > i { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: 50%; font-size: .8rem; color: var(--accent); background: var(--accent-tint); }
.bk-steps b { display: block; font-size: .9rem; color: var(--ink); }
.bk-steps small { font-size: .78rem; color: var(--ink-soft); }
.bk-steps p { margin: .25rem 0 0; font-size: .86rem; color: var(--ink-soft); white-space: pre-line; }
.bk-note { margin: .9rem 0 0; padding: .75rem .9rem; border-radius: .8rem; background: var(--surface-tint); font-size: .88rem; color: var(--ink); }
.bk-cancel { margin: 1rem 0 0; }
.bk-cancel summary { cursor: pointer; font-size: .86rem; font-weight: 800; color: #e11d48; list-style: none; }
.bk-cancel summary::-webkit-details-marker { display: none; }
.bk-cancel form { display: grid; gap: .6rem; margin-top: .7rem; }
.bk-cancel input { height: 2.7rem; padding: 0 .7rem; border: 1px solid var(--line); border-radius: .7rem; background: var(--surface-tint); color: var(--ink); font: 700 .9rem "Nunito", system-ui, sans-serif; }
.sv-btn--danger { color: #fff !important; background: #e11d48 !important; border-color: #e11d48 !important; }
.bk-save { display: flex; gap: .6rem; align-items: flex-start; margin: 0 0 1rem; padding: .8rem 1rem; border: 1px solid var(--accent-line, var(--line)); border-radius: .9rem; background: var(--accent-tint); font-size: .86rem; color: var(--ink); }
.bk-save i { margin-top: .15rem; color: var(--accent); }
@media (max-width: 820px) { .bk-grid { grid-template-columns: minmax(0, 1fr); } .bk-page { padding-top: .6rem; } }
</style>
