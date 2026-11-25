<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    private const MINOR_WORDS = ['and', 'of', 'in', 'the', 'for', 'to', 'a'];

    protected $fillable = ['survey_visit_id', 'name', 'code', 'description', 'sort_order', 'has_parameters'];

    protected $casts = [
        'has_parameters' => 'boolean',
    ];

    public function surveyVisit()
    {
        return $this->belongsTo(SurveyVisit::class);
    }

    public function parameters()
    {
        return $this->hasMany(Parameter::class)->orderBy('sort_order');
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    /** Keep has_parameters in step with the actual children. */
    public function syncHasParameters(): void
    {
        $this->update(['has_parameters' => $this->parameters()->exists()]);
    }

    /** "Area IV. Support to Students" -> "IV"; null for areas like "Compliance". */
    public function getNumeralAttribute(): ?string
    {
        return preg_match('/^Area\s+([IVXLC]+)\b/i', $this->name, $m) ? strtoupper($m[1]) : null;
    }

    /** "Area IV. Support to Students" -> "Support to Students". */
    public function getTitleAttribute(): string
    {
        if (! $this->numeral) {
            return $this->name;
        }

        return trim(preg_replace('/^Area\s+[IVXLC]+\s*[.:\-–]?\s*/iu', '', $this->name)) ?: $this->name;
    }

    /** Tab label: the numeral, or initials ("Task Force/Working Committee" -> "TFW", "Library" -> "LIB"). */
    public function getShortLabelAttribute(): string
    {
        if ($this->numeral) {
            return $this->numeral;
        }

        $words = array_values(array_filter(
            preg_split('/[^A-Za-z]+/', $this->name, -1, PREG_SPLIT_NO_EMPTY),
            fn ($w) => ! in_array(strtolower($w), self::MINOR_WORDS, true)
        ));

        if (! $words) {
            return '·';
        }

        return strtoupper(count($words) === 1 ? substr($words[0], 0, 3) : implode('', array_map(fn ($w) => $w[0], array_slice($words, 0, 3))));
    }

    /** Places a college files into: one per parameter, or the area itself when it has none. */
    public function slots(): int
    {
        if (! $this->has_parameters) {
            return 1;
        }

        return (int) ($this->parameters_count ?? $this->parameters->count());
    }
}
