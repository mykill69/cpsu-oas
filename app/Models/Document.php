<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['folder_id', 'name', 'path', 'size', 'mime', 'uploaded_by'];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getExtensionAttribute()
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function getHumanSizeAttribute()
    {
        $size = $this->size;
        foreach (['B', 'KB', 'MB'] as $unit) {
            if ($size < 1024) {
                return ($unit === 'B' ? $size : number_format($size, 1)) . ' ' . $unit;
            }
            $size /= 1024;
        }

        return number_format($size, 1) . ' GB';
    }

    /** Browsers can show these inline; everything else downloads. */
    public function isPreviewable(): bool
    {
        return $this->previewKind() !== null;
    }

    /** How the in-app viewer shows it: pdf / image / video / audio / text, or null. */
    public function previewKind(): ?string
    {
        return match ($this->extension) {
            'pdf' => 'pdf',
            'png', 'jpg', 'jpeg', 'gif', 'webp' => 'image',
            'mp4', 'webm', 'mov', 'm4v' => 'video',
            'mp3', 'wav', 'm4a' => 'audio',
            'txt', 'csv' => 'text',
            default => null,
        };
    }
}
