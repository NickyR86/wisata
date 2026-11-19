<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\BrowserAccess as B;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;
config(['session.driver'=>'array','siap.database_accounts'=>true,'database.default'=>'sqlite',
 'database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
Schema::create('pengguna',function($t){$t->increments('pengguna_id');$t->string('password');$t->string('status_akun');});
DB::table('pengguna')->insert(['pengguna_id'=>1,'password'=>'test-password-hash','status_akun'=>'aktif']);
session(['account_id'=>1,'account_password_fingerprint'=>hash('sha256','test-password-hash'),'demo.role'=>'sekretariat']);
function ensure($ok,$label){if(!$ok)throw new RuntimeException($label);}
$request=Request::create('/login');B::issue($request);
$token=Cookie::queued(B::COOKIE)->getValue();$request=Request::create('/akses/status','GET',[],[B::COOKIE=>$token]);
try{
 ensure(B::read($request)!==null,'Valid account could not resume');
 DB::table('pengguna')->where('pengguna_id',1)->update(['password'=>'new-password-hash']);
 ensure(B::read($request)===null,'Changed password must revoke access');
 DB::table('pengguna')->where('pengguna_id',1)->update(['password'=>'test-password-hash']);
 ensure(B::read($request)===null,'Revoked credential became valid again');
 B::issue($request);$token=Cookie::queued(B::COOKIE)->getValue();$request=Request::create('/akses/status','GET',[],[B::COOKIE=>$token]);
 DB::table('pengguna')->where('pengguna_id',1)->update(['status_akun'=>'nonaktif']);
 ensure(B::read($request)===null,'Disabled account must not resume');
 echo "PASS: valid account, password change, replay rejection, disabled account (SQLite memory only)\n";
}finally{B::revoke($request);}
