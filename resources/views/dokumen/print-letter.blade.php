@extends('layouts.app')
@section('content')
<div class="print-actions"><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}">Kembali ke Detail Permohonan</a><button class="btn btn-primary" data-print>Cetak / simpan PDF</button></div>
<div class="alert alert-info">{{ $mode==='draft'?'Cetakan memakai draft yang sudah disimpan. Simpan perubahan di editor sebelum mencetak.':'Cetakan versi yang telah disetujui untuk tanda tangan basah Kadis. Mencetak tidak mengubah status permohonan.' }} Versi {{ $r['version'] }}.</div>
@php($letterPrintMode=$mode)
@include('dokumen.letter')
@endsection
