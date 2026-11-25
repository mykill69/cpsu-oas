<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['start_year', 'is_current'];

    protected $casts = ['is_current' => 'boolean', 'start_year' => 'integer'];

    public function surveyVisits()
    {
        return $this->hasMany(SurveyVisit::class);
    }

    /** "SY 2026–2027" */
    public function getLabelAttribute(): string
    {
        return 'SY ' . $this->start_year . '–' . ($this->start_year + 1);
    }

    /** "SY 2026-2027" for folder names (plain hyphen). */
    public function getSlugLabelAttribute(): string
    {
        return 'SY ' . $this->start_year . '-' . ($this->start_year + 1);
    }

    public static function current(): self
    {
        return static::where('is_current', true)->first()
            ?? static::orderByDesc('start_year')->firstOrFail();
    }

    /** The year the signed-in user chose in the switcher, or the current one. */
    public static function viewing(): self
    {
        $id = session('school_year_id');

        return ($id ? static::find($id) : null) ?? static::current();
    }

    public function makeCurrent(): void
    {
        static::where('id', '!=', $this->id)->update(['is_current' => false]);
        $this->update(['is_current' => true]);
    }
}
