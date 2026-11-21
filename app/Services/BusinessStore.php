<?php
namespace App\Services;

use App\Support\DemoData as D;
use App\Support\FinanceDocument as F;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Maps the existing UI to normalized tables; no request JSON/session persistence. */
class BusinessStore
{
 private function json($value): string { return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
 private function stamp(array $data): array { return $data+['created_at'=>now(),'updated_at'=>now()]; }
 public function locations(): array {
  return DB::table('lokasi')->orderBy('lokasi_id')->get()->map(function($l){
   $rate=DB::table('tarif_retribusi')->where('lokasi_id',$l->lokasi_id)->where('berlaku_mulai','<=',date('Y-m-d'))->where(fn($q)=>$q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai','>=',date('Y-m-d')))->orderByDesc('berlaku_mulai')->value('tarif');
   return ['id'=>(int)$l->lokasi_id,'name'=>$l->nama_lokasi,'area'=>'Pangkalpinang','rate'=>(float)($rate??0),'managed'=>(bool)$l->dikelola_dispar,'active'=>(bool)$l->status_aktif,'dlh'=>(bool)($l->butuh_rekomendasi_dlh??false)];
  })->all();
 }
 public function tariff(int $location,string $activity,string $date): ?object {
  return DB::table('tarif_retribusi as t')->join('jenis_kegiatan as k','k.jenis_kegiatan_id','=','t.jenis_kegiatan_id')
   ->where('t.lokasi_id',$location)->where('k.nama',$activity)->where('t.berlaku_mulai','<=',$date)
   ->where(fn($q)=>$q->whereNull('t.berlaku_sampai')->orWhere('t.berlaku_sampai','>=',$date))->orderByDesc('t.berlaku_mulai')->select('t.*')->first();
 }
 public function all(): array { return DB::table('permohonan')->orderBy('permohonan_id')->get()->map(fn($p)=>$this->hydrate($p))->all(); }
 public function find(int $id): array { $p=DB::table('permohonan')->where('permohonan_id',$id)->first();abort_unless($p,404);return $this->hydrate($p); }
 private function hydrate(object $p): array {
  $id=$p->permohonan_id;
  $r=['id'=>(int)$id,'_v'=>(int)$p->lock_version,'tracking'=>$p->nomor_tracking,'name'=>$p->nama_pemohon,'pic'=>$p->penanggung_jawab,'phone'=>$p->kontak_pemohon,'email'=>$p->email_pemohon,'address'=>$p->alamat_pemohon,
   'location_id'=>(int)$p->lokasi_id,'activity'=>DB::table('jenis_kegiatan')->where('jenis_kegiatan_id',$p->jenis_kegiatan_id)->value('nama'),'date'=>$p->tanggal_kegiatan,'days'=>(int)$p->jumlah_hari,'submitted'=>substr($p->diterima_pada,0,10),'status'=>$p->status,
   'discount'=>$p->status_keringanan,'relief'=>$p->jenis_keringanan,'relief_note'=>$p->catatan_keputusan_keringanan,'discount_percent'=>(float)$p->persentase_disetujui,'discount_reason'=>$p->alasan_keringanan,
   'deleted_at'=>$p->deleted_at,'deleted_by'=>$p->deleted_by,'delete_reason'=>$p->alasan_hapus,
   'draft'=>'','version'=>1,'bill'=>null,'payment'=>null,'files'=>[],'documents'=>[],'templates'=>[],'uploads'=>[],'document_sources'=>[],'signed'=>false,'recipient'=>null,'completed'=>null,'history'=>[]];
  foreach(DB::table('persyaratan_permohonan')->where('permohonan_id',$id)->orderBy('persyaratan_permohonan_id')->get() as $req){
   $file=DB::table('dokumen_persyaratan')->where('persyaratan_permohonan_id',$req->persyaratan_permohonan_id)->orderByDesc('versi')->first();
   if($file)$r['files'][]=['label'=>$req->nama_snapshot,'name'=>$file->nama_asli,'stored'=>basename($file->file_path),'sample'=>false,'size'=>(int)$file->ukuran_byte];
  }
  $r['letter_source']=json_decode($p->data_surat??'{}',true)??[];
  $r['deleted_by_name']=$p->deleted_by?DB::table('pengguna')->where('pengguna_id',$p->deleted_by)->value('nama'):null;
  $draft=DB::table('draft_surat')->where('permohonan_id',$id)->orderByDesc('versi')->first();
  if($draft){$r['version']=(int)$draft->versi;$r['letter']=['number'=>$draft->nomor_draft,'date'=>$draft->tanggal_surat,'subject'=>$draft->perihal,'attachment'=>$draft->lampiran,'opening'=>$draft->pembuka,'body'=>$draft->isi_surat,'conditions'=>$draft->ketentuan,'closing'=>$draft->penutup,'structure'=>json_decode($draft->struktur_surat??'null',true)];if(empty($r['letter']['structure']))unset($r['letter']['structure']);$r['draft']=implode("\n\n",[$draft->pembuka,$draft->isi_surat,$draft->ketentuan,$draft->penutup]);}
  foreach(DB::table('pengaturan_dokumen_permohonan')->where('permohonan_id',$id)->get() as $settings){
   $r['templates'][$settings->jenis]=json_decode($settings->pengaturan,true);$r['document_sources'][$settings->jenis]=$settings->sumber;
   if($settings->sumber_ditinjau)$r['skrd_source']=json_decode($settings->sumber_ditinjau,true);
   if($settings->file_path)$r['uploads'][$settings->jenis]=['stored'=>basename($settings->file_path),'name'=>$settings->nama_asli,'size'=>(int)$settings->ukuran_byte];
  }
  $invoice=DB::table('tagihan_retribusi')->where('permohonan_id',$id)->first();
  if($invoice){
   $r['bill']=['rate'=>(float)$invoice->subtotal/$r['days'],'subtotal'=>(float)$invoice->subtotal,'percent'=>(float)$invoice->persentase_potongan,'total'=>(float)$invoice->jumlah_tagihan,'note'=>$invoice->catatan_penetapan];
   $payment=DB::table('pembayaran')->where('tagihan_retribusi_id',$invoice->tagihan_retribusi_id)->first();
   if($payment)$r['payment']=['date'=>$payment->tanggal_bayar,'proof'=>$payment->bukti_nama??'Bukti konfirmasi pembayaran'];
  }else{$payment=null;}
  $letter=DB::table('surat_persetujuan')->where('permohonan_id',$id)->first();
  $docs=DB::table('dokumen_terbit')->where(function($q)use($letter,$invoice,$payment){$q->whereRaw('1=0');if($letter)$q->orWhere('surat_persetujuan_id',$letter->surat_persetujuan_id);if($invoice)$q->orWhere('tagihan_retribusi_id',$invoice->tagihan_retribusi_id);if($payment)$q->orWhere('pembayaran_id',$payment->pembayaran_id);})->get();
  foreach($docs as $doc){
   $key=$doc->jenis==='persetujuan'?'surat':$doc->jenis;$r['documents'][]=$doc->jenis==='persetujuan'?'Surat Persetujuan':strtoupper($doc->jenis);
   $versions=DB::table('versi_dokumen')->where('dokumen_terbit_id',$doc->dokumen_terbit_id)->orderBy('versi')->get();
   foreach($versions as $v){$snapshot=json_decode($v->data_snapshot,true);if($doc->jenis==='skrd'){$r['skrd_versions'][]=['at'=>$v->created_at,'reason'=>$v->alasan_revisi,'data'=>$snapshot];$r['skrd_snapshot']=$snapshot;}}
   $latest=$versions->last();
   if($latest){
    $r['_document_snapshots'][$key]=json_decode($latest->data_snapshot,true);
    if($doc->jenis==='persetujuan'){
     $sig=DB::table('tanda_tangan_dokumen')->where('versi_dokumen_id',$latest->versi_dokumen_id)->where('status','berhasil')->first();
     if($sig){$r['signed']=true;$r['signature']=['name'=>$sig->nama_snapshot,'at'=>$sig->ditandatangani_pada,'reference'=>$sig->referensi_penyedia,'method'=>$sig->metode,'signer_id'=>$sig->pengguna_id]+($r['_document_snapshots'][$key]['signature']??[]);
      if($sig->metode==='unggahan_manual')$r['manual_letter']=['stored'=>basename($latest->file_path),'name'=>$latest->nama_asli,'size'=>$latest->ukuran_byte];}
    }
   }
  }
  $handover=DB::table('serah_terima')->where('permohonan_id',$id)->first();
  if($handover){$r['recipient']=$handover->nama_penerima;$r['completed']=substr($handover->diserahkan_pada,0,10);}
  $r['history']=DB::table('riwayat_disposisi')->where('permohonan_id',$id)->orderBy('riwayat_disposisi_id')->get()->map(fn($h)=>['at'=>$h->terjadi_pada,'role'=>$h->role_snapshot,'action'=>$h->aksi,'note'=>$h->catatan??''])->all();
  $r['_history_count']=count($r['history']);return $r;
 }
 public function create(array $r,int $actor): array {
  $location=collect($this->locations())->firstWhere('id',$r['location_id']);abort_unless($location,422,'Lokasi tidak ditemukan.');
  $requirements=\App\Support\PermitRequirements::forLocation($location); if(($r['discount']??'tidak_diajukan')!=='tidak_diajukan') $requirements[]=D::RELIEF_REQUIREMENT;
  $files=collect($r['files'])->keyBy('label');
  foreach($requirements as $label)abort_unless($files->has($label),422,'Berkas wajib belum lengkap: '.$label);
  $allowed=array_values($requirements); if($files->has(D::PROMOTER_REQUIREMENT)) $allowed[]=D::PROMOTER_REQUIREMENT; $r['files']=$files->only($allowed)->values()->all();
  return DB::transaction(function()use($r,$actor){
   $kind=DB::table('jenis_kegiatan')->where('nama',$r['activity'])->where('aktif',true)->value('jenis_kegiatan_id');abort_unless($kind,422,'Jenis kegiatan tidak aktif.');
   abort_unless(DB::table('lokasi')->where('lokasi_id',$r['location_id'])->where('status_aktif',true)->exists(),422);
   $id=DB::table('permohonan')->insertGetId($this->stamp([
    'data_surat'=>$this->json($r['letter_source']??[]),'nomor_tracking'=>'TMP-'.bin2hex(random_bytes(12)),'nama_pemohon'=>$r['name'],'penanggung_jawab'=>$r['pic'],'kontak_pemohon'=>$r['phone'],'email_pemohon'=>$r['email']??null,'alamat_pemohon'=>$r['address']??'Belum diisi (demo)',
    'lokasi_id'=>$r['location_id'],'jenis_kegiatan_id'=>$kind,'tanggal_kegiatan'=>$r['date'],'jumlah_hari'=>$r['days'],'diterima_pada'=>now(),'status'=>'masuk','diinput_oleh'=>$actor,
    'jenis_keringanan'=>$r['relief']??($r['discount']==='tidak_diajukan'?'normal':'pengurangan'),'status_keringanan'=>$r['discount'],'alasan_keringanan'=>$r['discount_reason']??null,'persentase_disetujui'=>$r['discount_percent']??0,
   ]),'permohonan_id');
   $r['id']=$id;$r['tracking']='DPR-'.date('Y').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT);$r['_v']=0;$r['_history_count']=0;
   DB::table('permohonan')->where('permohonan_id',$id)->update(['nomor_tracking'=>$r['tracking']]);
   foreach($r['files'] as $i=>$file){
    $catalogIndex=array_search($file['label'],array_merge(D::REQUIREMENTS,[D::RELIEF_REQUIREMENT,D::PROMOTER_REQUIREMENT]),true);abort_if($catalogIndex===false,422,'Jenis berkas tidak dikenal.');
    $type=DB::table('jenis_persyaratan')->where('kode','REQ-'.($catalogIndex+1))->value('jenis_persyaratan_id');abort_unless($type,422,'Katalog persyaratan belum disiapkan.');
    $req=DB::table('persyaratan_permohonan')->insertGetId($this->stamp(['permohonan_id'=>$id,'jenis_persyaratan_id'=>$type,'nama_snapshot'=>$file['label'],'wajib'=>$file['label']!==D::PROMOTER_REQUIREMENT]),'persyaratan_permohonan_id');
    if(!empty($file['sample'])){
     $stored='sample-'.bin2hex(random_bytes(12)).'.txt';$path=storage_path('app/demo-uploads/'.$stored);if(!is_dir(dirname($path)))mkdir(dirname($path),0775,true);
     file_put_contents($path,'DOKUMEN CONTOH DEMO - BUKAN DOKUMEN RESMI'."\n".$file['label']."\n".$r['name']);$file=['stored'=>$stored,'name'=>'contoh-'.($i+1).'.txt'];
    }
    $meta=$this->fileMetadata($file);
    DB::table('dokumen_persyaratan')->insert($this->stamp(['persyaratan_permohonan_id'=>$req,'versi'=>1,'disk'=>'local','diunggah_oleh'=>$actor,'diunggah_pada'=>now(),'status_verifikasi'=>'menunggu']+$meta));
   }
   $this->save($r,$actor);return $this->find($id);
  });
 }
 private function fileMetadata(array $file): array {
  $path=storage_path('app/demo-uploads/'.basename($file['stored']));abort_unless(is_file($path),422,'Berkas tidak ditemukan.');
  return ['file_path'=>'demo-uploads/'.basename($file['stored']),'nama_asli'=>$file['name'],'ukuran_byte'=>filesize($path),'mime_type'=>mime_content_type($path),'sha256'=>hash_file('sha256',$path)];
 }
 private function templateId(string $type,int $actor): int {
  $id=DB::table('template_surat')->where('jenis',$type)->orderByDesc('versi')->value('template_surat_id');
  return $id??DB::table('template_surat')->insertGetId($this->stamp(['jenis'=>$type,'versi'=>1,'isi_template'=>$this->json(['schema_version'=>1,'view'=>$type==='persetujuan'?'dokumen.letter':'dokumen.financial']),'dibuat_oleh'=>$actor,'dipublikasikan_pada'=>now()]),'template_surat_id');
 }
 public function save(array $r,int $actor): void {
  DB::transaction(function()use($r,$actor){
   $p=DB::table('permohonan')->where('permohonan_id',$r['id'])->lockForUpdate()->first();abort_unless($p,404);
   abort_unless((int)$p->lock_version===(int)$r['_v'],409,'Data sudah berubah. Muat ulang sebelum menyimpan.');
   $id=$r['id'];$old=$this->hydrate($p);$now=now();
   $reliefUpdate=['status_keringanan'=>$r['discount'],'persentase_disetujui'=>$r['discount_percent']??0,'catatan_keputusan_keringanan'=>$r['relief_note']??$p->catatan_keputusan_keringanan];
   if($old['discount']!==$r['discount']&&in_array($r['discount'],['disetujui','ditolak'],true)){$reliefUpdate['keringanan_diputuskan_oleh']=$actor;$reliefUpdate['keringanan_diputuskan_pada']=$now;}
   DB::table('permohonan')->where('permohonan_id',$id)->update(['data_surat'=>$this->json($r['letter_source']??[]),'alamat_pemohon'=>$r['address']??$p->alamat_pemohon,'status'=>$r['status'],'deleted_at'=>$r['deleted_at']??null,'deleted_by'=>empty($r['deleted_at'])?null:$actor,'alasan_hapus'=>$r['delete_reason']??null,'lock_version'=>$p->lock_version+1,'updated_at'=>$now]+$reliefUpdate);
   if($p->status==='masuk'&&$r['status']==='draft'){
    $reqs=DB::table('persyaratan_permohonan')->where('permohonan_id',$id)->pluck('persyaratan_permohonan_id');
    DB::table('dokumen_persyaratan')->whereIn('persyaratan_permohonan_id',$reqs)->update(['status_verifikasi'=>'diterima','diverifikasi_oleh'=>$actor,'diverifikasi_pada'=>$now,'updated_at'=>$now]);
   }
   $draft=DB::table('draft_surat')->where('permohonan_id',$id)->orderByDesc('versi')->first();
   if(!empty($r['draft'])||!empty($r['letter'])){
    $letter=D::letter($r);$content=['nomor_draft'=>$letter['number'],'tanggal_surat'=>$letter['date'],'perihal'=>$letter['subject'],'lampiran'=>$letter['attachment'],'pembuka'=>$letter['opening'],'isi_surat'=>$letter['body'],'ketentuan'=>$letter['conditions'],'penutup'=>$letter['closing'],'struktur_surat'=>$this->json($letter['structure'])];
    $changed=!$draft || ($draft->status==='perlu_revisi' && in_array($r['status'],['draft','review_draft']));foreach($content as $k=>$v)if($draft&&$draft->$k!==$v)$changed=true;
    if($changed){
     $draftId=DB::table('draft_surat')->insertGetId($this->stamp($content+['permohonan_id'=>$id,'template_surat_id'=>$this->templateId('persetujuan',$actor),'versi'=>($draft->versi??0)+1,'status'=>$r['status']==='review_draft'?'diajukan':'disusun','disusun_oleh'=>$actor,'dikirim_pada'=>$r['status']==='review_draft'?$now:null]),'draft_surat_id');
     $draft=DB::table('draft_surat')->where('draft_surat_id',$draftId)->first();
    }
    if($draft){$state=match($r['status']){'review_draft'=>'diajukan','verifikasi','rilis','tagihan','bayar','ttd','serah','selesai'=>'disetujui',default=>in_array($p->status,['review_draft','verifikasi'])&&$r['status']==='draft'?'perlu_revisi':'disusun'};
     DB::table('draft_surat')->where('draft_surat_id',$draft->draft_surat_id)->update(['status'=>$state,'dikirim_pada'=>$state==='diajukan'?($draft->dikirim_pada??$now):$draft->dikirim_pada,'updated_at'=>$now]);}
   }
   foreach(array_unique(array_merge(array_keys($r['templates']??[]),array_keys($r['uploads']??[]))) as $key){
    abort_unless(in_array($key,['skrd','sts','stbp','proof']),422);
    $values=['pengaturan'=>$this->json($r['templates'][$key]??[]),'sumber'=>$r['document_sources'][$key]??'template','sumber_ditinjau'=>$key==='skrd'&&isset($r['skrd_source'])?$this->json($r['skrd_source']):null,'updated_at'=>$now];
    if(isset($r['uploads'][$key]))$values+=$this->fileMetadata($r['uploads'][$key]);
    DB::table('pengaturan_dokumen_permohonan')->updateOrInsert(['permohonan_id'=>$id,'jenis'=>$key],$values+['created_at'=>$now]);
   }
   $letterId=DB::table('surat_persetujuan')->where('permohonan_id',$id)->value('surat_persetujuan_id');
   if(in_array('Surat Persetujuan',$r['documents'])&&!$letterId){abort_unless($draft,422,'Draft belum tersedia.');$letterId=DB::table('surat_persetujuan')->insertGetId($this->stamp(['permohonan_id'=>$id,'draft_surat_id'=>$draft->draft_surat_id,'dirilis_oleh'=>$actor,'dirilis_pada'=>$now]),'surat_persetujuan_id');}
   $invoice=DB::table('tagihan_retribusi')->where('permohonan_id',$id)->first();
   if($r['bill']&&!$invoice){
    $b=$r['bill'];$cfg=F::data($r,'skrd');$invoiceId=DB::table('tagihan_retribusi')->insertGetId($this->stamp(['permohonan_id'=>$id,'periode_retribusi'=>$cfg['period'].'-01','npwr'=>$cfg['npwr'],'keterangan_jatuh_tempo'=>$cfg['due'],'subtotal'=>$b['subtotal'],'persentase_potongan'=>$b['percent'],'nominal_potongan'=>$b['subtotal']-$b['total'],'jumlah_tagihan'=>$b['total'],'status'=>'terbit','diterbitkan_oleh'=>$actor,'diterbitkan_pada'=>$now,'catatan_penetapan'=>$b['note']??null]),'tagihan_retribusi_id');
    $rate=$this->tariff($r['location_id'],$r['activity'],$r['date']);
    foreach($cfg['rows'] as $i=>$line)DB::table('rincian_tagihan_retribusi')->insert($this->stamp(['tagihan_retribusi_id'=>$invoiceId,'urutan'=>$i+1,'tarif_retribusi_id'=>$rate?->tarif_retribusi_id,'kode_rekening'=>$line['code'],'uraian'=>$line['label'],'kuantitas'=>1,'satuan'=>'rincian','tarif_satuan'=>$line['amount'],'jumlah'=>$line['amount'],'alasan_tarif_khusus'=>$b['note']??null]));
    $invoice=DB::table('tagihan_retribusi')->where('tagihan_retribusi_id',$invoiceId)->first();
   }
   $payment=$invoice?DB::table('pembayaran')->where('tagihan_retribusi_id',$invoice->tagihan_retribusi_id)->first():null;
   if($invoice&&$r['bill']){
    $b=$r['bill'];
    $changed=abs((float)$invoice->jumlah_tagihan-$b['total'])>=0.005||abs((float)$invoice->subtotal-$b['subtotal'])>=0.005;
    $revision=count($r['skrd_versions']??[])>count($old['skrd_versions']??[]);
    if($changed)abort_unless(!$payment&&$revision&&strlen(trim(end($r['skrd_versions'])['reason']??''))>=5,422,'Perubahan nominal memerlukan revisi SKRD beralasan dan tagihan belum dibayar.');
    if($revision&&!$payment){
     $cfg=F::data($r,'skrd');$invoiceId=$invoice->tagihan_retribusi_id;
     DB::table('tagihan_retribusi')->where('tagihan_retribusi_id',$invoiceId)->update(['periode_retribusi'=>$cfg['period'].'-01','npwr'=>$cfg['npwr'],'keterangan_jatuh_tempo'=>$cfg['due'],'subtotal'=>$b['subtotal'],'persentase_potongan'=>$b['percent'],'nominal_potongan'=>$b['subtotal']-$b['total'],'jumlah_tagihan'=>$b['total'],'catatan_penetapan'=>$b['note']??null,'updated_at'=>$now]);
     foreach($cfg['rows'] as $i=>$line)DB::table('rincian_tagihan_retribusi')->updateOrInsert(['tagihan_retribusi_id'=>$invoiceId,'urutan'=>$i+1],['kode_rekening'=>$line['code'],'uraian'=>$line['label'],'kuantitas'=>1,'satuan'=>'rincian','tarif_satuan'=>$line['amount'],'jumlah'=>$line['amount'],'alasan_tarif_khusus'=>$b['note']??null,'created_at'=>$now,'updated_at'=>$now]);
     DB::table('rincian_tagihan_retribusi')->where('tagihan_retribusi_id',$invoiceId)->where('urutan','>',count($cfg['rows']))->delete();
     $invoice=DB::table('tagihan_retribusi')->where('tagihan_retribusi_id',$invoiceId)->first();
    }
   }
   if($r['payment']&&!$payment){
    abort_unless($invoice&&$invoice->jumlah_tagihan>0,422,'Tagihan harus positif untuk pembayaran.');
    $cfg=F::data($r,'stbp');$v=['tagihan_retribusi_id'=>$invoice->tagihan_retribusi_id,'jumlah_bayar'=>$invoice->jumlah_tagihan,'tanggal_bayar'=>$r['payment']['date'],'metode'=>$cfg['method']==='Tunai'?'tunai':'transfer','bank'=>$cfg['bank'],'nomor_rekening'=>$cfg['account'],'status'=>'terverifikasi','diinput_oleh'=>$actor,'diverifikasi_oleh'=>$actor,'diverifikasi_pada'=>$now];
    if(isset($r['uploads']['proof'])){foreach($this->fileMetadata($r['uploads']['proof']) as $k=>$v2){$map=['file_path'=>'bukti_path','nama_asli'=>'bukti_nama','ukuran_byte'=>'bukti_ukuran_byte','mime_type'=>'bukti_mime','sha256'=>'bukti_sha256'];$v[$map[$k]]=$v2;}$v['bukti_disk']='local';}
    $paymentId=DB::table('pembayaran')->insertGetId($this->stamp($v),'pembayaran_id');$payment=DB::table('pembayaran')->where('pembayaran_id',$paymentId)->first();
    DB::table('tagihan_retribusi')->where('tagihan_retribusi_id',$invoice->tagihan_retribusi_id)->update(['status'=>'lunas','updated_at'=>$now]);
   }
   $finalIds=[];
   foreach(['persetujuan'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $kind=>$label){
    if(!in_array($label,$r['documents']))continue;
    $owner=match($kind){'persetujuan'=>['surat_persetujuan_id'=>$letterId],'skrd'=>['tagihan_retribusi_id'=>$invoice->tagihan_retribusi_id],default=>['pembayaran_id'=>$payment->pembayaran_id]};
    $finalIds[$kind]=$this->document($r,$kind,$owner,$actor,$old);
   }
   if($r['status']==='selesai'&&!DB::table('serah_terima')->where('permohonan_id',$id)->exists()){
    $free=D::isFree($r); abort_unless($r['signed']&&($free||$payment)&&count($finalIds)===($free?1:4),422);
    $handover=DB::table('serah_terima')->insertGetId($this->stamp(['permohonan_id'=>$id,'nama_penerima'=>$r['recipient'],'diserahkan_oleh'=>$actor,'diserahkan_pada'=>$now,'kelengkapan_diperiksa'=>true]),'serah_terima_id');
    foreach($finalIds as $kind=>$version)if($kind!=='sts')DB::table('serah_terima_dokumen')->insert(['serah_terima_id'=>$handover,'versi_dokumen_id'=>$version]);
   }
   foreach(array_slice($r['history'],$r['_history_count']??0) as $event)DB::table('riwayat_disposisi')->insert(['permohonan_id'=>$id,'aktor_id'=>$actor,'role_snapshot'=>$event['role'],'status_sebelum'=>$p->status,'status_sesudah'=>$r['status'],'aksi'=>mb_substr($event['action'],0,50),'catatan'=>$event['note'],'terjadi_pada'=>$event['at'],'draft_surat_id'=>$draft?->draft_surat_id]);
   DB::table('audit_aktivitas')->insert(['aktor_id'=>$actor,'aksi'=>'permohonan_disimpan','jenis_objek'=>'permohonan','objek_id'=>$id,'sebelum'=>$this->json(['status'=>$p->status,'lock_version'=>$p->lock_version]),'sesudah'=>$this->json(['status'=>$r['status'],'lock_version'=>$p->lock_version+1]),'terjadi_pada'=>$now]);
  });
 }
 private function document(array $r,string $kind,array $owner,int $actor,array $old): int {
  $doc=DB::table('dokumen_terbit')->where($owner)->where('jenis',$kind)->first();$key=$kind==='persetujuan'?'surat':$kind;
  if(isset($r['signature']['at']))$r['signature']['at']=date('Y-m-d H:i:s',strtotime($r['signature']['at']));
  $snapshot=$kind==='persetujuan'?['schema_version'=>1,'letter'=>D::letter($r),'signature'=>$r['signature']??null,'identity'=>array_intersect_key($r,array_flip(['name','pic','date','days','activity','location_id']))]:F::data($r,$kind,$kind!=='skrd');
  if(!$doc){$docId=DB::table('dokumen_terbit')->insertGetId($this->stamp($owner+['jenis'=>$kind,'nomor_dokumen'=>$kind==='persetujuan'?D::letter($r)['number']:$snapshot['number'],'tahun_nomor'=>(int)date('Y'),'status'=>'terbit']),'dokumen_terbit_id');$doc=DB::table('dokumen_terbit')->where('dokumen_terbit_id',$docId)->first();}
  $latest=DB::table('versi_dokumen')->where('dokumen_terbit_id',$doc->dokumen_terbit_id)->orderByDesc('versi')->first();
  $source=$r['document_sources'][$key]??'template';$uploaded=$r['uploads'][$key]??null;
  if($kind==='persetujuan'&&!empty($r['manual_letter'])){$source='upload';$uploaded=$r['manual_letter'];}
  $fingerprint=$uploaded?$this->fileMetadata($uploaded)['sha256']:null;
  if($latest&&json_decode($latest->data_snapshot,true)==$snapshot&&($source!=='upload'||$latest->sha256===$fingerprint))return (int)$latest->versi_dokumen_id;
  $vars=['r'=>$r,'locations'=>collect($this->locations())->keyBy('id'),'docKey'=>$kind];
  if($source==='upload'&&$uploaded){$meta=$this->fileMetadata($uploaded);}else{
   $html=view($kind==='persetujuan'?'dokumen.letter':'dokumen.financial',$vars)->render();
   $css=file_get_contents(public_path('css/siptr-blade.css'));
   $html='<!doctype html><html lang="id"><meta charset="utf-8"><title>Dokumen demo</title><style>'.$css.'</style><body><p>DEMO — BUKAN DOKUMEN RESMI</p>'.$html.'</body></html>';
   $dir=storage_path('app/demo-uploads');if(!is_dir($dir))mkdir($dir,0775,true);$name='doc-'.bin2hex(random_bytes(16)).'.html';file_put_contents($dir.'/'.$name,$html);$meta=$this->fileMetadata(['stored'=>$name,'name'=>$kind.'-'.$r['tracking'].'.html']);
  }
  $version=DB::table('versi_dokumen')->insertGetId($this->stamp(['dokumen_terbit_id'=>$doc->dokumen_terbit_id,'template_surat_id'=>$this->templateId($kind,$actor),'versi'=>($latest->versi??0)+1,'tanggal_dokumen'=>$snapshot['issued_date']??D::letter($r)['date'],'sumber'=>$source==='upload'?'unggah':'sistem','data_snapshot'=>$this->json($snapshot),'disk'=>'local','status'=>'final','dibuat_oleh'=>$actor,'alasan_revisi'=>end($r['history'])['note']??null,'final_pada'=>now()]+$meta),'versi_dokumen_id');
  if($kind==='persetujuan'&&$r['signed']){
   $previousSig=$latest?DB::table('tanda_tangan_dokumen')->where('versi_dokumen_id',$latest->versi_dokumen_id)->where('status','berhasil')->first():null;
   $signer=$r['signature']['signer_id']??$previousSig?->pengguna_id??$actor;
   $user=DB::table('pengguna')->where('pengguna_id',$signer)->first();
   DB::table('tanda_tangan_dokumen')->insert($this->stamp(['versi_dokumen_id'=>$version,'urutan'=>1,'pengguna_id'=>$signer,'nama_snapshot'=>$r['signature']['name']??$user->nama,'nip_snapshot'=>$user->nip,'jabatan_snapshot'=>'Kepala Dinas Pariwisata','metode'=>$r['signature']['method']??'simulasi','status'=>'berhasil','ditandatangani_pada'=>$r['signature']['at'],'referensi_penyedia'=>$r['signature']['reference'],'hash_dokumen_ditandatangani'=>$meta['sha256']]));
  }
  return $version;
 }
 public function updateRate(int $id,int $amount,int $actor): void {
  DB::transaction(function()use($id,$amount,$actor){
   abort_unless(DB::table('lokasi')->where('lokasi_id',$id)->lockForUpdate()->first(),404);
   foreach(DB::table('jenis_kegiatan')->where('aktif',true)->get() as $kind){
    $q=DB::table('tarif_retribusi')->where('lokasi_id',$id)->where('jenis_kegiatan_id',$kind->jenis_kegiatan_id);
    $today=$q->clone()->where('berlaku_mulai',date('Y-m-d'))->first();
    if($today){abort_if(DB::table('rincian_tagihan_retribusi')->where('tarif_retribusi_id',$today->tarif_retribusi_id)->exists(),422,'Tarif hari ini sudah dirujuk; perubahan membutuhkan periode baru.');$q->clone()->where('tarif_retribusi_id',$today->tarif_retribusi_id)->update(['tarif'=>$amount,'updated_at'=>now()]);continue;}
    $q->clone()->whereNull('berlaku_sampai')->update(['berlaku_sampai'=>date('Y-m-d',strtotime('-1 day')),'updated_at'=>now()]);
    DB::table('tarif_retribusi')->insert($this->stamp(['lokasi_id'=>$id,'jenis_kegiatan_id'=>$kind->jenis_kegiatan_id,'tarif'=>$amount,'satuan'=>'hari','berlaku_mulai'=>date('Y-m-d'),'ditetapkan_oleh'=>$actor]));
   }
   $this->audit($actor,'tarif_diubah','lokasi',$id,null,['tarif_per_hari'=>$amount]);
  });
 }
 private function audit(int $actor,string $action,string $type,int $id,?array $before,?array $after,?string $reason=null): void {
  DB::table('audit_aktivitas')->insert(['aktor_id'=>$actor,'aksi'=>$action,'jenis_objek'=>$type,'objek_id'=>$id,'sebelum'=>$before===null?null:json_encode($before),'sesudah'=>$after===null?null:json_encode($after),'alasan'=>$reason,'terjadi_pada'=>now()]);
 }
 private function sameName(string $a,string $b): bool { $n=fn($v)=>mb_strtolower(preg_replace('/\s+/u',' ',trim($v))); return $n($a)===$n($b); }
 public function activities(bool $onlyActive=true): array {
  $q=DB::table('jenis_kegiatan')->orderBy('nama'); if($onlyActive)$q->where('aktif',true);
  return $q->get()->map(fn($k)=>['id'=>(int)$k->jenis_kegiatan_id,'name'=>$k->nama,'active'=>(bool)$k->aktif])->all();
 }
 public function createLocation(array $d,int $actor): int {
  return DB::transaction(function()use($d,$actor){
   $name=trim($d['name']);
   abort_if(DB::table('jenis_kegiatan')->where('aktif',true)->doesntExist(),422,'Tambahkan minimal satu jenis kegiatan aktif lebih dulu agar tarif lokasi dapat disimpan.');
   abort_if(DB::table('lokasi')->pluck('nama_lokasi')->contains(fn($n)=>$this->sameName($n,$name)),422,'Nama lokasi sudah ada.');
   $id=(int)DB::table('lokasi')->insertGetId($this->stamp(['nama_lokasi'=>$name,'status_aktif'=>true,'dikelola_dispar'=>(bool)$d['managed'],'butuh_rekomendasi_dlh'=>(bool)$d['dlh']]),'lokasi_id');
   $this->updateRate($id,(int)$d['rate'],$actor);
   $this->audit($actor,'lokasi_dibuat','lokasi',$id,null,['nama'=>$name,'dikelola_dispar'=>(bool)$d['managed'],'butuh_rekomendasi_dlh'=>(bool)$d['dlh'],'tarif_per_hari'=>(int)$d['rate']]);
   return $id;
  });
 }
 public function updateLocation(int $id,array $d,int $actor): void {
  DB::transaction(function()use($id,$d,$actor){
   $old=DB::table('lokasi')->where('lokasi_id',$id)->lockForUpdate()->first(); abort_unless($old,404);
   $name=trim($d['name']);
   abort_if(DB::table('lokasi')->where('lokasi_id','!=',$id)->pluck('nama_lokasi')->contains(fn($n)=>$this->sameName($n,$name)),422,'Nama lokasi sudah dipakai lokasi lain.');
   $new=['nama_lokasi'=>$name,'status_aktif'=>(bool)$d['active'],'dikelola_dispar'=>(bool)$d['managed'],'butuh_rekomendasi_dlh'=>(bool)$d['dlh']];
   DB::table('lokasi')->where('lokasi_id',$id)->update($new+['updated_at'=>now()]);
   $this->audit($actor,'lokasi_diubah','lokasi',$id,['nama'=>$old->nama_lokasi,'aktif'=>(bool)$old->status_aktif,'dikelola_dispar'=>(bool)$old->dikelola_dispar,'butuh_rekomendasi_dlh'=>(bool)($old->butuh_rekomendasi_dlh??false)],['nama'=>$name,'aktif'=>(bool)$d['active'],'dikelola_dispar'=>(bool)$d['managed'],'butuh_rekomendasi_dlh'=>(bool)$d['dlh']]);
  });
 }
 public function deleteLocation(int $id,int $actor): void {
  DB::transaction(function()use($id,$actor){
   $old=DB::table('lokasi')->where('lokasi_id',$id)->lockForUpdate()->first(); abort_unless($old,404);
   abort_if(DB::table('permohonan')->where('lokasi_id',$id)->exists(),422,'Lokasi sudah dipakai permohonan dan tidak dapat dihapus. Nonaktifkan saja agar tidak muncul di formulir.');
   abort_if(DB::table('rincian_tagihan_retribusi as r')->join('tarif_retribusi as t','t.tarif_retribusi_id','=','r.tarif_retribusi_id')->where('t.lokasi_id',$id)->exists(),422,'Tarif lokasi sudah dirujuk tagihan dan tidak dapat dihapus. Nonaktifkan saja.');
   DB::table('tarif_retribusi')->where('lokasi_id',$id)->delete();
   DB::table('lokasi')->where('lokasi_id',$id)->delete();
   $this->audit($actor,'lokasi_dihapus','lokasi',$id,['nama'=>$old->nama_lokasi],null);
  });
 }
 public function addActivity(string $name,int $actor): int {
  return DB::transaction(function()use($name,$actor){
   $name=trim($name);
   abort_if(DB::table('jenis_kegiatan')->pluck('nama')->contains(fn($n)=>$this->sameName($n,$name)),422,'Jenis kegiatan sudah ada.');
   $id=(int)DB::table('jenis_kegiatan')->insertGetId($this->stamp(['nama'=>$name,'aktif'=>true]),'jenis_kegiatan_id');
   foreach($this->locations() as $loc){
    DB::table('tarif_retribusi')->insert($this->stamp(['lokasi_id'=>$loc['id'],'jenis_kegiatan_id'=>$id,'tarif'=>(int)$loc['rate'],'satuan'=>'hari','berlaku_mulai'=>date('Y-m-d'),'ditetapkan_oleh'=>$actor]));
   }
   $this->audit($actor,'kegiatan_dibuat','jenis_kegiatan',$id,null,['nama'=>$name]);
   return $id;
  });
 }
 public function setActivityActive(int $id,bool $active,int $actor): void {
  DB::transaction(function()use($id,$active,$actor){
   $old=DB::table('jenis_kegiatan')->where('jenis_kegiatan_id',$id)->lockForUpdate()->first(); abort_unless($old,404);
   if(!$active)abort_if(DB::table('jenis_kegiatan')->where('aktif',true)->where('jenis_kegiatan_id','!=',$id)->doesntExist(),422,'Minimal satu jenis kegiatan harus aktif.');
   DB::table('jenis_kegiatan')->where('jenis_kegiatan_id',$id)->update(['aktif'=>$active,'updated_at'=>now()]);
   $this->audit($actor,$active?'kegiatan_diaktifkan':'kegiatan_dinonaktifkan','jenis_kegiatan',$id,['aktif'=>(bool)$old->aktif],['aktif'=>$active]);
  });
 }
}
