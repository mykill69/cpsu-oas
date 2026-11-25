@extends('layouts.app')

@section('title', $user->exists ? $user->name : 'New account')

@section('crumbs')
    <a href="{{ route('qa.users.index') }}">Accounts</a><span class="sep">/</span>
    <span class="here">{{ $user->exists ? $user->name : 'New' }}</span>
@endsection

@section('content')
@php $role = old('role', $user->getRoleNames()->first() ?? 'campus_incharge'); @endphp

<x-masthead :index="$user->exists ? 'Accounts · ' . $user->email : 'Accounts · New entry'" :title="$user->exists ? $user->name : 'New account'" />

<form class="sheet form-sheet" method="POST" action="{{ $user->exists ? route('qa.users.update', $user) : route('qa.users.store') }}">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Person</h3>
            <p>They sign in with “Sign in with Google” using this exact institutional email{{ config('oas.allowed_domain') ? ' (@' . config('oas.allowed_domain') . ')' : '' }}. Nobody else can get in.</p>
        </div>
        <div class="row-2">
            <div class="field">
                <label class="lbl" for="name">Full name</label>
                <input class="input @error('name') bad @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="off">
                @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="lbl" for="email">Email</label>
                <input class="input @error('email') bad @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="off">
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Role</h3>
            <p>An in-charge builds folders and uploads files for one college or campus. QA roles manage visits and can read every college's files.</p>
        </div>
        <div>
            <div class="options" role="radiogroup" aria-label="Role">
                @foreach ($roles as $value => $label)
                    <label class="option">
                        <input type="radio" name="role" value="{{ $value }}" @checked($role === $value)>
                        <div>
                            <b>{{ $label }}</b>
                            <span>{{ ['qa_director' => 'Builds survey visits, assigns colleges, manages accounts.', 'administrator' => 'Same access as the QA Director.', 'campus_incharge' => 'Files evidence for their own college or campus only.'][$value] ?? '' }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('role')<div class="err">{{ $message }}</div>@enderror

            <div class="field" id="collegeField" style="margin-top:16px">
                <label class="lbl" for="college_id">College / campus</label>
                <select class="input @error('college_id') bad @enderror" id="college_id" name="college_id">
                    <option value="">Choose…</option>
                    @foreach ($colleges as $college)
                        <option value="{{ $college->id }}" @selected(old('college_id', $user->college_id) == $college->id)>{{ $college->code }} — {{ $college->name }}</option>
                    @endforeach
                </select>
                @error('college_id')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    @if (\App\Http\Controllers\Auth\LoginController::passwordLoginEnabled())
    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Fallback password <span class="opt" style="font-weight:400;color:var(--ink-3)">(optional)</span></h3>
            <p>Only used while password sign-in is switched on for setup. Normal sign-in is through Google.</p>
        </div>
        <div class="row-2">
            <div class="field">
                <label class="lbl" for="password">{{ $user->exists ? 'New password' : 'Password' }}</label>
                <input class="input @error('password') bad @enderror" id="password" name="password" type="password" autocomplete="new-password">
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="lbl" for="password_confirmation">Repeat password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            </div>
        </div>
    </div>
    @endif

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save account</button>
        <a class="btn" href="{{ route('qa.users.index') }}">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var field = document.getElementById('collegeField');
    function sync() { var r = document.querySelector('input[name="role"]:checked'); field.hidden = !r || r.value !== 'campus_incharge'; }
    document.querySelectorAll('input[name="role"]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
})();
</script>
@endpush
