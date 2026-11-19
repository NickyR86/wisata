<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk · SIPTR Dispar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
@php
$wave = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5s2.5 2 5 2 2.5-2 5-2c1.3 0 1.9.5 2.5 1M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2c1.3 0 1.9.5 2.5 1M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2c1.3 0 1.9.5 2.5 1"/></svg>';
$labels = ['sekretariat'=>'Sekretariat','kabid'=>'Kepala Bidang','petugas'=>'Petugas Layanan Bidang','sekretaris'=>'Sekretaris','bendahara'=>'Bendahara Penerimaan','kadis'=>'Kepala Dinas','admin'=>'Admin'];
@endphp
<main class="login-grid">
<section class="login-art" aria-label="SIPTR Dispar">
<img src="{{ asset('images/login/latarlogin.png') }}" alt="Latar login Dinas Pariwisata">
<div class="shade"></div><div class="inner">
<div class="login-brand"><span class="login-crest"><img src="{{ asset('images/login/logo.png') }}" alt="Logo Dispar"></span><div><p class="brand-title">SIPTR Dispar</p><p class="brand-subtitle">Kota Pangkalpinang</p></div></div>
<div><h2>Sistem Pengajuan Persetujuan Acara / Event</h2><p class="art-description">Satu alur, satu status. Pantau setiap permohonan penggunaan tempat acara / event dari berkas masuk hingga surat siap diambil.</p></div>
<p class="copyright">© {{ date('Y') }} Dinas Pariwisata Kota Pangkalpinang</p>
</div></section>
<section class="login-form-side"><div class="login-box">
<div class="login-brand login-brand-m"><span class="logo-box">{!! $wave !!}</span><div><p class="brand-title">SIPTR Dispar</p><p class="brand-subtitle">Kota Pangkalpinang</p></div></div>
<h1>Masuk ke akun Anda</h1><p class="login-intro">Gunakan NIP atau username dinas yang terdaftar.</p>
<form method="post" action="{{ route('demo.enter') }}" class="login-fields">
@csrf
@if($returnTo=\App\Support\FormRecovery::destination(old('return_to',request('return_to'))))
<input type="hidden" name="return_to" value="{{ $returnTo }}">
<div class="alert alert-info">Setelah masuk, Anda akan kembali ke formulir sebelumnya.</div>
@endif
<input type="hidden" name="login_form" value="1">
<div><label for="nip">NIP / Username <span class="required">*</span></label><input class="form-control" id="nip" name="nip" type="text" value="{{ old('nip', (config('siap.database_accounts') || !app()->environment('local','testing')) ? '' : '198703122010011002') }}" placeholder="Masukkan NIP" minlength="6" maxlength="100" autocomplete="username" required></div>
<div><label for="password">Kata Sandi <span class="required">*</span></label><input class="form-control" id="password" name="password" type="password" value="{{ (config('siap.database_accounts') || !app()->environment('local','testing')) ? '' : 'demo1234' }}" placeholder="••••••••" autocomplete="current-password" required></div>
@unless(config('siap.database_accounts'))<div><label for="role">Masuk sebagai peran (demo)</label><select class="form-select" name="role" id="role">@foreach($labels as $key=>$label)<option value="{{ $key }}" @selected(old('role','sekretariat')===$key)>{{ $label }}</option>@endforeach</select></div>@endunless
@if(!config('siap.database_accounts') && !app()->environment('local','testing'))<div class="alert alert-warning mb-0" role="alert">Mode demo tidak tersedia di lingkungan ini. Akun database belum diaktifkan; hubungi administrator.</div>@endif
@if($errors->any())<div class="alert alert-danger mb-0" role="alert">{{ $errors->first() }}</div>@endif
<button type="submit" class="btn btn-primary w-100"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>Masuk</button>
</form>
<p class="login-help">Lupa kata sandi? Hubungi Admin Dinas.</p>
</div></section>
</main>
</body></html>
