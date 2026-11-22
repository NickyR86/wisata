@extends('layouts.app')
@section('content')
@php($db=\App\Support\Workspace::database())
<div class="page-heading"><div><span class="eyebrow">DATA MASTER</span><h1>Lokasi, Kegiatan & Tarif</h1><p>{{ $db?'Data di sini muncul otomatis di formulir Permohonan Baru. Lokasi yang sudah dipakai permohonan tidak dapat dihapus, hanya dinonaktifkan.':'Tarif di bawah adalah contoh untuk presentasi, bukan tarif resmi Dispar.' }}</p></div>@unless($db)<span class="demo-tag">DATA CONTOH</span>@endunless</div>
@unless($db)<div class="alert alert-warning mb-3" role="status">Mode demo: hanya tarif contoh yang dapat diubah. Menambah atau mengubah lokasi dan jenis kegiatan tersedia pada mode database.</div>@endunless

@if($db)
<section class="panel p-4 mb-4" aria-labelledby="kegiatan-title">
    <div class="section-title"><div><h2 id="kegiatan-title">Jenis Kegiatan</h2><p>Tambahkan jenis kegiatan lebih dulu sebelum menambah lokasi. Tarif lokasi yang sudah ada otomatis disalin ke jenis kegiatan baru.</p></div></div>
    <div class="row g-3 mb-3">
        @forelse($activities as $act)
        <div class="col-md-6"><div class="d-flex align-items-center justify-content-between gap-2 border rounded p-2">
            <div><strong>{{ $act['name'] }}</strong> <span class="status-badge {{ $act['active']?'status-selesai':'status-ditolak' }}">{{ $act['active']?'Aktif':'Nonaktif' }}</span></div>
            <form method="post" action="{{ route('master.activity.toggle',$act['id']) }}">@csrf<input type="hidden" name="active" value="{{ $act['active']?0:1 }}"><button class="btn btn-sm btn-outline-secondary">{{ $act['active']?'Nonaktifkan':'Aktifkan' }}</button></form>
        </div></div>
        @empty
        <div class="col-12"><p class="text-secondary mb-0">Belum ada jenis kegiatan.</p></div>
        @endforelse
    </div>
    <form method="post" action="{{ route('master.activity.add') }}" class="d-flex gap-2 flex-wrap" autocomplete="off">@csrf
        <label for="activity-name" class="visually-hidden">Nama jenis kegiatan</label>
        <input id="activity-name" name="name" class="form-control" style="max-width:420px" minlength="3" maxlength="150" placeholder="Nama jenis kegiatan baru" required>
        <button class="btn btn-primary">Tambah jenis kegiatan</button>
    </form>
</section>

<section class="panel p-4 mb-4" aria-labelledby="lokasi-baru-title">
    <div class="section-title"><div><h2 id="lokasi-baru-title">Tambah Lokasi</h2><p>Tarif awal berlaku per hari untuk semua jenis kegiatan yang aktif, dan dapat diubah kapan saja di kartu lokasi.</p></div></div>
    <form method="post" action="{{ route('master.add') }}" class="row g-3" autocomplete="off">@csrf
        <div class="col-md-6"><label for="new-name" class="form-label">Nama lokasi <span class="required">*</span></label><input id="new-name" name="name" class="form-control" minlength="3" maxlength="150" value="{{ old('name') }}" required></div>
        <div class="col-md-6"><label for="new-rate" class="form-label">Tarif per hari (Rp) <span class="required">*</span></label><input id="new-rate" type="number" name="rate" class="form-control" min="0" max="100000000" value="{{ old('rate',0) }}" required></div>
        <div class="col-md-6"><div class="form-check"><input type="hidden" name="managed" value="0"><input class="form-check-input" type="checkbox" id="new-managed" name="managed" value="1" checked><label class="form-check-label" for="new-managed">Dikelola Dispar</label></div></div>
        <div class="col-md-6"><div class="form-check"><input type="hidden" name="dlh" value="0"><input class="form-check-input" type="checkbox" id="new-dlh" name="dlh" value="1"><label class="form-check-label" for="new-dlh">Wajib rekomendasi DLH</label></div></div>
        <div class="col-12"><button class="btn btn-primary">Tambah lokasi</button></div>
    </form>
