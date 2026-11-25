<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Folder;
use App\Services\DriveSync;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * One Drive operation for a folder or a document. Safe to run late or twice:
 * when Drive isn't connected yet it does nothing, and "Sync now" on the Drive
 * page picks up whatever is still pending.
 */
class SyncToDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 5;
    public $backoff = [30, 120, 600, 1800];

    public function __construct(public string $action, public ?int $id = null, public ?string $driveId = null, public ?string $name = null)
    {
    }

    /** Queue it when a worker runs; otherwise do it right after the response so uploads feel instant. */
    public static function send(string $action, ?int $id = null, ?string $driveId = null, ?string $name = null): void
    {
        config('queue.default') === 'sync'
            ? static::dispatchAfterResponse($action, $id, $driveId, $name)
            : static::dispatch($action, $id, $driveId, $name);
    }

    public function handle(DriveSync $sync, GoogleDrive $drive): void
    {
        if (! $sync->ready()) {
            return;
        }

        try {
            match ($this->action) {
                'document' => ($doc = Document::find($this->id)) ? $sync->pushDocument($doc) : null,
                'folder' => ($f = Folder::find($this->id)) ? $sync->folderId($f) : null,
                'rename' => $this->driveId ? $drive->rename($this->driveId, $this->name) : null,
                'trash' => $this->driveId ? $drive->trash($this->driveId) : null,
            };
        } catch (\Throwable $e) {
            if ($this->action === 'document') {
                Document::whereKey($this->id)->update(['drive_error' => mb_substr($e->getMessage(), 0, 500)]);
            }
            Log::warning('Drive sync failed', ['action' => $this->action, 'id' => $this->id, 'error' => $e->getMessage()]);

            if (config('queue.default') !== 'sync') {
                throw $e; // let the worker retry with backoff
            }
        }
    }
}
