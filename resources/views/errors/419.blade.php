@php($returnTo=\App\Support\FormRecovery::origin(request()))
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pengiriman belum diproses · SIPTR Dispar</title>
<link rel="stylesheet" href="{{ asset('css/siptr-reference.css') }}">
<link rel="stylesheet" href="{{ asset('css/access-recovery.css') }}">
</head><body class="recovery-page">
<header class="recovery-brand"><span class="recovery-mark"><x-icon name="waves" size="26"/></span><div><strong>SIPTR Dispar</strong><small>Kota Pangkalpinang</small></div></header>
<main class="recovery-card" aria-labelledby="recovery-title">
<span class="recovery-symbol"><x-icon name="clock" size="30"/></span>
<p class="recovery-eyebrow">AKSES LAYANAN</p>
<h1 id="recovery-title">Pengiriman belum diproses</h1>
<p class="recovery-description">Halaman ini perlu menyelaraskan kembali akses Anda. Data dari pengiriman ini belum disimpan.</p>
<div class="recovery-notice"><x-icon name="file" size="22"/><div><strong>{{ $returnTo?'Lanjutkan dari formulir sebelumnya':'Silakan buka kembali halaman masuk' }}</strong><p>{{ $returnTo?'Kami akan memeriksa akses sebelum membuka formulir asal. Jika perlu masuk kembali, Anda akan diarahkan ke formulir tersebut setelahnya.':'Halaman masuk yang sebelumnya terbuka sudah tidak berlaku. Buka halaman masuk untuk mendapatkan akses yang baru.' }}</p></div></div>
<p class="recovery-status" role="status" data-recovery-status>{{ $returnTo?'Akses akan diperiksa saat Anda melanjutkan.':'' }}</p>
<div class="recovery-actions"><a class="btn btn-primary" data-recovery-action href="{{ $returnTo?route('access.recover',['return_to'=>$returnTo]):route('login') }}">{{ $returnTo?'Periksa akses & lanjutkan':'Kembali ke halaman masuk' }} <x-icon name="arrow" size="18"/></a></div>
@if($returnTo)<p class="recovery-footnote">Jika tersedia, gunakan <strong>Pulihkan isian yang belum disimpan</strong> di formulir. Berkas yang belum terkirim perlu dipilih kembali setelah halaman dimuat ulang.</p>@endif
</main><footer class="recovery-footer">Sistem Pelayanan Dinas Pariwisata</footer>
@if($returnTo)<script src="{{ asset('js/access-recovery.js') }}"></script>@endif
</body></html>
