@extends('layouts.app')

@section('title', 'Accreditor access')

@section('content')
<x-masthead index="QA office" title="Accreditor access">
    <x-slot name="meta">Every link given to an accreditor. Each one opens a single college's files for a single visit, read-only, with a one-time code sent to the accreditor's email.</x-slot>
</x-masthead>

<section class="sheet">
    <div class="sheet-head">
        <x-card-title icon="share">Shared links</x-card-title>
        <span class="sheet-note">To share, open a college's files from <a href="{{ route('qa.files.index') }}">College files</a> and use “Share with accreditor”.</span>
    </div>
    @if ($shares->isEmpty())
        <x-empty title="Nothing shared yet">Shares you create appear here with their status, so you can resend or revoke them.</x-empty>
    @else
        <div class="table-scroll">
            <table class="ledger">
                <thead><tr><th>Accreditor</th><th>College · visit</th><th>Status</th><th>Access until</th><th>Last opened</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($shares as $s)
                    @php $tone = ['active' => 'open', 'invited' => 'blue', 'expired' => 'closed', 'revoked' => 'red'][$s->status]; @endphp
                    <tr>
                        <td>
                            <span class="t-title">{{ $s->name }}</span>
                            <div class="t-sub">{{ $s->email }}</div>
                        </td>
                        <td>
                            <a class="t-title" href="{{ route('binder.visit', [$s->college, $s->surveyVisit]) }}#share"><span class="code">{{ $s->college->code }}</span> {{ $s->surveyVisit->name }}</a>
                            <div class="t-sub">Shared by {{ $s->creator?->name ?? '—' }} · {{ $s->created_at->format('M j, Y') }}</div>
                        </td>
                        <td><span class="stamp {{ $tone }}">{{ ucfirst($s->status) }}</span></td>
                        <td class="nowrap">{{ $s->expires_at->format('M j, Y') }}</td>
                        <td class="nowrap">{{ $s->last_viewed_at ? $s->last_viewed_at->diffForHumans() . ' · ' . $s->view_count . '×' : 'Not yet' }}</td>
                        <td class="end">
                            @if ($s->isUsable())
                                <form class="inline" method="POST" action="{{ route('qa.shares.resend', $s) }}"
                                      data-confirm="A new link and code go to {{ $s->email }}. The old link stops working." data-confirm-title="Send a new link?" data-confirm-ok="Send">
                                    @csrf <button class="link" type="submit">Resend</button>
                                </form>
                                <form class="inline" method="POST" action="{{ route('qa.shares.revoke', $s) }}"
                                      data-confirm="{{ $s->name }} will lose access immediately." data-confirm-title="Revoke access?" data-confirm-ok="Revoke">
                                    @csrf <button class="link danger" type="submit">Revoke</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
