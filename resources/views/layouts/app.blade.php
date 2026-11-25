<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts._head')
</head>
<body>
<div class="shell">
    @include('layouts.sidebar')
    <div class="main-wrap">
        @yield('content')
    </div>
</div>
@include('layouts._chrome')
</body>
</html>
