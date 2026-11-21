@extends('layouts.app')
@section('content')
@php($loc=$locations[$r['location_id']])
<div class="print-actions"><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}"><x-icon name="back" size="17"/> Detail Permohonan</a><button class="btn btn-primary" data-print><x-icon name="print" size="17"/> Cetak / simpan PDF</button></div>
<article class="paper checklist-sheet" aria-labelledby="checklist-title">
    <h1 id="checklist-title" class="checklist-title">LEMBAR PERIKSA KELENGKAPAN PERSYARATAN PERMOHONAN</h1>
    <dl class="checklist-meta">
        <div><dt>Nomor tiket</dt><dd>{{ $r['tracking'] }}</dd></div>
        <div><dt>Pemohon</dt><dd>{{ $r['name'] }} ({{ $r['pic'] }})</dd></div>
        <div><dt>Kegiatan</dt><dd>{{ $r['activity'] }}</dd></div>
        <div><dt>Lokasi</dt><dd>{{ $loc['name'] }}</dd></div>
        <div><dt>Tanggal pelaksanaan</dt><dd>{{ \App\Support\DemoData::date($r['date']) }} · {{ $r['days'] }} hari</dd></div>
        @if(\App\Support\DemoData::isFree($r))<div><dt>Retribusi</dt><dd>Bebas biaya (disetujui)</dd></div>@endif
    </dl>
    <table class="checklist-table">
        <thead><tr><th>Persyaratan Permohonan</th><th>Keterangan</th></tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr><td><span class="checklist-box" aria-hidden="true">{{ $row['state']==='ada'?'☑':($row['state']==='belum'?'☐':'−') }}</span> {{ $row['label'] }}</td><td class="checklist-note">{{ $row['note'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="checklist-signs">
        <div><p>Petugas Pelayanan Perizinan</p><div class="checklist-sign-space"></div><p class="checklist-line">(................................)</p></div>
        <div><p>Kepala Bidang Destinasi Pariwisata dan Industri Pariwisata</p><div class="checklist-sign-space"></div><p class="checklist-line">(................................)</p></div>
    </div>
    <p class="checklist-foot">Dicetak dari sistem pada {{ date('d-m-Y H:i') }}. Tanda ☑ = ada, ☐ = wajib tetapi belum ada, − = tidak berlaku atau tidak diunggah.</p>
</article>
@endsection
