@extends('admin.layouts.sidebar')
@section('title', 'Journaux système')
@section('page-title', 'Journaux système (Logs Laravel)')

@section('topbar-actions')
<form action="{{ route('admin.logs.clear') }}" method="POST"
      onsubmit="return confirm('Effacer tous les logs ?')">
    @csrf
    <button class="btn btn-danger btn-sm">
        <i class="fas fa-trash me-1"></i> Vider les logs
    </button>
</form>
@endsection

@section('content')

@if($error)
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}</div>
@else

<div class="mb-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">300 dernières lignes — du plus récent au plus ancien</small>
    <div class="d-flex gap-2">
        <span class="badge" style="background:#ff7b72">ERROR</span>
        <span class="badge" style="background:#e3b341;color:#000">WARNING</span>
        <span class="badge" style="background:#58a6ff">INFO</span>
    </div>
</div>

<div id="log-output">
@foreach($lines as $line)
@php
    $class = '';
    if (str_contains(strtoupper($line), '.ERROR') || str_contains(strtoupper($line), '[ERROR]')) $class = 'log-error';
    elseif (str_contains(strtoupper($line), '.WARNING') || str_contains(strtoupper($line), '[WARNING]')) $class = 'log-warning';
    elseif (str_contains(strtoupper($line), '.INFO') || str_contains(strtoupper($line), '[INFO]')) $class = 'log-info';
@endphp
<span class="{{ $class }}">{{ $line }}</span>
<br>
@endforeach
</div>

@endif
@endsection
