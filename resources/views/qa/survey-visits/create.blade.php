@extends('layouts.app')

@section('title', 'New survey visit')

@section('crumbs')
    <a href="{{ route('qa.survey-visits.index') }}">Survey visits</a><span class="sep">/</span><span class="here">New</span>
@endsection

@section('content')
@php $visit = new \App\Models\SurveyVisit(['visit_type' => 'PRELIMINARY']); $start = old('start', 'outline'); @endphp

<x-masthead index="Survey visits · New entry" title="New survey visit">
    <x-slot name="meta"><span>Name it, choose how its areas are filled in, and you can still edit everything afterwards.</span></x-slot>
</x-masthead>

<form class="sheet form-sheet" method="POST" action="{{ route('qa.survey-visits.store') }}">
    @csrf
    @include('qa.survey-visits._fields')

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Areas &amp; parameters</h3>
            <p>“Area II. Faculty” lines become areas. “A. …”, “B. …” lines under an area become its parameters. A line without a letter, like “Compliance”, becomes an area where colleges file directly.</p>
        </div>
        <div class="options">
            <label class="option">
                <input type="radio" name="start" value="outline" @checked($start === 'outline')>
                <div>
                    <b>Paste the outline</b>
                    <span>Straight from the Word file. Headings like “Parameters” and “Areas:” are skipped.</span>
                    <div class="option-extra" data-for="outline">
                        <textarea class="input outline" name="outline" aria-label="Outline" spellcheck="false" placeholder="Area I. Vision, Mission, Goals and Objectives
Parameters
A.  Statement of Vision, Mission, Goals and Objectives
B.  Dissemination and Acceptability
Area II. Faculty
A.  Academic Qualifications and Professional Experience
…
Task Force/Working Committee
Compliance">{{ old('outline') }}</textarea>
                        <div class="hint" id="outlinePreview" aria-live="polite"></div>
                    </div>
                </div>
            </label>

            <label class="option">
                <input type="radio" name="start" value="copy" @checked($start === 'copy')>
                <div>
                    <b>Copy an existing visit</b>
                    <span>Same areas and parameters, without any college folders.</span>
                    <div class="option-extra" data-for="copy">
                        <select class="input" name="copy_from" aria-label="Visit to copy">
                            <option value="">Choose a visit…</option>
                            @foreach ($templates as $t)
                                <option value="{{ $t->id }}" @selected(old('copy_from') == $t->id)>{{ $t->name }} — {{ $t->areas_count }} areas</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </label>

            <label class="option">
                <input type="radio" name="start" value="blank" @checked($start === 'blank')>
                <div><b>Start empty</b><span>Add areas one at a time on the next page.</span></div>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Create visit</button>
        <a class="btn" href="{{ route('qa.survey-visits.index') }}">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    // Only the chosen option's input is shown and submitted.
    function sync() {
        var chosen = document.querySelector('input[name="start"]:checked').value;
        document.querySelectorAll('.option-extra').forEach(function (box) {
            var on = box.getAttribute('data-for') === chosen;
            box.hidden = !on;
            box.querySelectorAll('textarea, select').forEach(function (el) { el.disabled = !on; });
        });
    }
    document.querySelectorAll('input[name="start"]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();

    // Live count of what the outline will produce (mirrors App\Support\OutlineParser).
    var ta = document.querySelector('textarea[name="outline"]'), out = document.getElementById('outlinePreview');
    function preview() {
        var areas = 0, params = 0;
        ta.value.split(/\r?\n/).forEach(function (raw) {
            var line = raw.replace(/\s+/g, ' ').trim();
            if (!line || /^(parameters|areas)\s*:?$/i.test(line) || /\bsurvey\b|^revisit all areas$/i.test(line)) return;
            if (areas && /^[A-Z][.)]\s*.+$/.test(line)) params++; else areas++;
        });
        out.textContent = areas ? 'Reads as ' + areas + ' area' + (areas === 1 ? '' : 's') + ' and ' + params + ' parameter' + (params === 1 ? '' : 's') + '.' : '';
    }
    ta.addEventListener('input', preview); preview();
})();
</script>
@endpush
