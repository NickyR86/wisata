<article class="financial-paper print-area skrd-reference finance-skrd" aria-label="Pratinjau SKRD" data-skrd-percent="{{ $cfg['percent'] }}">
<div class="paper-note">DEMO — BUKAN DOKUMEN RESMI</div>
<div class="skrd-frame">
<header class="skrd-head"><div><strong>PEMERINTAH KOTA PANGKALPINANG<br>DINAS PARIWISATA</strong><small>Jalan Rasa Kunda, Kel. Sriwijaya</small></div><h3>SURAT KETETAPAN RETRIBUSI DAERAH<br>(SKRD)</h3></header>
<div class="skrd-top"><div class="skrd-data"><dl class="skrd-period"><dt>MASA</dt><dd>: <span data-period-month>{{ \Carbon\Carbon::parse($cfg['period'].'-01')->locale('id')->translatedFormat('F') }}</span></dd><dt>TAHUN</dt><dd>: <span data-period-year>{{ substr($cfg['period'],0,4) }}</span></dd></dl><dl class="skrd-identities"><dt>NAMA</dt><dd>: <span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd>: <span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NOMOR POKOK WAJIB RETRIBUSI (NPWR)</dt><dd>: <span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd><dt>TANGGAL JATUH TEMPO</dt><dd>: <span data-template-value="due">{{ $cfg['due'] }}</span></dd></dl></div><aside>NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></aside></div>
<table class="skrd-grid"><colgroup><col style="width:6%">@for($j=0;$j<8;$j++)<col style="width:4.25%">@endfor<col style="width:35%"><col style="width:25%"></colgroup><thead><tr><th>NO.</th><th colspan="8">KODE REKENING</th><th>URAIAN RETRIBUSI</th><th><span class="skrd-money">JUMLAH <span>(Rp)</span></span></th></tr></thead><tbody data-finance-rows data-skrd-rows>
@foreach($cfg['rows'] as $row)
@for($line=0;$line<5;$line++)
<tr><td>{{ $loop->index*5+$line+1 }}</td>
@for($j=0;$j<8;$j++)<td>{{ $line===0?(explode('.',$row['code'])[$j]??''):'' }}</td>@endfor
@if($line===0)<td class="skrd-account"><u>{{ $row['label'] }}</u></td><td></td>
@elseif($line===1)<td rowspan="3" class="skrd-description"><span data-template-value="description">{{ $cfg['description'] }}</span></td><td rowspan="4"><span class="skrd-money">Rp <span>{{ number_format($row['amount'],0,',','.') }}</span></span></td>
@elseif($line===4)<td>PEMBAYARAN SEWA UNTUK TANGGAL {{ $D::date($cfg['rental_date']??$cfg['source']['date']) }}</td>
@endif
</tr>
@endfor
@endforeach
</tbody><tbody>
<tr class="skrd-assessment"><td rowspan="{{ $cfg['percent']>0?6:4 }}"></td><td colspan="8" rowspan="{{ $cfg['percent']>0?6:4 }}"></td><td>Jumlah Ketetapan Pokok Retribusi</td><td><span class="skrd-money">Rp <span data-skrd-base>{{ number_format($cfg['base'],0,',','.') }}</span></span></td></tr>
@if($cfg['percent']>0)<tr><td>Keringanan disetujui ({{ $cfg['percent'] }}%)</td><td data-skrd-discount>− {{ $D::rupiah($cfg['base']-$cfg['total']) }}</td></tr><tr><td>Pokok setelah keringanan</td><td data-skrd-net>{{ $D::rupiah($cfg['total']) }}</td></tr>@endif
<tr class="skrd-penalty"><td>Jumlah Sanksi <span class="skrd-sanksi">a. &nbsp; Denda</span></td><td class="text-end">0%</td></tr><tr class="skrd-increase"><td><span class="skrd-sanksi">b. &nbsp; Kenaikan</span></td><td>Rp &nbsp; 0</td></tr><tr class="skrd-total"><td>Jumlah Keseluruhan</td><td><span class="skrd-money">Rp <span data-skrd-total>{{ number_format($cfg['total'],0,',','.') }}</span></span></td></tr>
</tbody></table>
<section class="skrd-attention"><p>Dengan huruf &nbsp; : &nbsp; <span data-skrd-words>{{ strtoupper($cfg['words']) }}</span></p><u>PERHATIAN</u> &nbsp; :<div class="finance-preserve"><ol class="skrd-attention-list" data-attention-list>
@foreach(preg_split('/\R/u', $cfg['attention']) as $point)
@if(trim($point)!=='')<li>{{ preg_replace('/^\s*\d+[.)]\s*/u', '', trim($point)) }}</li>
@endif
@endforeach
</ol></div></section>
<section class="skrd-sign"><div>PANGKALPINANG, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>A.N. KEPALA DINAS PARIWISATA<br>KOTA PANGKALPINANG,<br><strong><span data-template-value="signer_title">{{ $cfg['signer_title'] }}</span></strong><div class="skrd-sign-space">RUANG TANDA TANGAN DEMO</div><u><span data-template-value="signer_name">{{ $cfg['signer_name'] }}</span></u><br><span data-template-value="signer_rank">{{ $cfg['signer_rank'] }}</span><br>NIP. <span data-template-value="signer_nip">{{ $cfg['signer_nip'] }}</span></div></section>
<div class="skrd-cut">──────────── POTONG DI SINI ────────────</div>
<section class="skrd-receipt"><div class="skrd-stub-number">NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></div><strong>TANDA TERIMA</strong><dl class="skrd-stub-fields"><dt>NAMA</dt><dd>: <span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd>: <span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NPWR</dt><dd>: <span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd></dl><div class="skrd-stub-sign">Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>Yang menerima,<div class="skrd-receiver-space"></div><span data-template-value="payer">{{ $cfg['payer'] }}</span></div></section>
</div></article>
