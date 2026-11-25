@extends('layouts.app')

@section('title', 'Survey visits')

@section('content')
<x-masthead index="QA Office · Register" title="Survey visits">
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('qa.survey-visits.create') }}"><x-icon name="plus" /> New survey visit</a>
    </x-slot>
    <x-slot name="meta">
        <span>Each visit carries its own areas and parameters. Assigned colleges file their folders under them.</span>
    </x-slot>
</x-masthead>

<div class="sheet">
    @if ($surveyVisits->isEmpty())
        <x-empty title="No survey visits yet">
            Paste the area outline from your document and the visit is ready in one step.
            <x-slot name="action"><a class="btn btn-primary" href="{{ route('qa.survey-visits.create') }}">New survey visit</a></x-slot>
        </x-empty>
    @else
        <div class="table-scroll">
            <table class="ledger">
                <thead>
                <tr>
                    <th class="ln">No.</th>
                    <th>Visit</th>
                    <th>Type</th>
                    <th class="num">Areas</th>
                    <th class="num">Parameters</th>
                    <th class="num">Colleges</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($surveyVisits as $visit)
                    <tr>
                        <td class="ln">{{ str_pad($loop->iteration, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>
                            <a class="t-title" href="{{ route('qa.survey-visits.show', $visit) }}">{{ $visit->name }}</a>
                            @if ($visit->description)<div class="t-sub">{{ \Illuminate\Support\Str::limit($visit->description, 70) }}</div>@endif
                        </td>
                        <td class="nowrap">{{ $visit->type_label }}</td>
                        <td class="num">{{ $visit->areas_count }}</td>
                        <td class="num">{{ $visit->parameters_count }}</td>
                        <td class="num">{{ $visit->colleges_count }}</td>
                        <td><x-stamp :open="$visit->is_active" /></td>
                        <td class="end">
                            <a class="link" href="{{ route('qa.survey-visits.edit', $visit) }}">Details</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
