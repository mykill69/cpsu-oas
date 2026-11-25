@php $photo = file_exists(public_path('images/campus.jpg')) ? asset('images/campus.jpg') : null; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
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
            <h1>Every area, every parameter, <mark>one place.</mark></h1>
            <p class="lede">The QA office sets up each AACCUP survey visit. Colleges and campuses file their evidence under it, folder by folder.</p>
        </div>

        <div class="auth-index" aria-hidden="true">
            <span class="cap">Areas of evaluation</span>
            <ol>
                @foreach (['I' => 'Vision, Mission, Goals & Objectives', 'II' => 'Faculty', 'III' => 'Curriculum & Instruction', 'IV' => 'Support to Students', 'V' => 'Research', 'VI' => 'Extension & Community', 'VII' => 'Library', 'VIII' => 'Physical Plant & Facilities', 'IX' => 'Laboratories', 'X' => 'Administration'] as $no => $area)
                    <li><span>{{ $no }}</span>{{ $area }}</li>
                @endforeach
            </ol>
        </div>
    </section>

    <main class="auth-form-wrap">
        @php
            $google = filled(config('services.google.client_id'));
            $passwords = \App\Http\Controllers\Auth\LoginController::passwordLoginEnabled();
            $domain = config('oas.allowed_domain');
        @endphp
        <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="eyebrow">CPSU · Online Accreditation System</div>
            <h2>Welcome back</h2>
            <p class="sub">Sign in with your institutional Google account{{ $domain ? ' (@' . $domain . ')' : '' }}. Your account must be registered by the QA office.</p>

            @if ($errors->any())
                <div class="slip bad" role="alert"><span class="slip-text">{{ $errors->first() }}</span></div>
            @endif

            @if ($google)
                <a class="gbtn" href="{{ route('auth.google') }}">
                    <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    Sign in with Google
                </a>
            @else
                <div class="notice warn">Google sign-in isn't set up yet (GOOGLE_CLIENT_ID is empty). Password sign-in is on until it is.</div>
            @endif

            @if ($passwords)
            @if ($google)<div class="divider">or, for setup only</div>@endif
            <div class="field">
                <label class="lbl" for="email">Email</label>
                <input class="input {{ $errors->has('email') ? 'bad' : '' }}" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label class="lbl" for="password">Password</label>
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="remember" @checked(old('remember'))> Keep me signed in on this computer</label>
            </div>

            <button class="btn btn-primary" type="submit">Sign in with password</button>
            @endif
            <div class="lockline"><x-icon name="shield" /> Files in this system are confidential to CPSU and its accreditors.</div>
        </form>
    </main>
</div>
<script>
    document.querySelector('.auth-form').addEventListener('submit', function () { var b = this.querySelector('[type=submit]'); if (b) b.classList.add('is-busy'); });
</script>
</body>
</html>
