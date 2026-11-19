<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Support\DemoData as D;
use App\Services\BusinessStore;
config(['database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true],'siap.database_business'=>true]);
DB::purge('sqlite');
// MySQL CHECK statements are omitted only in this isolated SQLite test.
$schema=file_get_contents(__DIR__.'/../database/migrations/2026_11_05_000001_create_siap_wisata_revision_one.php');
$schema=preg_replace('/^\s*DB::statement\(.*\);\s*$/m','',$schema);
$schema=preg_replace('/^<\?php/','',$schema);
$m=eval($schema);$m->up();
foreach(['2026_11_06_000001_support_business_persistence.php','2026_11_15_000001_add_structured_approval_letter.php'] as $file)(require __DIR__.'/../database/migrations/'.$file)->up();
Artisan::call('siap:siapkan-demo-db');
$actor=(int)DB::table('pengguna')->where('username','demo.petugas')->value('pengguna_id');
$store=app(BusinessStore::class);
$r=D::requests()[8];$r['location_id']=1;$r['letter']=D::letter($r);$r['draft']='Surat manual uji';
$r=$store->create($r,$actor);$r['status']='ttd';$store->save($r,$actor);$r=$store->find($r['id']);
$signer=(int)DB::table('pengguna')->where('username','demo.kadis')->value('pengguna_id');
$path=storage_path('app/demo-uploads/manual-db-test-'.bin2hex(random_bytes(8)).'.pdf');file_put_contents($path,"%PDF-1.4\nDEMO test");
try {
 $r['manual_letter']=['stored'=>basename($path),'name'=>'manual.pdf'];$r['signed']=true;$r['status']='serah';
 $r['signature']=['name'=>'Kadis Uji','signer_id'=>$signer,'method'=>'unggahan_manual','at'=>date('Y-m-d').' 00:00:00','reference'=>'MANUAL-TEST','reason'=>'TTE offline'];
 $store->save($r,$actor);$r=$store->find($r['id']);
 if($r['status']!=='serah'||$r['signature']['method']!=='unggahan_manual'||$r['manual_letter']['stored']!==basename($path))throw new RuntimeException('manual reload failed');
 $sig=DB::table('tanda_tangan_dokumen')->orderByDesc('tanda_tangan_dokumen_id')->first();
 $v=DB::table('versi_dokumen')->where('versi_dokumen_id',$sig->versi_dokumen_id)->first();
 if($sig->pengguna_id!==$signer||$v->dibuat_oleh!==$actor||$v->sumber!=='unggah')throw new RuntimeException('signer/uploader distinction failed');
 $r['status']='selesai';$r['recipient']='Penerima uji';$store->save($r,$actor);$r=$store->find($r['id']);
 if($r['manual_letter']['stored']!==basename($path))throw new RuntimeException('handover replaced signed PDF');
 if(!DB::table('serah_terima_dokumen')->where('versi_dokumen_id',$v->versi_dokumen_id)->exists())throw new RuntimeException('handover version mismatch');
 echo "PASS: manual PDF persistence, signer/uploader, queue status and handover version.\n";
}finally{unlink($path);}
