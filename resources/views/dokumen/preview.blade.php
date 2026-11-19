@extends('layouts.app')
@section('content')
@php
$origin=request('from');
$origins=['serah-terima'=>'Serah Terima','arsip'=>'Arsip Digital','tanda-tangan'=>'Tanda Tangan','retribusi'=>'Retribusi'];
$back=isset($origins[$origin])?route('listing',['kind'=>$origin,'selected'=>$r['id']]):route('detail',$r['id']);
@endphp
<div class="print-actions"><a class="btn btn-outline-primary" href="{{ $back }}">← Kembali ke {{ $origins[$origin]??'Detail Permohonan' }}</a><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}"><x-icon name="back" size="17"/> Detail Permohonan</a>@if(empty($r['manual_letter'])||$type!=='Surat Persetujuan')<button class="btn btn-primary" data-print><x-icon name="print" size="17"/> Cetak / simpan PDF</button>@endif</div>@if($type!=='Surat Persetujuan')
@php($docKey=strtolower($type))
@if(($r['document_sources'][$docKey]??'template')==='upload')<section class="panel p-4 mb-3"><h2>Dokumen manual {{ $type }}</h2><p>{{ $r['uploads'][$docKey]['name'] }}</p><a class="btn btn-primary" href="{{ route('uploaded',[$r['id'],$docKey]) }}" target="_blank" rel="noopener">Buka / cetak berkas unggahan</a><p class="small mt-3">Berkas manual dipilih sebagai sumber dokumen ini.</p></section>@else @include('dokumen.financial') @endif
@else
@if(!empty($r['manual_letter']))<section class="panel p-4"><h2>Surat Persetujuan — tanda tangan basah</h2><p>Dokumen final adalah hasil pindai yang diunggah Petugas Layanan.</p><a class="btn btn-primary" href="{{ route('uploaded',[$r['id'],'surat-manual']) }}" target="_blank" rel="noopener">Buka / cetak PDF bertanda tangan</a></section>@else @include('dokumen.letter') @endif
@endif
@endsection
