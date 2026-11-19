@php($manualEligible=\App\Support\ManualSignature::eligible($r))
@if($manualEligible&&in_array($role,['petugas','kadis']))
<section class="panel p-4 my-4"><h2 class="fs-5">Tanda tangan basah</h2><p>Jika tanda tangan digital tidak dapat digunakan, cetak versi yang disetujui. Petugas Layanan mencatat hasilnya setelah ditandatangani Kadis.</p>
<a class="btn btn-outline-primary mb-3" href="{{ route('letter.print',[$r['id'],'manual']) }}" target="_blank" rel="noopener">Cetak untuk Tanda Tangan Basah</a>
@if($role==='petugas')
<form action="{{ route('letter.manual',$r['id']) }}" method="post" enctype="multipart/form-data">@csrf
<input type="hidden" name="source_token" value="{{ \App\Support\ManualSignature::token($r) }}">
<p class="small text-secondary">Surat {{ \App\Support\DemoData::letter($r)['number'] }} · Versi draft {{ $r['version'] }}</p>
<label for="manual-signer" class="form-label">Penandatangan (Kepala Dinas)</label><select id="manual-signer" name="signer_id" class="form-select mb-3" required>@foreach(\App\Support\ManualSignature::signers() as $signer)<option value="{{ $signer['id'] }}">{{ $signer['name'] }}</option>@endforeach</select>
<label for="manual-date" class="form-label">Tanggal tanda tangan</label><input id="manual-date" class="form-control mb-3" type="date" name="signed_date" max="{{ date('Y-m-d') }}" value="{{ old('signed_date') }}" required>
<label for="manual-reason" class="form-label">Alasan penggunaan tanda tangan basah</label><textarea id="manual-reason" class="form-control mb-3" name="reason" minlength="5" maxlength="1000" required>{{ old('reason') }}</textarea>
<label for="manual-file" class="form-label">PDF surat bertanda tangan (maksimal 10 MB)</label><input id="manual-file" type="file" class="form-control mb-3" name="signed_file" accept="application/pdf,.pdf" required>
<label class="d-block mb-3"><input type="checkbox" name="confirmed" value="1" required> Saya telah mencocokkan seluruh halaman, nomor, versi surat, dan tanda tangan pada hasil pindai dengan dokumen fisik.</label>
<button class="btn btn-primary">Simpan Hasil Tanda Tangan &amp; Teruskan ke Serah Terima</button></form>
@else<p class="small text-secondary">Setelah surat ditandatangani, Petugas Layanan mengunggah hasil pindai melalui Detail Permohonan.</p>@endif</section>
@endif
@if(!empty($r['manual_letter']))<section class="panel p-4 my-4"><h2 class="fs-5">Surat bertanda tangan basah</h2><p>Penandatangan: {{ $r['signature']['name'] }} · {{ substr($r['signature']['at'],0,10) }}</p><a class="btn btn-outline-primary" href="{{ route('uploaded',[$r['id'],'surat-manual']) }}" target="_blank" rel="noopener">Buka / cetak PDF bertanda tangan</a></section>@endif
