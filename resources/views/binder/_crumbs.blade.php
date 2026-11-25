{{-- Breadcrumb root for binder pages. Expects $mode, $college, $visit. --}}
@if ($mode === 'qa')
    <a href="{{ route('qa.files.show', $college) }}">College files</a><span class="sep">/</span>
    <a href="{{ route('qa.files.show', $college) }}">{{ $college->code }}</a><span class="sep">/</span>
@elseif ($mode === 'incharge')
    <a href="{{ route('binder.index') }}">My visits</a><span class="sep">/</span>
@else
    <span>Shared documents</span><span class="sep">/</span>
@endif
