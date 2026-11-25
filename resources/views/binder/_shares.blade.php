{{-- QA only: accreditor access to this college's binder for this visit. Expects $college, $surveyVisit, $shares. --}}
@php $justSent = session('sent_share'); @endphp
<section class="sheet share-card" id="share">
    <div class="sheet-head">
        <x-card-title icon="share">Accreditor access</x-card-title>
    </div>

    <form class="sheet-body" method="POST" action="{{ route('qa.shares.store', [$college, $surveyVisit]) }}" data-sending>
        @csrf
        <p class="muted" style="margin-bottom:14px">The accreditor gets an email with a private link and a one-time code. They see these files read-only, nothing else.</p>
        <div class="field">
            <label class="lbl" for="sh-name">Accreditor's name</label>
            <input class="input @error('name') bad @enderror" id="sh-name" name="name" value="{{ old('name') }}" required maxlength="150" placeholder="e.g. Dr. Maria Reyes">
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label class="lbl" for="sh-email">Email</label>
            <input class="input @error('email') bad @enderror" id="sh-email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" placeholder="accreditor@example.com">
            @error('email')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label class="lbl" for="sh-exp">Access until</label>
            <input class="input @error('expires_at') bad @enderror" id="sh-exp" name="expires_at" type="date" required
                   value="{{ old('expires_at', now()->addDays(14)->toDateString()) }}" min="{{ now()->addDay()->toDateString() }}" max="{{ now()->addYear()->subDay()->toDateString() }}">
            @error('expires_at')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label class="lbl" for="sh-note">Note in the email <span class="opt">(optional)</span></label>
            <textarea class="input" id="sh-note" name="note" rows="2" maxlength="1000" placeholder="e.g. For the preliminary survey on Dec 3–4.">{{ old('note') }}</textarea>
        </div>
        <button class="btn btn-primary" type="submit" style="margin-top:14px;width:100%"><x-icon name="mail" /> Send link &amp; code</button>
    </form>

    <ul class="plist share-list" style="border-top:1px solid var(--line)">
        {{-- Placeholder shown while a new share is being sent. --}}
        <li class="skel-row" hidden aria-hidden="true">
            <div class="top"><span class="skel" style="width:55%"></span><span class="skel" style="width:58px;height:20px;border-radius:999px"></span></div>
            <span class="skel" style="width:70%"></span>
            <span class="skel" style="width:45%;margin-top:8px"></span>
        </li>

        @foreach ($shares as $s)
            @php $tone = ['active' => 'open', 'invited' => 'blue', 'expired' => 'closed', 'revoked' => 'red'][$s->status]; @endphp
            <li class="{{ $justSent == $s->id ? ($s->email_error && ! $s->emailed_at ? 'just-failed' : 'just-sent') : '' }}" id="share-{{ $s->id }}">
                @if ($justSent == $s->id && ! $s->email_error)
                    <div class="sent-flag" role="status"><x-icon name="mail" /> Sent just now</div>
                @endif
                <div class="top">
                    <div style="min-width:0">
                        <div class="t-title">{{ $s->name }}</div>
                        <div class="t-sub" style="word-break:break-all">{{ $s->email }}</div>
                    </div>
                    <span class="stamp {{ $tone }}">{{ ucfirst($s->status) }}</span>
                </div>

                @if ($s->email_error)
                    <div class="mail-state bad" title="{{ $s->email_error }}"><x-icon name="alert" /> Email not sent. Check the mail settings, then Resend.</div>
                @elseif ($s->emailed_at)
                    <div class="mail-state ok"><x-icon name="mail" /> Link &amp; code emailed {{ $s->emailed_at->diffForHumans() }} · {{ $s->emailed_at->format('M j, g:i A') }}</div>
                @endif

                <div class="muted">
                    Until {{ $s->expires_at->format('M j, Y') }}
                    · {{ $s->last_viewed_at ? 'last opened ' . $s->last_viewed_at->diffForHumans() : 'not opened yet' }}
                </div>
                @if ($s->isUsable())
                    <div style="display:flex;gap:6px;margin-top:8px">
                        <form class="inline" method="POST" action="{{ route('qa.shares.resend', $s) }}" data-sending="{{ $s->email }}"
                              data-confirm="A new link and code go to {{ $s->email }}. The old link stops working." data-confirm-title="Send a new link?" data-confirm-ok="Send">
                            @csrf <button class="link" type="submit">Resend</button>
                        </form>
                        <form class="inline" method="POST" action="{{ route('qa.shares.revoke', $s) }}"
                              data-confirm="{{ $s->name }} will lose access immediately." data-confirm-title="Revoke access?" data-confirm-ok="Revoke">
                            @csrf <button class="link danger" type="submit">Revoke</button>
                        </form>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    {{-- Frosted "sending" overlay over the whole card. --}}
    <div class="sending-veil" hidden role="status" aria-live="polite">
        <div class="sending-box">
            <div class="env" aria-hidden="true">
                <svg viewBox="0 0 64 48"><rect x="4" y="8" width="56" height="36" rx="6" fill="#fff" stroke="#27914b" stroke-width="2.5"/><path d="M6 12l26 18L58 12" fill="none" stroke="#27914b" stroke-width="2.5" stroke-linejoin="round"/></svg>
                <span class="trail"></span>
            </div>
            <b>Sending link &amp; code…</b>
            <span class="to"></span>
            <div class="bar"><i></i></div>
            <small>Please keep this page open.</small>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    var card = document.getElementById('share'); if (!card) return;
    var veil = card.querySelector('.sending-veil'), to = veil.querySelector('.to');

    // Fired by the page's submit handler once a form really submits (after any confirm dialog).
    document.addEventListener('oas:submitting', function (e) {
        var form = e.detail;
        if (!form.hasAttribute('data-sending') || !card.contains(form)) return;
        var email = form.getAttribute('data-sending') || (form.querySelector('[name=email]') || {}).value || '';
        to.textContent = email ? 'to ' + email : '';
        if (!form.getAttribute('data-sending')) card.querySelector('.skel-row').hidden = false; // new share: show placeholder row
        veil.hidden = false;
        card.setAttribute('aria-busy', 'true');
    });

    // Bring the just-sent share into view.
    var hit = card.querySelector('.just-sent, .just-failed');
    if (hit) setTimeout(function () { hit.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 150);
})();
</script>
@endpush
