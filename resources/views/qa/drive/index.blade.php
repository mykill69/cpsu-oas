@extends('layouts.app')

@section('title', 'Google Drive')

@section('content')
<x-masthead index="Records" title="Google Drive storage">
    <x-slot name="meta">Every folder and file the colleges upload is copied into the Drive of {{ $account }}, organised by school year, college, visit, area and parameter.</x-slot>
</x-masthead>

@unless ($configured)
    <div class="notice warn"><x-icon name="alert" /> Google isn't configured. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to the .env file (see docs/google-setup.md).</div>
@endunless

<div class="grid-main">
    <section class="sheet">
        <div class="sheet-head">
            <x-card-title icon="drive">Connection</x-card-title>
            @if ($connected)<span class="stamp open">Connected</span>@else<span class="stamp closed">Not connected</span>@endif
        </div>
        <div class="sheet-body">
            @if ($connected)
                <dl class="kv">
                    <dt>Account</dt><dd>{{ $email }}</dd>
                    <dt>Top folder</dt><dd>{{ config('oas.drive_root_name') }}</dd>
                    <dt>Access</dt><dd>Only files this system created (Google “drive.file” permission)</dd>
                    <dt>Files in Drive</dt><dd>{{ number_format($synced) }}</dd>
                    <dt>Waiting</dt><dd>{{ number_format($pending) }}</dd>
                    <dt>No-reply email</dt>
                    <dd>
                        @if ($canMail && $mailer === 'gmail')
                            <span class="stamp open">On</span> sends as {{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;
                        @elseif ($mailer !== 'gmail')
                            <span class="stamp closed">Not used</span> MAIL_MAILER is “{{ $mailer }}”
                        @else
                            <span class="stamp note">Needs Reconnect</span> click Reconnect and allow “Send email on your behalf”
                        @endif
                    </dd>
                </dl>
                <div class="toolbar" style="margin-top:18px">
                    <form class="inline" method="POST" action="{{ route('qa.drive.sync') }}">@csrf
                        <button class="btn btn-primary" type="submit" @disabled(! $pending)><x-icon name="upload" /> Sync waiting files now</button>
                    </form>
                    <a class="btn" href="{{ route('qa.drive.connect') }}">Reconnect</a>
                    <form class="inline" method="POST" action="{{ route('qa.drive.disconnect') }}"
                          data-confirm="Uploads will stay on the server until Drive is connected again. Nothing in Drive is deleted." data-confirm-title="Disconnect Google Drive?" data-confirm-ok="Disconnect">
                        @csrf <button class="btn btn-danger" type="submit">Disconnect</button>
                    </form>
                </div>
            @else
                <p style="margin-bottom:14px">Sign in as <b>{{ $account }}</b> and allow access. The system then creates a “{{ config('oas.drive_root_name') }}” folder in that Drive and fills it as colleges upload.</p>
                @if ($configured)
                    <a class="btn btn-primary" href="{{ route('qa.drive.connect') }}"><x-icon name="google" /> Connect {{ $account }}</a>
                @endif
                @if ($pending)<p class="muted" style="margin-top:12px">{{ $pending }} uploaded {{ \Illuminate\Support\Str::plural('file', $pending) }} are on the server waiting for Drive.</p>@endif
            @endif
        </div>
    </section>

    <section class="sheet">
        <div class="sheet-head"><x-card-title icon="alert">Needs attention</x-card-title></div>
        @if ($queue === 'sync')
            <div class="notice warn" style="margin:14px">Uploads go to Drive right after each request. For big files, run a queue worker (QUEUE_CONNECTION=database) — see docs/google-setup.md.</div>
        @endif
        @if ($failed->isEmpty())
            <x-empty title="All clear">No failed Drive uploads.</x-empty>
        @else
            <ul class="feed">
                @foreach ($failed as $doc)
                    <li>
                        <span class="ext {{ $doc->extension }}">{{ $doc->extension }}</span>
                        <div>
                            <a href="{{ route('binder.folder', $doc->folder_id) }}">{{ \Illuminate\Support\Str::limit($doc->name, 40) }}</a>
                            <div class="doc-meta" style="color:var(--red)">{{ \Illuminate\Support\Str::limit($doc->drive_error, 120) }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
