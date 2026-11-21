<?php
use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/login',[DemoController::class,'login'])->name('login');
Route::post('/demo/masuk',[DemoController::class,'enter'])->name('demo.enter');
Route::get('/akses/pulihkan',function (\Illuminate\Http\Request $request) {
    $target=\App\Support\FormRecovery::destination($request->query('return_to'));
    if(!$target)return redirect()->route('login');
    if(session('demo.role'))return redirect()->to($target);
    return redirect()->route('login',['return_to'=>$target]);
})->name('access.recover');
Route::get('/akses/status',function () {
    $role=session('demo.role');
    if(config('siap.database_accounts')&&$role) {
        $user=\App\Models\Pengguna::find(session('account_id'));
        $currentRole=$user?->roles()->whereIn('name',array_keys(\App\Support\DemoData::ROLES))->orderBy('role_id')->value('name');
        if(!$user||$user->status_akun!=='aktif'||!$currentRole||!hash_equals(hash('sha256',$user->password),(string)session('account_password_fingerprint'))) {
            \App\Support\BrowserAccess::revoke(request()); session()->invalidate(); $role=null;
        } else { $role=$currentRole; session(['demo.role'=>$role]); }
    }
    return response()->json(['authenticated'=>(bool)$role,'role'=>$role,'identity'=>config('siap.database_accounts')?'account:'.session('account_id'):'demo:'.$role,'token'=>csrf_token()])->header('Cache-Control','no-store, private');
})->name('access.status');
Route::middleware(\App\Http\Middleware\DemoSession::class)->group(function() {
    Route::get('/',[DemoController::class,'home'])->name('home');
    Route::get('/dashboard',[DemoController::class,'dashboard'])->name('dashboard');
    // Tautan lama tetap berfungsi.
    Route::redirect('/ringkasan','/dashboard')->name('summary');
    Route::post('/demo/keluar',[DemoController::class,'logout'])->name('demo.logout');
    Route::post('/demo/reset',[DemoController::class,'reset'])->name('demo.reset');
    Route::get('/permohonan/baru',[DemoController::class,'create'])->name('permohonan.create');
    Route::post('/permohonan',[DemoController::class,'store'])->name('permohonan.store');
    Route::post('/permohonan/{id}/sumber-surat',[DemoController::class,'updateLetterSource'])->whereNumber('id')->name('letter.source');
    Route::get('/permohonan/{id}',[DemoController::class,'detail'])->whereNumber('id')->name('detail');
    Route::post('/permohonan/{id}/aksi',[DemoController::class,'act'])->whereNumber('id')->name('demo.act');
    Route::get('/permohonan/{id}/cetak-surat/{mode}',[DemoController::class,'printLetter'])->whereNumber('id')->name('letter.print');
    Route::post('/permohonan/{id}/tanda-tangan-manual',[DemoController::class,'recordManualSignature'])->whereNumber('id')->name('letter.manual');
    Route::get('/dokumen/{id}/{type}',[DemoController::class,'document'])->whereNumber('id')->name('document');
    Route::match(['get','post'],'/permohonan/{id}/template/{type}',[DemoController::class,'template'])->whereNumber('id')->name('template');
    Route::post('/permohonan/{id}/unggah/{type}',[DemoController::class,'upload'])->whereNumber('id')->name('upload');
    Route::get('/permohonan/{id}/berkas/{type}',[DemoController::class,'uploaded'])->whereNumber('id')->name('uploaded');
    Route::post('/permohonan/{id}/ingatkan',[DemoController::class,'remind'])->whereNumber('id')->name('remind');
    Route::get('/kalender',[DemoController::class,'calendar'])->name('kalender');
    Route::get('/laporan',[DemoController::class,'reports'])->name('laporan');
    Route::get('/admin/pengguna',[DemoController::class,'users'])->name('pengguna');
    Route::post('/admin/pengguna',[DemoController::class,'addUser'])->name('pengguna.store');
    Route::get('/admin/lokasi',[DemoController::class,'master'])->name('master');
    Route::get('/permohonan/{id}/lembar-periksa',[DemoController::class,'checklist'])->whereNumber('id')->name('checklist');
    Route::post('/admin/lokasi/{id}',[DemoController::class,'updateRate'])->whereNumber('id')->name('master.update');
    Route::post('/admin/lokasi',[DemoController::class,'addLocation'])->name('master.add');
    Route::post('/admin/lokasi/{id}/ubah',[DemoController::class,'editLocation'])->whereNumber('id')->name('master.edit');
    Route::post('/admin/lokasi/{id}/hapus',[DemoController::class,'removeLocation'])->whereNumber('id')->name('master.remove');
    Route::post('/admin/kegiatan',[DemoController::class,'addActivity'])->name('master.activity.add');
    Route::post('/admin/kegiatan/{id}/status',[DemoController::class,'toggleActivity'])->whereNumber('id')->name('master.activity.toggle');
    Route::post('/admin/pengguna/{id}',[DemoController::class,'editUser'])->name('pengguna.update');
    Route::post('/permohonan/{id}/hapus',[DemoController::class,'removeRequest'])->whereNumber('id')->name('permohonan.remove');
    Route::post('/permohonan/{id}/pulihkan',[DemoController::class,'restoreRequest'])->whereNumber('id')->name('permohonan.restore');
    Route::get('/arsip/cadangan',[DemoController::class,'archivePackage'])->name('archive.package');
    Route::get('/ekspor',[DemoController::class,'export'])->name('export');
    Route::get('/layanan/{kind}',[DemoController::class,'listing'])->name('listing');
});
