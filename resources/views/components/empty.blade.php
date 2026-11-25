@props(['title', 'tinted' => true])
<div {{ $attributes->merge(['class' => 'empty' . ($tinted ? ' tinted' : '')]) }}>
    <svg class="art" viewBox="0 0 150 100" aria-hidden="true">
        <path d="M22 70c-8-6-10-20-2-26 4 8 6 16 2 26z" fill="#a8d9b6"/>
        <path d="M26 72c-2-12 6-22 16-22-2 10-8 18-16 22z" fill="#7cc795"/>
        <path d="M128 72c8-6 10-20 2-26-4 8-6 16-2 26z" fill="#a8d9b6"/>
        <path d="M124 74c2-12-6-22-16-22 2 10 8 18 16 22z" fill="#7cc795"/>
        <ellipse cx="75" cy="88" rx="48" ry="5" fill="#d7efdf"/>
        <path d="M42 30a4 4 0 0 1 4-4h18l6 6h34a4 4 0 0 1 4 4v44a4 4 0 0 1-4 4H46a4 4 0 0 1-4-4z" fill="#27914b"/>
        <path d="M40 42a4 4 0 0 1 4-4h62a4 4 0 0 1 4 4l-3 38a4 4 0 0 1-4 4H47a4 4 0 0 1-4-4z" fill="#3fae64"/>
        <circle cx="75" cy="60" r="11" fill="#fff" opacity=".95"/>
        <path d="M75 66v-12m0 0-5 5m5-5 5 5" stroke="#27914b" stroke-width="2.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <b>{{ $title }}</b>
    @if (trim($slot))<p>{{ $slot }}</p>@endif
    @isset($action){{ $action }}@endisset
</div>
