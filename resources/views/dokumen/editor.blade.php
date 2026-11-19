@extends('layouts.app')
@section('content')
@php
$cfg=\App\Support\FinanceDocument::data($r,$docKey,true);
$fields=['number'=>'Nomor dokumen','payer'=>'Nama penyetor / wajib retribusi','issued_date'=>$docKey==='sts'?'Tanggal setoran':'Tanggal dokumen / penerimaan'];
if($docKey==='sts') unset($fields['payer']);
$fields+= $docKey==='skrd'?['rental_date'=>'Tanggal pelaksanaan / sewa','period'=>'Masa / tahun retribusi','address'=>'Alamat','npwr'=>'NPWR','due'=>'Keterangan jatuh tempo']:['bank'=>'Nama bank','account'=>'Nomor rekening bank'];
@endphp
<a class="back-link" href="{{ route('listing',['kind'=>'retribusi','selected'=>$r['id']]) }}">← Kembali ke Retribusi</a>
<div class="page-heading"><div><h1>Edit Template {{ strtoupper($docKey) }}</h1><p>{{ $r['name'] }} · {{ $r['tracking'] }}</p></div></div>
<form method="post" class="row g-4" data-template-editor>@csrf
@if(isset($r['_v']))<input type="hidden" name="record_version" value="{{ $r['_v'] }}">@endif

