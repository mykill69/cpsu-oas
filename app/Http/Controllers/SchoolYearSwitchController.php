<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use Illuminate\Http\Request;

/** The year picker in the sidebar: which school year's records everyone is looking at. */
class SchoolYearSwitchController extends Controller
{
    public function __invoke(Request $request)
    {
        $year = SchoolYear::findOrFail($request->validate(['school_year_id' => 'required|integer'])['school_year_id']);
        session(['school_year_id' => $year->id]);

        return redirect()->route('dashboard')->with('success', "Now showing {$year->label}.");
    }
}
