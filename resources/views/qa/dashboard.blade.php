@extends('layouts.app')

@section('title', 'Overview')

@section('content')
<x-masthead title="Overview" :tall="true">
    <x-slot name="meta">Track accreditation progress and manage survey visits</x-slot>
</x-masthead>

<div class="stats">
    <a class="stat green" href="{{ route('qa.survey-visits.index') }}">
        <span class="stat-ico"><x-icon name="visits" /></span>
        <span><b>{{ $stats['open_visits'] }}</b><span>Open survey visits</span></span>
        <span class="go"><x-icon name="arrow" /></span>
    </a>
    <a class="stat blue" href="{{ route('qa.colleges.index') }}">
        <span class="stat-ico"><x-icon name="college" /></span>
        <span><b>{{ $stats['colleges'] }}</b><span>Active colleges &amp; campuses</span></span>
        <span class="go"><x-icon name="arrow" /></span>
    </a>
    <a class="stat orange" href="{{ route('qa.users.index') }}">
        <span class="stat-ico"><x-icon name="people" /></span>
        <span><b>{{ $stats['incharges'] }}</b><span>In-charge accounts</span></span>
        <span class="go"><x-icon name="arrow" /></span>
    </a>
    <a class="stat purple" href="{{ route('qa.files.index') }}">
        <span class="stat-ico"><x-icon name="file" /></span>
        <span><b>{{ number_format($stats['documents']) }}</b><span>Files on record</span></span>
        <span class="go"><x-icon name="arrow" /></span>
    </a>
</div>

<div class="grid-main">
    <section class="sheet">
        <div class="sheet-head">
            <x-card-title icon="visits">Open survey visits</x-card-title>
            <a class="link" href="{{ route('qa.survey-visits.index') }}">All visits <x-icon name="arrow" /></a>
        </div>
        @if ($visits->isEmpty())
            <x-empty title="No open visits">
                Create a survey visit, then assign the colleges being accredited.
                <x-slot name="action"><a class="btn btn-primary" href="{{ route('qa.survey-visits.create') }}">New survey visit</a></x-slot>
            </x-empty>
        @else
            <div class="table-scroll">
                <table class="ledger">
                    <thead><tr><th>Visit</th><th>Structure</th><th>Colleges under it</th></tr></thead>
                    <tbody>
                    @foreach ($visits as $visit)
                        <tr>
                            <td>
                                <a class="t-title" href="{{ route('qa.survey-visits.show', $visit) }}">{{ $visit->name }}</a>
                                <div class="t-sub">{{ $visit->type_label }}</div>
                            </td>
                            <td class="nowrap">{{ $visit->areas->count() }} areas · {{ $visit->areas->sum('parameters_count') }} parameters</td>
                            <td>
                                @forelse ($visit->colleges as $college)
                                    @php $done = array_sum($coverage[$visit->id][$college->id] ?? []); $total = $visit->areas->sum(fn ($a) => $a->slots()); @endphp
                                    <a class="code" href="{{ route('binder.visit', [$college, $visit]) }}" title="{{ $college->name }}: {{ $done }} of {{ $total }} filed · {{ $files[$visit->id][$college->id] ?? 0 }} files">{{ $college->code }}</a>
                                @empty
                                    <span class="muted">None assigned</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="sheet">
        <div class="sheet-head">
            <x-card-title icon="upload">Latest uploads</x-card-title>
            @if ($recent->isNotEmpty())<a class="link" href="{{ route('qa.files.index') }}">All files <x-icon name="arrow" /></a>@endif
        </div>
        @if ($recent->isEmpty())
            <x-empty title="Nothing uploaded yet.">Files from in-charges will show here.</x-empty>
        @else
            <ul class="feed">
                @foreach ($recent as $doc)
                    <li>
                        <span class="ext {{ $doc->extension }}">{{ $doc->extension ?: 'file' }}</span>
                        <div>
                            <a href="{{ route('binder.folder', $doc->folder) }}">{{ \Illuminate\Support\Str::limit($doc->name, 48) }}</a>
                            <div class="doc-meta">{{ $doc->folder->college->code }} · {{ $doc->folder->area->short_label }}{{ $doc->folder->parameter ? '-' . $doc->folder->parameter->code : '' }} · {{ $doc->created_at->diffForHumans() }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
