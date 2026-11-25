<?php

use App\Support\OutlineParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Older seeds stored codes like "PARAM_A". The builder now keeps plain letters A, B, C... in order. */
return new class extends Migration
{
    public function up()
    {
        foreach (DB::table('parameters')->orderBy('area_id')->orderBy('sort_order')->orderBy('id')->get(['id', 'area_id'])->groupBy('area_id') as $params) {
            foreach ($params->values() as $i => $p) {
                DB::table('parameters')->where('id', $p->id)->update(['code' => OutlineParser::letter($i), 'sort_order' => $i + 1]);
            }
        }
    }

    public function down()
    {
        // Letters are the canonical form; nothing to restore.
    }
};
