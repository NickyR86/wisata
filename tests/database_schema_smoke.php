<?php
// Run against an already migrated database. Fixture rows are always rolled back.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$definitions=json_decode(file_get_contents(__DIR__.'/../docs/database/schema.json'),true);
$assert=function($ok,$message){if(!$ok)throw new RuntimeException($message);};
foreach($definitions as $name=>$definition){
 $assert(Schema::hasTable($name),'Missing table: '.$name);
 foreach($definition['fields'] as $f)$assert(Schema::hasColumn($name,$f['name']),'Missing column: '.$name.'.'.$f['name']);
}
$db=DB::getDatabaseName();
$constraints=DB::select('SELECT CONSTRAINT_TYPE AS kind, COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? GROUP BY CONSTRAINT_TYPE',[$db]);
$reject=function(callable $operation,string $label)use($assert){
 try{$operation();}catch(\Illuminate\Database\QueryException $e){$assert(in_array((int)($e->errorInfo[1]??0),[1062,1451,1452,4025]),'Unexpected SQL error: '.$e->getMessage());return;}
 throw new RuntimeException('Constraint did not reject '.$label);
};
DB::beginTransaction();
try{
 $key='test_'.bin2hex(random_bytes(6));
 $user=['nama'=>'Pengujian migration','username'=>$key,'password'=>'not-a-login-hash','status_akun'=>'aktif'];
 $id=DB::table('pengguna')->insertGetId($user,'pengguna_id');
 $reject(fn()=>DB::table('pengguna')->insert($user),'duplicate username');
 $reject(fn()=>DB::table('pengguna')->insert(array_merge($user,['username'=>$key.'x','status_akun'=>'invalid'])),'invalid account status');
 $loc=DB::table('lokasi')->insertGetId(['nama_lokasi'=>$key,'status_aktif'=>1,'dikelola_dispar'=>1],'lokasi_id');
 $kind=DB::table('jenis_kegiatan')->insertGetId(['nama'=>$key,'aktif'=>1],'jenis_kegiatan_id');
 $rate=['lokasi_id'=>$loc,'jenis_kegiatan_id'=>$kind,'tarif'=>1000,'satuan'=>'hari','berlaku_mulai'=>'2026-01-01','ditetapkan_oleh'=>$id];
 DB::table('tarif_retribusi')->insert($rate);
 $reject(fn()=>DB::table('tarif_retribusi')->insert($rate),'duplicate tariff period');
 $reject(fn()=>DB::table('tarif_retribusi')->insert(array_merge($rate,['berlaku_mulai'=>'2026-02-01','tarif'=>-1])),'negative amount');
 $reject(fn()=>DB::table('tarif_retribusi')->insert(array_merge($rate,['berlaku_mulai'=>'2026-03-01','berlaku_sampai'=>'2026-02-01'])),'reversed date range');
 $reject(fn()=>DB::table('tarif_retribusi')->insert(array_merge($rate,['berlaku_mulai'=>'2026-04-01','lokasi_id'=>0])),'missing parent');
 $reject(fn()=>DB::table('pengguna')->where('pengguna_id',$id)->delete(),'delete referenced user');
 $reject(fn()=>DB::table('dokumen_terbit')->insert(['jenis'=>'skrd','status'=>'draft']),'document without owner');
 echo "PASS: 26 tables and all defined columns; 8 rejection checks; fixtures rolled back.\n";
 foreach($constraints as $c)echo $c->kind.': '.$c->total."\n";
}finally{DB::rollBack();}
