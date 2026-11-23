<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title>{{ $title }} · SIPTR Dispar</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link href="{{ asset('css/siptr-reference.css') }}" rel="stylesheet"><link href="{{ asset('css/siptr-blade.css') }}" rel="stylesheet"><link href="{{ asset('css/ui-comfort.css') }}" rel="stylesheet"></head>
<body data-access-role="{{ $role }}" data-access-identity="{{ config('siap.database_accounts')?'account:'.session('account_id'):'demo:'.$role }}" data-save-success="{{ session()->has('success')?'1':'0' }}"><a class="skip-link" href="#main">Lewati ke konten utama</a>
@php
$roles=['sekretariat'=>'Sekretariat','kabid'=>'Kepala Bidang','petugas'=>'Petugas Layanan Bidang','sekretaris'=>'Sekretaris','bendahara'=>'Bendahara Penerimaan','kadis'=>'Kepala Dinas','admin'=>'Admin'];
$short=['sekretariat'=>'Sekretariat','kabid'=>'Kabid','petugas'=>'Petugas Layanan','sekretaris'=>'Sekretaris','bendahara'=>'Bendahara','kadis'=>'Kadis','admin'=>'Admin'];
$nav=[['dashboard','Dashboard','bar-chart-3',route('dashboard')],['antrean','Antrean Permohonan','layout-list',route('listing','antrean')]];
if($role==='sekretariat') $nav[]=['baru','Permohonan Baru','file-plus-2',route('permohonan.create')];
if($role==='petugas') $nav[]=['draft','Editor Draft Surat','pen-line',route('listing','draft')];
if($role==='sekretaris') $nav[]=['verifikasi','Verifikasi Draft','badge-check',route('listing','verifikasi')];
if($role==='kabid') $nav[]=['rilis','Rilis Surat','send',route('listing','rilis')];
if($role==='bendahara') $nav[]=['retribusi','Penetapan Retribusi','receipt',route('listing','retribusi')];
if($role==='kadis') $nav[]=['tanda-tangan','Tanda Tangan Digital','signature',route('listing','tanda-tangan')];
if($role==='sekretariat') $nav[]=['serah-terima','Serah Terima Berkas','package-check',route('listing','serah-terima')];
$nav=array_merge($nav,[['arsip','Arsip Digital','archive',route('listing','arsip')],['kalender','Kalender Jadwal','calendar-days',route('kalender')]]);
if(in_array($role,['kadis','admin'])) $nav[]=['laporan','Statistik & Laporan','bar-chart-3',route('laporan')];
$nav[]=['riwayat','Log & Riwayat','history',route('listing','riwayat')];
if($role==='admin') $nav=array_merge($nav,[['pengguna','Kelola User & Role','users',route('pengguna')],['master','Data Master','database',route('master')]]);
$titles=['pratinjau'=>'Pratinjau Dokumen','dashboard'=>'Dashboard','antrean'=>'Antrean Permohonan','baru'=>'Permohonan Baru','detail'=>'Detail Permohonan','draft'=>'Editor Draft Surat','verifikasi'=>'Verifikasi Draft Surat','rilis'=>'Rilis Surat Persetujuan','retribusi'=>'Penetapan & Pembayaran Retribusi','tanda-tangan'=>'Tanda Tangan Digital','serah-terima'=>'Serah Terima & Pengambilan Berkas','arsip'=>'Arsip Digital','kalender'=>'Kalender Jadwal','riwayat'=>'Log & Riwayat','pengguna'=>'Kelola User & Role','master'=>'Data Master','laporan'=>'Statistik & Laporan'];
$activePage=($page??'')==='dashboard'||($page??'')==='detail'?'antrean':($page??'');
@endphp
<a class="visually-hidden-focusable" href="#main">Lewati navigasi</a>
<div class="shell"><aside class="sidebar brand-rail" id="sidebar">
<a class="sb-head" href="{{ route('dashboard') }}"><span class="logo-box"><x-icon name="waves"/></span><span class="brand-text"><span class="a d-block">SIPTR Dispar</span><span class="b d-block">Kota Pangkalpinang</span></span></a>
<nav class="sb-nav scroll-quiet">@foreach($nav as [$key,$label,$icon,$url])<a class="side-item {{ $activePage===$key?'active':'' }}" href="{{ $url }}" title="{{ $label }}" @if($activePage===$key) aria-current="page" @endif><span class="bar"></span><span class="ic"><x-icon :name="$icon" size="24"/></span><span class="lbl">{{ $label }}</span></a>@endforeach</nav>
<div class="sb-foot"><div class="sb-user"><div class="avatar">{{ strtoupper(substr($short[$role],0,2)) }}</div><div class="min-w-0 flex-grow-1"><p class="n text-truncate">{{ $short[$role] }}</p><p class="s text-truncate">Sedang masuk</p></div><form method="post" action="{{ route('demo.logout') }}">@csrf<button class="out" title="Keluar" aria-label="Keluar"><x-icon name="log-out" size="18"/></button></form></div></div>
</aside><div class="main-col"><header class="topbar"><div class="d-flex align-items-center gap-2"><div class="logo-mini"><x-icon name="waves" size="16"/></div><h1>{{ $titles[$page??'']??$title }}</h1></div><div class="dropdown"><button class="role-btn" data-bs-toggle="dropdown" aria-expanded="false"><span class="dot"></span><span class="pre">Peran:</span> {{ $short[$role] }} <x-icon name="chevron-down" size="14"/></button><ul class="dropdown-menu dropdown-menu-end">@unless(config('siap.database_accounts'))<li><h6 class="dropdown-header">Ganti peran (demo)</h6></li>@foreach($roles as $key=>$label)<li><form method="post" action="{{ route('demo.enter') }}">@csrf<input type="hidden" name="role" value="{{ $key }}"><button class="dropdown-item {{ $role===$key?'active':'' }}">{{ $label }}</button></form></li>@endforeach
@endunless
<li><hr class="dropdown-divider"></li><li><form method="post" action="{{ route('demo.logout') }}">@csrf<button class="dropdown-item">Keluar</button></form></li></ul></div></header>
<main class="main-wrap" id="main" tabindex="-1"><x-flash/>@yield('content')</main></div>
<nav class="bottom-nav brand-rail">@foreach(array_slice($nav,0,4) as [$key,$label,$icon,$url])<a class="{{ $activePage===$key?'active':'' }}" href="{{ $url }}"><x-icon :name="$icon" size="22"/><span class="l text-truncate">{{ explode(' ',$label)[0] }}</span></a>@endforeach<button type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Menu lainnya"><x-icon name="menu" size="22"/><span class="l">Lainnya</span></button></nav></div>
<div class="offcanvas offcanvas-bottom" id="mobileNav" tabindex="-1" aria-labelledby="mobileNavTitle" style="height:75vh"><div class="offcanvas-header"><h2 class="fs-5" id="mobileNavTitle">Menu {{ $short[$role] }}</h2><button class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button></div><div class="offcanvas-body"><nav class="vstack gap-2">@foreach($nav as [$key,$label,$icon,$url])<a class="btn btn-light justify-content-start" href="{{ $url }}"><x-icon :name="$icon"/>{{ $label }}</a>@endforeach</nav></div></div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script><script src="{{ asset('js/app.js') }}"></script><script src="{{ asset('js/ux.js') }}" defer></script>@stack('scripts')
<script src="{{ asset('js/approval-pages.js') }}"></script>
<script src="{{ asset('js/browser-access.js') }}"></script>
</body></html>
