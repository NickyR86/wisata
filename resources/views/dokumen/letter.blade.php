@php
$D=\App\Support\DemoData::class;
$letter=$D::letter($r);
$s=$letter['structure'];
$src=$s['source'];
$render=fn($text)=>\App\Support\ApprovalLetter::render((string)$text,$src);
@endphp
<article class="approval-letter approval-reference print-area" data-letter-source="{{ json_encode($src,JSON_UNESCAPED_UNICODE) }}">
@if(($letterPrintMode??'')==='draft')<p class="draft-print-mark">DRAFT · BELUM UNTUK DITANDATANGANI / DISERAHKAN</p>@endif
<header class="letter-head"><span class="letter-seal"><x-icon name="stamp" size="36"/></span><div><strong>PEMERINTAH KOTA PANGKAL PINANG</strong><strong>DINAS PARIWISATA</strong><small>Jalan Rasakunda Kecamatan Girimaya; Pos-el: dispar@pangkalpinangkota.go.id</small><small>Laman: www.wonderful.pangkalpinangkota.go.id; Pangkal Pinang 33143</small></div></header>
<div class="letter-title"><h3>SURAT PERSETUJUAN KEPALA DINAS PARIWISATA<br>KOTA PANGKAL PINANG</h3><p>NOMOR <span data-letter-value="number">{{ old('letter.number',$letter['number']) }}</span></p><p>TENTANG</p><strong data-structured-value="about">{{ $render(old('letter.structure.about',$s['about'])) }}</strong></div>
<div class="letter-basis"><div>Dasar :</div><ol type="a" data-structured-list="legal_basis">@foreach(old('letter.structure.legal_basis',$s['legal_basis']) as $item)<li>{{ $render($item) }}</li>@endforeach<li data-structured-value="request_reference">{{ $render(old('letter.structure.request_reference',$s['request_reference'])) }}</li></ol></div>
<p class="text-center mt-4">MEMBERI PERSETUJUAN :</p>
<p>Kepada :</p><dl class="letter-identities">@foreach(['Nama'=>$src['pic'],'Jabatan'=>$src['jabatan'],'Alamat'=>$src['alamat']] as $label=>$value)<dt>{{ $label }}</dt><dd>: {{ $value }}</dd>@endforeach</dl>
<div class="letter-purpose"><span>Untuk :</span><span data-structured-value="purpose">{{ $render(old('letter.structure.purpose',$s['purpose'])) }}</span></div>
@foreach(['opening','body'] as $key)<p class="letter-body" data-letter-value="{{ $key }}">{{ $letter[$key] }}</p>@endforeach
<p class="mb-0">Dengan persyaratan sebagai berikut :</p><ol data-structured-list="terms">@foreach(old('letter.structure.terms',$s['terms']) as $item)<li>{{ $render($item) }}</li>@endforeach</ol>
<p class="letter-body" data-letter-value="closing">{{ $letter['closing'] }}</p>
<div class="letter-sign"><p>Ditetapkan di Pangkal Pinang<br>pada tanggal <span data-letter-value="date">{{ old('letter.date',$letter['date']) }}</span></p><strong>KEPALA DINAS PARIWISATA<br>KOTA PANGKAL PINANG,</strong><div class="letter-signmark">{{ ($letterPrintMode??'')==='manual'?'':($r['signed']?(($r['signature']['method']??'')==='unggahan_manual'?'TANDA TANGAN BASAH · LIHAT PDF ASLI':'DITANDATANGANI · SIMULASI TTE'):'Menunggu tanda tangan') }}</div><strong>{{ $r['signature']['name']??'Aditya Pratama (pejabat fiktif)' }}</strong>@if($r['signed'])<small>{{ $r['signature']['at']??'' }} · {{ $r['signature']['reference']??'' }}</small>@endif</div>
<div class="letter-copies">Tembusan:<ol data-structured-list="copies">@foreach(old('letter.structure.copies',$s['copies']) as $item)<li>{{ $render($item) }}</li>@endforeach</ol></div>
<footer>DEMO · Bukan dokumen resmi @if(!$r['signed']) · {{ ($letterPrintMode??'')==='manual'?'Versi disetujui untuk tanda tangan basah':'Draft versi' }} {{ $r['version'] }} @endif</footer>
</article>
