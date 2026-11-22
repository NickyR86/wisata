<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
class ManualSignature
{
 public static function eligible(array $r): bool {
  return empty($r['deleted_at'])&&$r['status']==='ttd'&&!$r['signed']&&(!empty($r['payment'])||DemoData::noFee($r))&&in_array('Surat Persetujuan',$r['documents']);
 }
 public static function token(array $r): string {
  return hash('sha256',json_encode([$r['id'],$r['version'],DemoData::letter($r)],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 }
 public static function signers(): array {
  if(!Workspace::database())return [['id'=>'kadis','name'=>'Aditya Pratama (pejabat fiktif)']];
  $q=DB::table('pengguna as p')->join('model_has_roles as m','m.model_id','=','p.pengguna_id')->join('roles as r','r.role_id','=','m.role_id')
   ->where('m.model_type',\App\Models\Pengguna::class)->where('r.name','kadis');
  if(config('siap.database_accounts'))$q->where('p.status_akun','aktif');
  return $q->select('p.pengguna_id as id','p.nama as name')->distinct()->get()->map(fn($v)=>(array)$v)->all();
 }
}
