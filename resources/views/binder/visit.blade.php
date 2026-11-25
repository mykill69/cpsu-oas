@extends($mode === 'review' ? 'layouts.review' : 'layouts.app')

@section('title', $college->code . ' · ' . $surveyVisit->name)

@section('crumbs')
    @include('binder._crumbs', ['visit' => $surveyVisit])
    <span class="here">{{ $surveyVisit->name }}</span>
@endsection

@section('content')
@php
    $places = $surveyVisit->areas->sum(fn ($a) => $a->slots());
    $done = array_sum($filled);
    $fileTotal = array_sum(array_column($counts, 'files'));
    $countLabel = fn ($c) => $c['folders'] . ' ' . \Illuminate\Support\Str::plural('folder', $c['folders']) . ' · ' . $c['files'] . ' ' . \Illuminate\Support\Str::plural('file', $c['files']);
    $statusOff = $mode === 'incharge' ? 'Closed' : 'Read only';
@endphp

<x-masthead :index="$college->name . ' · ' . $surveyVisit->type_label" :title="$surveyVisit->name">
    <x-slot name="meta">
        <x-stamp :open="$canEdit" on="Open for filing" :off="$statusOff" />
        <span>{{ $done }} of {{ $places }} places filed · {{ $fileTotal }} {{ \Illuminate\Support\Str::plural('file', $fileTotal) }}</span>
    </x-slot>
    @if ($mode === 'qa')
        <x-slot name="actions">
            <a class="btn btn-primary" href="#share"><x-icon name="share" /> Share with accreditor</a>
            <a class="btn" href="{{ route('qa.survey-visits.show', $surveyVisit) }}">Visit structure</a>
        </x-slot>
    @endif
</x-masthead>

@if ($mode === 'qa')
    <div class="notice"><x-icon name="eye" /> You're viewing {{ $college->name }}'s files. Only their in-charge can add or change folders.</div>
@elseif ($mode === 'review')
    <div class="notice"><x-icon name="lock" /> Read-only access granted by the CPSU Office of Quality Assurance. Please keep these documents confidential.</div>
@elseif (! $canEdit)
    <div class="notice warn">This visit is closed. You can still open and download your files.</div>
@endif

@if ($surveyVisit->areas->isEmpty())
    <div class="sheet"><x-empty title="This visit has no areas yet">The QA office is still setting it up.</x-empty></div>
@else
    <div class="binder {{ $mode === 'qa' ? 'with-side' : '' }}">
        <nav class="rail" aria-label="Areas">
            @foreach ($surveyVisit->areas as $area)
                @php $t = $area->slots(); $f = $filled[$area->id] ?? 0; @endphp
                <a class="tab {{ $f && $t ? ($f >= $t ? 'full' : 'part') : '' }}" href="#area-{{ $area->id }}" title="{{ $area->name }} — {{ $f }}/{{ $t }}">{{ $area->short_label }}</a>
            @endforeach
        </nav>

        <div class="sheet">
            <div class="sheet-head">
                <x-card-title icon="folder">Areas &amp; parameters</x-card-title>
                <x-strip :areas="$surveyVisit->areas" :filled="$filled" />
            </div>
            @foreach ($surveyVisit->areas as $area)
                <section class="sec" id="area-{{ $area->id }}">
                    <div class="sec-kicker">
                        <span>{{ $area->numeral ? 'Area ' . $area->numeral : 'Section ' . $loop->iteration }}</span>
                        <span>·</span>
                        <span>{{ $filled[$area->id] ?? 0 }}/{{ $area->slots() }} filed</span>
                    </div>
                    <h2 class="sec-title">{{ $area->title }}</h2>

                    <div class="rows">
                        @if ($area->has_parameters)
                            @foreach ($area->parameters as $parameter)
                                @php $c = $counts['p' . $parameter->id] ?? null; @endphp
                                <a class="prow" href="{{ $links->node($college, $area, $parameter) }}">
                                    <span class="letter">{{ $parameter->code }}</span>
                                    <span class="pname">{{ $parameter->name }}</span>
                                    <span class="count {{ $c ? 'has' : '' }}"><i class="dot"></i>{{ $c ? $countLabel($c) : 'Nothing filed' }}</span>
                                    <x-icon name="chevron" class="chev" />
                                </a>
                            @endforeach
                        @else
                            @php $c = $counts['a' . $area->id] ?? null; @endphp
                            <a class="prow" href="{{ $links->node($college, $area) }}">
                                <span class="letter"><x-icon name="folder" /></span>
                                <span class="pname">Folders filed directly under this area</span>
                                <span class="count {{ $c ? 'has' : '' }}"><i class="dot"></i>{{ $c ? $countLabel($c) : 'Nothing filed' }}</span>
                                <x-icon name="chevron" class="chev" />
                            </a>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        @if ($mode === 'qa')
            <div class="stack">
                @include('binder._shares')
            </div>
        @endif
    </div>
@endif
@endsection
