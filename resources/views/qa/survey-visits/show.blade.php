@extends('layouts.app')

@section('title', $surveyVisit->name)

@section('crumbs')
    <a href="{{ route('qa.survey-visits.index') }}">Survey visits</a><span class="sep">/</span><span class="here">{{ $surveyVisit->name }}</span>
@endsection

@section('content')
@php
    $areas = $surveyVisit->areas;
    $assigned = $surveyVisit->colleges->pluck('id')->all();
    $paramTotal = $areas->sum(fn ($a) => $a->parameters->count());
@endphp

<x-masthead :index="'Survey visit · ' . $surveyVisit->type_label" :title="$surveyVisit->name">
    <x-slot name="actions">
        <a class="btn" href="{{ route('qa.survey-visits.edit', $surveyVisit) }}">Details</a>
        <form class="inline" method="POST" action="{{ route('qa.survey-visits.duplicate', $surveyVisit) }}">
            @csrf
            <button class="btn" type="submit"><x-icon name="copy" /> Duplicate</button>
        </form>
    </x-slot>
    <x-slot name="meta">
        <x-stamp :open="$surveyVisit->is_active" on="Open for filing" />
        <span>{{ $areas->count() }} areas · {{ $paramTotal }} parameters</span>
        <span>{{ count($assigned) }} {{ \Illuminate\Support\Str::plural('college', count($assigned)) }} assigned</span>
        @if ($surveyVisit->description)<span class="muted">{{ $surveyVisit->description }}</span>@endif
    </x-slot>
</x-masthead>

