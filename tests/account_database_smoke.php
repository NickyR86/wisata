<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
$service=app(AccountService::class);
$assert=function($condition,$label){if(!$condition)throw new RuntimeException($label);};
DB::beginTransaction();
try {
 $key='test'.bin2hex(random_bytes(6));$pass=bin2hex(random_bytes(12));
 $data=['name'=>'Uji akun','username'=>$key,'nip'=>null,'role'=>'admin','active'=>true,'password'=>$pass];
 $u=$service->save($data,null,null);
 $assert(Hash::check($pass,$u->password) && $u->password!==$pass,'password hash');
 $assert($service->authenticate($key,$pass)?->getKey()===$u->getKey(),'login valid');
 $assert($service->authenticate($key,'wrong')===null,'wrong password');
 $staff=$service->save(array_merge($data,['username'=>$key.'s','role'=>'petugas']),null,$u->getKey());
 $assert($staff->roles()->value('name')==='petugas','role pivot');
 $service->save(array_merge($data,['username'=>$key.'s','role'=>'petugas','active'=>false,'password'=>'']),$staff->getKey(),$u->getKey());
 $assert($service->authenticate($key.'s',$pass)===null,'inactive login');
 $failed=false;try{$service->save(array_merge($data,['username'=>$key.'x','nip'=>$key]),null,$u->getKey());}catch(\Illuminate\Validation\ValidationException){$failed=true;}
 $assert($failed,'cross-column identifier collision');
 $audit=DB::table('audit_aktivitas')->where('objek_id',$staff->getKey())->where('jenis_objek','pengguna')->get();
 $assert($audit->count()===2,'create and update audit');
 foreach($audit as $row)$assert(!str_contains($row->sesudah,'password')&&!str_contains($row->sesudah,$pass),'audit secret exclusion');
 echo "PASS: account creation/update, hashing, login, roles, inactive rejection, identifier collision, audit. Fixtures rolled back.\n";
}finally{DB::rollBack();}
