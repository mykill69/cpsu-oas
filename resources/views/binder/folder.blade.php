@extends($mode === 'review' ? 'layouts.review' : 'layouts.app')

@php
    $place = $parameter ? $area->short_label . '-' . $parameter->code : $area->short_label;
    $nodeLabel = $parameter ? $parameter->code . '. ' . $parameter->name : $area->title;
    $nodeUrl = $links->node($college, $area, $parameter);
    $chain = $folder ? $folder->ancestry() : collect();
    $here = $folder ? $folder->name : $nodeLabel;
    $maxMb = \App\Http\Controllers\DocumentController::MAX_MB;
@endphp

@section('title', $here)

@section('crumbs')
    @include('binder._crumbs')
    <a href="{{ $links->visit($college, $visit) }}">{{ $visit->name }}</a><span class="sep">/</span>
    @if ($folder)
        <a href="{{ $nodeUrl }}">{{ $place }}</a><span class="sep">/</span>
        @foreach ($chain as $node)
            @if ($loop->last)
                <span class="here">{{ $node->name }}</span>
            @else
                <a href="{{ $links->folder($node) }}">{{ $node->name }}</a><span class="sep">/</span>
            @endif
        @endforeach
    @else
        <span class="here">{{ $place }}</span>
    @endif
@endsection

@section('content')
<x-masthead :index="$college->code . ' · ' . ($area->numeral ? 'Area ' . $area->numeral . ' · ' . $area->title : $area->title)" :title="$here">
    <x-slot name="meta">
        @if ($folder)
            <span>In <a href="{{ $nodeUrl }}">{{ $nodeLabel }}</a></span>
            @if ($folder->creator)<span>Created by {{ $folder->creator->name }} · {{ $folder->created_at->format('M j, Y') }}</span>@endif
        @else
            <span>{{ $parameter ? 'Parameter ' . $parameter->code . ' of ' . $area->name : 'Folders filed directly under this area' }}</span>
        @endif
        @unless ($canEdit)
            <x-stamp :open="false" :off="$mode === 'incharge' ? 'Closed' : 'Read only'" />
        @endunless
    </x-slot>
</x-masthead>

@if ($errors->any())
    <div class="slip bad" role="alert"><div class="slip-text"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
@endif

