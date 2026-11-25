{{-- Accreditor layout: no sidebar, no account. A bar says exactly what was shared and until when. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
<div class="review-bar">
    <x-icon name="lock" />
    <span>Confidential · shared with <b>{{ $share->name }}</b> · {{ $share->college->name }} · access until {{ $share->expires_at->format('M j, Y') }}</span>
    <span class="sp"></span>
    <form method="POST" action="{{ route('review.leave', $token) }}">
        @csrf
        <button type="submit">Leave</button>
    </form>
</div>
<div class="main-wrap" style="max-width:1280px;margin:0 auto">
    @yield('content')
</div>
@include('layouts._chrome')
</body>
</html>
