{{-- Coverage strip: one cell per area. $filled = [area_id => slots that hold at least one folder]. --}}
@props(['areas', 'filled' => []])
@php
    $complete = 0;
    $cells = $areas->map(function ($area) use ($filled, &$complete) {
        $total = $area->slots();
        $done = min((int) ($filled[$area->id] ?? 0), $total);
        $state = ($done === 0 || $total === 0) ? '' : ($done >= $total ? 'full' : 'part');
        if ($state === 'full') $complete++;
        return ['label' => $area->short_label, 'state' => $state, 'title' => "{$area->name}: {$done} of {$total} filed"];
    });
@endphp
<span {{ $attributes->merge(['class' => 'strip']) }} role="img" aria-label="{{ $complete }} of {{ $areas->count() }} areas fully filed">
    @foreach ($cells as $c)
        <span class="cell {{ $c['state'] }}" title="{{ $c['title'] }}">{{ $c['label'] }}</span>
    @endforeach
</span>
