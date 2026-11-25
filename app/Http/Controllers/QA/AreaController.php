<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\SurveyVisit;
use App\Support\OutlineParser;
use Illuminate\Http\Request;

/** Areas are managed inside their survey visit (the visit page is the builder). */
class AreaController extends Controller
{
    public function store(Request $request, SurveyVisit $surveyVisit)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        $surveyVisit->areas()->create([
            'name' => $data['name'],
            'code' => OutlineParser::areaCode($data['name']),
            'sort_order' => $surveyVisit->areas()->max('sort_order') + 1,
            'has_parameters' => false,
        ]);

        return $this->backToVisit($surveyVisit, 'Area added.');
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);
        $area->update($data + ['code' => OutlineParser::areaCode($data['name'])]);

        return $this->backToVisit($area->surveyVisit, 'Area updated.');
    }

    public function destroy(Area $area)
    {
        if ($area->folders()->exists()) {
            return back()->with('error', "\"{$area->name}\" already holds college folders and can't be removed.");
        }

        $visit = $area->surveyVisit;
        $area->delete();
        self::renumber($visit);

        return $this->backToVisit($visit, 'Area removed.');
    }

    public function move(Request $request, Area $area)
    {
        $visit = $area->surveyVisit;
        $ids = $visit->areas()->pluck('id')->all();
        $i = array_search($area->id, $ids);
        $j = $request->input('direction') === 'up' ? $i - 1 : $i + 1;

        if (isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $order => $id) {
                Area::whereKey($id)->update(['sort_order' => $order + 1]);
            }
        }

        return redirect()->to(route('qa.survey-visits.show', $visit) . '#area-' . $area->id);
    }

    private static function renumber(SurveyVisit $visit): void
    {
        foreach ($visit->areas()->pluck('id') as $order => $id) {
            Area::whereKey($id)->update(['sort_order' => $order + 1]);
        }
    }

    private function backToVisit(SurveyVisit $visit, string $message)
    {
        return redirect()->route('qa.survey-visits.show', $visit)->with('success', $message);
    }
}
