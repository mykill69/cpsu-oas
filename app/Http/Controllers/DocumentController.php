<?php

namespace App\Http\Controllers;

use App\Jobs\SyncToDrive;
use App\Models\Document;
use App\Models\Folder;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

class DocumentController extends Controller
{
    public const ACCEPT = 'pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,jpg,jpeg,png,gif,webp,mp4,webm,mov,m4v,mp3,wav,m4a,zip,rar';
    public const MAX_MB = 200;

    public function store(Request $request, Folder $folder)
    {
        $this->authorize('edit-binder', [$folder->college, $folder->area->surveyVisit]);

        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:' . (self::MAX_MB * 1024) . '|mimes:' . self::ACCEPT,
        ], [
            'files.*.max' => 'Each file must be ' . self::MAX_MB . ' MB or smaller.',
            'files.*.mimes' => 'Accepted: PDF, Office files, images, videos, audio, text and ZIP/RAR.',
        ]);

        foreach ($request->file('files') as $file) {
            // Kept on the server until it reaches the system Drive (see SyncToDrive).
            $doc = $folder->documents()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $file->store("binders/{$folder->college_id}/{$folder->id}"),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'uploaded_by' => $request->user()->id,
            ]);
            SyncToDrive::send('document', $doc->id);
        }

        $count = count($request->file('files'));

        return back()->with('success', $count === 1 ? 'File uploaded.' : "{$count} files uploaded.");
    }

    public function show(Request $request, Document $document)
    {
        $folder = $document->folder;
        $this->authorize('view-binder', [$folder->college, $folder->area->surveyVisit]);

        return self::respond($request, $document);
    }

    /**
     * Stream a file through the app — from local disk if it's still here, otherwise from
     * Drive. The browser only ever sees this app's URL, never a Drive link.
     */
    public static function respond(Request $request, Document $document)
    {
        $inline = $request->boolean('inline') && $document->isPreviewable();
        $disposition = HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $document->name, preg_replace('/[^\x20-\x7e]|[%\/\\\\"]/', '_', $document->name));
        $headers = [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];
        if ($inline && $document->previewKind() === 'text') {
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        if ($document->path && Storage::exists($document->path)) {
            return response()->file(Storage::path($document->path), $headers); // handles Range itself
        }

        abort_unless($document->drive_id, 404, 'The stored file is missing.');

        $drive = app(GoogleDrive::class)->download($document->drive_id, $request->header('Range'));
        foreach (['Content-Length', 'Content-Range', 'Accept-Ranges'] as $h) {
            if ($drive->hasHeader($h)) {
                $headers[$h] = $drive->getHeaderLine($h);
            }
        }

        return response()->stream(function () use ($drive) {
            $body = $drive->getBody();
            while (! $body->eof()) {
                echo $body->read(1024 * 256);
                flush();
            }
        }, $drive->getStatusCode(), $headers);
    }

    public function destroy(Document $document)
    {
        $folder = $document->folder;
        $this->authorize('edit-binder', [$folder->college, $folder->area->surveyVisit]);

        if ($document->path) {
            Storage::delete($document->path);
        }
        if ($document->drive_id) {
            SyncToDrive::send('trash', null, $document->drive_id);
        }
        $document->delete();

        return back()->with('success', 'File deleted.');
    }
}