<section class="sheet {{ $canEdit && $folder ? 'drop-target' : '' }}" id="sheet" data-drop-label="Drop to upload into “{{ $here }}”">
    <div class="sheet-head">
        <x-card-title icon="folder">{{ $folder ? 'Contents' : 'Folders' }}</x-card-title>
        @if ($canEdit)
            <div class="toolbar">
                @if ($folder)
                    <details class="pop">
                        <summary class="btn btn-sm"><x-icon name="pencil" /> Rename</summary>
                        <form class="pop-panel" method="POST" action="{{ route('folders.update', $folder) }}">
                            @csrf @method('PATCH')
                            <label class="lbl" for="rename">Folder name</label>
                            <input class="input" id="rename" name="name" value="{{ $folder->name }}" required maxlength="150">
                            <div class="btns"><button class="btn btn-primary btn-sm" type="submit">Save</button></div>
                        </form>
                    </details>
                    <form class="inline" method="POST" action="{{ route('folders.destroy', $folder) }}"
                          data-confirm="“{{ $folder->name }}” and everything inside it will be deleted." data-confirm-title="Delete this folder?" data-confirm-ok="Delete folder">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                @endif
                <details class="pop" @if($errors->has('name')) open @endif>
                    <summary class="btn btn-sm"><x-icon name="folder" /> {{ $folder ? 'New subfolder' : 'New folder' }}</summary>
                    <form class="pop-panel" method="POST" action="{{ route('folders.store') }}">
                        @csrf
                        <input type="hidden" name="college_id" value="{{ $college->id }}">
                        <input type="hidden" name="area_id" value="{{ $area->id }}">
                        @if ($parameter)<input type="hidden" name="parameter_id" value="{{ $parameter->id }}">@endif
                        @if ($folder)<input type="hidden" name="parent_id" value="{{ $folder->id }}">@endif
                        <label class="lbl" for="folder-name">Folder name</label>
                        <input class="input" id="folder-name" name="name" required maxlength="150" value="{{ old('name') }}" placeholder="e.g. Exhibit A – Board Resolutions">
                        <div class="btns"><button class="btn btn-primary btn-sm" type="submit">Create folder</button></div>
                    </form>
                </details>
                @if ($folder)
                    <form id="uploadForm" class="inline" method="POST" action="{{ route('documents.store', $folder) }}" enctype="multipart/form-data">
                        @csrf
                        <input class="sr-only" type="file" id="files" name="files[]" multiple accept=".{{ str_replace(',', ',.', \App\Http\Controllers\DocumentController::ACCEPT) }}">
                        <label class="btn btn-primary btn-sm" for="files" tabindex="0" id="uploadBtn"><x-icon name="upload" /> Upload files</label>
                    </form>
                @endif
            </div>
        @endif
    </div>

    <div class="uploading" id="uploading" hidden></div>

    @if ($folders->isEmpty() && $documents->isEmpty())
        @if ($folder)
            <x-empty title="This folder is empty">
                @if ($canEdit) Drop files here, or use Upload files. PDF, Office files, images, videos and ZIP up to {{ $maxMb }} MB each. @else No files have been filed here yet. @endif
            </x-empty>
        @else
            <x-empty title="No folders yet">
                @if ($canEdit) Start with a folder for this parameter, e.g. “Exhibit A – Board Resolutions”. Files go inside folders. @else Nothing has been filed here yet. @endif
            </x-empty>
        @endif
    @endif

    @if ($folders->isNotEmpty())
        <div class="tiles">
            @foreach ($folders as $f)
                <a class="tile" href="{{ $links->folder($f) }}">
                    <span class="fico"><x-icon name="folder" /></span>
                    <span style="min-width:0">
                        <b>{{ $f->name }}</b>
                        <small>{{ $f->documents_count }} {{ \Illuminate\Support\Str::plural('file', $f->documents_count) }}@if ($f->children_count) · {{ $f->children_count }} {{ \Illuminate\Support\Str::plural('folder', $f->children_count) }}@endif</small>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($documents->isNotEmpty())
        <div class="files-head">
            <span>Files · {{ $documents->count() }}</span>
            <span style="text-transform:none;letter-spacing:0;font-weight:500">{{ \App\Models\Document::make(['size' => $documents->sum('size')])->human_size }} total</span>
        </div>
        <ul class="doclist">
            @foreach ($documents as $doc)
                <li>
                    <span class="ext {{ $doc->extension }}">{{ $doc->extension ?: 'file' }}</span>
                    <div>
                        <button type="button" class="doc-name" data-view data-kind="{{ $doc->previewKind() }}"
                                data-src="{{ $doc->isPreviewable() ? $links->document($doc, true) : '' }}" data-dl="{{ $links->document($doc) }}"
                                data-name="{{ $doc->name }}" data-meta="{{ $doc->human_size }} · {{ $doc->uploader?->name }} · {{ $doc->created_at->format('M j, Y') }}">{{ $doc->name }}</button>
                        <div class="doc-meta">
                            @if ($mode !== 'review')
                                <span class="sync-dot {{ $doc->drive_id ? 'ok' : ($doc->drive_error ? 'err' : 'wait') }}"
                                      title="{{ $doc->drive_id ? 'Saved in Google Drive' : ($doc->drive_error ? 'Drive upload failed; it will retry' : 'Waiting to reach Google Drive') }}"></span>
                            @endif
                            {{ $doc->human_size }} · {{ $doc->uploader?->name ?? 'Unknown' }} · {{ $doc->created_at->format('M j, Y · g:i A') }}
                        </div>
                    </div>
                    <div class="tools">
                        <button type="button" class="ibtn" data-view data-kind="{{ $doc->previewKind() }}"
                                data-src="{{ $doc->isPreviewable() ? $links->document($doc, true) : '' }}" data-dl="{{ $links->document($doc) }}"
                                data-name="{{ $doc->name }}" data-meta="{{ $doc->human_size }}" aria-label="View {{ $doc->name }}" title="View"><x-icon name="eye" /></button>
                        <a class="ibtn" href="{{ $links->document($doc) }}" aria-label="Download {{ $doc->name }}" title="Download"><x-icon name="download" /></a>
                        @if ($canEdit)
                            <form class="inline" method="POST" action="{{ route('documents.destroy', $doc) }}"
                                  data-confirm="{{ $doc->name }} will be deleted." data-confirm-title="Delete this file?" data-confirm-ok="Delete file">
                                @csrf @method('DELETE')
                                <button class="ibtn" type="submit" aria-label="Delete {{ $doc->name }}" title="Delete"><x-icon name="x" /></button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@include('binder._viewer')
@endsection

@if ($canEdit && $folder)
@push('scripts')
<script>
(function () {
    var form = document.getElementById('uploadForm'), input = document.getElementById('files'),
        sheet = document.getElementById('sheet'), status = document.getElementById('uploading'), label = document.getElementById('uploadBtn');
    var MAX = {{ $maxMb }} * 1024 * 1024, TOTAL = 550 * 1024 * 1024;

    function send(files) {
        if (!files || !files.length) return;
        var total = 0, big = null;
        Array.prototype.forEach.call(files, function (f) { total += f.size; if (f.size > MAX && !big) big = f; });
        if (big) { alert(big.name + ' is larger than {{ $maxMb }} MB.'); input.value = ''; return; }
        if (files.length > 20) { alert('Upload at most 20 files at a time.'); input.value = ''; return; }
        if (total > TOTAL) { alert('That batch is over 550 MB. Upload it in smaller groups.'); input.value = ''; return; }
        if (input.files !== files) input.files = files;
        status.textContent = 'Uploading ' + (files.length === 1 ? files[0].name : files.length + ' files') + '…';
        status.hidden = false;
        label.classList.add('is-busy');
        form.submit();
    }

    input.addEventListener('change', function () { send(input.files); });
    label.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });

    var depth = 0;
    sheet.addEventListener('dragenter', function (e) { if (e.dataTransfer.types.indexOf('Files') < 0) return; e.preventDefault(); depth++; sheet.classList.add('dragging'); });
    sheet.addEventListener('dragover', function (e) { if (e.dataTransfer.types.indexOf('Files') >= 0) e.preventDefault(); });
    sheet.addEventListener('dragleave', function () { if (--depth <= 0) { depth = 0; sheet.classList.remove('dragging'); } });
    sheet.addEventListener('drop', function (e) { e.preventDefault(); depth = 0; sheet.classList.remove('dragging'); send(e.dataTransfer.files); });
})();
</script>
@endpush
@endif
