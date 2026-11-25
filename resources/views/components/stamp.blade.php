@props(['open' => true, 'on' => 'Open', 'off' => 'Closed'])
<span {{ $attributes->merge(['class' => 'stamp ' . ($open ? 'open' : 'closed')]) }}>{{ $open ? $on : $off }}</span>
