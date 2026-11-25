<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Folder extends Model
{
    protected $fillable = ['college_id', 'area_id', 'parameter_id', 'parent_id', 'name', 'created_by'];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('name');
    }

    public function documents()
    {
        return $this->hasMany(Document::class)->orderBy('name');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Root-to-self chain, for breadcrumbs. */
    public function ancestry()
    {
        $chain = collect([$this]);
        $node = $this;
        while ($node->parent_id && ($node = $node->parent)) {
            $chain->prepend($node);
        }

        return $chain;
    }

    /** Delete the folder tree and its stored files (the DB cascade alone would orphan the files on disk). */
    public function deleteWithFiles(): void
    {
        $ids = collect([$this->id]);
        $frontier = $ids;
        while ($frontier->isNotEmpty()) {
            $frontier = Folder::whereIn('parent_id', $frontier)->pluck('id');
            $ids = $ids->merge($frontier);
        }

        Storage::delete(Document::whereIn('folder_id', $ids)->whereNotNull('path')->pluck('path')->all());
        $this->delete();

        // Trashing the Drive folder takes its whole subtree with it (recoverable from Drive's trash for 30 days).
        if ($this->drive_id) {
            \App\Jobs\SyncToDrive::send('trash', null, $this->drive_id);
        }
    }
}