<div class="binder with-side">
    <nav class="rail" aria-label="Areas">
        @foreach ($areas as $area)
            <a class="tab" href="#area-{{ $area->id }}" title="{{ $area->name }}">{{ $area->short_label }}</a>
        @endforeach
    </nav>

    {{-- ===== Structure ===== --}}
    <section class="sheet" aria-label="Areas and parameters">
        <div class="sheet-head">
            <x-card-title icon="folder">Areas &amp; parameters</x-card-title>
            <span class="sheet-note">Click a name to rename it</span>
        </div>

        @forelse ($areas as $area)
            <section class="sec" id="area-{{ $area->id }}">
                <div class="sec-head">
                    <div class="sec-main">
                        <div class="sec-kicker">
                            <span>{{ $area->numeral ? 'Area ' . $area->numeral : 'Section ' . str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>·</span>
                            <span>{{ $area->parameters->isEmpty() ? 'No parameters · colleges file here directly' : $area->parameters->count() . ' parameters' }}</span>
                        </div>
                        <details class="rename">
                            <summary title="Rename area"><span class="sec-title">{{ $area->title }}</span><x-icon name="pencil" class="pen" /></summary>
                            <form class="rename-form" method="POST" action="{{ route('qa.areas.update', $area) }}">
                                @csrf @method('PUT')
                                <input class="input" name="name" value="{{ $area->name }}" required maxlength="255" aria-label="Area name">
                                <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                <button class="btn btn-sm" type="button" onclick="this.closest('details').removeAttribute('open')">Cancel</button>
                            </form>
                        </details>
                    </div>
                    <div class="sec-tools">
                        <form class="inline" method="POST" action="{{ route('qa.areas.move', $area) }}">
                            @csrf <input type="hidden" name="direction" value="up">
                            <button class="ibtn" type="submit" aria-label="Move {{ $area->name }} up" title="Move up" @disabled($loop->first)><x-icon name="up" /></button>
                        </form>
                        <form class="inline" method="POST" action="{{ route('qa.areas.move', $area) }}">
                            @csrf <input type="hidden" name="direction" value="down">
                            <button class="ibtn" type="submit" aria-label="Move {{ $area->name }} down" title="Move down" @disabled($loop->last)><x-icon name="down" /></button>
                        </form>
                        <form class="inline" method="POST" action="{{ route('qa.areas.destroy', $area) }}"
                              data-confirm="“{{ $area->name }}” and its {{ $area->parameters->count() }} parameters will be removed from this visit." data-confirm-title="Remove this area?" data-confirm-ok="Remove area">
                            @csrf @method('DELETE')
                            <button class="ibtn" type="submit" aria-label="Remove {{ $area->name }}" title="Remove"><x-icon name="x" /></button>
                        </form>
                    </div>
                </div>

                @if ($area->parameters->isNotEmpty())
                    <div class="rows">
                        @foreach ($area->parameters as $parameter)
                            <div class="prow">
                                <span class="letter">{{ $parameter->code }}.</span>
                                <details class="rename">
                                    <summary title="Rename parameter"><span>{{ $parameter->name }}</span><x-icon name="pencil" class="pen" /></summary>
                                    <form class="rename-form" method="POST" action="{{ route('qa.parameters.update', $parameter) }}">
                                        @csrf @method('PUT')
                                        <input class="input" name="name" value="{{ $parameter->name }}" required maxlength="255" aria-label="Parameter name">
                                        <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                        <button class="btn btn-sm" type="button" onclick="this.closest('details').removeAttribute('open')">Cancel</button>
                                    </form>
                                </details>
                                <div class="tools">
                                    <form class="inline" method="POST" action="{{ route('qa.parameters.move', $parameter) }}">
                                        @csrf <input type="hidden" name="direction" value="up">
                                        <button class="ibtn" type="submit" aria-label="Move parameter {{ $parameter->code }} up" @disabled($loop->first)><x-icon name="up" /></button>
                                    </form>
                                    <form class="inline" method="POST" action="{{ route('qa.parameters.move', $parameter) }}">
                                        @csrf <input type="hidden" name="direction" value="down">
                                        <button class="ibtn" type="submit" aria-label="Move parameter {{ $parameter->code }} down" @disabled($loop->last)><x-icon name="down" /></button>
                                    </form>
                                    <form class="inline" method="POST" action="{{ route('qa.parameters.destroy', $parameter) }}"
                                          data-confirm="Parameter {{ $parameter->code }}. {{ $parameter->name }} will be removed. The letters after it shift up." data-confirm-title="Remove this parameter?" data-confirm-ok="Remove">
                                        @csrf @method('DELETE')
                                        <button class="ibtn" type="submit" aria-label="Remove parameter {{ $parameter->code }}"><x-icon name="x" /></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <details class="adder" @if($errors->any() && old('_area') == $area->id) open @endif>
                    <summary><x-icon name="plus" /> Add parameter {{ \App\Support\OutlineParser::letter($area->parameters->count()) }}</summary>
                    <form class="adder-form" method="POST" action="{{ route('qa.parameters.store', $area) }}">
                        @csrf <input type="hidden" name="_area" value="{{ $area->id }}">
                        <input class="input input-sm" name="name" required maxlength="255" placeholder="Parameter name" aria-label="New parameter for {{ $area->name }}">
                        <button class="btn btn-sm" type="submit">Add</button>
                    </form>
                </details>
            </section>
        @empty
            <x-empty title="No areas yet">Add the first area below, or delete this visit and paste an outline instead.</x-empty>
        @endforelse

        <form class="add-area" method="POST" action="{{ route('qa.areas.store', $surveyVisit) }}">
            @csrf
            <label class="sr-only" for="new-area">New area name</label>
            <input class="input input-sm" id="new-area" name="name" required maxlength="255" placeholder="New area, e.g. “Area XI. Quality Assurance” or “Compliance”">
            <button class="btn btn-primary btn-sm" type="submit"><x-icon name="plus" /> Add area</button>
        </form>
    </section>

    {{-- ===== Side ===== --}}
    <div class="stack">
        <section class="sheet">
            <div class="sheet-head">
                <x-card-title icon="visits">Filing coverage</x-card-title>
                <span class="sheet-note">{{ $totalNodes }} places to file</span>
            </div>
            @if ($surveyVisit->colleges->isEmpty())
                <x-empty title="No colleges yet">Tick the colleges being accredited below.</x-empty>
            @else
                <ul class="plist">
                    @foreach ($surveyVisit->colleges as $college)
                        @php $done = array_sum($coverage[$college->id] ?? []); @endphp
                        <li>
                            <div class="top">
                                <a class="t-title" href="{{ route('binder.visit', [$college, $surveyVisit]) }}">{{ $college->name }}</a>
                                <span class="muted nowrap">{{ $done }}/{{ $totalNodes }} · {{ (int) ($files[$college->id] ?? 0) }} files</span>
                            </div>
                            <x-strip :areas="$areas" :filled="$coverage[$college->id] ?? []" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="sheet" id="colleges">
            <div class="sheet-head"><x-card-title icon="college">Colleges under this visit</x-card-title></div>
            <form method="POST" action="{{ route('qa.survey-visits.colleges', $surveyVisit) }}">
                @csrf @method('PUT')
                <div class="sheet-body">
                    @forelse ($allColleges->groupBy(fn ($c) => $c->is_extension ? 'Extension campuses' : 'Main campus') as $group => $colleges)
                        <div class="sec-kicker" style="margin-top: {{ $loop->first ? 0 : 18 }}px">{{ $group }}</div>
                        @foreach ($colleges as $college)
                            <label class="check">
                                <input type="checkbox" name="colleges[]" value="{{ $college->id }}" @checked(in_array($college->id, $assigned))>
                                <span>{{ $college->name }} <span class="code">{{ $college->code }}</span></span>
                            </label>
                        @endforeach
                    @empty
                        <p class="muted">No active colleges. <a href="{{ route('qa.colleges.create') }}">Add one</a>.</p>
                    @endforelse
                </div>
                @if ($allColleges->isNotEmpty())
                    <div class="sheet-foot"><button class="btn btn-sm" type="submit">Save assignments</button></div>
                @endif
            </form>
        </section>
    </div>
</div>
@endsection
