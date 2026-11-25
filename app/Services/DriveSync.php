<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Mirrors the binder into the system Drive:
 *
 *   CPSU OAS Accreditation Files / SY 2026-2027 / CAS – College of Arts and Sciences /
 *   Preliminary Survey Visit / Area I. Vision… / A. Statement of… / <college folders…> / file
 *
 * Fixed levels are remembered in drive_nodes; college-made folders keep their id on the row.
 */
class DriveSync
{
    public function __construct(private GoogleDrive $drive)
    {
    }

    public function ready(): bool
    {
        return $this->drive->isConnected();
    }

    public function folderId(Folder $folder): string
    {
        if ($folder->drive_id) {
            return $folder->drive_id;
        }

        $folder->loadMissing(['college', 'area.surveyVisit.schoolYear', 'parameter', 'parent']);
        $parentId = $folder->parent ? $this->folderId($folder->parent) : $this->placeId($folder);

        $folder->forceFill(['drive_id' => $this->drive->createFolder($folder->name, $parentId)])->saveQuietly();

        return $folder->drive_id;
    }

    public function pushDocument(Document $doc): void
    {
        if ($doc->drive_id || ! $doc->path || ! Storage::exists($doc->path)) {
            return;
        }

        $parent = $this->folderId($doc->folder);
        $doc->forceFill([
            'drive_id' => $this->drive->upload(Storage::path($doc->path), $doc->name, $doc->mime, $parent),
            'drive_synced_at' => now(),
            'drive_error' => null,
        ])->save();

        if (! config('oas.drive_keep_local')) {
            Storage::delete($doc->path);
            $doc->forceFill(['path' => null])->save();
        }
    }

    /** The parameter (or parameterless area) a root folder sits in. */
    private function placeId(Folder $folder): string
    {
        $visit = $folder->area->surveyVisit;
        $year = $visit->schoolYear;
        $c = $folder->college;

        $id = $this->node('sy' . $year->id, $year->slug_label, $this->rootId());
        $id = $this->node("c{$c->id}.sy{$year->id}", "{$c->code} – {$c->name}", $id);
        $id = $this->node("v{$visit->id}.c{$c->id}", $visit->name, $id);
        $id = $this->node("a{$folder->area_id}.c{$c->id}", $folder->area->name, $id);
        if ($folder->parameter) {
            $id = $this->node("p{$folder->parameter_id}.c{$c->id}", "{$folder->parameter->code}. {$folder->parameter->name}", $id);
        }

        return $id;
    }

    public function rootId(): string
    {
        if ($id = AppSetting::get('drive.root_id')) {
            return $id;
        }
        $id = $this->drive->createFolder(config('oas.drive_root_name'), null);
        AppSetting::put('drive.root_id', $id);

        return $id;
    }

    private function node(string $key, string $name, string $parentId): string
    {
        $existing = DB::table('drive_nodes')->where('key', $key)->value('drive_id');
        if ($existing) {
            return $existing;
        }

        $id = $this->drive->createFolder($name, $parentId);
        DB::table('drive_nodes')->insert(['key' => $key, 'drive_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }
}
