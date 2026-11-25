@extends('layouts.app')

@section('title', 'My survey visits')

@section('content')
<x-masthead :index="$college->code . ' · ' . $college->name" title="My survey visits">
    <x-slot name="meta"><span>Open a visit to build folders under each parameter and upload your evidence.</span></x-slot>
</x-masthead>

<div class="sheet">
    <div class="sheet-head">
        <x-card-title icon="visits">Assigned by the QA office</x-card-title>
        <span class="legend">
            <span><i class="cell"></i> Empty</span>
            <span><i class="cell part"></i> Partly filed</span>
            <span><i class="cell full"></i> Complete</span>
        </span>
    </div>

    @if ($visits->isEmpty())
        <x-empty title="No visits assigned yet">The QA office adds {{ $college->code }} to a survey visit once it's scheduled. It will appear here.</x-empty>
    @else
        <div class="docket">
            @foreach ($visits as $visit)
                @php $filled = $coverage[$visit->id][$college->id] ?? []; @endphp
                <article>
                    <div class="docket-head">
                        <div>
                            <div class="sec-kicker">{{ $visit->type_label }} · {{ $visit->areas_count }} areas · {{ $visit->parameters_count }} parameters</div>
                            <h3 class="docket-title"><a href="{{ route('binder.visit', [$college, $visit]) }}">{{ $visit->name }}</a></h3>
                            @if ($visit->description)<p class="muted" style="margin-top:2px">{{ $visit->description }}</p>@endif
                        </div>
                        <x-stamp :open="$visit->is_active" on="Open for filing" />
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <x-strip :areas="$visit->areas" :filled="$filled" />
                        <span class="toolbar">
                            <span class="muted">{{ (int) ($fileCounts[$visit->id] ?? 0) }} files</span>
                            <a class="btn btn-sm {{ $visit->is_active ? 'btn-primary' : '' }}" href="{{ route('binder.visit', [$college, $visit]) }}">Open binder <x-icon name="chevron" /></a>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
