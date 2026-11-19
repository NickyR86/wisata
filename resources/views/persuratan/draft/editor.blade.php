@php
$letter=\App\Support\DemoData::letter($r);
$structure=old('letter.structure',$letter['structure']);
$source=$letter['structure']['source'];
@endphp
<p class="text-secondary">Versi {{ $r['version'] }} · Petugas Layanan menyusun surat; Kabid meninjau dan memberi catatan.</p>
<div class="draft-layout" data-approval-editor><div>
<section class="panel p-3 mb-4"><h3 class="fs-6">Identitas surat</h3>
@foreach(['number'=>'Nomor surat demo','date'=>'Tanggal penetapan'] as $key=>$label)
<label class="form-label" for="letter-{{ $key }}">{{ $label }}</label><input id="letter-{{ $key }}" class="form-control mb-3" name="letter[{{ $key }}]" type="{{ $key==='date'?'date':'text' }}" value="{{ old('letter.'.$key,$letter[$key]) }}" maxlength="150" required data-letter-input="{{ $key }}">
@endforeach</section>
<section class="panel p-3 mb-4"><h3 class="fs-6">Data dari permohonan</h3>
<dl class="detail-grid">@foreach($source as $key=>$value)<div><dt>{{ ucfirst(str_replace('_',' ',$key)) }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl>
<a href="{{ route('detail',$r['id']) }}" target="_blank" rel="noopener">Tinjau permohonan</a>
<label class="d-block mt-3"><input type="checkbox" name="sync_letter_source" value="1"> Ambil data permohonan terbaru saat menyimpan</label>
<p class="small text-secondary">Sumber tersimpan bersama versi draft. Pembaruan sumber tidak mengubah redaksi yang disunting. Isian “Belum diisi” perlu dilengkapi pada permohonan.</p></section>
@foreach(['about'=>'Tentang','purpose'=>'Untuk / kegiatan yang disetujui','request_reference'=>'Rujukan surat pemohon'] as $key=>$label)
<label class="form-label" for="structured-{{ $key }}">{{ $label }}</label><textarea id="structured-{{ $key }}" name="letter[structure][{{ $key }}]" rows="3" maxlength="2000" required class="form-control mb-3" data-structured-input="{{ $key }}">{{ $structure[$key] }}</textarea>
@endforeach
<p class="small text-secondary">Variabel {kegiatan}, {lokasi}, {tanggal}, {durasi}, {jam_mulai}, {jam_selesai}, {pemohon}, {nomor_permohonan}, dan {tanggal_permohonan} mengikuti sumber di atas.</p>
@foreach(['legal_basis'=>'Dasar hukum','terms'=>'Ketentuan pelaksanaan','copies'=>'Tembusan'] as $key=>$label)
<section class="panel p-3 mb-4" data-letter-list="{{ $key }}"><h3 class="fs-6">{{ $label }}</h3>
@if($key==='legal_basis')<p class="small text-secondary">Daftar awal mengikuti contoh surat Reuni IKAK. Periksa kesesuaian acuan sebelum mengirim untuk review.</p>@endif
<div data-list-items>@foreach($structure[$key]??[] as $item)<div class="letter-edit-row mb-3"><textarea class="form-control" rows="3" maxlength="3000" aria-label="{{ $label }} butir {{ $loop->iteration }}" name="letter[structure][{{ $key }}][]">{{ $item }}</textarea><div class="d-flex gap-2 mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" data-item-up aria-label="Naikkan butir">↑</button><button type="button" class="btn btn-sm btn-outline-secondary" data-item-down aria-label="Turunkan butir">↓</button><button type="button" class="btn btn-sm btn-outline-danger" data-item-remove>Hapus</button></div></div>@endforeach</div>
<button type="button" class="btn btn-sm btn-outline-primary" data-item-add>Tambah butir</button></section>
@endforeach
<details class="mb-4"><summary>Narasi tambahan / isi draft sebelumnya</summary><p class="small text-secondary">Dipertahankan untuk draft lama. Kosongkan bagian yang tidak diperlukan pada format surat baru.</p>
@foreach(['subject'=>'Perihal internal','attachment'=>'Lampiran internal','opening'=>'Pembuka tambahan','body'=>'Isi tambahan','conditions'=>'Ketentuan lama (arsip)','closing'=>'Penutup tambahan'] as $key=>$label)
<label class="form-label" for="letter-{{ $key }}">{{ $label }}</label><textarea id="letter-{{ $key }}" class="form-control mb-3" name="letter[{{ $key }}]" rows="3" maxlength="5000" data-letter-input="{{ $key }}">{{ old('letter.'.$key,$letter[$key]) }}</textarea>
@endforeach</details>
<p class="small text-secondary">Cetak menggunakan versi tersimpan; simpan perubahan terlebih dahulu.</p><a class="btn btn-outline-secondary mb-3" href="{{ route('letter.print',[$r['id'],'draft']) }}" target="_blank" rel="noopener">Cetak Draft</a>
<div class="action-buttons"><button class="btn btn-outline-primary" name="action" value="save">Simpan draft</button><button class="btn btn-primary" name="action" value="send">Kirim ke Kabid</button></div>
</div><div><h3 class="fs-6">Pratinjau surat persetujuan</h3><p class="small text-secondary">Daftar huruf dan angka mengikuti susunan butir. Simpan sebelum meninggalkan halaman.</p>@include('dokumen.letter')</div></div>
