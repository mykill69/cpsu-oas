<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollegeController extends Controller
{
    public function index()
    {
        $colleges = College::withCount(['surveyVisits', 'users'])
            ->orderBy('is_extension')->orderBy('name')->get();

        return view('qa.colleges.index', compact('colleges'));
    }

    public function create()
    {
        return view('qa.colleges.form', ['college' => new College(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        College::create($this->validated($request));

        return redirect()->route('qa.colleges.index')->with('success', 'College added.');
    }

    public function edit(College $college)
    {
        return view('qa.colleges.form', compact('college'));
    }

    public function update(Request $request, College $college)
    {
        $college->update($this->validated($request, $college));

        return redirect()->route('qa.colleges.index')->with('success', 'College updated.');
    }

    public function destroy(College $college)
    {
        if ($college->folders()->exists()) {
            return back()->with('error', "{$college->name} has filed documents. Mark it inactive instead.");
        }

        $college->delete();

        return redirect()->route('qa.colleges.index')->with('success', 'College removed.');
    }

    private function validated(Request $request, ?College $college = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:30', Rule::unique('colleges', 'code')->ignore($college)],
            'dean' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        return $data + [
            'is_extension' => $request->boolean('is_extension'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
