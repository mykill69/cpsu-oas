@extends('layouts.app')

@section('title', $college->name . ' · Files')

@section('crumbs')
    <a href="{{ route('qa.files.index') }}">College files</a><span class="sep">/</span><span class="here">{{ $college->code }}</span>
@endsection

@section('content')
@php $totalFiles = $files->sum('n'); @endphp
<x-masthead :index="$college->is_extension ? 'Extension campus' : 'Main campus'" :title="$college->name">
    <x-slot name="meta">
        <span>{{ $visits->count() }} {{ \Illuminate\Support\Str::plural('visit', $visits->count()) }} assigned · {{ $totalFiles }} {{ \Illuminate\Support\Str::plural('file', $totalFiles) }}</span>
        <span>In-charge: {{ $incharges->pluck('name')->join(', ') ?: 'none yet' }}</span>
    </x-slot>
</x-masthead>

<div class="grid-main">
    <section class="sheet">
        <div class="sheet-head">
            <x-card-title icon="visits">Survey visits</x-card-title>
            <span class="legend">
                <span><i class="cell"></i> Empty</span>
                <span><i class="cell part"></i> Partly filed</span>
                <span><i class="cell full"></i> Complete</span>
            </span>
        </div>
        @if ($visits->isEmpty())
            <x-empty title="Not assigned to any visit">Assign {{ $college->code }} from a survey visit's page.</x-empty>
        @else
            <div class="docket">
                @foreach ($visits as $visit)
                    @php $f = $files[$visit->id] ?? null; $sharesHere = $shares->where('survey_visit_id', $visit->id)->filter->isUsable()->count(); @endphp
                    <article>
                        <div class="docket-head">
                            <div>
                                <div class="sec-kicker">{{ $visit->type_label }}</div>
                                <h3 class="docket-title"><a href="{{ route('binder.visit', [$college, $visit]) }}">{{ $visit->name }}</a></h3>
                                <div class="muted">{{ $f->n ?? 0 }} files{{ $f ? ' · ' . \App\Models\Document::make(['size' => (int) $f->bytes])->human_size : '' }}@if ($sharesHere) · shared with {{ $sharesHere }} {{ \Illuminate\Support\Str::plural('accreditor', $sharesHere) }}@endif</div>
                            </div>
                            <x-stamp :open="$visit->is_active" />
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                            <x-strip :areas="$visit->areas" :filled="$coverage[$visit->id][$college->id] ?? []" />
                            <span class="toolbar">
                                <a class="btn btn-sm" href="{{ route('binder.visit', [$college, $visit]) }}#share"><x-icon name="share" /> Share</a>
                                <a class="btn btn-sm btn-primary" href="{{ route('binder.visit', [$college, $visit]) }}">Open files <x-icon name="arrow" /></a>
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="sheet">
        <div class="sheet-head"><x-card-title icon="upload">Latest uploads</x-card-title></div>
        @if ($recent->isEmpty())
            <x-empty title="Nothing uploaded yet.">Files from {{ $college->code }}'s in-charge will show here.</x-empty>
        @else
            <ul class="feed">
                @foreach ($recent as $doc)
                    <li>
                        <span class="ext {{ $doc->extension }}">{{ $doc->extension ?: 'file' }}</span>
                        <div>
                            <a href="{{ route('binder.folder', $doc->folder) }}">{{ \Illuminate\Support\Str::limit($doc->name, 48) }}</a>
                            <div class="doc-meta">{{ $doc->folder->area->short_label }}{{ $doc->folder->parameter ? '-' . $doc->folder->parameter->code : '' }} · {{ $doc->uploader?->name }} · {{ $doc->created_at->diffForHumans() }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
