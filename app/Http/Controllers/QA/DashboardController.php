<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Document;
use App\Models\SchoolYear;
use App\Models\SurveyVisit;
use App\Models\User;
use App\Support\Coverage;

class DashboardController extends Controller
{
    public function index()
    {
        $year = SchoolYear::viewing();

        $visits = SurveyVisit::inYear($year)->with(['colleges', 'areas' => fn ($q) => $q->withCount('parameters')])
            ->where('is_active', true)->orderBy('id')->get();

        $coverage = Coverage::map($visits->pluck('id'));

        $files = [];
        Document::join('folders', 'folders.id', '=', 'documents.folder_id')
            ->join('areas', 'areas.id', '=', 'folders.area_id')
            ->whereIn('areas.survey_visit_id', $visits->pluck('id'))
            ->selectRaw('areas.survey_visit_id as v, folders.college_id as c, COUNT(*) as n')
            ->groupBy('areas.survey_visit_id', 'folders.college_id')
            ->get()->each(function ($r) use (&$files) { $files[$r->v][$r->c] = (int) $r->n; });

        $inYear = fn ($q) => $q->whereHas('folder.area.surveyVisit', fn ($v) => $v->where('school_year_id', $year->id));

        $stats = [
            'open_visits' => $visits->count(),
            'colleges' => College::where('is_active', true)->count(),
            'incharges' => User::role('campus_incharge')->count(),
            'documents' => Document::where($inYear)->count(),
        ];

        $recent = Document::where($inYear)->with(['folder.college', 'folder.area', 'folder.parameter', 'uploader'])
            ->latest()->take(10)->get();

        return view('qa.dashboard', compact('stats', 'visits', 'coverage', 'files', 'recent', 'year'));
    }
}
