@extends('layouts.app')
@section('content')
<nav class="d-flex gap-2 mb-4"><a class="btn btn-outline-secondary" href="{{ route('listing','riwayat') }}">Riwayat Permohonan</a><a class="btn btn-primary" href="{{ route('listing',['kind'=>'riwayat','tab'=>'hapus']) }}">Riwayat Hapus</a></nav>
<h2 class="mb-3">Permohonan dihapus sementara ({{ $deleted->count() }})</h2><p class="text-secondary">Pemulihan mengembalikan permohonan ke tahap terakhirnya.</p>
<div class="vstack gap-3">@forelse($deleted as $item)<section class="panel p-4"><div class="d-flex justify-content-between gap-3 flex-wrap"><div><h3>{{ $item['name'] }}</h3><p class="mono">{{ $item['tracking'] }}</p></div><x-status-badge :status="$item['status']"/></div><p>{{ $item['delete_reason'] }}</p><small>{{ $item['deleted_by_name']??($roles[$item['deleted_by']]??'Petugas') }} · {{ $item['deleted_at'] }}</small><form method="post" action="{{ route('permohonan.restore',$item['id']) }}" class="mt-3">@csrf
@if(isset($item['_v']))<input type="hidden" name="record_version" value="{{ $item['_v'] }}">@endif
<button class="btn btn-outline-primary">Pulihkan permohonan</button></form></section>@empty<section class="panel p-5">Belum ada permohonan dihapus.</section>@endforelse</div>
@endsection
