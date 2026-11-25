{{-- Identity section shared by create and edit. Expects $visit. --}}
<div class="form-sec">
    <div class="form-sec-intro">
        <h3>Identity</h3>
        <p>How the visit appears to the QA office and to the colleges assigned to it.</p>
    </div>
    <div>
        <div class="field">
            <label class="lbl" for="name">Name</label>
            <input class="input @error('name') bad @enderror" id="name" name="name" value="{{ old('name', $visit->name) }}" required maxlength="255" placeholder="e.g. Preliminary Survey Visit — BS Agriculture">
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="row-2 field">
            <div class="field">
                <label class="lbl" for="visit_type">Type</label>
                <select class="input @error('visit_type') bad @enderror" id="visit_type" name="visit_type" required>
                    @foreach (\App\Models\SurveyVisit::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('visit_type', $visit->visit_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('visit_type')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label class="lbl" for="code">Reference code <span class="opt">(optional)</span></label>
                <input class="input mono @error('code') bad @enderror" id="code" name="code" value="{{ old('code', $visit->code) }}" maxlength="60" placeholder="Made from the name">
                @error('code')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="field">
            <label class="lbl" for="description">Notes <span class="opt">(optional)</span></label>
            <textarea class="input" id="description" name="description" rows="2" placeholder="e.g. For Level I · Revisit all areas">{{ old('description', $visit->description) }}</textarea>
        </div>
    </div>
</div>
