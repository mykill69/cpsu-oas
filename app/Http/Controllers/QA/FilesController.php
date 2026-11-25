<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Document;
use App\Models\SchoolYear;
use App\Models\VisitShare;
use App\Support\Coverage;
use Illuminate\Http\Request;

/** QA office view of everything the colleges and campuses have filed, per school year. */
class FilesController extends Controller
{
    public function index(Request $request)
    {
        $year = SchoolYear::viewing();
        $q = trim((string) $request->input('q'));

        $stats = Document::join('folders', 'folders.id', '=', 'documents.folder_id')
            ->join('areas', 'areas.id', '=', 'folders.area_id')
            ->join('survey_visits', 'survey_visits.id', '=', 'areas.survey_visit_id')
            ->where('survey_visits.school_year_id', $year->id)
            ->selectRaw('folders.college_id, COUNT(*) as files, COUNT(DISTINCT folders.id) as folders, SUM(documents.size) as bytes, MAX(documents.created_at) as last_at')
            ->groupBy('folders.college_id')->get()->keyBy('college_id');

        $inYear = fn ($q) => $q->whereHas('area.surveyVisit', fn ($v) => $v->where('school_year_id', $year->id));
        $colleges = College::withCount(['surveyVisits' => fn ($v) => $v->where('school_year_id', $year->id), 'folders' => $inYear])
            ->orderBy('is_extension')->orderBy('name')->get();

        $results = $q === '' ? null : Document::where('documents.name', 'like', '%' . addcslashes($q, '%_\\') . '%')
            ->whereHas('folder.area.surveyVisit', fn ($v) => $v->where('school_year_id', $year->id))
            ->with(['folder.college', 'folder.area.surveyVisit', 'folder.parameter', 'uploader:id,name'])
            ->latest()->limit(60)->get();

        return view('qa.files.index', compact('colleges', 'stats', 'results', 'q', 'year'));
    }

    public function show(College $college)
    {
        $year = SchoolYear::viewing();
        $visits = $college->surveyVisits()->inYear($year)->with(['areas' => fn ($q) => $q->withCount('parameters')])->orderBy('id')->get();
        $coverage = Coverage::map($visits->pluck('id'), $college->id);

        $files = Document::join('folders', 'folders.id', '=', 'documents.folder_id')
            ->join('areas', 'areas.id', '=', 'folders.area_id')
            ->where('folders.college_id', $college->id)
            ->whereIn('areas.survey_visit_id', $visits->pluck('id'))
            ->selectRaw('areas.survey_visit_id as v, COUNT(*) as n, SUM(documents.size) as bytes')
            ->groupBy('areas.survey_visit_id')->get()->keyBy('v');

        $recent = Document::whereHas('folder', fn ($q) => $q->where('college_id', $college->id)
                ->whereHas('area', fn ($a) => $a->whereIn('survey_visit_id', $visits->pluck('id'))))
            ->with(['folder.area', 'folder.parameter', 'uploader:id,name'])
            ->latest()->take(15)->get();

        $shares = VisitShare::where('college_id', $college->id)->whereIn('survey_visit_id', $visits->pluck('id'))->with('surveyVisit')->latest()->get();
        $incharges = $college->users()->role('campus_incharge')->get();

        return view('qa.files.show', compact('college', 'visits', 'coverage', 'files', 'recent', 'shares', 'incharges', 'year'));
    }
}
