<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Parameter;
use App\Support\OutlineParser;
use Illuminate\Http\Request;

/** Parameters are managed inside their area, on the survey visit page. Letters are always A, B, C... in order. */
class ParameterController extends Controller
{
    public function store(Request $request, Area $area)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);

        if ($area->folders()->whereNull('parameter_id')->exists()) {
            return back()->with('error', "Colleges already filed folders directly under \"{$area->name}\". Parameters can't be added to it now.");
        }

        $area->parameters()->create(['name' => $data['name'], 'code' => '', 'sort_order' => 999999]);
        $this->reletter($area);

        return $this->back($area, 'Parameter added.');
    }

    public function update(Request $request, Parameter $parameter)
    {
        $parameter->update($request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]));

        return $this->back($parameter->area, 'Parameter updated.');
    }

    public function destroy(Parameter $parameter)
    {
        if ($parameter->folders()->exists()) {
            return back()->with('error', "Parameter {$parameter->code} already holds college folders and can't be removed.");
        }

        $area = $parameter->area;
        $parameter->delete();
        $this->reletter($area);

        return $this->back($area, 'Parameter removed.');
    }

    public function move(Request $request, Parameter $parameter)
    {
        $area = $parameter->area;
        $ids = $area->parameters()->pluck('id')->all();
        $i = array_search($parameter->id, $ids);
        $j = $request->input('direction') === 'up' ? $i - 1 : $i + 1;

        if (isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            $this->reletter($area, $ids);
        }

        return $this->back($area);
    }

    private function reletter(Area $area, ?array $ids = null): void
    {
        foreach ($ids ?? $area->parameters()->pluck('id')->all() as $i => $id) {
            Parameter::whereKey($id)->update(['sort_order' => $i + 1, 'code' => OutlineParser::letter($i)]);
        }
        $area->syncHasParameters();
    }

    private function back(Area $area, ?string $message = null)
    {
        $redirect = redirect()->to(route('qa.survey-visits.show', $area->survey_visit_id) . '#area-' . $area->id);

        return $message ? $redirect->with('success', $message) : $redirect;
    }
}
