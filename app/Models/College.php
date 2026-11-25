<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'dean', 'description', 'is_active', 'is_extension'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_extension' => 'boolean',
    ];

    public function surveyVisits()
    {
        return $this->belongsToMany(SurveyVisit::class)->withTimestamps();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }
}
