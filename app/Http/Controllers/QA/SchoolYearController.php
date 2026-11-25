<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\SurveyVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Each school year is its own accreditation cycle; earlier years stay as a read-only record. */
class SchoolYearController extends Controller
{
    public function index()
    {
        $years = SchoolYear::withCount('surveyVisits')->orderByDesc('start_year')->get();

        return view('qa.school-years.index', compact('years'));
    }

    /** Open a new school year, optionally carrying over last year's visit structures and colleges. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'start_year' => 'required|integer|min:2000|max:2100|unique:school_years,start_year',
            'copy_from' => 'nullable|exists:school_years,id',
        ], ['start_year.unique' => 'That school year already exists.']);

        $year = DB::transaction(function () use ($data, $request) {
            $year = SchoolYear::create(['start_year' => $data['start_year']]);

            if (! empty($data['copy_from'])) {
                $visits = SurveyVisit::where('school_year_id', $data['copy_from'])->with(['areas.parameters', 'colleges'])->get();
                foreach ($visits as $visit) {
                    $copy = SurveyVisit::create($visit->only(['name', 'code', 'description', 'visit_type']) + ['school_year_id' => $year->id, 'is_active' => true]);
                    SurveyVisitController::copyStructure($visit, $copy);
                    if ($request->boolean('copy_colleges')) {
                        $copy->colleges()->sync($visit->colleges->pluck('id'));
                    }
                }
            }

            if ($request->boolean('make_current')) {
                $this->switchCurrent($year, $request->boolean('close_previous'));
            }

            return $year;
        });

        session(['school_year_id' => $year->id]);

        return redirect()->route('qa.school-years.index')->with('success', "{$year->label} is ready" . ($year->is_current ? ' and is now the current year.' : '.'));
    }

    public function makeCurrent(Request $request, SchoolYear $schoolYear)
    {
        $this->switchCurrent($schoolYear, $request->boolean('close_previous'));
        session(['school_year_id' => $schoolYear->id]);

        return back()->with('success', "{$schoolYear->label} is now the current school year.");
    }

    private function switchCurrent(SchoolYear $year, bool $closeOthers): void
    {
        $year->makeCurrent();
        if ($closeOthers) {
            SurveyVisit::where('school_year_id', '!=', $year->id)->update(['is_active' => false]);
        }
    }
}
