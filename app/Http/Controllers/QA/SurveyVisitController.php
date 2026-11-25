<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Document;
use App\Models\SchoolYear;
use App\Models\SurveyVisit;
use App\Support\Coverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SurveyVisitController extends Controller
{
    public function index()
    {
        $year = SchoolYear::viewing();
        $surveyVisits = SurveyVisit::inYear($year)->withCount(['areas', 'parameters', 'colleges'])->orderBy('id')->get();

        return view('qa.survey-visits.index', compact('surveyVisits', 'year'));
    }

    public function create()
    {
        $year = SchoolYear::viewing();
        $templates = SurveyVisit::with('schoolYear')->withCount('areas')->orderByDesc('school_year_id')->orderBy('id')->get();

        return view('qa.survey-visits.create', compact('templates', 'year'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $request->validate([
            'outline' => 'nullable|string|max:20000',
            'copy_from' => 'nullable|exists:survey_visits,id',
        ]);

        $visit = DB::transaction(function () use ($data, $request) {
            $visit = SurveyVisit::create($data + ['school_year_id' => SchoolYear::viewing()->id]);

            if ($request->filled('copy_from')) {
                self::copyStructure(SurveyVisit::with('areas.parameters')->find($request->copy_from), $visit);
            } elseif ($request->filled('outline')) {
                $visit->buildFromOutline($request->outline);
            }

            return $visit;
        });

        return redirect()->route('qa.survey-visits.show', $visit)
            ->with('success', 'Survey visit created. Add or adjust its areas and parameters below.');
    }

    public function show(SurveyVisit $surveyVisit)
    {
        $surveyVisit->load(['areas.parameters', 'colleges']);
        $areaIds = $surveyVisit->areas->pluck('id');

        $coverage = Coverage::map([$surveyVisit->id])[$surveyVisit->id] ?? [];
        $files = Document::join('folders', 'folders.id', '=', 'documents.folder_id')
            ->whereIn('folders.area_id', $areaIds)
            ->selectRaw('folders.college_id, COUNT(*) as n')
            ->groupBy('folders.college_id')
            ->pluck('n', 'college_id');

        $totalNodes = $surveyVisit->areas->sum(fn ($a) => $a->slots());
        $allColleges = College::where('is_active', true)->orderBy('is_extension')->orderBy('name')->get();

        return view('qa.survey-visits.show', compact('surveyVisit', 'coverage', 'files', 'totalNodes', 'allColleges'));
    }

    public function edit(SurveyVisit $surveyVisit)
    {
        return view('qa.survey-visits.edit', compact('surveyVisit'));
    }

    public function update(Request $request, SurveyVisit $surveyVisit)
    {
        $data = $this->validated($request, $surveyVisit);
        $data['is_active'] = $request->boolean('is_active');
        $surveyVisit->update($data);

        return redirect()->route('qa.survey-visits.show', $surveyVisit)->with('success', 'Survey visit updated.');
    }

    public function destroy(SurveyVisit $surveyVisit)
    {
        if ($surveyVisit->hasFolders()) {
            return back()->with('error', 'This visit already has folders from colleges. Close it instead of deleting it.');
        }

        $surveyVisit->delete();

        return redirect()->route('qa.survey-visits.index')->with('success', 'Survey visit deleted.');
    }

    public function duplicate(SurveyVisit $surveyVisit)
    {
        $copy = DB::transaction(function () use ($surveyVisit) {
            $copy = $surveyVisit->replicate(['code']);
            $copy->name = $surveyVisit->name . ' (copy)';
            $copy->code = $this->uniqueCode($surveyVisit->code . '_COPY', $surveyVisit->school_year_id);
            $copy->save();
            self::copyStructure($surveyVisit->load('areas.parameters'), $copy);

            return $copy;
        });

        return redirect()->route('qa.survey-visits.show', $copy)->with('success', 'Copied. Rename it and assign colleges.');
    }

    public function assignColleges(Request $request, SurveyVisit $surveyVisit)
    {
        $request->validate(['colleges' => 'array', 'colleges.*' => 'exists:colleges,id']);
        $surveyVisit->colleges()->sync($request->input('colleges', []));

        return back()->with('success', 'College assignments saved.');
    }

    private function validated(Request $request, ?SurveyVisit $visit = null): array
    {
        $yearId = $visit->school_year_id ?? SchoolYear::viewing()->id;
        if (! $request->filled('code')) {
            $request->merge(['code' => $this->uniqueCode(Str::upper(Str::slug($request->input('name', 'VISIT'), '_')), $yearId, $visit)]);
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:60', Rule::unique('survey_visits', 'code')->where('school_year_id', $yearId)->ignore($visit)],
            'description' => 'nullable|string|max:2000',
            'visit_type' => ['required', Rule::in(array_keys(SurveyVisit::TYPES))],
        ]);
    }

    private function uniqueCode(string $base, int $yearId, ?SurveyVisit $ignore = null): string
    {
        $base = substr($base ?: 'VISIT', 0, 50);
        $code = $base;
        for ($i = 2; SurveyVisit::where('school_year_id', $yearId)->where('code', $code)->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->exists(); $i++) {
            $code = $base . '_' . $i;
        }

        return $code;
    }

    public static function copyStructure(SurveyVisit $from, SurveyVisit $to): void
    {
        foreach ($from->areas as $area) {
            $newArea = $to->areas()->create($area->only(['name', 'code', 'description', 'sort_order', 'has_parameters']));
            foreach ($area->parameters as $parameter) {
                $newArea->parameters()->create($parameter->only(['name', 'code', 'description', 'sort_order', 'is_compliance']));
            }
        }
    }
}
