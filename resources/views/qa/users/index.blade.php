@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
@php
    $groups = $users->groupBy(fn ($u) => $u->isInCharge() ? 'Campus / college in-charges' : 'QA office & administrators');
@endphp

<x-masthead index="Records · Access" title="Accounts">
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('qa.users.create') }}"><x-icon name="plus" /> New account</a>
    </x-slot>
    <x-slot name="meta"><span>An in-charge only sees the visits assigned to their own college or campus.</span></x-slot>
</x-masthead>

<div class="sheet">
    <div class="table-scroll">
        <table class="ledger">
            <thead><tr><th>Name</th><th>Role</th><th>College / campus</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            @foreach ($groups->sortKeysDesc() as $group => $rows)
                <tr class="group"><td colspan="4">{{ $group }} · {{ $rows->count() }}</td></tr>
                @foreach ($rows as $user)
                    <tr>
                        <td>
                            <a class="t-title" href="{{ route('qa.users.edit', $user) }}">{{ $user->name }}</a>
                            @if ($user->is(auth()->user()))<span class="stamp note" style="margin-left:6px">You</span>@endif
                            <div class="t-sub">{{ $user->email }}</div>
                        </td>
                        <td>{{ $user->role_label }}</td>
                        <td>
                            @if ($user->college)
                                <span class="code">{{ $user->college->code }}</span> {{ $user->college->name }}
                            @elseif ($user->isInCharge())
                                <span class="stamp note">Not linked</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="end">
                            <a class="link" href="{{ route('qa.users.edit', $user) }}">Edit</a>
                            @unless ($user->is(auth()->user()))
                                <form class="inline" method="POST" action="{{ route('qa.users.destroy', $user) }}"
                                      data-confirm="{{ $user->name }} will no longer be able to sign in. Files they uploaded stay on record." data-confirm-title="Remove this account?" data-confirm-ok="Remove account">
                                    @csrf @method('DELETE')
                                    <button class="link danger" type="submit">Remove</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
