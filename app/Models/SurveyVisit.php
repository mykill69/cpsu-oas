<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyVisit extends Model
{
    use HasFactory;

    public const TYPES = [
        'PRELIMINARY' => 'Preliminary',
        'FIRST' => '1st Survey',
        'SECOND' => '2nd Survey',
        'THIRD' => '3rd Survey',
        'FOURTH' => '4th Survey',
    ];

    protected $fillable = ['school_year_id', 'name', 'code', 'description', 'visit_type', 'is_active'];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function scopeInYear($query, SchoolYear $year)
    {
        return $query->where('survey_visits.school_year_id', $year->id);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function areas()
    {
        return $this->hasMany(Area::class)->orderBy('sort_order');
    }

    public function parameters()
    {
        return $this->hasManyThrough(Parameter::class, Area::class);
    }

    public function colleges()
    {
        return $this->belongsToMany(College::class)->withTimestamps()->orderBy('name');
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->visit_type] ?? $this->visit_type;
    }

    /** Append areas/parameters from a pasted outline (see OutlineParser). */
    public function buildFromOutline(string $outline): void
    {
        $start = (int) $this->areas()->max('sort_order');

        foreach (\App\Support\OutlineParser::parse($outline) as $i => $row) {
            $area = $this->areas()->create([
                'name' => $row['name'],
                'code' => $row['code'],
                'sort_order' => $start + $i + 1,
                'has_parameters' => count($row['parameters']) > 0,
            ]);
            foreach ($row['parameters'] as $j => $name) {
                $area->parameters()->create(['name' => $name, 'code' => \App\Support\OutlineParser::letter($j), 'sort_order' => $j + 1]);
            }
        }
    }

    public function hasFolders(): bool
    {
        return Folder::whereIn('area_id', $this->areas()->pluck('id'))->exists();
    }
}
