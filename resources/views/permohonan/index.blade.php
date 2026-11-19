@extends('layouts.app')
@section('content')
@php
$D=\App\Support\DemoData::class;
$short=['sekretariat'=>'Sekretariat','kabid'=>'Kabid','petugas'=>'Petugas Layanan','sekretaris'=>'Sekretaris','bendahara'=>'Bendahara','kadis'=>'Kadis','admin'=>'Admin'];
$active=$requests->whereNotIn('status',['selesai','ditolak']);
$mine=$active->filter(fn($r)=>($D::OWNER[$r['status']]??'')===$role);
$urgent=$active->filter(fn($r)=>$D::elapsed($r)>=3);
$url=fn($changes)=>route('listing',array_merge(request()->except('page'),['kind'=>$kind],$changes));
@endphp
<div class="vstack gap-5">
@if($kind==='riwayat'&&in_array($role,['sekretariat','admin']))<nav class="d-flex gap-2"><a class="btn btn-primary" href="{{ route('listing','riwayat') }}">Riwayat Permohonan</a><a class="btn btn-outline-secondary" href="{{ route('listing',['kind'=>'riwayat','tab'=>'hapus']) }}">Riwayat Hapus</a></nav>@endif
@if($kind==='antrean')
<div class="queue-heading"><div><h2>Pekerjaan {{ $short[$role] }}</h2><p>{{ $mine->count() }} permohonan memerlukan tindakan Anda.</p></div><div class="d-flex gap-2 flex-wrap"><a class="btn {{ request('mine')?'btn-primary':'btn-outline-secondary' }}" href="{{ $url(['mine'=>request('mine')?null:1]) }}"><x-icon name="list-filter" size="16"/> Perlu tindakan saya</a>@if($role==='sekretariat')<a class="btn btn-primary" href="{{ route('permohonan.create') }}"><x-icon name="file-plus-2" size="16"/> Permohonan Baru</a>@endif</div></div>
<div class="row g-3 queue-stats">@foreach([['','Total Aktif','layers',$active->count()],['masuk','Berkas Masuk','inbox',$requests->where('status','masuk')->count()],['bayar','Menunggu Bayar','wallet',$requests->where('status','bayar')->count()],['serah','Siap Diambil','package-check',$requests->where('status','serah')->count()]] as [$status,$label,$icon,$count])<div class="col-6 col-lg-3"><a class="card card-lift h-100 {{ request('status','')===$status?'stat-on':'' }}" href="{{ $url(['status'=>$status,'mine'=>null,'sla'=>null]) }}"><div class="card-body p-4"><div class="d-flex align-items-center justify-content-between"><span class="stat-chip"><x-icon :name="$icon" size="18"/></span><p class="stat-n">{{ $count }}</p></div><p class="t-125 fw-medium text-secondary mt-3 mb-0">{{ $label }}</p></div></a></div>@endforeach</div>
@else
<div class="d-flex justify-content-between align-items-center gap-3"><div><h2 class="section-heading">{{ $kind==='arsip'?'Arsip Digital':'Log & Riwayat' }}</h2><p class="t-13 text-secondary">{{ $kind==='arsip'?'Dokumen permohonan yang telah selesai dan diserahkan.':'Telusuri perjalanan permohonan dan riwayat disposisi.' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('export',['kind'=>$kind]) }}"><x-icon name="download" size="16"/> Unduh Rekap</a>@if($kind==='arsip')<a class="btn btn-outline-secondary" href="{{ route('archive.package') }}">Cadangan metadata</a>@endif</div>
@endif
@if(request('sla'))<div class="sla-filter-note"><x-icon name="clock" size="16"/><span>{{ request('sla')==='overdue'?'Menampilkan permohonan melewati 3 hari kerja sejak pengajuan.':'Menampilkan permohonan pada hari kerja ke-3 sejak pengajuan.' }}</span><a href="{{ $url(['sla'=>null]) }}">Hapus filter SLA</a></div>@endif
@include('permohonan.filters')
@if($kind==='arsip')
<section class="panel table-responsive"><table class="table"><thead><tr><th>Pemohon</th><th>Penyerahan / penerima</th><th>Dokumen lengkap</th><th>Tindakan</th></tr></thead><tbody>@foreach($rows as $record)<tr><td><strong>{{ $record['name'] }}</strong><small class="d-block">{{ $record['tracking'] }}</small></td><td>{{ $record['completed'] }}<small class="d-block">{{ $record['recipient'] }}</small></td><td><div class="d-flex gap-2 flex-wrap">@foreach(['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $slug=>$label)@if(in_array($label,$record['documents']))<a class="btn btn-light btn-sm" href="{{ route('document',[$record['id'],$slug,'from'=>'arsip']) }}">{{ $label }}</a>@endif @endforeach</div></td><td><a href="{{ route('detail',$record['id']) }}">Detail</a></td></tr>@endforeach</tbody></table></section>
@elseif($kind==='riwayat')
<section class="panel table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Permohonan</th><th>Pelaku / peran</th><th>Tindakan & catatan</th></tr></thead><tbody>@foreach($rows as $record)@foreach(array_reverse($record['history']) as $entry)<tr><td class="text-nowrap">{{ $entry['at'] }}</td><td><a href="{{ route('detail',$record['id']) }}">{{ $record['tracking'] }}</a><small class="d-block">{{ $record['name'] }}</small></td><td>{{ $roles[$entry['role']]??$entry['role'] }}<small class="d-block">Pengguna demo</small></td><td><strong>{{ $entry['action'] }}</strong><small class="d-block">{{ $entry['note'] }}</small></td></tr>@endforeach @endforeach</tbody></table></section>
@else<x-request-table :rows="$rows" :locations="$locations"/>@endif
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 t-12 text-secondary"><span>Menampilkan {{ $rows->firstItem()??0 }}–{{ $rows->lastItem()??0 }} dari {{ $rows->total() }} permohonan</span><div class="btn-group"><a class="btn btn-outline-secondary btn-sm {{ $rows->onFirstPage()?'disabled':'' }}" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a><span class="btn btn-light btn-sm">{{ $rows->currentPage() }} / {{ max(1,$rows->lastPage()) }}</span><a class="btn btn-outline-secondary btn-sm {{ !$rows->hasMorePages()?'disabled':'' }}" href="{{ $rows->nextPageUrl() }}">Berikutnya</a></div></div>
</div>
@endsection

