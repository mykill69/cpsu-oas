<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The Preliminary Survey Visit also has a "Task Force/Working Committee" section, filed first. */
return new class extends Migration
{
    private const NAME = 'Task Force/Working Committee';

    public function up()
    {
        $visits = DB::table('survey_visits')->where('code', 'PRELIMINARY')->pluck('id');

        foreach ($visits as $visitId) {
            if (DB::table('areas')->where('survey_visit_id', $visitId)->where('name', self::NAME)->exists()) {
                continue;
            }

            DB::table('areas')->where('survey_visit_id', $visitId)->increment('sort_order');
            DB::table('areas')->insert([
                'survey_visit_id' => $visitId,
                'name' => self::NAME,
                'code' => 'TASK_FORCE_WORKING_COMMITTEE',
                'sort_order' => 1,
                'has_parameters' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Only remove it where nothing has been filed under it yet.
        $ids = DB::table('areas')->join('survey_visits', 'survey_visits.id', '=', 'areas.survey_visit_id')
            ->where('survey_visits.code', 'PRELIMINARY')->where('areas.name', self::NAME)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('folders')->whereColumn('folders.area_id', 'areas.id'))
            ->pluck('areas.id');
        DB::table('areas')->whereIn('id', $ids)->delete();
    }
};
