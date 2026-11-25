@extends('layouts.app')

@section('title', 'College files')

@section('content')
<x-masthead index="QA office" title="College files">
    <x-slot name="meta">Everything the colleges and campuses have filed, by college. Search by file name across all of them.</x-slot>
</x-masthead>

<form class="sheet" method="GET" action="{{ route('qa.files.index') }}" style="padding:14px 18px;display:flex;gap:10px;flex-wrap:wrap">
    <label class="search" style="flex:1;min-width:240px">
        <span class="sr-only">Search files</span>
        <x-icon name="search" />
        <input class="input" type="search" name="q" value="{{ $q }}" placeholder="Search file names, e.g. “board resolution”">
    </label>
    <button class="btn btn-primary" type="submit">Search</button>
    @if ($q !== '')<a class="btn" href="{{ route('qa.files.index') }}">Clear</a>@endif
</form>

@if ($results !== null)
    <section class="sheet">
        <div class="sheet-head">
            <x-card-title icon="search">Results for “{{ $q }}”</x-card-title>
            <span class="sheet-note">{{ $results->count() }}{{ $results->count() === 60 ? '+' : '' }} {{ \Illuminate\Support\Str::plural('file', $results->count()) }}</span>
        </div>
        @if ($results->isEmpty())
            <x-empty title="No files match">Try a shorter word, or part of the file name.</x-empty>
        @else
            <ul class="doclist">
                @foreach ($results as $doc)
                    <li>
                        <span class="ext {{ $doc->extension }}">{{ $doc->extension ?: 'file' }}</span>
                        <div>
                            <a class="doc-name" href="{{ route('binder.folder', $doc->folder) }}">{{ $doc->name }}</a>
                            <div class="doc-meta">
                                <span class="code">{{ $doc->folder->college->code }}</span>
                                {{ $doc->folder->area->surveyVisit->name }} · {{ $doc->folder->area->short_label }}{{ $doc->folder->parameter ? '-' . $doc->folder->parameter->code : '' }} · {{ $doc->folder->name }}
                                · {{ $doc->human_size }} · {{ $doc->created_at->format('M j, Y') }}
                            </div>
                        </div>
                        <div class="tools">
                            <a class="ibtn" href="{{ route('documents.show', $doc) }}" aria-label="Download {{ $doc->name }}" title="Download"><x-icon name="download" /></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif

<section class="sheet">
    <div class="sheet-head">
        <x-card-title icon="college">By college &amp; campus</x-card-title>
    </div>
    <div class="table-scroll">
        <table class="ledger">
            <thead><tr><th>College / campus</th><th class="num">Visits</th><th class="num">Folders</th><th class="num">Files</th><th class="num">Size</th><th>Last upload</th><th><span class="sr-only">Open</span></th></tr></thead>
            <tbody>
            @foreach ($colleges->groupBy(fn ($c) => $c->is_extension ? 'Extension campuses' : 'Main campus') as $group => $rows)
                <tr class="group"><td colspan="7">{{ $group }}</td></tr>
                @foreach ($rows as $college)
                    @php $s = $stats[$college->id] ?? null; @endphp
                    <tr>
                        <td>
                            <a class="t-title" href="{{ route('qa.files.show', $college) }}">{{ $college->name }}</a>
                            <div class="t-sub"><span class="code">{{ $college->code }}</span></div>
                        </td>
                        <td class="num">{{ $college->survey_visits_count }}</td>
                        <td class="num">{{ $college->folders_count }}</td>
                        <td class="num">{{ $s->files ?? 0 }}</td>
                        <td class="num nowrap">{{ $s ? \App\Models\Document::make(['size' => (int) $s->bytes])->human_size : '—' }}</td>
                        <td class="nowrap">{{ $s ? \Carbon\Carbon::parse($s->last_at)->diffForHumans() : '—' }}</td>
                        <td class="end"><a class="btn btn-sm" href="{{ route('qa.files.show', $college) }}">Open <x-icon name="arrow" /></a></td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
