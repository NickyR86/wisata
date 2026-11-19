@php
$D=\App\Support\DemoData::class;
$cfg=\App\Support\FinanceDocument::data($r,$docKey,$editingFinance??false);
$loc=$locations[$r['location_id']];
@endphp
@if($docKey==='skrd')
@include('dokumen.skrd')
@else
<article class="financial-paper print-area finance-{{ $docKey }}" aria-label="Pratinjau {{ strtoupper($docKey) }}">
<div class="paper-note">DEMO — BUKAN DOKUMEN RESMI {{ !in_array(strtoupper($docKey),$r['documents'])?' · BELUM TERBIT':'' }}</div>
@if($docKey==='skrd')
<header class="financial-heading"><div><strong>PEMERINTAH KOTA PANGKALPINANG<br>DINAS PARIWISATA</strong><small>Pangkalpinang · Dokumen demonstrasi</small></div><div><h3>SURAT KETETAPAN RETRIBUSI DAERAH<br>(SKRD)</h3></div></header>
@else
<header class="finance-receipt-heading"><strong>PEMERINTAH KOTA PANGKAL PINANG</strong><h3>{{ $docKey==='sts'?'SURAT TANDA SETORAN':'SURAT TANDA BUKTI PENERIMAAN' }}</h3><div>Nomor: <span data-template-value="number">{{ $cfg['number'] }}</span></div></header>
@endif
<section class="financial-body">
@if($docKey==='skrd')
<div class="skrd-identity"><div><p class="text-end">MASA / TAHUN: <span data-template-value="period">{{ \Carbon\Carbon::parse($cfg['period'].'-01')->locale('id')->translatedFormat('F Y') }}</span></p><dl class="receipt-fields"><dt>NAMA</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd><span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NOMOR POKOK WAJIB RETRIBUSI (NPWR)</dt><dd><span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd><dt>TANGGAL JATUH TEMPO</dt><dd><span data-template-value="due">{{ $cfg['due'] }}</span></dd></dl></div><aside><strong>NO. URUT</strong><br><span data-template-value="number">{{ $cfg['number'] }}</span></aside></div>
@elseif($docKey==='stbp')
<p>Telah diterima uang secara <span data-template-value="method">{{ $cfg['method'] }}</span>:</p>
<dl class="receipt-fields"><dt>a. Dari</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>b. Tanggal</dt><dd><span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span></dd><dt>c. Di Bank</dt><dd><span data-template-value="bank">{{ $cfg['bank'] }}</span></dd><dt>d. Nomor Rekening</dt><dd><span data-template-value="account">{{ $cfg['account'] }}</span></dd><dt>e. Sebesar</dt><dd>{{ $D::rupiah($cfg['total']) }}</dd><dt>f. Terbilang</dt><dd>{{ ucfirst($cfg['words']) }}</dd><dt>g. Sebagai Pembayaran</dt><dd><span data-template-value="description">{{ $cfg['description'] }}</span></dd></dl>
<p>Dengan rincian penerimaan sebagai berikut:</p>
@else
<dl class="receipt-fields"><dt>1. Nomor STS</dt><dd><span data-template-value="number">{{ $cfg['number'] }}</span></dd><dt>2. Tanggal</dt><dd><span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span></dd><dt>3. Bank</dt><dd><span data-template-value="bank">{{ $cfg['bank'] }}</span></dd><dt>4. Nomor Rekening</dt><dd><span data-template-value="account">{{ $cfg['account'] }}</span></dd><dt>5. Penerimaan Tanggal</dt><dd>{{ $D::rupiah($cfg['total']) }}</dd></dl>
<p>Harap diterima uang sebesar {{ $D::rupiah($cfg['total']) }} (terbilang: {{ $cfg['words'] }})</p>
@endif
<table class="receipt-table"><thead><tr><th>No.</th><th>Kode Rekening</th><th>{{ $docKey==='skrd'?'Uraian Retribusi':'Uraian' }}</th><th>Jumlah (Rp)</th></tr></thead><tbody data-finance-rows>
@foreach($cfg['rows'] as $row)
<tr><td>{{ $loop->iteration }}</td><td>@if($docKey==='skrd')<span class="account-code-cells">@foreach(explode('.',$row['code']) as $part)<span>{{ $part }}</span>@endforeach</span>@else{{ $row['code'] }}@endif</td><td>{{ $row['label'] }}</td><td>{{ $D::rupiah($row['amount']) }}</td></tr>
@endforeach
</tbody><tbody>
@if($docKey==='skrd')
<tr><td colspan="2"></td><td colspan="2"><span data-template-value="description">{{ $cfg['description'] }}</span><br>Tempat: {{ $loc['name'] }}<br>PEMBAYARAN SEWA UNTUK TANGGAL {{ $D::date($r['date']) }} · {{ $r['days'] }} hari</td></tr>
<tr><td colspan="2" rowspan="5"></td><td>Subtotal retribusi</td><td>{{ $D::rupiah($cfg['base']) }}</td></tr>
<tr><td>Keringanan disetujui ({{ $cfg['percent'] }}%)</td><td>− {{ $D::rupiah($cfg['base']-$cfg['total']) }}</td></tr>
<tr><td>Jumlah Ketetapan Pokok Retribusi</td><td>{{ $D::rupiah($cfg['total']) }}</td></tr>
<tr><td>Jumlah Sanksi: a. Denda</td><td>Rp 0</td></tr><tr><td>b. Kenaikan</td><td>Rp 0</td></tr>
<tr><th colspan="3">Jumlah Keseluruhan</th><th>{{ $D::rupiah($cfg['total']) }}</th></tr>
@else
<tr><th colspan="3" class="text-end">JUMLAH</th><th>{{ $D::rupiah($cfg['total']) }}</th></tr>
@endif
</tbody></table>
@if($docKey==='skrd')
<p class="mt-2">Dengan huruf: <strong>{{ ucfirst($cfg['words']) }}</strong></p><strong><u>PERHATIAN:</u></strong><div class="finance-preserve"><span data-template-value="attention">{{ $cfg['attention'] }}</span></div>
@endif
@if(!$r['bill'])<p class="small mt-2">Nominal masih estimasi; belum menjadi penetapan.</p>@endif
<div class="receipt-signatures">
@if($docKey==='sts')
<div>Mengetahui,<br><strong>PENGGUNA ANGGARAN</strong><p class="signature-demo">RUANG TANDA TANGAN DEMO</p><u><span data-template-value="authority_name">{{ $cfg['authority_name'] }}</span></u><br>NIP <span data-template-value="authority_nip">{{ $cfg['authority_nip'] }}</span></div>
@endif
<div>
@if($docKey==='skrd')Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>a.n. KEPALA DINAS PARIWISATA<br>KOTA PANGKALPINANG,<br>
@elseif($docKey==='sts')Disiapkan oleh,<br>
@endif
<strong><span data-template-value="signer_title">{{ $cfg['signer_title'] }}</span></strong><br>@if($docKey==='stbp')DINAS PARIWISATA
@endif
<p class="signature-demo">RUANG TANDA TANGAN DEMO</p><u><span data-template-value="signer_name">{{ $cfg['signer_name'] }}</span></u><br>NIP <span data-template-value="signer_nip">{{ $cfg['signer_nip'] }}</span></div></div>
@if($docKey==='skrd')
<div class="receipt-stub"><div class="cut-line">POTONG DI SINI</div><div class="d-flex justify-content-between"><strong>TANDA TERIMA</strong><div>NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></div></div><dl class="receipt-fields"><dt>NAMA</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd><span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NPWR</dt><dd><span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd></dl><p class="text-end">Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>Yang menerima,<br><br><br><span data-template-value="payer">{{ $cfg['payer'] }}</span></p></div>
@endif
</section></article>

@endif
