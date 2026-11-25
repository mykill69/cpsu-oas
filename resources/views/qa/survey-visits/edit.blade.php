@extends('layouts.app')

@section('title', 'Details · ' . $surveyVisit->name)

@section('crumbs')
    <a href="{{ route('qa.survey-visits.index') }}">Survey visits</a><span class="sep">/</span>
    <a href="{{ route('qa.survey-visits.show', $surveyVisit) }}">{{ $surveyVisit->name }}</a><span class="sep">/</span>
    <span class="here">Details</span>
@endsection

@section('content')
@php $visit = $surveyVisit; @endphp

<x-masthead index="Survey visits · Details" :title="$visit->name">
    <x-slot name="meta"><x-stamp :open="$visit->is_active" on="Open for filing" /></x-slot>
</x-masthead>

<form class="sheet form-sheet" method="POST" action="{{ route('qa.survey-visits.update', $visit) }}">
    @csrf @method('PUT')
    @include('qa.survey-visits._fields')

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Filing</h3>
            <p>Close the visit when the survey is done so nothing changes afterwards.</p>
        </div>
        <div>
            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $visit->is_active))>
                <span>Open for filing<small>When closed, in-charges can still open and download their files but can't add, rename or delete anything.</small></span>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn" href="{{ route('qa.survey-visits.show', $visit) }}">Cancel</a>
    </div>
</form>

<form class="sheet form-sheet" method="POST" action="{{ route('qa.survey-visits.destroy', $visit) }}"
      data-confirm="“{{ $visit->name }}” and all of its areas and parameters will be removed." data-confirm-title="Delete this visit?" data-confirm-ok="Delete visit">
    @csrf @method('DELETE')
    <div class="form-sec" style="border-bottom:0">
        <div class="form-sec-intro">
            <h3>Delete visit</h3>
            <p>Only possible while no college has filed folders under it.</p>
        </div>
        <div><button class="btn btn-danger" type="submit">Delete this visit</button></div>
    </div>
</form>
@endsection
