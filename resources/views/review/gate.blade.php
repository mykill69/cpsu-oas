@php
    $photo = file_exists(public_path('images/campus.jpg')) ? asset('images/campus.jpg') : null;
    [$u, $d] = explode('@', $share->email) + [1 => ''];
    $masked = mb_substr($u, 0, 2) . str_repeat('•', max(1, mb_strlen($u) - 2)) . '@' . $d;
    $usable = $share->isUsable();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<div class="auth">
    <section class="auth-paper" @if($photo) style="--hero-photo: url('{{ $photo }}')" @endif>
        <div class="auth-seal">
            <img src="{{ asset('images/cpsu-logo.png') }}" alt="CPSU seal">
            <div>
                <b>Central Philippines State University</b>
                <small>Office of Quality Assurance</small>
            </div>
        </div>
        <div style="position:relative">
            <h1>Confidential documents, <mark>shared with you.</mark></h1>
            <p class="lede">The QA office gave you read-only access to the evidence filed by {{ $share->college->name }} for the {{ $share->surveyVisit->name }}.</p>
        </div>
        <div class="auth-index">
            <span class="cap">Shared</span>
            <dl class="kv" style="color:#e6f3ea">
                <dt style="color:#bfe0c8">College</dt><dd>{{ $share->college->name }}</dd>
                <dt style="color:#bfe0c8">Survey visit</dt><dd>{{ $share->surveyVisit->name }}</dd>
                <dt style="color:#bfe0c8">For</dt><dd>{{ $share->name }}</dd>
                <dt style="color:#bfe0c8">Until</dt><dd>{{ $share->expires_at->format('F j, Y') }}</dd>
            </dl>
        </div>
    </section>

    <main class="auth-form-wrap">
        <div class="auth-form">
            <div class="eyebrow">Accreditor access</div>

            @if (! $usable)
                <h2>{{ $share->revoked_at ? 'Access was withdrawn' : 'This access has ended' }}</h2>
                <p class="sub">
                    {{ $share->revoked_at ? 'The QA office has revoked this link.' : 'The access period ended on ' . $share->expires_at->format('F j, Y') . '.' }}
                    If you still need the documents, please contact the CPSU Office of Quality Assurance.
                </p>
            @else
                <h2>Enter your code</h2>
                <p class="sub">We emailed a 6-digit code to <b>{{ $masked }}</b>. It works once and expires after {{ \App\Models\VisitShare::OTP_MINUTES }} minutes.</p>

                @foreach (['success' => 'ok', 'error' => 'bad'] as $key => $tone)
                    @if (session($key))<div class="slip {{ $tone }}" role="status"><span class="slip-text">{{ session($key) }}</span></div>@endif
                @endforeach

                <form method="POST" action="{{ route('review.verify', $token) }}" id="otpForm">
                    @csrf
                    <label class="lbl" for="code">One-time code</label>
                    <input class="input otp-input {{ $errors->has('code') ? 'bad' : '' }}" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                           pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="••••••">
                    @error('code')<div class="err">{{ $message }}</div>@enderror
                    <button class="btn btn-primary" type="submit">Open documents</button>
                </form>

                <form method="POST" action="{{ route('review.resend', $token) }}" style="margin-top:16px;text-align:center">
                    @csrf
                    <span class="muted">Didn't get it, or it expired?</span>
                    <button class="link" type="submit">Email me a new code</button>
                </form>
            @endif

            <div class="lockline"><x-icon name="lock" /> Codes only go to the email address the QA office registered.</div>
        </div>
    </main>
</div>
<script>
    (function () {
        var f = document.getElementById('otpForm'); if (!f) return;
        var c = document.getElementById('code');
        c.addEventListener('input', function () { c.value = c.value.replace(/\D/g, '').slice(0, 6); });
        f.addEventListener('submit', function () { f.querySelector('[type=submit]').classList.add('is-busy'); });
    })();
</script>
</body>
</html>
