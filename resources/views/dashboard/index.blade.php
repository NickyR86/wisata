@extends('layouts.app')
@section('content')
@php
$D=\App\Support\DemoData::class;
$active=$requests->whereNotIn('status',['selesai','ditolak']);
$late=$active->filter(fn($r)=>$D::elapsed($r)>3)->sortByDesc(fn($r)=>$D::elapsed($r));
$due=$active->filter(fn($r)=>$D::elapsed($r)===3);
$mine=$active->filter(fn($r)=>($D::OWNER[$r['status']]??'')===$role);
$upcoming=$requests->filter(fn($r)=>in_array($r['status'],['ttd','serah','selesai'])&&\Carbon\CarbonImmutable::parse($r['date'])->addDays($r['days']-1)->toDateString()>=date('Y-m-d'))->sortBy('date')->take(4);
$outstanding=$requests->where('status','bayar')->sum(fn($r)=>$r['bill']['total']??0);
$paid=$requests->filter(fn($r)=>$r['payment'])->sum(fn($r)=>$r['bill']['total']??0);
$taskUrl=match($role){'petugas'=>route('listing','draft'),'sekretaris'=>route('listing','verifikasi'),'bendahara'=>route('listing','retribusi'),'kadis'=>route('listing','tanda-tangan'),default=>route('listing',['kind'=>'antrean','mine'=>1])};
@endphp
<div class="vstack gap-5">
<section class="summary-intro"><div><p class="eyebrow">DINAS PARIWISATA KOTA PANGKALPINANG</p><h2>Kondisi pelayanan hari ini</h2><p>{{ now()->translatedFormat('l, d F Y') }} · {{ \App\Support\Workspace::database() ? 'Ringkasan permohonan dari database lokal.' : 'Ringkasan seluruh permohonan pada sesi ini.' }}</p></div><a class="btn btn-primary" href="{{ $taskUrl }}"><x-icon name="{{ $role==='kadis'?'signature':'arrow-right' }}" size="18"/>{{ $role==='kadis'?'Menunggu tanda tangan':'Buka tugas saya' }} <span class="summary-count">{{ $mine->count() }}</span></a></section>
<div class="row g-3 summary-metrics">
@foreach([
 ['Permohonan aktif',$active->count(),'layers',route('listing','antrean'),'Dalam proses pelayanan','teal'],
 ['Melewati SLA',$late->count(),'alert-triangle',route('listing',['kind'=>'antrean','sla'=>'overdue']),'Lebih dari 3 hari kerja','danger'],
 ['Mencapai batas SLA',$due->count(),'clock',route('listing',['kind'=>'antrean','sla'=>'due']),'Pada hari kerja ke-3','warning'],
 ['Siap diserahkan',$requests->where('status','serah')->count(),'package-check',route('listing',['kind'=>'antrean','status'=>'serah']),'Surat sudah ditandatangani','success']
] as [$label,$count,$icon,$url,$caption,$tone])
<div class="col-6 col-xl-3"><a href="{{ $url }}" class="card card-lift metric-card tone-{{ $tone }}"><div class="d-flex justify-content-between align-items-center"><x-icon :name="$icon" size="20"/><strong>{{ $count }}</strong></div><h3>{{ $label }}</h3><p>{{ $caption }}</p></a></div>@endforeach
</div>
<div class="row g-5"><div class="col-xl-7"><section class="panel h-100"><div class="panel-header"><div><h2>Perlu perhatian</h2><p class="t-12 text-secondary mt-1">Permohonan paling lama melewati batas layanan.</p></div><a href="{{ route('listing',['kind'=>'antrean','sla'=>'overdue']) }}" class="t-12">Lihat semua <x-icon name="chevron-right" size="14"/></a></div>
@forelse($late->take(4) as $item)<a class="attention-row" href="{{ route('detail',$item['id']) }}"><span class="attention-days">{{ $D::elapsed($item) }}<small>hari kerja</small></span><span class="flex-grow-1 min-w-0"><strong>{{ $item['name'] }}</strong><small class="mono">{{ $item['tracking'] }}</small><span class="t-12 text-secondary">{{ $statuses[$item['status']] }} · {{ $roles[$D::OWNER[$item['status']]] }}</span></span><x-icon name="chevron-right" size="18"/></a>@empty<div class="summary-empty"><x-icon name="check-circle-2" size="30"/><p>Tidak ada permohonan melewati SLA.</p></div>@endforelse</section></div>
<div class="col-xl-5"><section class="panel h-100"><div class="panel-header"><h2>Sebaran tahap pelayanan</h2><span class="t-12 text-secondary">{{ $active->count() }} aktif</span></div><div class="p-5">
@foreach(['Peninjauan'=>['masuk'],'Penyusunan & verifikasi'=>['draft','review_draft','verifikasi','rilis'],'Retribusi & pembayaran'=>['tagihan','bayar'],'Tanda tangan & penyerahan'=>['ttd','serah']] as $label=>$stages)
@php($count=$active->whereIn('status',$stages)->count())
<div class="stage-row"><div class="d-flex justify-content-between"><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="stage-track"><span style="width:{{ $active->count()?round($count/$active->count()*100):0 }}%"></span></div></div>@endforeach
<p class="t-12 text-secondary mt-4">Membantu melihat tahap tempat permohonan menumpuk. Buka antrean untuk memprosesnya.</p></div></section></div></div>
<div class="row g-5"><div class="col-xl-7"><section class="panel h-100"><div class="panel-header"><h2>Agenda terdekat</h2><a href="{{ route('kalender') }}" class="t-12">Kalender Jadwal <x-icon name="chevron-right" size="14"/></a></div>@forelse($upcoming as $item)<a class="attention-row" href="{{ route('detail',$item['id']) }}"><span class="summary-date"><strong>{{ date('d',strtotime($item['date'])) }}</strong><small>{{ \Carbon\CarbonImmutable::parse($item['date'])->translatedFormat('M') }}</small></span><span class="flex-grow-1"><strong>{{ $item['name'] }}</strong><small>{{ \App\Support\DemoData::locationName($item,$locations) }}</small>@if($item['status']==='ttd')<span class="t-12 text-warning-emphasis">Menunggu tanda tangan</span>@endif</span><x-icon name="chevron-right" size="18"/></a>@empty<div class="summary-empty"><p>Belum ada agenda mendatang setelah pembayaran.</p></div>@endforelse</section></div>@if(in_array($role,['bendahara','kadis','admin']))<div class="col-xl-5"><section class="panel h-100"><div class="panel-header"><h2>Ringkasan retribusi</h2><x-icon name="wallet" size="18"/></div><div class="p-5"><div class="finance-total"><span>Menunggu pembayaran</span><strong>{{ $D::rupiah($outstanding) }}</strong><small>{{ $requests->where('status','bayar')->count() }} tagihan belum lunas</small></div><div class="finance-total border-top pt-4 mt-4"><span>Pembayaran dikonfirmasi</span><strong>{{ $D::rupiah($paid) }}</strong><small>{{ \App\Support\Workspace::database() ? 'Akumulasi pembayaran pada database lokal' : 'Akumulasi pada data sesi ini' }}</small></div><a class="btn btn-outline-secondary mt-4" href="{{ route('listing',['kind'=>'antrean','status'=>'bayar']) }}">Lihat tagihan belum lunas <x-icon name="arrow-right" size="16"/></a></div></section></div>@endif</div>
<p class="summary-footnote"><x-icon name="info" size="15"/> SLA dihitung sejak pengajuan, tidak diulang per tahap. Penghitungan hari kerja saat ini mengecualikan Sabtu–Minggu; hari libur belum dikonfigurasi.</p>
</div>
@endsection

