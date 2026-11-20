<?php
namespace App\Console\Commands;
use App\Support\DemoData as D;
use App\Models\Pengguna;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class PrepareBusinessDemo extends Command
{
 protected $signature='siap:siapkan-demo-db';
 protected $description='Siapkan aktor demo dan master awal secara idempotent; tidak mereset permohonan.';
 public function handle(): int {
  if(!app()->environment('local','testing')){$this->error('Hanya untuk demo lokal.');return 1;}
  DB::transaction(function(){
   foreach(D::ROLES as $key=>$label){
    $id=DB::table('pengguna')->where('username','demo.'.$key)->value('pengguna_id');
    if(!$id)$id=DB::table('pengguna')->insertGetId(['nama'=>'Demo '.$label,'username'=>'demo.'.$key,'password'=>Hash::make(bin2hex(random_bytes(32))),'status_akun'=>'nonaktif','created_at'=>now(),'updated_at'=>now()],'pengguna_id');
    $role=DB::table('roles')->where('name',$key)->where('guard_name','web')->value('role_id');
    if(!$role)$role=DB::table('roles')->insertGetId(['name'=>$key,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()],'role_id');
    DB::table('model_has_roles')->insertOrIgnore(['model_type'=>Pengguna::class,'model_id'=>$id,'role_id'=>$role]);
   }
   $admin=DB::table('pengguna')->where('username','demo.admin')->value('pengguna_id');
   foreach(D::ACTIVITIES as $name)if(!DB::table('jenis_kegiatan')->where('nama',$name)->exists())DB::table('jenis_kegiatan')->insert(['nama'=>$name,'aktif'=>true,'created_at'=>now(),'updated_at'=>now()]);
   foreach(D::locations() as $sample){
    $id=DB::table('lokasi')->where('nama_lokasi',$sample['name'])->value('lokasi_id');
    if(!$id)$id=DB::table('lokasi')->insertGetId(['nama_lokasi'=>$sample['name'],'status_aktif'=>true,'dikelola_dispar'=>$sample['managed'],'butuh_rekomendasi_dlh'=>\App\Support\PermitRequirements::needsDlh(['name'=>$sample['name']]),'created_at'=>now(),'updated_at'=>now()],'lokasi_id');
    foreach(DB::table('jenis_kegiatan')->get() as $kind)if(!DB::table('tarif_retribusi')->where('lokasi_id',$id)->where('jenis_kegiatan_id',$kind->jenis_kegiatan_id)->exists())DB::table('tarif_retribusi')->insert(['lokasi_id'=>$id,'jenis_kegiatan_id'=>$kind->jenis_kegiatan_id,'tarif'=>$sample['rate'],'satuan'=>'hari','berlaku_mulai'=>'2020-01-01','ditetapkan_oleh'=>$admin,'created_at'=>now(),'updated_at'=>now()]);
   }
   foreach(D::REQUIREMENTS as $i=>$label)if(!DB::table('jenis_persyaratan')->where('kode','REQ-'.($i+1))->exists())DB::table('jenis_persyaratan')->insert(['kode'=>'REQ-'.($i+1),'nama'=>$label,'kondisi_wajib'=>'selalu','aktif'=>true,'created_at'=>now(),'updated_at'=>now()]);
  });
  $this->info('Master dan aktor demo database siap. Tidak ada permohonan yang dihapus atau diimpor.');return 0;
 }
}
