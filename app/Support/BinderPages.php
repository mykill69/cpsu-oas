<?php

namespace App\Support;

use App\Models\Area;
use App\Models\College;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Parameter;
use App\Models\SurveyVisit;

/**
 * View data for the three binder pages. Shared by staff (BinderController) and
 * accreditors (ReviewController) so both always see the same thing.
 */
class BinderPages
{
    public static function visit(College $college, SurveyVisit $visit): array
    {
        $visit->load('areas.parameters');

        $folders = Folder::where('college_id', $college->id)
            ->whereIn('area_id', $visit->areas->pluck('id'))
            ->get(['id', 'area_id', 'parameter_id', 'parent_id']);
        $files = Document::whereIn('folder_id', $folders->pluck('id'))
            ->selectRaw('folder_id, COUNT(*) as n')->groupBy('folder_id')->pluck('n', 'folder_id');

        // Totals keyed "p{id}" for parameters and "a{id}" for parameterless areas.
        $counts = [];
        foreach ($folders as $f) {
            $key = $f->parameter_id ? 'p' . $f->parameter_id : 'a' . $f->area_id;
            $counts[$key]['folders'] = ($counts[$key]['folders'] ?? 0) + 1;
            $counts[$key]['files'] = ($counts[$key]['files'] ?? 0) + ($files[$f->id] ?? 0);
        }

        $filled = [];
        foreach ($visit->areas as $area) {
            $filled[$area->id] = $area->has_parameters
                ? $area->parameters->filter(fn ($p) => isset($counts['p' . $p->id]))->count()
                : (int) isset($counts['a' . $area->id]);
        }

        return ['college' => $college, 'surveyVisit' => $visit, 'counts' => $counts, 'filled' => $filled];
    }

    public static function node(College $college, Area $area, ?Parameter $parameter): array
    {
        abort_if($parameter && $parameter->area_id != $area->id, 404);
        abort_if(! $parameter && $area->has_parameters, 404);

        $folders = Folder::where(['college_id' => $college->id, 'area_id' => $area->id, 'parameter_id' => $parameter?->id])
            ->whereNull('parent_id')
            ->withCount(['documents', 'children'])
            ->with('creator:id,name')
            ->orderBy('name')->get();

        return [
            'college' => $college,
            'visit' => $area->surveyVisit,
            'area' => $area,
            'parameter' => $parameter,
            'folder' => null,
            'folders' => $folders,
            'documents' => collect(),
        ];
    }

    public static function folder(Folder $folder): array
    {
        $folder->load(['college', 'area.surveyVisit', 'parameter']);

        return [
            'college' => $folder->college,
            'visit' => $folder->area->surveyVisit,
            'area' => $folder->area,
            'parameter' => $folder->parameter,
            'folder' => $folder,
            'folders' => $folder->children()->withCount(['documents', 'children'])->with('creator:id,name')->get(),
            'documents' => $folder->documents()->with('uploader:id,name')->get(),
        ];
    }
}
