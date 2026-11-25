<?php

namespace App\Http\Controllers;

use App\Jobs\SyncToDrive;
use App\Models\Area;
use App\Models\SchoolYear;
use App\Models\College;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Parameter;
use App\Models\SurveyVisit;
use App\Models\VisitShare;
use App\Support\BinderLinks;
use App\Support\BinderPages;
use App\Support\Coverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A college's binder for one survey visit: visit -> area -> parameter -> folders -> files.
 * QA reads every binder; the college's in-charge builds it (see the gates in AuthServiceProvider).
 */
class BinderController extends Controller
{
    /** In-charge home: the visits their college is assigned to. */
    public function index(Request $request)
    {
        $college = $request->user()->college;
        abort_unless($college, 403, 'Your account is not linked to a college yet. Ask the QA office.');

        $year = SchoolYear::viewing();
        $visits = $college->surveyVisits()->inYear($year)->withCount(['areas', 'parameters'])
            ->with(['areas' => fn ($q) => $q->withCount('parameters')])->orderBy('id')->get();
        $coverage = Coverage::map($visits->pluck('id'), $college->id);
        $fileCounts = Document::join('folders', 'folders.id', '=', 'documents.folder_id')
            ->join('areas', 'areas.id', '=', 'folders.area_id')
            ->where('folders.college_id', $college->id)
            ->selectRaw('areas.survey_visit_id, COUNT(*) as n')
            ->groupBy('areas.survey_visit_id')
            ->pluck('n', 'survey_visit_id');

        return view('binder.index', compact('college', 'visits', 'fileCounts', 'coverage', 'year'));
    }

    public function visit(College $college, SurveyVisit $surveyVisit)
    {
        $this->authorize('view-binder', [$college, $surveyVisit]);

        $data = BinderPages::visit($college, $surveyVisit);
        if (auth()->user()->isQa()) {
            $data['shares'] = VisitShare::where(['college_id' => $college->id, 'survey_visit_id' => $surveyVisit->id])->latest()->get();
        }

        return view('binder.visit', $data + $this->context($college, $surveyVisit));
    }

    /** Root folders under one parameter, or under an area that has no parameters. */
    public function node(College $college, Area $area, ?Parameter $parameter = null)
    {
        $this->authorize('view-binder', [$college, $area->surveyVisit]);

        return view('binder.folder', BinderPages::node($college, $area, $parameter) + $this->context($college, $area->surveyVisit));
    }

    public function folder(Folder $folder)
    {
        $data = BinderPages::folder($folder);
        $this->authorize('view-binder', [$data['college'], $data['visit']]);

        return view('binder.folder', $data + $this->context($data['college'], $data['visit']));
    }

    /** Who is looking (qa / incharge) and whether they may write. */
    private function context(College $college, SurveyVisit $visit): array
    {
        $user = auth()->user();

        return [
            'mode' => $user->isQa() ? 'qa' : 'incharge',
            'canEdit' => $user->can('edit-binder', [$college, $visit]),
            'links' => new BinderLinks(),
        ];
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'college_id' => 'required|exists:colleges,id',
            'area_id' => 'required|exists:areas,id',
            'parameter_id' => 'nullable|exists:parameters,id',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        $area = Area::findOrFail($data['area_id']);
        $college = College::findOrFail($data['college_id']);
        $this->authorize('edit-binder', [$college, $area->surveyVisit]);

        // The target must be a real node of this area, and a parent folder must sit in the same place.
        if (! empty($data['parameter_id'])) {
            abort_unless(Parameter::whereKey($data['parameter_id'])->where('area_id', $area->id)->exists(), 422);
        } else {
            abort_if($area->has_parameters, 422);
        }
        if (! empty($data['parent_id'])) {
            $parent = Folder::findOrFail($data['parent_id']);
            abort_unless($parent->college_id == $college->id && $parent->area_id == $area->id
                && $parent->parameter_id == ($data['parameter_id'] ?? null), 422);
        }

        $siblingExists = Folder::where([
            'college_id' => $college->id, 'area_id' => $area->id,
            'parameter_id' => $data['parameter_id'] ?? null, 'parent_id' => $data['parent_id'] ?? null,
        ])->where('name', $data['name'])->exists();
        if ($siblingExists) {
            return back()->withInput()->withErrors(['name' => 'A folder with that name already exists here.']);
        }

        $folder = Folder::create($data + ['created_by' => $request->user()->id]);
        SyncToDrive::send('folder', $folder->id);

        return back()->with('success', 'Folder created.');
    }

    public function renameFolder(Request $request, Folder $folder)
    {
        $this->authorize('edit-binder', [$folder->college, $folder->area->surveyVisit]);
        $folder->update($request->validate(['name' => 'required|string|max:150']));
        if ($folder->drive_id) {
            SyncToDrive::send('rename', null, $folder->drive_id, $folder->name);
        }

        return back()->with('success', 'Folder renamed.');
    }

    public function destroyFolder(Folder $folder)
    {
        $this->authorize('edit-binder', [$folder->college, $folder->area->surveyVisit]);

        $back = $folder->parent_id
            ? route('binder.folder', $folder->parent_id)
            : route('binder.node', array_filter([$folder->college_id, $folder->area_id, $folder->parameter_id]));

        DB::transaction(fn () => $folder->deleteWithFiles());

        return redirect($back)->with('success', 'Folder and its contents deleted.');
    }
}
