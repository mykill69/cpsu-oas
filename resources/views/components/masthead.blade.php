{{-- Hero banner used at the top of every page: breadcrumbs, title, subtitle, actions, school-year chip. --}}
@props(['index' => null, 'title', 'tall' => false])
@php
    $photo = file_exists(public_path('images/campus.jpg')) ? asset('images/campus.jpg') : null;
    $me = auth()->user();
@endphp
<header class="hero {{ $tall ? 'tall' : '' }}" @if($photo) style="--hero-photo: url('{{ $photo }}')" @endif>
    <div class="hero-top">
        <div style="min-width:0">
            @hasSection('crumbs')
                <nav class="crumbs" aria-label="Breadcrumb">@yield('crumbs')</nav>
            @endif
            <div style="display:flex;align-items:center;gap:6px">
                @if ($me)
                    <button class="menu-btn" type="button" aria-label="Open menu" aria-controls="side" aria-expanded="false" id="menuBtn"><x-icon name="menu" /></button>
                @endif
                <div style="min-width:0">
                    @if ($index)<div class="hero-eyebrow">{{ $index }}</div>@endif
                    <h1>{{ $title }}</h1>
                </div>
            </div>
            @isset($meta)<div class="hero-sub">{{ $meta }}</div>@endisset
        </div>

        @if ($me)
          @php $sy = \App\Models\SchoolYear::viewing(); @endphp
          <div class="hero-right">
            <span class="year-chip {{ $sy->is_current ? '' : 'past' }}" title="{{ $sy->is_current ? 'Current school year' : 'Past school year — read-only for colleges' }}">
                <x-icon name="clock" /> {{ $sy->label }}{{ $sy->is_current ? '' : ' · past' }}
            </span>
          </div>
        @endif
    </div>
    @isset($actions)<div class="hero-actions">{{ $actions }}</div>@endisset
</header>