</section>
@endif

<div class="row g-4">@foreach($locations->reject(fn($l)=>!empty($l['other'])) as $loc)<div class="col-md-6"><section class="panel p-4"><div class="panel-title"><span class="stat-icon"><x-icon name="pin"/></span><span class="status-badge {{ $loc['active']?'status-selesai':'status-ditolak' }}">{{ $loc['active']?'Aktif':'Nonaktif' }}</span></div><h2>{{ $loc['name'] }}</h2><p class="small text-secondary mt-2">{{ $loc['area'] }} · {{ $loc['managed']?'Dikelola Dispar':'Dikelola pihak lain' }}@if($db) · {{ !empty($loc['dlh'])?'Wajib rekomendasi DLH':'Tanpa rekomendasi DLH' }}@endif</p>
<form method="post" action="{{ route('master.update',$loc['id']) }}">@csrf<label class="form-label" for="rate{{ $loc['id'] }}">{{ $db?'Tarif per hari (Rp)':'Tarif contoh per hari (Rp)' }}</label><div class="d-flex gap-2"><input type="number" id="rate{{ $loc['id'] }}" name="rate" class="form-control" min="0" max="100000000" value="{{ $loc['rate'] }}" required><button class="btn btn-outline-primary">Simpan</button></div></form>
@if($db)
<details class="mt-3"><summary>Ubah data lokasi</summary>
    <form method="post" action="{{ route('master.edit',$loc['id']) }}" class="mt-2" autocomplete="off">@csrf
        <label class="form-label" for="name{{ $loc['id'] }}">Nama lokasi</label><input id="name{{ $loc['id'] }}" name="name" class="form-control mb-2" minlength="3" maxlength="150" value="{{ $loc['name'] }}" required>
        <div class="form-check"><input type="hidden" name="managed" value="0"><input class="form-check-input" type="checkbox" id="managed{{ $loc['id'] }}" name="managed" value="1" @checked($loc['managed'])><label class="form-check-label" for="managed{{ $loc['id'] }}">Dikelola Dispar</label></div>
        <div class="form-check"><input type="hidden" name="dlh" value="0"><input class="form-check-input" type="checkbox" id="dlh{{ $loc['id'] }}" name="dlh" value="1" @checked(!empty($loc['dlh']))><label class="form-check-label" for="dlh{{ $loc['id'] }}">Wajib rekomendasi DLH</label></div>
        <div class="form-check mb-2"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" id="active{{ $loc['id'] }}" name="active" value="1" @checked($loc['active'])><label class="form-check-label" for="active{{ $loc['id'] }}">Aktif (tampil di formulir)</label></div>
        <button class="btn btn-outline-primary btn-sm">Simpan perubahan</button>
    </form>
</details>
<details class="mt-2"><summary>Hapus lokasi</summary>
    <p class="small text-secondary mt-2 mb-2">Hanya bisa bila lokasi belum pernah dipakai permohonan. Bila sudah dipakai, gunakan Nonaktifkan di atas.</p>
    <form method="post" action="{{ route('master.remove',$loc['id']) }}">@csrf<button class="btn btn-outline-danger btn-sm">Hapus lokasi ini</button></form>
</details>
@endif
</section></div>@endforeach</div>
@unless($db)
<section class="panel p-4 mt-4"><h2>Ketentuan keringanan dalam demo</h2><p class="text-secondary mt-3">Keringanan 25% dapat diajukan pada lokasi yang dikelola Dispar, termasuk Pasir Padi dan Jeramba Gantung. Lokasi yang memenuhi syarat tidak otomatis memberi potongan kepada setiap pemohon.</p><p class="mb-0 small">Potongan diterapkan hanya pada contoh pengajuan berstatus <strong>disetujui</strong>. Peran pengambil keputusan keringanan belum ditetapkan di demo. Tarif per jenis kegiatan dan ketentuan pembebasan memerlukan integrasi aturan pada backend.</p></section>
@endunless
@endsection
