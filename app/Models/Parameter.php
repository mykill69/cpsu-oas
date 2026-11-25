<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasFactory;

    protected $fillable = ['area_id', 'name', 'code', 'description', 'sort_order', 'is_compliance'];

    protected $casts = [
        'is_compliance' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }
}
