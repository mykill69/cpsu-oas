@extends('layouts.app')

@section('title', 'Colleges & campuses')

@section('content')
<x-masthead index="Records · Units under accreditation" title="Colleges & campuses">
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('qa.colleges.create') }}"><x-icon name="plus" /> Add college or campus</a>
    </x-slot>
    <x-slot name="meta"><span>Each unit gets its own in-charge account and its own folders in every visit it's assigned to.</span></x-slot>
</x-masthead>

<div class="sheet">
    @if ($colleges->isEmpty())
        <x-empty title="No colleges yet">
            <x-slot name="action"><a class="btn btn-primary" href="{{ route('qa.colleges.create') }}">Add college or campus</a></x-slot>
        </x-empty>
    @else
        <div class="table-scroll">
            <table class="ledger">
                <thead><tr><th class="ln">Code</th><th>Name</th><th class="num">Visits</th><th class="num">In-charges</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($colleges->groupBy(fn ($c) => $c->is_extension ? 'Extension campuses' : 'Main campus') as $group => $rows)
                    <tr class="group"><td colspan="6">{{ $group }} · {{ $rows->count() }}</td></tr>
                    @foreach ($rows as $college)
                        <tr>
                            <td class="ln"><span class="code">{{ $college->code }}</span></td>
                            <td>
                                <a class="t-title" href="{{ route('qa.colleges.edit', $college) }}">{{ $college->name }}</a>
                                @if ($college->dean)<div class="t-sub">{{ $college->dean }}</div>@endif
                            </td>
                            <td class="num">{{ $college->survey_visits_count }}</td>
                            <td class="num">{{ $college->users_count }}</td>
                            <td><x-stamp :open="$college->is_active" on="Active" off="Inactive" /></td>
                            <td class="end">
                                <a class="link" href="{{ route('qa.colleges.edit', $college) }}">Edit</a>
                                <form class="inline" method="POST" action="{{ route('qa.colleges.destroy', $college) }}"
                                      data-confirm="{{ $college->name }} will be removed. Units with filed documents can't be removed; mark them inactive instead." data-confirm-title="Remove this college?" data-confirm-ok="Remove">
                                    @csrf @method('DELETE')
                                    <button class="link danger" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
