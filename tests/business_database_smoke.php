<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Services\BusinessStore;
use App\Support\DemoData as D;
use App\Support\FinanceDocument as F;
config(['siap.database_business'=>true]);
$store=app(BusinessStore::class);
$actor=fn($role)=>(int)DB::table('pengguna')->where('username','demo.'.$role)->value('pengguna_id');
$assert=function($ok,$label){if(!$ok)throw new RuntimeException($label);};
DB::beginTransaction();
try {
 $r=D::requests()[0];$r['date']=date('Y-m-d',strtotime('+30 days'));$r['location_id']=$store->locations()[0]['id'];$r['draft']='';unset($r['templates']);
 $r=$store->create($r,$actor('sekretariat'));$id=$r['id'];$stale=$r;
 $step=function($status,$role,$edit=null)use($store,$actor,$id){
  $r=$store->find($id);$r['status']=$status;if($edit)$edit($r);
  $r['history'][]=['at'=>date('Y-m-d H:i:s'),'role'=>$role,'action'=>'Uji '.$status,'note'=>'Pengujian transaksi'];
  $store->save($r,$actor($role));return $store->find($id);
 };
 $assert(count($r['files'])===4,'requirements');
 $r=$step('draft','kabid');
 try{$store->save($stale,$actor('sekretariat'));throw new RuntimeException('stale update accepted');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$assert($e->getStatusCode()===409,'optimistic lock');}
 $r=$step('draft','petugas',function(&$r){$r['draft']='Narasi draft yang tersimpan di database untuk pengujian layanan.';});
 $assert(str_contains($r['draft'],'Narasi draft'),'draft persistence');
 $r=$step('review_draft','petugas');$r=$step('verifikasi','kabid');$r=$step('rilis','sekretaris');
 $r=$step('tagihan','kabid',function(&$r){$r['documents'][]='Surat Persetujuan';});
 $r=$step('bayar','bendahara',function(&$r){$r['bill']=['rate'=>750000,'subtotal'=>750000,'percent'=>0,'total'=>750000,'note'=>'Uji'];F::freezeSkrd($r,'Uji terbit');$r['documents'][]='SKRD';});
 $assert($r['bill']['total']===750000.0,'invoice persistence');
 $r=$step('ttd','bendahara',function(&$r){$r['payment']=['date'=>date('Y-m-d')];$r['documents']=array_merge($r['documents'],['STBP','STS']);});
 $r=$step('serah','kadis',function(&$r){$r['signed']=true;$r['signature']=['name'=>'Kadis Demo','at'=>date('Y-m-d H:i:s'),'reference'=>'SIM-TEST'];});
 $r=$step('selesai','sekretariat',function(&$r){$r['recipient']='Penerima Uji';$r['completed']=date('Y-m-d');});
 $assert($r['status']==='selesai'&&$r['recipient']==='Penerima Uji'&&$r['signed'],'handover');
 $assert(DB::table('serah_terima_dokumen')->whereIn('serah_terima_id',DB::table('serah_terima')->where('permohonan_id',$id)->select('serah_terima_id'))->count()===3,'handover exact documents excluding STS');
 $assert(count($r['documents'])===4,'documents');
 $r=$step('selesai','sekretariat',function(&$r){$r['deleted_at']=date('Y-m-d H:i:s');$r['delete_reason']='Uji hapus';});
 $assert($r['deleted_at']!==null,'soft delete');
 $r=$step('selesai','sekretariat',function(&$r){$r['deleted_at']=null;});$assert($r['deleted_at']===null,'restore');
 echo "PASS: intake, files, draft, release, SKRD, payment, signature, handover, soft delete/restore and stale-write rejection.\n";
}finally{DB::rollBack();}
