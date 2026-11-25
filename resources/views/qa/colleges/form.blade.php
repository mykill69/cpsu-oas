@extends('layouts.app')

@section('title', $college->exists ? $college->name : 'Add college or campus')

@section('crumbs')
    <a href="{{ route('qa.colleges.index') }}">Colleges &amp; campuses</a><span class="sep">/</span>
    <span class="here">{{ $college->exists ? $college->code : 'New' }}</span>
@endsection

@section('content')
<x-masthead :index="$college->exists ? 'Colleges & campuses · ' . $college->code : 'Colleges & campuses · New entry'" :title="$college->exists ? $college->name : 'Add college or campus'" />

<form class="sheet form-sheet" method="POST" action="{{ $college->exists ? route('qa.colleges.update', $college) : route('qa.colleges.store') }}">
    @csrf
    @if ($college->exists) @method('PUT') @endif

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Unit</h3>
            <p>The code is how the unit appears in tables and coverage strips, so keep it short.</p>
        </div>
        <div>
            <div class="row-2 field">
                <div class="field">
                    <label class="lbl" for="name">Name</label>
                    <input class="input @error('name') bad @enderror" id="name" name="name" value="{{ old('name', $college->name) }}" required maxlength="255">
                    @error('name')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="lbl" for="code">Code</label>
                    <input class="input mono @error('code') bad @enderror" id="code" name="code" value="{{ old('code', $college->code) }}" required maxlength="30" placeholder="e.g. CAS">
                    @error('code')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="field">
                <label class="lbl" for="dean">Dean / campus administrator <span class="opt">(optional)</span></label>
                <input class="input" id="dean" name="dean" value="{{ old('dean', $college->dean) }}" maxlength="255">
            </div>
            <div class="field">
                <label class="lbl" for="description">Notes <span class="opt">(optional)</span></label>
                <textarea class="input" id="description" name="description" rows="2">{{ old('description', $college->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="form-sec">
        <div class="form-sec-intro">
            <h3>Standing</h3>
            <p>Inactive units stay on record but can't be assigned to new visits.</p>
        </div>
        <div>
            <label class="check"><input type="checkbox" name="is_extension" value="1" @checked(old('is_extension', $college->is_extension))> <span>Extension campus</span></label>
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $college->is_active))> <span>Active</span></label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn" href="{{ route('qa.colleges.index') }}">Cancel</a>
    </div>
</form>
@endsection
