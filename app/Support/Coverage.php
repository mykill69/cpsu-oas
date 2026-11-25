<?php

namespace App\Support;

use App\Models\Folder;

/** How much of each area a college has filed: parameters (or the area itself) holding at least one folder. */
class Coverage
{
    /** @return array [visit_id][college_id][area_id] => filled slots */
    public static function map($visitIds, ?int $collegeId = null): array
    {
        $rows = Folder::join('areas', 'areas.id', '=', 'folders.area_id')
            ->whereIn('areas.survey_visit_id', $visitIds)
            ->when($collegeId, fn ($q) => $q->where('folders.college_id', $collegeId))
            ->selectRaw('areas.survey_visit_id as v, folders.college_id as c, folders.area_id as a, COUNT(DISTINCT COALESCE(folders.parameter_id, 0)) as n')
            ->groupBy('areas.survey_visit_id', 'folders.college_id', 'folders.area_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->v][$r->c][$r->a] = (int) $r->n;
        }

        return $map;
    }
}