@if($docKey==='skrd')
@include('dokumen.skrd-source')
@endif
<div class="col-xl-5"><section class="panel p-4"><h2 class="mb-3">Identitas dokumen</h2><p class="small text-secondary">Perubahan disimpan khusus untuk dokumen ini. Tanggal STS dan STBP dapat berbeda. Nomor dan identitas penandatangan di bawah adalah data demo.</p>
@foreach($fields as $key=>$label)
<label class="form-label" for="{{ $key }}">{{ $label }}</label><input class="form-control mb-3" id="{{ $key }}" name="{{ $key }}" type="{{ $key==='period'?'month':(in_array($key,['issued_date','rental_date'])?'date':'text') }}" value="{{ $docKey==='skrd'&&$key==='payer'?$cfg[$key]:old($key,$cfg[$key]) }}" @if($docKey==='skrd'&&$key==='payer') readonly @endif required data-template-input>
@endforeach
@if($docKey==='stbp')
<label for="method" class="form-label">Cara penerimaan</label><select id="method" name="method" class="form-select mb-3" data-template-input>@foreach(['Tunai','Non Tunai'] as $method)<option @selected(old('method',$cfg['method'])===$method)>{{ $method }}</option>@endforeach</select>
@endif
@if($docKey!=='sts')
<label for="description" class="form-label">{{ $docKey==='stbp'?'Sebagai pembayaran (kegiatan)':'Uraian kegiatan / tempat yang dipakai' }}</label><textarea id="description" name="description" class="form-control mb-2" rows="4" maxlength="3000" required data-template-input>{{ old('description',$cfg['description']) }}</textarea>
@if($docKey==='skrd')
<label class="small d-block mb-2"><input type="checkbox" name="reset_description" value="1"> Ganti uraian dengan uraian otomatis dari kegiatan dan lokasi terbaru saat menyimpan.</label>
@endif
<p class="small text-secondary">Uraian panjang otomatis menambah tinggi sel pada dokumen. Diisi awal dari permohonan; setelah disimpan menjadi uraian khusus dokumen ini. Tanggal sewa diisi awal dari permohonan dan dapat disesuaikan khusus dokumen ini. Masa retribusi dan tanggal dokumen tetap terpisah.</p>
@else
<input type="hidden" name="description" value="{{ $cfg['description'] }}">
<p class="small text-secondary">Label “Penerimaan Tanggal” pada cetakan mengikuti contoh yang diberikan, yang memuat nominal penerimaan.</p>
@endif
<hr><h2 class="mb-3">{{ $docKey==='skrd'?'Rekening dan nominal ketetapan':'Rincian penerimaan / setoran' }}</h2>
@if($docKey==='skrd')
<p class="small">Nominal mengikuti Penetapan Retribusi dan tidak diubah melalui editor surat. Kode rekening dan uraian tetap dapat diedit.</p>
@else
<p class="small">Kode rekening di sini adalah kode pendapatan, berbeda dari nomor rekening bank. Total rincian harus <strong>{{ \App\Support\DemoData::rupiah($docKey==='skrd'?$cfg['base']:$cfg['total']) }}</strong>{{ $docKey==='skrd'?' sebelum keringanan.':'.' }} Nominal penetapan diubah melalui Penetapan Retribusi sebelum penerbitan.</p>
@endif
<div data-finance-input-rows>
@foreach($docKey==='skrd'?$cfg['rows']:old('rows',$cfg['rows']) as $row)
<fieldset class="finance-row-editor border rounded p-3 mb-3"><legend class="float-none w-auto fs-6">{{ $docKey==='skrd' ? 'Rekening retribusi' : 'Rincian '.$loop->iteration }}</legend>
@foreach(['code'=>'Kode rekening pendapatan','label'=>'Jenis / uraian retribusi','amount'=>'Nominal (rupiah)'] as $key=>$label)
<label class="form-label" for="row-{{ $loop->parent->index }}-{{ $key }}">{{ $label }}</label><input id="row-{{ $loop->parent->index }}-{{ $key }}" name="rows[{{ $loop->parent->index }}][{{ $key }}]" data-row-field="{{ $key }}" type="{{ $key==='amount'?'number':'text' }}" @if($key==='amount') min="0" max="999999999999" step="0.01" @else maxlength="{{ $key==='code'?100:300 }}" @endif value="{{ $row[$key] }}" class="form-control mb-2" @if($docKey==='skrd'&&$key==='amount') readonly @endif required>
@endforeach
<button type="button" class="btn btn-sm btn-outline-danger" data-remove-finance-row @if($docKey==='skrd') hidden @endif>Hapus rincian</button></fieldset>
@endforeach
</div><button type="button" class="btn btn-outline-secondary mb-2" data-add-finance-row @if($docKey==='skrd') hidden @endif>+ Tambah rincian</button><p data-finance-row-total class="small" aria-live="polite"></p>
@if($docKey==='skrd')
<p class="small text-secondary">Keringanan {{ $cfg['percent'] }}% mengikuti keputusan yang tersimpan. Denda dan kenaikan ditampilkan Rp 0 pada skenario demo; perhitungan sanksi belum diterapkan.</p>
<label for="attention" class="form-label">Catatan perhatian (satu poin per baris; nomor otomatis)</label><textarea class="form-control mb-3" name="attention" id="attention" rows="4" maxlength="2000" data-template-input>{{ old('attention',$cfg['attention']) }}</textarea>
@endif
<hr><h2 class="mb-3">Penandatangan</h2>
@foreach(['signer_title'=>'Jabatan penandatangan','signer_name'=>'Nama penandatangan (demo)','signer_nip'=>'NIP (demo)']+($docKey==='skrd'?['signer_rank'=>'Pangkat / golongan (demo)']:[])+($docKey==='sts'?['authority_name'=>'Nama Pengguna Anggaran (demo)','authority_nip'=>'NIP Pengguna Anggaran (demo)']:[]) as $key=>$label)
<label class="form-label" for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" name="{{ $key }}" value="{{ old($key,$cfg[$key]) }}" class="form-control mb-3" required data-template-input>
@endforeach
<button class="btn btn-primary">{{ $docKey==='skrd'&&isset($r['skrd_snapshot'])?'Simpan Revisi SKRD':'Simpan Template' }}</button></section></div>
<div class="col-xl-7"><div class="d-flex justify-content-between mb-3"><h2>Pratinjau Langsung</h2><span class="small text-secondary">Isi dan rincian mengikuti editor</span></div><div class="financial-preview">@include('dokumen.financial',['editingFinance'=>true])</div></div></form>
@endsection
