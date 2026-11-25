@extends('layouts.app')

@section('title', 'School years')

@section('content')
@php $next = ($years->max('start_year') ?? now()->year) + 1; $latest = $years->first(); @endphp

<x-masthead index="Records" title="School years">
    <x-slot name="meta">Each school year is its own accreditation cycle with its own visits and files. Earlier years stay here to revisit, read-only for the colleges.</x-slot>
</x-masthead>

<div class="grid-main">
    <section class="sheet">
        <div class="sheet-head"><x-card-title icon="clock">All school years</x-card-title></div>
        <div class="table-scroll">
            <table class="ledger">
                <thead><tr><th>School year</th><th class="num">Survey visits</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($years as $y)
                    <tr>
                        <td>
                            <span class="t-title">{{ $y->label }}</span>
                            <div class="t-sub">Started {{ $y->created_at->format('M j, Y') }}</div>
                        </td>
                        <td class="num">{{ $y->survey_visits_count }}</td>
                        <td>
                            @if ($y->is_current)
                                <span class="stamp open">Current</span>
                            @else
                                <span class="stamp closed">Past</span>
                            @endif
                        </td>
                        <td class="end">
                            <form class="inline" method="POST" action="{{ route('school-year.switch') }}">
                                @csrf <input type="hidden" name="school_year_id" value="{{ $y->id }}">
                                <button class="link" type="submit">View</button>
                            </form>
                            @unless ($y->is_current)
                                <form class="inline" method="POST" action="{{ route('qa.school-years.current', $y) }}"
                                      data-confirm="{{ $y->label }} becomes the year colleges file into. Visits of other years are closed." data-confirm-title="Make this the current year?" data-confirm-ok="Make current">
                                    @csrf <input type="hidden" name="close_previous" value="1">
                                    <button class="link" type="submit">Make current</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="sheet">
        <div class="sheet-head"><x-card-title icon="plus">Start a new school year</x-card-title></div>
        <form class="sheet-body" method="POST" action="{{ route('qa.school-years.store') }}">
            @csrf
            <div class="field">
                <label class="lbl" for="start_year">School year</label>
                <select class="input @error('start_year') bad @enderror" id="start_year" name="start_year">
                    @for ($y = $next - 1; $y <= $next + 1; $y++)
                        @unless ($years->contains('start_year', $y))
                            <option value="{{ $y }}" @selected($y === $next)>SY {{ $y }}–{{ $y + 1 }}</option>
                        @endunless
                    @endfor
                </select>
                @error('start_year')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="lbl" for="copy_from">Carry over visit structures from</label>
                <select class="input" id="copy_from" name="copy_from">
                    <option value="">Start empty</option>
                    @foreach ($years as $y)
                        <option value="{{ $y->id }}" @selected($latest && $y->id === $latest->id)>{{ $y->label }} ({{ $y->survey_visits_count }} visits)</option>
                    @endforeach
                </select>
                <div class="hint">Copies areas and parameters only. Files and folders stay with their own year.</div>
            </div>
            <div class="field">
                <label class="check"><input type="checkbox" name="copy_colleges" value="1" checked> <span>Keep the same colleges on each visit</span></label>
                <label class="check"><input type="checkbox" name="make_current" value="1" checked> <span>Make it the current year</span></label>
                <label class="check"><input type="checkbox" name="close_previous" value="1" checked> <span>Close visits of earlier years<small>Colleges can still open and download old files.</small></span></label>
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:16px;width:100%">Start school year</button>
        </form>
    </section>
</div>
@endsection
