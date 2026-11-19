@php
$source=\App\Support\FinanceDocument::source($r);
$changes=\App\Support\FinanceDocument::sourceChanges($r);
$labels=['name'=>'Pemohon','pic'=>'Penanggung jawab','location'=>'Lokasi','activity'=>'Kegiatan','date'=>'Tanggal kegiatan','days'=>'Durasi (hari)','rate'=>'Tarif master','managed'=>'Dikelola Dispar','discount'=>'Keputusan keringanan'];
@endphp
<input type="hidden" name="source_token" value="{{ hash('sha256',json_encode($source)) }}">
<div class="col-12"><section class="panel p-4">
<h2>Data sumber SKRD</h2>
<p class="small">Identitas, lokasi, tanggal, dan durasi berasal dari permohonan. Nomor dokumen, masa retribusi, NPWR, alamat, serta uraian tambahan tetap merupakan isian petugas. Periksa uraian bila data sumber berubah.</p>
<dl class="detail-grid">@foreach(array_slice($labels,0,6,true) as $key=>$label)<div><dt>{{ $label }}</dt><dd>{{ $source[$key] }}</dd></div>@endforeach</dl>
@if($changes)
<div class="alert alert-warning"><strong>Data sumber berubah sejak penyimpanan sebelumnya.</strong><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Field</th><th>Sebelumnya</th><th>Sekarang</th></tr></thead><tbody>@foreach($changes as $key=>$change)<tr><td>{{ $labels[$key] }}</td><td>{{ is_bool($change['before'])?($change['before']?'Ya':'Tidak'):$change['before'] }}</td><td>{{ is_bool($change['after'])?($change['after']?'Ya':'Tidak'):$change['after'] }}</td></tr>@endforeach</tbody></table></div><label><input type="checkbox" name="source_reviewed" value="1" required> Saya sudah meninjau sumber terbaru, uraian, dan rincian; gunakan sumber terbaru saat menyimpan.</label></div>
@elseif(!isset($r['skrd_source']))
<p class="small text-secondary">Belum ada catatan sumber sebelumnya. Periksa data dan uraian lama pada penyimpanan pertama.</p>
@endif
@if(isset($r['skrd_snapshot']))
<p class="small">SKRD terbit tetap memakai salinan tersimpan. Form ini membuat versi revisi; nominal hanya dapat diubah sebelum pembayaran. Perubahan belum berlaku pada cetakan sebelum disimpan.</p>
<label for="revision_reason" class="form-label">Alasan revisi SKRD</label><textarea id="revision_reason" name="revision_reason" class="form-control" minlength="5" maxlength="500" required>{{ old('revision_reason') }}</textarea>
<details class="mt-3"><summary>Riwayat versi SKRD ({{ count($r['skrd_versions']) }})</summary><ul>@foreach($r['skrd_versions'] as $version)<li><a href="{{ route('document',[$r['id'],'skrd','version'=>$loop->iteration,'from'=>'retribusi']) }}" target="_blank" rel="noopener">Versi {{ $loop->iteration }}</a> · {{ $version['at'] }} · {{ $version['reason'] }}</li>@endforeach</ul></details>
@endif
</section></div>
