@php
    $me = auth()->user();
    $items = $me->isQa()
        ? [
            ['Overview', 'home', 'qa.dashboard', ['qa.dashboard']],
            ['Survey visits', 'visits', 'qa.survey-visits.index', ['qa.survey-visits.*', 'binder.*']],
            ['College files', 'folder', 'qa.files.index', ['qa.files.*']],
            ['Accreditor access', 'share', 'qa.shares.index', ['qa.shares.*']],
            'Records',
            ['Colleges & campuses', 'college', 'qa.colleges.index', ['qa.colleges.*']],
            ['Accounts', 'people', 'qa.users.index', ['qa.users.*']],
            ['School years', 'clock', 'qa.school-years.index', ['qa.school-years.*']],
            ['Google Drive', 'drive', 'qa.drive.index', ['qa.drive.*']],
        ]
        : ($me->isInCharge() ? [['My survey visits', 'visits', 'binder.index', ['binder.*']]] : []);
    $viewing = \App\Models\SchoolYear::viewing();
    $years = \App\Models\SchoolYear::orderByDesc('start_year')->get();
@endphp
<aside class="side" id="side">
    <a class="brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('images/cpsu-logo.png') }}" alt="">
        <span>
            <b>CPSU · OAS</b>
            <small>Online Accreditation<br>System</small>
        </span>
    </a>

    <form class="year-pick" method="POST" action="{{ route('school-year.switch') }}">
        @csrf
        <label for="sy">School year</label>
        <select id="sy" name="school_year_id" onchange="this.form.submit()">
            @foreach ($years as $y)
                <option value="{{ $y->id }}" @selected($y->id === $viewing->id)>{{ $y->label }}{{ $y->is_current ? '' : ' (past)' }}</option>
            @endforeach
        </select>
        <noscript><button type="submit">Go</button></noscript>
    </form>

    <nav aria-label="Main">
        @foreach ($items as $item)
            @if (is_string($item))
                <div class="side-label">{{ $item }}</div>
            @else
                @php $on = request()->routeIs(...$item[3]); @endphp
                <a class="idx {{ $on ? 'on' : '' }}" href="{{ route($item[2]) }}" @if($on) aria-current="page" @endif>
                    <x-icon :name="$item[1]" /> {{ $item[0] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Account menu: opens upward with email and Sign out. --}}
    <details class="acct">
        <summary aria-label="Account menu for {{ $me->name }}">
            <span class="av" aria-hidden="true">{{ mb_strtoupper(mb_substr($me->name, 0, 1)) }}</span>
            <span class="acct-txt">
                <b>{{ $me->name }}</b>
                <small>{{ $me->role_label }}{{ $me->college ? ' · ' . $me->college->code : '' }}</small>
            </span>
            <x-icon name="up" class="chev" />
        </summary>
        <div class="acct-menu" role="menu">
            <div class="meta">
                <b>{{ $me->name }}</b>
                <span>{{ $me->email }}</span>
                <span>{{ $me->college ? $me->college->name : 'Office of Quality Assurance' }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem"><x-icon name="logout" /> Sign out</button>
            </form>
        </div>
    </details>
</aside>
