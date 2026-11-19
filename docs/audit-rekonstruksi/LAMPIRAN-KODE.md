# Lampiran kode sebelum–sesudah

Sumber: ZIP lama dan snapshot 24 Oktober 2026 yang dicatat manifest. Ini perubahan bersih antara dua kondisi, bukan urutan setiap revisi. `-` kode lama, `+` kode sesudah; `@@` nomor baris saat snapshot. File baru menggunakan `/dev/null`. Seluruh 20 bagian patch disertakan tanpa merangkum baris kode.

## app/Http/Controllers/DemoController.php

```diff
diff --git a/app/Http/Controllers/DemoController.php b/app/Http/Controllers/DemoController.php
--- a/app/Http/Controllers/DemoController.php
+++ b/app/Http/Controllers/DemoController.php
@@ -16,6 +16,14 @@
         if (!session()->has('demo.requests')) {
             session(['demo.requests'=>D::requests(),'demo.locations'=>D::locations(),'demo.users'=>D::users()]);
         }
+        if (!session('demo.enriched_v2')) {
+            $existing=collect(session('demo.requests'))->keyBy('id');
+            foreach(D::requests() as $sample) {
+                if(!$existing->has($sample['id'])) $existing->put($sample['id'],$sample);
+                else { $old=$existing[$sample['id']]; foreach(['templates','signature'] as $key) if(empty($old[$key])&&isset($sample[$key])) $old[$key]=$sample[$key]; $existing->put($sample['id'],$old); }
+            }
+            session(['demo.requests'=>$existing->values()->all(),'demo.enriched_v2'=>true]);
+        }
         session(['demo.requests'=>array_map(function($r) { $r['tracking']=preg_replace('/^PKP-/', 'DPR-', $r['tracking']); return $r; },session('demo.requests'))]);
     }
     private function role(): string { return session('demo.role',''); }
@@ -74,7 +82,7 @@
             if($q->boolean('mine')&&(D::OWNER[$r['status']]??null)!==$this->role()) return false;
             if($q->query('sla')==='overdue'&&D::elapsed($r)<=3) return false;
             if($q->query('sla')==='due'&&D::elapsed($r)!==3) return false;
-            $date=CarbonImmutable::parse($r['submitted']); $today=CarbonImmutable::today();
+            $date=CarbonImmutable::parse($kind==='arsip'?($r['completed']??$r['submitted']):$r['submitted']); $today=CarbonImmutable::today();
             $period=$q->query('period','all');
             if($period==='today'&&!$date->isSameDay($today)) return false;
             if($period==='week'&&($date->lt($today->subDays(6))||$date->gt($today))) return false;
@@ -83,6 +91,23 @@
             return true;
         });
         $rows=($q->query('sort')==='oldest'?$rows->sortBy('id'):$rows->sortByDesc('id'))->values();
+        if($kind==='arsip') {
+            $rows=($q->query('sort')==='oldest'?$rows->sortBy('completed'):$rows->sortByDesc('completed'))->values();
+            return $this->view('arsip.index',['title'=>'Arsip digital','page'=>'arsip','kind'=>$kind,'rows'=>$rows,'r'=>$rows->firstWhere('id',(int)$q->query('selected'))??$rows->first()]);
+        }
+        if($kind==='riwayat') {
+            $events=collect(session('demo.requests'))->filter(fn($r)=>empty($r['deleted_at'])||in_array($this->role(),['sekretariat','admin']))->flatMap(function($r) { return collect($r['history'])->map(fn($e)=>array_merge($e,['record'=>$r])); });
+            $events=$events->filter(function($e) use($q) {
+                if($q->filled('q')&&!str_contains(mb_strtolower($e['record']['name'].' '.$e['record']['tracking'].' '.$e['action'].' '.$e['note']),mb_strtolower($q->q))) return false;
+                if($q->filled('actor')&&$e['role']!==$q->actor) return false;
+                $date=CarbonImmutable::parse($e['at']); $today=CarbonImmutable::today();
+                return match($q->query('period','all')) { 'today'=>$date->isSameDay($today),'week'=>$date->gte($today->subDays(6))&&$date->lte($today->endOfDay()),'month'=>$date->format('Y-m')===$today->format('Y-m'),'lastmonth'=>$date->format('Y-m')===$today->subMonthNoOverflow()->format('Y-m'),default=>true };
+            });
+            $events=($q->query('sort')==='oldest'?$events->sortBy('at'):$events->sortByDesc('at'))->values();
+            $number=max(1,(int)$q->query('page',1));
+            $logs=new \Illuminate\Pagination\LengthAwarePaginator($events->forPage($number,20)->values(),$events->count(),20,$number,['path'=>$q->url(),'query'=>$q->query()]);
+            return $this->view('riwayat.index',['title'=>'Log & riwayat','page'=>'riwayat','logs'=>$logs]);
+        }
         if(in_array($kind,['draft','verifikasi','rilis','retribusi','tanda-tangan','serah-terima'])) {
             $selected=$rows->firstWhere('id',(int)$q->query('selected'))??$rows->first();
             return $this->view('permohonan.work',['title'=>$map[$kind][0],'page'=>$kind,'kind'=>$kind,'queue'=>$rows,'r'=>$selected,'canAct'=>$selected&&(D::OWNER[$selected['status']]??'')===$this->role(),'canSeeFiles'=>$selected&&D::canSeeFiles($this->role(),$selected)]);
@@ -132,7 +157,15 @@
             case 'revise': $r['status']='draft'; $r['version']++; $label='Draft dikembalikan untuk revisi'; break;
             case 'approve': $r['status']=['masuk'=>'draft','review_draft'=>'verifikasi','verifikasi'=>'rilis'][$from]; $label=['masuk'=>'Review berkas disetujui','review_draft'=>'Draft disetujui Kabid','verifikasi'=>'Draft diverifikasi Sekretaris'][$from]; break;
             case 'save': case 'send':
-                $q->validate(['draft'=>'required|string|min:30|max:15000']); $r['draft']=$q->draft;
+                if($q->has('letter')) {
+                    $rules=['letter'=>'required|array'];
+                    foreach(['number','subject','attachment'] as $key) $rules['letter.'.$key]='required|string|max:200';
+                    $rules['letter.date']='required|date_format:Y-m-d';
+                    foreach(['opening','body','conditions','closing'] as $key) $rules['letter.'.$key]='required|string|max:5000';
+                    $valid=$q->validate($rules);
+                    $r['letter']=array_intersect_key($valid['letter'],D::letter($r));
+                    $r['draft']=implode("\n\n",array_intersect_key($r['letter'],array_flip(['opening','body','conditions','closing'])));
+                } else { $q->validate(['draft'=>'required|string|min:30|max:15000']); $r['draft']=$q->draft; }
                 if($action==='send') $r['status']='review_draft';
                 $label=$action==='send'?'Draft dikirim ke Kabid':'Draft disimpan'; break;
             case 'release': $r['status']='tagihan'; $r['documents'][]='Surat Persetujuan'; $label='Surat dirilis untuk proses retribusi'; break;
@@ -140,7 +173,9 @@
                 if($r['discount']==='diajukan') return back()->withErrors(['action'=>'Keputusan keringanan belum tersedia. Penetapan ditunda. Gunakan contoh pengajuan yang sudah disetujui untuk demo diskon; kewenangan keputusan belum diasumsikan.']);
                 $loc=collect(session('demo.locations'))->firstWhere('id',$r['location_id']);
                 $pct=$r['discount']==='disetujui'&&$loc['managed']?25:0;
-                $r['bill']=['rate'=>$loc['rate'],'subtotal'=>$loc['rate']*$r['days'],'percent'=>$pct,'total'=>$loc['rate']*$r['days']*(1-$pct/100)];
+                $q->validate(['rate'=>'nullable|numeric|min:0|max:100000000','rate_reason'=>'required_with:rate|string|max:500']);
+                $rate=$q->filled('rate')?(float)$q->rate:$loc['rate'];
+                $r['bill']=['rate'=>$rate,'subtotal'=>$rate*$r['days'],'percent'=>$pct,'total'=>$rate*$r['days']*(1-$pct/100),'note'=>$q->rate_reason];
                 $r['documents'][]='SKRD'; $r['status']='bayar'; $label='SKRD demo diterbitkan'; break;
             case 'pay':
                 $q->validate(['payment_checked'=>'accepted','payment_date'=>'required|date|before_or_equal:today']);
@@ -148,13 +183,14 @@
                 $r['payment']=['date'=>$q->payment_date,'proof'=>$r['uploads']['proof']['name']??'bukti_pembayaran_contoh.pdf']; $r['status']='ttd';
                 $r['documents']=array_unique(array_merge($r['documents'],['STBP','STS'])); $label='Pembayaran demo dikonfirmasi; STBP dan STS demo dibuat'; break;
             case 'sign':
-                $q->validate(['confirmed'=>'accepted']); abort_unless($r['payment'],422); $r['signed']=true; $r['status']='serah'; $label='Simulasi tanda tangan selesai'; break;
+                $q->validate(['confirmed'=>'accepted']); abort_unless($r['payment'],422); $r['signed']=true; $r['signature']=['name'=>'Drs. Aditya Pratama (fiktif)','at'=>date('Y-m-d H:i'),'reference'=>'SIM-'.$r['tracking']]; $r['status']='serah'; $label='Simulasi tanda tangan selesai'; break;
             case 'handover':
                 $q->validate(['confirmed'=>'accepted','recipient'=>'required|string|max:100']);
                 abort_unless($r['signed']&&count(array_intersect(['Surat Persetujuan','SKRD','STBP'],$r['documents']))===3,422);
                 $r['recipient']=$q->recipient; $r['completed']=date('Y-m-d'); $r['status']='selesai'; $label='Dokumen diserahkan; permohonan selesai'; break;
         }
         $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>$label,'note'=>$note]; $this->save($r);
+        if($action==='save') return redirect()->route('listing',['kind'=>'draft','selected'=>$id])->with('success','Draft '.$r['tracking'].' berhasil disimpan. Anda tetap berada di editor; draft belum dikirim ke Kabid.');
         return redirect()->route('detail',$id)->with('success',$label.'. Perubahan hanya tersimpan di sesi demo ini.');
     }
     public function document(int $id,string $type)
@@ -162,17 +198,28 @@
         $r=$this->record($id); $types=['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'];
         abort_unless(isset($types[$type])&&in_array($types[$type],$r['documents']),404);
         abort_unless(D::canSeeFiles($this->role(),$r),403);
-        return $this->view('dokumen.preview',['r'=>$r,'type'=>$types[$type],'title'=>'Pratinjau dokumen demo','page'=>'arsip']);
+        return $this->view('dokumen.preview',['r'=>$r,'type'=>$types[$type],'title'=>'Pratinjau dokumen demo','page'=>'pratinjau']);
     }
     public function template(Request $q,int $id,string $type)
     {
         $this->requireRole(['bendahara']); $r=$this->record($id);
         abort_unless(in_array($type,['skrd','sts','stbp'])&&in_array($r['status'],['tagihan','bayar','ttd']),403);
         if($q->isMethod('post')) {
-            $data=$q->validate(['description'=>'required|string|max:3000','address'=>'nullable|string|max:300','bank'=>'nullable|string|max:100','account'=>'nullable|string|max:100','due'=>'nullable|string|max:200']);
-            $r['templates'][$type]=$data; $r['document_sources'][$type]='template';
+            $data=$q->validate(['description'=>'required|string|max:3000','address'=>'nullable|string|max:300','bank'=>'nullable|string|max:100','account'=>'nullable|string|max:100','due'=>'nullable|string|max:200','period'=>'nullable|date_format:Y-m','issued_date'=>'nullable|date','npwr'=>'nullable|string|max:100',
+                'number'=>'nullable|string|max:160','payer'=>'nullable|string|max:200','method'=>'nullable|in:Tunai,Non Tunai',
+                'signer_rank'=>'nullable|string|max:100','signer_name'=>'nullable|string|max:150','signer_nip'=>'nullable|string|max:30','signer_title'=>'nullable|string|max:200',
+                'authority_name'=>'nullable|string|max:150','authority_nip'=>'nullable|string|max:30','attention'=>'nullable|string|max:2000',
+                'rows'=>'sometimes|required|array|min:1|max:20','rows.*.code'=>'required|string|max:100',
+                'rows.*.label'=>'required|string|max:300','rows.*.amount'=>'required|integer|min:0|max:999999999999']);
+            if(isset($data['rows'])) {
+                $finance=\App\Support\FinanceDocument::data($r,$type);
+                $expected=$type==='skrd'?$finance['base']:$finance['total'];
+                if(abs(array_sum(array_column($data['rows'],'amount'))-$expected)>0.01)
+                    throw \Illuminate\Validation\ValidationException::withMessages(['rows'=>'Jumlah rincian harus sama dengan '.\App\Support\DemoData::rupiah($expected).'. Nominal mengikuti penetapan retribusi; ubah penetapan sebelum diterbitkan, bukan melalui template.']);
+            }
+            $r['templates'][$type]=array_replace($r['templates'][$type]??[],$data); $r['document_sources'][$type]='template';
             $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Template '.strtoupper($type).' diperbarui','note'=>'Uraian untuk permohonan ini; nominal penetapan tidak berubah.']; $this->save($r);
-            return redirect()->route('listing',['kind'=>'retribusi','selected'=>$id])->with('success','Template '.strtoupper($type).' disimpan untuk permohonan ini.');
+            return redirect()->to('/permohonan/'.$id.'/template/'.$type)->with('success','Template '.strtoupper($type).' disimpan untuk permohonan ini.');
         }
         return $this->view('dokumen.editor',['r'=>$r,'docKey'=>$type,'title'=>'Edit Template '.strtoupper($type),'page'=>'retribusi']);
     }
```

## app/Support/DemoData.php

```diff
diff --git a/app/Support/DemoData.php b/app/Support/DemoData.php
--- a/app/Support/DemoData.php
+++ b/app/Support/DemoData.php
@@ -70,6 +70,32 @@
                 'history'=>[['at'=>$submitted->format('Y-m-d').' 09:00','role'=>'sekretariat','action'=>'Permohonan dicatat','note'=>'Data contoh untuk demonstrasi.'],['at'=>$submitted->format('Y-m-d').' 10:15','role'=>self::OWNER[$status]??'sekretariat','action'=>'Posisi awal demo: '.self::STATUS[$status],'note'=>'Riwayat sebelum posisi ini diringkas untuk data contoh.']],
             ];
         }
+        $extraNames=['Yayasan Pendidikan Cendekia','Komunitas Lari Babel','Universitas Bangka Belitung (demo)','Sanggar Tari Melati','Panitia Pekan Literasi','Forum Pemuda Rangkui','Yayasan Bina Anak','Komunitas Bahari Sejahtera','Panitia Pameran UMKM','Sanggar Musik Nusantara','Yayasan Lentera Bangka','Forum Seni Kota'];
+        foreach (array_values($rows) as $i=>$source) {
+            $source['id']=330+$i; $source['tracking']='DPR-'.date('Y').'-'.str_pad((string)(330+$i),4,'0',STR_PAD_LEFT); $source['name']=$extraNames[$i];
+            $rows[]=$source;
+        }
+        foreach ($rows as $i=>&$row) {
+            $row['date']=$today->startOfMonth()->addDays(2+($i%12)*2)->toDateString();
+            $row['draft']='';
+            $row['draft']=self::draft($row,self::locations()[($row['location_id']-1)]['name']);
+            foreach(['skrd','sts','stbp'] as $type) $row['templates'][$type]=[
+                'address'=>'Jl. Contoh Bahari No. '.($i+1).', Pangkalpinang (alamat fiktif)',
+                'npwr'=>'DEMO-'.($i+100),'period'=>$today->format('Y-m'),'issued_date'=>$today->toDateString(),
+                'due'=>$today->addDays(7)->toDateString(),'bank'=>'Bank Contoh','account'=>'DEMO-000123',
+                'description'=>'Pelaksanaan '.$row['activity'].' oleh '.$row['name']
+            ];
+            if($row['signed']) $row['signature']=['name'=>'Drs. Aditya Pratama (fiktif)','at'=>$row['submitted'].' 12:00','reference'=>'SIM-'.$row['tracking']];
+            $steps=['masuk','draft','review_draft','verifikasi','rilis','tagihan','bayar','ttd','serah','selesai'];
+            $row['history']=[];
+            $last=array_search($row['status'],$steps,true);
+            foreach(array_slice($steps,0,$last===false?1:$last+1) as $n=>$step) $row['history'][]=[
+                'at'=>$row['submitted'].' '.sprintf('%02d:%02d',8+intdiv($n,2),($n%2)*30),
+                'role'=>self::OWNER[$step]??'sekretariat','action'=>self::STATUS[$step],
+                'note'=>$n?'Berkas dan hasil tahap sebelumnya telah diperiksa (skenario demo).':'Permohonan beserta empat berkas persyaratan diterima.'
+            ];
+        }
+        unset($row);
         return $rows;
     }
 
@@ -88,8 +114,23 @@
         if ($r['status']==='ditolak') return false;
         return $role==='petugas'?$stage>=1:($role==='bendahara'&&$stage>=5);
     }
+    public static function letter(array $r): array
+    {
+        $legacy=empty($r['letter'])&&!empty($r['draft'])&&!str_contains($r['draft'],'Dokumen ini merupakan contoh draft')?$r['draft']:null;
+        return array_merge([
+            'number'=>'DEMO/'.$r['tracking'].'/DISPAR/'.date('Y'),
+            'date'=>substr($r['signature']['at']??$r['submitted'],0,10),
+            'subject'=>'Persetujuan penyelenggaraan kegiatan di tempat rekreasi',
+            'attachment'=>count($r['files']).' berkas persyaratan',
+            'opening'=>'Dengan hormat, berdasarkan permohonan dan pemeriksaan kelengkapan administrasi, berikut identitas kegiatan yang diajukan:',
+            'body'=>$legacy??'Pada prinsipnya penyelenggaraan kegiatan tersebut dapat disetujui setelah seluruh tahapan verifikasi, kewajiban retribusi, dan persetujuan pejabat berwenang diselesaikan.',
+            'conditions'=>"1. Menjaga kebersihan, keamanan, dan ketertiban lokasi.\n2. Mengembalikan fasilitas dalam kondisi semula.\n3. Memenuhi kewajiban retribusi sesuai SKRD.\n4. Berkoordinasi dengan petugas lokasi sebelum kegiatan.",
+            'closing'=>'Demikian surat ini disusun untuk dipergunakan sebagaimana mestinya.',
+        ],$r['letter']??[]);
+    }
+
     public static function draft(array $r,string $location): string
     {
-        return $r['draft']?:"Sehubungan dengan permohonan dari {$r['name']}, pada prinsipnya kegiatan {$r['activity']} di {$location} pada ".self::date($r['date'])." selama {$r['days']} hari dapat diproses lebih lanjut.\n\nPemohon wajib menjaga kebersihan, keamanan, dan ketertiban serta memenuhi kewajiban retribusi sesuai penetapan.\n\nDokumen ini merupakan contoh draft untuk demonstrasi, bukan surat persetujuan yang sah.";
+        return $r['draft']?:"Dengan hormat,\n\nSehubungan dengan permohonan dari {$r['name']}, pada prinsipnya kegiatan {$r['activity']} di {$location} pada ".self::date($r['date'])." selama {$r['days']} hari dapat diproses lebih lanjut.\n\nKetentuan pelaksanaan:\n1. Pemohon menjaga kebersihan, keamanan, dan ketertiban lokasi.\n2. Fasilitas dikembalikan dalam kondisi semula setelah kegiatan.\n3. Kewajiban retribusi dipenuhi sesuai SKRD yang diterbitkan.\n4. Penanggung jawab berkoordinasi dengan petugas lokasi sebelum kegiatan.\n\nDemikian surat ini disusun untuk dipergunakan dalam demonstrasi alur pelayanan.\n\nDokumen ini merupakan contoh draft untuk demonstrasi, bukan surat persetujuan yang sah.";
     }
 }
```

## app/Support/FinanceDocument.php

```diff
diff --git a/app/Support/FinanceDocument.php b/app/Support/FinanceDocument.php
new file mode 100644
--- /dev/null
+++ b/app/Support/FinanceDocument.php
@@ -0,0 +1,43 @@
+<?php
+namespace App\Support;
+
+class FinanceDocument
+{
+    public static function data(array $r, string $type): array
+    {
+        $loc=DemoData::locations()[$r['location_id']];
+        $base=$r['bill']['subtotal']??($loc['rate']*$r['days']);
+        $percent=$r['bill']['percent']??($r['discount']==='disetujui'&&$loc['managed']?25:0);
+        $total=$r['bill']['total']??round($base*(1-$percent/100));
+        $defaults=[
+            'number'=>'DEMO/'.$r['tracking'].'/'.strtoupper($type),
+            'payer'=>$r['pic'].' / '.$r['name'], 'address'=>'Alamat contoh, Pangkalpinang',
+            'npwr'=>'—', 'period'=>substr($r['date'],0,7),
+            'issued_date'=>$r['payment']['date']??date('Y-m-d'),
+            'due'=>'2 hari sebelum pelaksanaan kegiatan',
+            'bank'=>'Bank contoh (demo)', 'account'=>'0000000000', 'method'=>'Non Tunai',
+            'description'=>'Pelaksanaan kegiatan '.$r['activity'].' di '.$loc['name'],
+            'signer_rank'=>'Penata Tingkat I / III.d (demo)', 'signer_name'=>'Pejabat Contoh (fiktif)', 'signer_nip'=>'000000000000000000',
+            'signer_title'=>$type==='skrd'?'Kabid Destinasi Pariwisata dan Industri Pariwisata':'Bendahara Penerimaan',
+            'authority_name'=>'Kepala Dinas Contoh (fiktif)', 'authority_nip'=>'000000000000000000',
+            'attention'=>"Harap penyetoran dilakukan pada bank / Bendahara Penerimaan Dinas Pariwisata Kota Pangkalpinang.\nPembayaran dilakukan sesuai tanggal jatuh tempo yang tercantum pada ketetapan.",
+            'rows'=>[['code'=>'4.1.02.02.009.00001','label'=>'Retribusi Pelayanan Tempat Rekreasi dan Olahraga','amount'=>$type==='skrd'?$base:$total]],
+        ];
+        $saved=array_filter($r['templates'][$type]??[],fn($v)=>$v!==null&&$v!=='');
+        return array_replace($defaults,$saved,['base'=>$base,'percent'=>$percent,'total'=>$total,'words'=>self::words((int)round($total)).' rupiah']);
+    }
+
+    public static function words(int $n): string
+    {
+        $w=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
+        if($n<12)return $w[$n];
+        if($n<20)return self::words($n-10).' belas';
+        if($n<100)return self::words(intdiv($n,10)).' puluh'.($n%10?' '.self::words($n%10):'');
+        if($n<200)return 'seratus'.($n%100?' '.self::words($n%100):'');
+        if($n<1000)return self::words(intdiv($n,100)).' ratus'.($n%100?' '.self::words($n%100):'');
+        if($n<2000)return 'seribu'.($n%1000?' '.self::words($n%1000):'');
+        foreach([1000000000000=>'triliun',1000000000=>'miliar',1000000=>'juta',1000=>'ribu'] as $unit=>$label)
+            if($n>=$unit)return self::words(intdiv($n,$unit)).' '.$label.($n%$unit?' '.self::words($n%$unit):'');
+        return '';
+    }
+}
```

## public/css/siptr-blade.css

```diff
diff --git a/public/css/siptr-blade.css b/public/css/siptr-blade.css
--- a/public/css/siptr-blade.css
+++ b/public/css/siptr-blade.css
@@ -28,3 +28,36 @@
 .receipt-stub{border-top:2px dashed #777;margin-top:30px;padding-top:15px}.receipt-stub>span{float:right;font-size:10px}
 @media(max-width:767px){.permit-progress{display:block;min-width:0}.permit-progress li{display:flex;align-items:flex-start;gap:14px;padding:0 0 20px}.permit-progress li:last-child{padding-bottom:0}.permit-progress li:not(:last-child)::after{left:12px;top:27px;bottom:0;width:2px;height:auto;right:auto}.step-node{flex-shrink:0}.step-label{margin:3px 0 0;font-size:12px}.financial-preview .financial-paper{min-width:600px}.financial-paper{padding:10px}.financial-body{padding:10px}.receipt-table{font-size:10px}}
 @media print{.financial-paper{max-width:none;border:0;padding:0;font-size:11px}.financial-heading,.receipt-table{break-inside:avoid}.financial-preview{max-height:none;overflow:visible;padding:0}.receipt-signatures{break-inside:avoid}}
+
+/* Compact working queues and printable documents */
+.col-queue .queue-option{padding:12px 16px}.col-queue .queue-option .status-badge{padding:2px 6px}.col-queue .queue-option small{margin-bottom:4px}.print-actions{flex-wrap:wrap}
+@media print{@page{size:A4;margin:12mm}.shell,.main-col,.main-wrap{display:block!important;height:auto!important;overflow:visible!important;width:100%!important;margin:0!important;padding:0!important}.print-area{position:static!important;visibility:visible!important;width:100%!important;max-width:100%!important;padding:0!important;box-shadow:none!important}.print-area *{visibility:visible!important}.paper-note{font-size:9px}.offcanvas,.modal,.alert{display:none!important}}
+
+.archive-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:24px}.archive-list{align-self:start;max-height:720px;overflow:auto}.archive-name{font:600 24px Georgia,serif!important}.approval-letter{max-width:800px;margin:20px auto 28px;background:#fff;border:1px solid #e5e8eb;border-radius:6px;padding:42px;box-shadow:0 8px 24px #163a3510;font-size:14px;line-height:1.8;color:#172326}.letter-head{display:flex;align-items:center;gap:24px;border-bottom:2px solid #12605b;padding-bottom:20px}.letter-head>div{flex:1;text-align:center}.letter-head strong,.letter-head small{display:block}.letter-seal{border-radius:50%;padding:14px;background:#e8f1ef;color:#12605b}.letter-title{text-align:center;margin:22px 0}.letter-title h3{font-size:15px;font-weight:700;text-decoration:underline}.letter-title strong{font-size:14px}.letter-identities{display:grid;grid-template-columns:170px 1fr;gap:5px;margin:20px 0}.letter-identities dt{font-weight:400}.letter-identities dd{margin:0}.letter-body{white-space:pre-wrap}.letter-sign{margin:36px 0 20px auto;max-width:300px;text-align:center}.letter-sign p{margin:0}.letter-sign small,.letter-sign strong{display:block}.letter-signmark{padding:25px 0;color:#12605b;font-size:12px}.approval-letter footer{font-size:10px;border-top:1px solid #ddd;padding-top:10px;color:#65716f}.audit-table{font-size:13px}.audit-table td{vertical-align:top;padding:16px!important}.audit-table th{white-space:nowrap}.audit-table td:last-child{min-width:220px;max-width:340px}
+@media(max-width:800px){.archive-layout{grid-template-columns:1fr}.archive-list{max-height:280px}.approval-letter{padding:22px;font-size:12px}.letter-identities{grid-template-columns:120px 1fr}.letter-head{gap:10px}.letter-seal{padding:8px}}
+@media print{.approval-letter{border:0;box-shadow:none;border-radius:0;max-width:none;font-size:11pt}.letter-sign{break-inside:avoid}.letter-head{break-inside:avoid}}
+
+.draft-layout{display:grid;grid-template-columns:minmax(260px,0.8fr) minmax(380px,1.2fr);gap:24px}.draft-layout .approval-letter{padding:28px;margin-top:0}.draft-layout textarea{line-height:1.7}@media(max-width:1200px){.draft-layout{grid-template-columns:1fr}}
+
+/* Separate official-reference layouts; all values remain fictional demo data. */
+.finance-receipt-heading{border:1px solid #111;text-align:center;padding:24px 14px;font-size:13px}
+.finance-receipt-heading h3{font:bold 15px Arial,sans-serif;margin:10px 0}
+.skrd-identity{display:grid;grid-template-columns:76% 24%;margin:-20px -20px 18px;border-bottom:1px solid #111}
+.skrd-identity>div{padding:16px}.skrd-identity aside{border-left:1px solid #111;text-align:center;padding:12px;font-size:10px;overflow-wrap:anywhere}
+.skrd-identity .receipt-fields{font-size:10px;gap:6px;margin:12px 0}
+.finance-preserve{white-space:pre-wrap}.finance-stbp .receipt-signatures{justify-content:flex-start}
+.finance-sts .receipt-signatures{justify-content:space-between}.finance-sts .receipt-signatures>div{max-width:48%}
+.finance-stbp .receipt-fields,.finance-sts .receipt-fields{margin:32px 0 40px;gap:16px}
+.cut-line{text-align:center;font-size:10px;margin-top:-23px;margin-bottom:12px;background:white;width:140px;margin-left:auto;margin-right:auto}
+.receipt-table th:last-child,.receipt-table td:last-child{text-align:right}.finance-row-editor legend{font-size:13px!important}
+@media print{.financial-paper{font-size:10px;line-height:1.4}.financial-body{padding:14px}.skrd-identity{margin:-14px -14px 12px}.receipt-signatures{margin:30px 0 20px}.receipt-stub{break-inside:avoid}.receipt-table th,.receipt-table td{padding:4px}.paper-note{padding:4px;margin-bottom:8px}.finance-stbp,.finance-sts{min-height:250mm}.finance-stbp .receipt-signatures,.finance-sts .receipt-signatures{margin-top:50px}}
+
+.account-code-cells{display:flex;flex-wrap:wrap}.account-code-cells>span{border-right:1px solid #111;padding:0 4px}.account-code-cells>span:last-child{border:0}
+
+.receipt-table td[colspan="2"]{text-align:left;white-space:normal}
+
+@media print{.finance-skrd .receipt-signatures{margin:20px 0 15px}.finance-skrd .signature-demo{padding:10px 0;margin:20px 0 15px}.finance-skrd .receipt-stub{margin-top:20px}.finance-skrd .receipt-stub .receipt-fields{margin:12px 0;gap:6px}.finance-skrd .receipt-stub p{margin-bottom:0}}
+
+/* SKRD: continuous form grid matching the supplied paper reference. */
+.skrd-reference{font:10px/1.4 Arial,sans-serif;padding:18px}.skrd-frame{border:1px solid #111}.skrd-head{display:grid;grid-template-columns:29% 71%;border-bottom:1px solid #111;text-align:center}.skrd-head>div{padding:5px;border-right:1px solid #111;font-size:9px}.skrd-head small{display:block;font-size:7px}.skrd-head h3{font:bold 13px/1.4 Arial,sans-serif;margin:0;padding:5px;color:#111}.skrd-top{display:grid;grid-template-columns:75% 25%;border-bottom:1px solid #111;min-height:135px}.skrd-data{padding:12px 7% 12px 7%}.skrd-top aside{border-left:1px solid #111;text-align:center;padding:5px;font-size:9px;overflow-wrap:anywhere}.skrd-period,.skrd-identities,.skrd-stub-fields{display:grid;grid-template-columns:49% 51%;margin:0;gap:5px 0;font-size:9px}.skrd-period{margin-left:49%;grid-template-columns:30% 70%;margin-bottom:18px}.skrd-reference dt{font-weight:600}.skrd-reference dd{margin:0;overflow-wrap:anywhere}.skrd-grid{width:100%;table-layout:fixed;border-collapse:collapse;font-size:8px;line-height:1.3}.skrd-grid th,.skrd-grid td{border:1px solid #111;padding:1px 3px;height:13px;vertical-align:top;overflow-wrap:anywhere}.skrd-grid tr>*:first-child{border-left:0}.skrd-grid tr>*:last-child{border-right:0}.skrd-grid thead th{border-top:0;text-align:center;font-weight:600}.skrd-grid tbody[data-finance-rows] td:nth-child(-n+9){text-align:center}.skrd-grid .skrd-description{text-transform:uppercase}.skrd-money{display:flex;justify-content:space-between;gap:3px}.skrd-sanksi{float:right;min-width:48%}.skrd-total td{border-top:1px solid #111}.skrd-attention{padding:3px 3px 12px;border-bottom:1px solid #111;font-size:9px;min-height:95px}.skrd-attention p{margin:0 0 12px}.skrd-attention .finance-preserve{padding:12px 14px 0}.skrd-sign{min-height:175px;display:flex;justify-content:flex-end;padding:18px 3% 10px;border-bottom:1px solid #111;text-align:center;font-size:8px}.skrd-sign>div{width:48%}.skrd-sign strong{text-transform:uppercase;font-size:8px}.skrd-sign-space{height:78px;display:flex;align-items:center;justify-content:center;color:#888;font-size:7px}.skrd-cut{text-align:center;font-size:8px;border-bottom:1px solid #111;padding:2px}.skrd-receipt{position:relative;padding:20px 4px 12px;min-height:145px;font-size:8px}.skrd-stub-number{position:absolute;right:5%;top:12px;max-width:30%;text-align:center;overflow-wrap:anywhere}.skrd-stub-fields{margin:20px 0 0 5%;width:58%;grid-template-columns:25% 75%;font-size:8px}.skrd-stub-sign{margin-left:60%;text-align:center;font-size:8px}.skrd-receiver-space{height:28px}
+@media print{.skrd-reference{font-size:9px}.skrd-reference .paper-note{margin-bottom:6px}.skrd-frame{break-inside:auto}.skrd-sign,.skrd-receipt{break-inside:avoid}.skrd-grid tr{break-inside:avoid}}
```

## public/js/app.js

```diff
diff --git a/public/js/app.js b/public/js/app.js
--- a/public/js/app.js
+++ b/public/js/app.js
@@ -36,9 +36,61 @@
 if(draftEditor&&draftPreview)draftEditor.addEventListener('input',()=>draftPreview.textContent=draftEditor.value);
 
 document.querySelectorAll('[data-template-input]').forEach(input=>input.addEventListener('input',()=>{
- document.querySelectorAll('[data-template-value="'+input.name+'"]').forEach(node=>node.textContent=input.value||'—');
+ document.querySelectorAll('[data-template-value="'+input.name+'"]').forEach(node=>node.textContent=(input.value&&['issued_date','period'].includes(input.name)?new Date(input.value+(input.name==='period'?'-01':'')+'T12:00:00').toLocaleDateString('id-ID',input.name==='period'?{month:'long',year:'numeric'}:{day:'2-digit',month:'long',year:'numeric'}):input.value)||'—');
 }));
 
 const sampleFiles=document.getElementById('files');
 function syncRequirementUploads(){document.querySelectorAll('[data-requirement-upload]').forEach(input=>{input.disabled=!!sampleFiles?.checked;input.required=!sampleFiles?.checked;});}
 sampleFiles?.addEventListener('change',syncRequirementUploads);syncRequirementUploads();
+
+document.querySelectorAll('[data-letter-input]').forEach(input=>{input.addEventListener('input',()=>{document.querySelectorAll('[data-letter-value="'+input.dataset.letterInput+'"]').forEach(target=>target.textContent=input.value);});});
+
+// Receipt rows use textContent so entered descriptions remain plain text.
+const financeRows=document.querySelector('[data-finance-input-rows]');
+if(financeRows){
+ const preview=document.querySelector('[data-finance-rows]');
+ const skrd=preview.hasAttribute('data-skrd-rows');
+ const skrdPlace=skrd?preview.querySelector('.skrd-description').textContent.split('Tempat:').pop():'';
+ const skrdDate=skrd?preview.querySelector('tr:last-child td:nth-last-child(2)').textContent:'';
+ const money=value=>'Rp '+Number(value||0).toLocaleString('id-ID');
+ function syncFinanceRows(){
+  preview.replaceChildren();let sum=0;
+  financeRows.querySelectorAll('fieldset').forEach((row,index)=>{
+   row.querySelector('legend').textContent='Rincian '+(index+1);
+   const tr=document.createElement('tr');const num=document.createElement('td');num.textContent=index+1;tr.append(num);
+   row.querySelectorAll('[data-row-field]').forEach(input=>{
+    input.name=`rows[${index}][${input.dataset.rowField}]`;
+    input.id=`row-${index}-${input.dataset.rowField}`;input.previousElementSibling.htmlFor=input.id;
+    const td=document.createElement('td');td.textContent=input.dataset.rowField==='amount'?money(input.value):input.value;if(input.dataset.rowField==='code'&&document.querySelector('.finance-skrd')){td.textContent='';const group=document.createElement('span');group.className='account-code-cells';input.value.split('.').forEach(part=>{const cell=document.createElement('span');cell.textContent=part;group.append(cell);});td.append(group);}tr.append(td);
+    if(input.dataset.rowField==='amount')sum+=Number(input.value||0);
+   });preview.append(tr);
+   row.querySelector('[data-remove-finance-row]').disabled=financeRows.children.length===1;
+  });
+  if(skrd){
+   preview.replaceChildren();
+   financeRows.querySelectorAll('fieldset').forEach((row,index)=>{
+    const code=row.querySelector('[data-row-field="code"]').value.split('.');
+    const label=row.querySelector('[data-row-field="label"]').value;
+    const amount=row.querySelector('[data-row-field="amount"]').value;
+    for(let line=0;line<5;line++){
+     const tr=document.createElement('tr');const add=(text)=>{const td=document.createElement('td');td.textContent=text;tr.append(td);return td;};
+     add(index*5+line+1);for(let j=0;j<8;j++)add(line===0?(code[j]||''):'');
+     if(line===0){const td=add('');const u=document.createElement('u');u.textContent=label;td.append(u);add('');}
+     if(line===1){const td=add('');td.rowSpan=3;td.className='skrd-description';const span=document.createElement('span');span.dataset.templateValue='description';span.textContent=document.getElementById('description').value;td.append(span,document.createElement('br'),document.createTextNode('Tempat: '+skrdPlace));const amt=add(money(amount));amt.rowSpan=3;}
+     if(line===4){add(skrdDate);add('');}preview.append(tr);
+    }
+   });
+  }
+  document.querySelector('[data-finance-row-total]').textContent='Jumlah rincian yang diisi: '+money(sum);
+  document.querySelector('[data-add-finance-row]').disabled=financeRows.children.length>=20;
+ }
+ financeRows.addEventListener('input',syncFinanceRows);
+ financeRows.addEventListener('click',event=>{if(event.target.closest('[data-remove-finance-row]')&&financeRows.children.length>1){event.target.closest('fieldset').remove();syncFinanceRows();}});
+ document.querySelector('[data-add-finance-row]').addEventListener('click',()=>{const row=financeRows.firstElementChild.cloneNode(true);row.querySelectorAll('input').forEach(input=>input.value=input.dataset.rowField==='amount'?'0':'');financeRows.append(row);syncFinanceRows();});
+ document.querySelectorAll('[data-template-input]').forEach(input=>input.dispatchEvent(new Event('input')));
+ syncFinanceRows();
+}
+
+const financePeriod=document.querySelector('[data-template-editor] #period');
+function updateFinancePeriod(){if(!financePeriod?.value)return;document.querySelectorAll('[data-period-month]').forEach(el=>el.textContent=new Date(financePeriod.value+'-01T12:00:00').toLocaleDateString('id-ID',{month:'long'}));document.querySelectorAll('[data-period-year]').forEach(el=>el.textContent=financePeriod.value.slice(0,4));}
+financePeriod?.addEventListener('input',updateFinancePeriod);updateFinancePeriod();
```

## resources/views/arsip/index.blade.php

```diff
diff --git a/resources/views/arsip/index.blade.php b/resources/views/arsip/index.blade.php
new file mode 100644
--- /dev/null
+++ b/resources/views/arsip/index.blade.php
@@ -0,0 +1,15 @@
+@extends('layouts.app')
+@section('content')
+@php
+$D=\App\Support\DemoData::class;
+$url=fn($changes)=>route('listing',array_merge(request()->except('selected'),['kind'=>'arsip'],$changes));
+@endphp
+<section class="panel p-4 mb-4 d-flex gap-3"><x-icon name="archive"/><div><h2>Arsip Digital Permohonan</h2><p class="mb-0 small">Berkas untuk pemohon telah diserahkan. Salinan digital dan dokumen internal dinas dapat ditelusuri di sini.</p></div></section>
+<div class="d-flex gap-2 justify-content-end mb-3"><a class="btn btn-light btn-sm" href="{{ route('export',['kind'=>'arsip']) }}">Unduh Rekap CSV</a><a class="btn btn-light btn-sm" href="{{ route('archive.package') }}">Cadangan Semua Metadata</a></div>
+@include('permohonan.filters')
+<div class="archive-layout mt-4"><aside class="panel archive-list"><h2 class="p-3 border-bottom">Permohonan Diarsipkan <span class="float-end">{{ $rows->count() }}</span></h2>@forelse($rows as $item)<a class="queue-option {{ $r['id']===$item['id']?'selected':'' }}" href="{{ $url(['selected'=>$item['id']]) }}"><strong>{{ $item['name'] }}</strong><small>{{ $item['tracking'] }}</small><span class="small">Diserahkan · {{ $D::date($item['completed']) }}</span></a>@empty<p class="p-4">Tidak ada arsip sesuai pencarian.</p>@endforelse</aside>
+@if($r)<div class="vstack gap-4"><section class="panel p-4"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><h2 class="archive-name">{{ $r['name'] }}</h2><p class="small mt-2">{{ $r['tracking'] }} · {{ $r['activity'] }} · {{ $locations[$r['location_id']]['name'] }}</p><strong>{{ $D::rupiah($r['bill']['total']??0) }} · Lunas</strong></div><a class="btn btn-outline-secondary" href="{{ route('archive.package',['id'=>$r['id']]) }}">Unduh Metadata Arsip</a></div><dl class="detail-grid mt-3"><div><dt>Penanggung jawab</dt><dd>{{ $r['pic'] }}</dd></div><div><dt>Waktu kegiatan</dt><dd>{{ $D::date($r['date']) }} · {{ $r['days'] }} hari</dd></div><div><dt>Penyerahan / penerima</dt><dd>{{ $D::date($r['completed']) }} · {{ $r['recipient'] }}</dd></div><div><dt>Tanda tangan surat</dt><dd>{{ $r['signed']?'Simulasi TTE selesai':'Belum ditandatangani' }}</dd></div></dl></section>
+<section class="panel p-4"><h2 class="mb-3">Dokumen Terbit</h2>@foreach(['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $slug=>$label)<div class="document-row"><span class="file-icon"><x-icon name="file-text"/></span><div class="flex-grow-1"><strong>{{ $label }} <span class="small text-secondary">· {{ $slug==='sts'?'Internal dinas':'Salinan untuk pemohon' }}</span></strong><small>{{ in_array($label,$r['documents'])?($slug==='surat'?\App\Support\DemoData::letter($r)['number']:\App\Support\FinanceDocument::data($r,$slug)['number']):'Belum diterbitkan' }}</small><small>Sumber: {{ ($r['document_sources'][$slug]??'template')==='upload'?'Unggahan manual':'Template sistem' }}</small></div>@if(in_array($label,$r['documents']))<a class="btn btn-light btn-sm" href="{{ route('document',[$r['id'],$slug,'from'=>'arsip']) }}">Lihat / Cetak</a>@else<span class="small text-secondary">Tidak tersedia</span>@endif</div>@endforeach</section>
+<section class="panel p-4"><h2 class="mb-3">Berkas Persyaratan ({{ count($r['files']) }})</h2>@foreach($r['files'] as $i=>$file)<div class="document-row"><x-icon name="file-text"/><div class="flex-grow-1"><strong>{{ $file['label'] }}</strong><small>{{ $file['name'] }}</small></div>@if(!empty($file['stored']))<a href="{{ route('uploaded',[$r['id'],'requirement-'.$i]) }}">Lihat</a>@else<span class="small text-secondary">Contoh berkas · metadata</span>@endif</div>@endforeach</section>
+<div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}">Detail Permohonan</a><a class="btn btn-light" href="{{ route('listing',['kind'=>'riwayat','q'=>$r['tracking']]) }}">Riwayat Aktivitas</a></div></div>@endif</div>
+@endsection
\ No newline at end of file
```

## resources/views/dokumen/editor.blade.php

```diff
diff --git a/resources/views/dokumen/editor.blade.php b/resources/views/dokumen/editor.blade.php
--- a/resources/views/dokumen/editor.blade.php
+++ b/resources/views/dokumen/editor.blade.php
@@ -1,12 +1,47 @@
 @extends('layouts.app')
 @section('content')
-@php($cfg=$r['templates'][$docKey]??[])
-<a class="back-link" href="{{ route('listing',['kind'=>'retribusi','selected'=>$r['id']]) }}"><x-icon name="back" size="16"/> Kembali ke Retribusi</a>
+@php
+$cfg=\App\Support\FinanceDocument::data($r,$docKey);
+$fields=['number'=>'Nomor dokumen','payer'=>'Nama penyetor / wajib retribusi','issued_date'=>$docKey==='sts'?'Tanggal setoran':'Tanggal dokumen / penerimaan'];
+if($docKey==='sts') unset($fields['payer']);
+$fields+= $docKey==='skrd'?['period'=>'Masa / tahun retribusi','address'=>'Alamat','npwr'=>'NPWR','due'=>'Keterangan jatuh tempo']:['bank'=>'Nama bank','account'=>'Nomor rekening bank'];
+@endphp
+<a class="back-link" href="{{ route('listing',['kind'=>'retribusi','selected'=>$r['id']]) }}">← Kembali ke Retribusi</a>
 <div class="page-heading"><div><h1>Edit Template {{ strtoupper($docKey) }}</h1><p>{{ $r['name'] }} · {{ $r['tracking'] }}</p></div></div>
 <form method="post" class="row g-4" data-template-editor>@csrf
-<div class="col-lg-4"><section class="panel p-4"><h2 class="mb-3">Isi Template</h2><p class="small text-secondary">Perubahan berlaku untuk dokumen permohonan ini. Data pemohon dan nominal diisi sistem. Simpan template untuk memilihnya kembali sebagai sumber dokumen setelah unggah manual.</p>
-<label for="description" class="form-label">{{ $docKey==='stbp'?'Sebagai pembayaran':'Uraian retribusi' }}</label><textarea id="description" name="description" class="form-control mb-3" rows="7" maxlength="3000" required data-template-input>{{ old('description',$cfg['description']??('Pelaksanaan kegiatan '.$r['activity'].' di '.$locations[$r['location_id']]['name'])) }}</textarea>
-@foreach(($docKey==='skrd'?['address'=>'Alamat pemohon','due'=>'Keterangan jatuh tempo']:['bank'=>'Bank','account'=>'Nomor rekening']) as $key=>$label)<label class="form-label" for="{{ $key }}">{{ $label }}</label><input class="form-control mb-3" id="{{ $key }}" name="{{ $key }}" value="{{ old($key,$cfg[$key]??'') }}" maxlength="{{ $key==='address'?300:($key==='due'?200:100) }}" data-template-input>@endforeach
-<div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="{{ route('listing',['kind'=>'retribusi','selected'=>$r['id']]) }}">Batal</a><button class="btn btn-primary">Simpan Template</button></div></section></div>
-<div class="col-lg-8"><div class="d-flex justify-content-between mb-3"><h2>Pratinjau Langsung</h2><span class="small text-secondary">Diperbarui saat mengetik</span></div><div class="financial-preview">@include('dokumen.financial')</div></div></form>
+<div class="col-xl-5"><section class="panel p-4"><h2 class="mb-3">Identitas dokumen</h2><p class="small text-secondary">Perubahan disimpan khusus untuk dokumen ini. Tanggal STS dan STBP dapat berbeda. Nomor dan identitas penandatangan di bawah adalah data demo.</p>
+@foreach($fields as $key=>$label)
+<label class="form-label" for="{{ $key }}">{{ $label }}</label><input class="form-control mb-3" id="{{ $key }}" name="{{ $key }}" type="{{ $key==='period'?'month':($key==='issued_date'?'date':'text') }}" value="{{ old($key,$cfg[$key]) }}" required data-template-input>
+@endforeach
+@if($docKey==='stbp')
+<label for="method" class="form-label">Cara penerimaan</label><select id="method" name="method" class="form-select mb-3" data-template-input>@foreach(['Tunai','Non Tunai'] as $method)<option @selected(old('method',$cfg['method'])===$method)>{{ $method }}</option>@endforeach</select>
+@endif
+@if($docKey!=='sts')
+<label for="description" class="form-label">{{ $docKey==='stbp'?'Sebagai pembayaran (kegiatan)':'Uraian kegiatan / tempat yang dipakai' }}</label><textarea id="description" name="description" class="form-control mb-2" rows="4" maxlength="3000" required data-template-input>{{ old('description',$cfg['description']) }}</textarea>
+<p class="small text-secondary">Diisi awal dari permohonan; setelah disimpan menjadi uraian khusus dokumen ini. Tanggal {{ $r['date'] }} dan durasi {{ $r['days'] }} hari mengikuti permohonan.</p>
+@else
+<input type="hidden" name="description" value="{{ $cfg['description'] }}">
+<p class="small text-secondary">Label “Penerimaan Tanggal” pada cetakan mengikuti contoh yang diberikan, yang memuat nominal penerimaan.</p>
+@endif
+<hr><h2 class="mb-3">Rincian {{ $docKey==='skrd'?'ketetapan':'penerimaan / setoran' }}</h2>
+<p class="small">Kode rekening di sini adalah kode pendapatan, berbeda dari nomor rekening bank. Total rincian harus <strong>{{ \App\Support\DemoData::rupiah($docKey==='skrd'?$cfg['base']:$cfg['total']) }}</strong>{{ $docKey==='skrd'?' sebelum keringanan.':'.' }} Nominal penetapan diubah melalui Penetapan Retribusi sebelum penerbitan.</p>
+<div data-finance-input-rows>
+@foreach(old('rows',$cfg['rows']) as $row)
+<fieldset class="finance-row-editor border rounded p-3 mb-3"><legend class="float-none w-auto fs-6">Rincian {{ $loop->iteration }}</legend>
+@foreach(['code'=>'Kode rekening pendapatan','label'=>'Uraian rekening','amount'=>'Nominal (rupiah)'] as $key=>$label)
+<label class="form-label" for="row-{{ $loop->parent->index }}-{{ $key }}">{{ $label }}</label><input id="row-{{ $loop->parent->index }}-{{ $key }}" name="rows[{{ $loop->parent->index }}][{{ $key }}]" data-row-field="{{ $key }}" type="{{ $key==='amount'?'number':'text' }}" @if($key==='amount') min="0" max="999999999999" step="1" @else maxlength="{{ $key==='code'?100:300 }}" @endif value="{{ $row[$key] }}" class="form-control mb-2" required>
+@endforeach
+<button type="button" class="btn btn-sm btn-outline-danger" data-remove-finance-row>Hapus rincian</button></fieldset>
+@endforeach
+</div><button type="button" class="btn btn-outline-secondary mb-2" data-add-finance-row>+ Tambah rincian</button><p data-finance-row-total class="small" aria-live="polite"></p>
+@if($docKey==='skrd')
+<p class="small text-secondary">Keringanan {{ $cfg['percent'] }}% mengikuti keputusan yang tersimpan. Denda dan kenaikan ditampilkan Rp 0 pada skenario demo; perhitungan sanksi belum diterapkan.</p>
+<label for="attention" class="form-label">Catatan perhatian</label><textarea class="form-control mb-3" name="attention" id="attention" rows="4" maxlength="2000" data-template-input>{{ old('attention',$cfg['attention']) }}</textarea>
+@endif
+<hr><h2 class="mb-3">Penandatangan</h2>
+@foreach(['signer_title'=>'Jabatan penandatangan','signer_name'=>'Nama penandatangan (demo)','signer_nip'=>'NIP (demo)']+($docKey==='skrd'?['signer_rank'=>'Pangkat / golongan (demo)']:[])+($docKey==='sts'?['authority_name'=>'Nama Pengguna Anggaran (demo)','authority_nip'=>'NIP Pengguna Anggaran (demo)']:[]) as $key=>$label)
+<label class="form-label" for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" name="{{ $key }}" value="{{ old($key,$cfg[$key]) }}" class="form-control mb-3" required data-template-input>
+@endforeach
+<button class="btn btn-primary">Simpan Template</button></section></div>
+<div class="col-xl-7"><div class="d-flex justify-content-between mb-3"><h2>Pratinjau Langsung</h2><span class="small text-secondary">Isi dan rincian mengikuti editor</span></div><div class="financial-preview">@include('dokumen.financial')</div></div></form>
 @endsection
```

## resources/views/dokumen/financial.blade.php

```diff
diff --git a/resources/views/dokumen/financial.blade.php b/resources/views/dokumen/financial.blade.php
--- a/resources/views/dokumen/financial.blade.php
+++ b/resources/views/dokumen/financial.blade.php
@@ -1,28 +1,63 @@
 @php
 $D=\App\Support\DemoData::class;
-$cfg=$r['templates'][$docKey]??[];
+$cfg=\App\Support\FinanceDocument::data($r,$docKey);
 $loc=$locations[$r['location_id']];
-$subtotal=$r['bill']['subtotal']??($loc['rate']*$r['days']);
-$percent=$r['bill']['percent']??($r['discount']==='disetujui'&&$loc['managed']?25:0);
-$total=$r['bill']['total']??($subtotal*(1-$percent/100));
-$description=$cfg['description']??('Pelaksanaan kegiatan '.$r['activity'].' di '.$loc['name']);
-$number='DEMO/'.$r['tracking'].'/'.strtoupper($docKey);
-$titles=['skrd'=>'SURAT KETETAPAN RETRIBUSI DAERAH','sts'=>'SURAT TANDA SETORAN','stbp'=>'SURAT TANDA BUKTI PENERIMAAN'];
 @endphp
-<article class="financial-paper" aria-label="Pratinjau {{ strtoupper($docKey) }}">
+@if($docKey==='skrd')
+@include('dokumen.skrd')
+@else
+<article class="financial-paper print-area finance-{{ $docKey }}" aria-label="Pratinjau {{ strtoupper($docKey) }}">
 <div class="paper-note">DEMO — BUKAN DOKUMEN RESMI {{ !in_array(strtoupper($docKey),$r['documents'])?' · BELUM TERBIT':'' }}</div>
-<header class="financial-heading"><div><strong>PEMERINTAH KOTA PANGKALPINANG<br>DINAS PARIWISATA</strong><small>Template demonstrasi pelayanan retribusi</small></div><div><h3>{{ $titles[$docKey] }}</h3><strong>({{ strtoupper($docKey) }})</strong><small>{{ $number }}</small></div></header>
+@if($docKey==='skrd')
+<header class="financial-heading"><div><strong>PEMERINTAH KOTA PANGKALPINANG<br>DINAS PARIWISATA</strong><small>Pangkalpinang · Dokumen demonstrasi</small></div><div><h3>SURAT KETETAPAN RETRIBUSI DAERAH<br>(SKRD)</h3></div></header>
+@else
+<header class="finance-receipt-heading"><strong>PEMERINTAH KOTA PANGKAL PINANG</strong><h3>{{ $docKey==='sts'?'SURAT TANDA SETORAN':'SURAT TANDA BUKTI PENERIMAAN' }}</h3><div>Nomor: <span data-template-value="number">{{ $cfg['number'] }}</span></div></header>
+@endif
 <section class="financial-body">
 @if($docKey==='skrd')
-<p class="text-end">MASA: {{ now()->translatedFormat('F') }} · TAHUN: {{ date('Y') }}</p>
-<dl class="receipt-fields"><dt>NAMA</dt><dd>{{ $r['pic'] }} / {{ $r['name'] }}</dd><dt>ALAMAT</dt><dd data-template-value="address">{{ $cfg['address']??'—' }}</dd><dt>NPWR</dt><dd>—</dd><dt>TANGGAL JATUH TEMPO</dt><dd data-template-value="due">{{ $cfg['due']??'Diisi sesuai penetapan Bendahara' }}</dd></dl>
+<div class="skrd-identity"><div><p class="text-end">MASA / TAHUN: <span data-template-value="period">{{ \Carbon\Carbon::parse($cfg['period'].'-01')->locale('id')->translatedFormat('F Y') }}</span></p><dl class="receipt-fields"><dt>NAMA</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd><span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NOMOR POKOK WAJIB RETRIBUSI (NPWR)</dt><dd><span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd><dt>TANGGAL JATUH TEMPO</dt><dd><span data-template-value="due">{{ $cfg['due'] }}</span></dd></dl></div><aside><strong>NO. URUT</strong><br><span data-template-value="number">{{ $cfg['number'] }}</span></aside></div>
+@elseif($docKey==='stbp')
+<p>Telah diterima uang secara <span data-template-value="method">{{ $cfg['method'] }}</span>:</p>
+<dl class="receipt-fields"><dt>a. Dari</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>b. Tanggal</dt><dd><span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span></dd><dt>c. Di Bank</dt><dd><span data-template-value="bank">{{ $cfg['bank'] }}</span></dd><dt>d. Nomor Rekening</dt><dd><span data-template-value="account">{{ $cfg['account'] }}</span></dd><dt>e. Sebesar</dt><dd>{{ $D::rupiah($cfg['total']) }}</dd><dt>f. Terbilang</dt><dd>{{ ucfirst($cfg['words']) }}</dd><dt>g. Sebagai Pembayaran</dt><dd><span data-template-value="description">{{ $cfg['description'] }}</span></dd></dl>
+<p>Dengan rincian penerimaan sebagai berikut:</p>
 @else
-<p>{{ $docKey==='stbp'?'Telah diterima uang secara tunai/non tunai:':'Rincian setoran penerimaan:' }}</p>
-<dl class="receipt-fields"><dt>{{ $docKey==='stbp'?'Dari':'Nomor STS' }}</dt><dd>{{ $docKey==='stbp'?$r['pic'].' / '.$r['name']:$number }}</dd><dt>Tanggal</dt><dd>{{ $r['payment']['date']??'Belum dikonfirmasi' }}</dd><dt>Bank</dt><dd data-template-value="bank">{{ $cfg['bank']??'—' }}</dd><dt>Nomor rekening</dt><dd data-template-value="account">{{ $cfg['account']??'—' }}</dd><dt>Sebesar</dt><dd>{{ $D::rupiah($total) }}</dd><dt>{{ $docKey==='stbp'?'Sebagai pembayaran':'Uraian setoran' }}</dt><dd data-template-value="description">{{ $description }}</dd></dl>
+<dl class="receipt-fields"><dt>1. Nomor STS</dt><dd><span data-template-value="number">{{ $cfg['number'] }}</span></dd><dt>2. Tanggal</dt><dd><span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span></dd><dt>3. Bank</dt><dd><span data-template-value="bank">{{ $cfg['bank'] }}</span></dd><dt>4. Nomor Rekening</dt><dd><span data-template-value="account">{{ $cfg['account'] }}</span></dd><dt>5. Penerimaan Tanggal</dt><dd>{{ $D::rupiah($cfg['total']) }}</dd></dl>
+<p>Harap diterima uang sebesar {{ $D::rupiah($cfg['total']) }} (terbilang: {{ $cfg['words'] }})</p>
 @endif
-<table class="receipt-table"><thead><tr><th>No.</th><th>Kode rekening</th><th>Uraian retribusi</th><th>Jumlah (Rp)</th></tr></thead><tbody><tr><td>1</td><td>4.1.02.02.009.00001<br><small>Acuan contoh</small></td><td>Retribusi Pelayanan Tempat Rekreasi dan Olahraga<br><span data-template-value="description">{{ $description }}</span><br>Pelaksanaan: {{ $D::date($r['date']) }} · {{ $r['days'] }} hari</td><td>{{ $D::rupiah($subtotal) }}</td></tr><tr><td colspan="3">Keringanan disetujui ({{ $percent }}%)</td><td>− {{ $D::rupiah($subtotal-$total) }}</td></tr><tr><th colspan="3">JUMLAH {{ $docKey==='skrd'?'KETETAPAN':'PENERIMAAN' }}</th><th>{{ $D::rupiah($total) }}</th></tr></tbody></table>
-@if(!$r['bill'])<p class="small mt-3">Nominal masih estimasi; belum menjadi penetapan.</p>@endif
-@if($docKey==='skrd')<p class="small mt-4"><strong>PERHATIAN</strong><br>SKRD merupakan dokumen penetapan retribusi, bukan bukti pelunasan. Pembayaran dicatat terpisah oleh Bendahara.</p>@endif
-<div class="receipt-signatures">@if($docKey==='sts')<div>Mengetahui,<br><strong>PENGGUNA ANGGARAN</strong><p>Area tanda tangan</p><span>Nama pejabat sesuai kewenangan</span></div>@endif<div>{{ $docKey==='skrd'?'Pejabat penandatangan SKRD':'BENDAHARA PENERIMAAN' }}<br>DINAS PARIWISATA<p>Area tanda tangan</p><span>Nama pejabat sesuai kewenangan</span></div></div>
-@if($docKey==='skrd')<div class="receipt-stub"><strong>TANDA TERIMA</strong><span>{{ $number }}</span><p>Nama: {{ $r['pic'] }}<br>Alamat: <span data-template-value="address">{{ $cfg['address']??'—' }}</span></p><p class="text-end">Yang menerima<br><br>{{ $r['pic'] }}</p></div>@endif
+<table class="receipt-table"><thead><tr><th>No.</th><th>Kode Rekening</th><th>{{ $docKey==='skrd'?'Uraian Retribusi':'Uraian' }}</th><th>Jumlah (Rp)</th></tr></thead><tbody data-finance-rows>
+@foreach($cfg['rows'] as $row)
+<tr><td>{{ $loop->iteration }}</td><td>@if($docKey==='skrd')<span class="account-code-cells">@foreach(explode('.',$row['code']) as $part)<span>{{ $part }}</span>@endforeach</span>@else{{ $row['code'] }}@endif</td><td>{{ $row['label'] }}</td><td>{{ $D::rupiah($row['amount']) }}</td></tr>
+@endforeach
+</tbody><tbody>
+@if($docKey==='skrd')
+<tr><td colspan="2"></td><td colspan="2"><span data-template-value="description">{{ $cfg['description'] }}</span><br>Tempat: {{ $loc['name'] }}<br>PEMBAYARAN SEWA UNTUK TANGGAL {{ $D::date($r['date']) }} · {{ $r['days'] }} hari</td></tr>
+<tr><td colspan="2" rowspan="5"></td><td>Subtotal retribusi</td><td>{{ $D::rupiah($cfg['base']) }}</td></tr>
+<tr><td>Keringanan disetujui ({{ $cfg['percent'] }}%)</td><td>− {{ $D::rupiah($cfg['base']-$cfg['total']) }}</td></tr>
+<tr><td>Jumlah Ketetapan Pokok Retribusi</td><td>{{ $D::rupiah($cfg['total']) }}</td></tr>
+<tr><td>Jumlah Sanksi: a. Denda</td><td>Rp 0</td></tr><tr><td>b. Kenaikan</td><td>Rp 0</td></tr>
+<tr><th colspan="3">Jumlah Keseluruhan</th><th>{{ $D::rupiah($cfg['total']) }}</th></tr>
+@else
+<tr><th colspan="3" class="text-end">JUMLAH</th><th>{{ $D::rupiah($cfg['total']) }}</th></tr>
+@endif
+</tbody></table>
+@if($docKey==='skrd')
+<p class="mt-2">Dengan huruf: <strong>{{ ucfirst($cfg['words']) }}</strong></p><strong><u>PERHATIAN:</u></strong><div class="finance-preserve"><span data-template-value="attention">{{ $cfg['attention'] }}</span></div>
+@endif
+@if(!$r['bill'])<p class="small mt-2">Nominal masih estimasi; belum menjadi penetapan.</p>@endif
+<div class="receipt-signatures">
+@if($docKey==='sts')
+<div>Mengetahui,<br><strong>PENGGUNA ANGGARAN</strong><p class="signature-demo">RUANG TANDA TANGAN DEMO</p><u><span data-template-value="authority_name">{{ $cfg['authority_name'] }}</span></u><br>NIP <span data-template-value="authority_nip">{{ $cfg['authority_nip'] }}</span></div>
+@endif
+<div>
+@if($docKey==='skrd')Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>a.n. KEPALA DINAS PARIWISATA<br>KOTA PANGKALPINANG,<br>
+@elseif($docKey==='sts')Disiapkan oleh,<br>
+@endif
+<strong><span data-template-value="signer_title">{{ $cfg['signer_title'] }}</span></strong><br>@if($docKey==='stbp')DINAS PARIWISATA
+@endif
+<p class="signature-demo">RUANG TANDA TANGAN DEMO</p><u><span data-template-value="signer_name">{{ $cfg['signer_name'] }}</span></u><br>NIP <span data-template-value="signer_nip">{{ $cfg['signer_nip'] }}</span></div></div>
+@if($docKey==='skrd')
+<div class="receipt-stub"><div class="cut-line">POTONG DI SINI</div><div class="d-flex justify-content-between"><strong>TANDA TERIMA</strong><div>NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></div></div><dl class="receipt-fields"><dt>NAMA</dt><dd><span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd><span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NPWR</dt><dd><span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd></dl><p class="text-end">Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>Yang menerima,<br><br><br><span data-template-value="payer">{{ $cfg['payer'] }}</span></p></div>
+@endif
 </section></article>
+
+@endif
```

## resources/views/dokumen/letter.blade.php

```diff
diff --git a/resources/views/dokumen/letter.blade.php b/resources/views/dokumen/letter.blade.php
new file mode 100644
--- /dev/null
+++ b/resources/views/dokumen/letter.blade.php
@@ -0,0 +1,11 @@
+@php($D=\App\Support\DemoData::class)
+@php($letter=$D::letter($r))
+<article class="approval-letter print-area">
+<header class="letter-head"><span class="letter-seal"><x-icon name="stamp" size="28"/></span><div><small>PEMERINTAH KOTA PANGKALPINANG</small><strong>DINAS PARIWISATA</strong><small>Kota Pangkalpinang · Dokumen demonstrasi pelayanan</small></div></header>
+<div class="letter-title"><h3>SURAT PERSETUJUAN</h3><strong>PENYELENGGARAAN KEGIATAN DI TEMPAT REKREASI</strong><p>Nomor: <span data-letter-value="number">{{ old('letter.number',$letter['number']) }}</span></p></div>
+<p>Perihal: <span data-letter-value="subject">{{ old('letter.subject',$letter['subject']) }}</span><br>Lampiran: <span data-letter-value="attachment">{{ old('letter.attachment',$letter['attachment']) }}</span></p><p class="letter-body" data-letter-value="opening">{{ old('letter.opening',$letter['opening']) }}</p>
+<dl class="letter-identities">@foreach(['Nama / Lembaga'=>$r['name'],'Penanggung Jawab'=>$r['pic'],'Jenis Kegiatan'=>$r['activity'],'Lokasi'=>$locations[$r['location_id']]['name'],'Waktu Pelaksanaan'=>$D::date($r['date']).' ('.$r['days'].' hari)'] as $label=>$value)<dt>{{ $label }}</dt><dd>: {{ $value }}</dd>@endforeach</dl>
+@foreach(['body','conditions','closing'] as $key)<p class="letter-body" data-letter-value="{{ $key }}">{{ old('letter.'.$key,$letter[$key]) }}</p>@endforeach
+<div class="letter-sign"><p>Pangkalpinang, <span data-letter-value="date">{{ old('letter.date',$letter['date']) }}</span></p><strong>Kepala Dinas Pariwisata</strong><small>Kota Pangkalpinang</small><div class="letter-signmark">{{ $r['signed']?'DITANDATANGANI · SIMULASI TTE':'Menunggu tanda tangan' }}</div><strong>{{ $r['signature']['name']??'Aditya Pratama (pejabat fiktif)' }}</strong>@if($r['signed'])<small>{{ $r['signature']['at']??'' }} · {{ $r['signature']['reference']??'' }}</small>@endif</div>
+<footer>DEMO · Bukan dokumen resmi @if(!$r['signed']) · Draft versi {{ $r['version'] }} · Belum berlaku sebagai persetujuan final @endif</footer>
+</article>
\ No newline at end of file
```

## resources/views/dokumen/preview.blade.php

```diff
diff --git a/resources/views/dokumen/preview.blade.php b/resources/views/dokumen/preview.blade.php
--- a/resources/views/dokumen/preview.blade.php
+++ b/resources/views/dokumen/preview.blade.php
@@ -1,9 +1,14 @@
 @extends('layouts.app')
 @section('content')
-<div class="print-actions"><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}"><x-icon name="back" size="17"/> Kembali ke permohonan</a><button class="btn btn-primary" data-print><x-icon name="print" size="17"/> Cetak / simpan PDF</button></div>@if($type!=='Surat Persetujuan')
+@php
+$origin=request('from');
+$origins=['serah-terima'=>'Serah Terima','arsip'=>'Arsip Digital','tanda-tangan'=>'Tanda Tangan','retribusi'=>'Retribusi'];
+$back=isset($origins[$origin])?route('listing',['kind'=>$origin,'selected'=>$r['id']]):route('detail',$r['id']);
+@endphp
+<div class="print-actions"><a class="btn btn-outline-primary" href="{{ $back }}">← Kembali ke {{ $origins[$origin]??'Detail Permohonan' }}</a><a class="btn btn-outline-primary" href="{{ route('detail',$r['id']) }}"><x-icon name="back" size="17"/> Detail Permohonan</a><button class="btn btn-primary" data-print><x-icon name="print" size="17"/> Cetak / simpan PDF</button></div>@if($type!=='Surat Persetujuan')
 @php($docKey=strtolower($type))
 @if(($r['document_sources'][$docKey]??'template')==='upload')<section class="panel p-4 mb-3"><h2>Dokumen manual {{ $type }}</h2><p>{{ $r['uploads'][$docKey]['name'] }}</p><a class="btn btn-primary" href="{{ route('uploaded',[$r['id'],$docKey]) }}" target="_blank" rel="noopener">Buka / cetak berkas unggahan</a><p class="small mt-3">Berkas manual dipilih sebagai sumber dokumen ini.</p></section>@else @include('dokumen.financial') @endif
 @else
-<article class="paper"><div class="paper-note">CONTOH UNTUK DEMONSTRASI FRONTEND — BUKAN DOKUMEN RESMI</div><header class="paper-header"><span>PEMERINTAH KOTA PANGKALPINANG</span><h2>DINAS PARIWISATA</h2><small>SIAP-Wisata · Pratinjau template demonstrasi</small></header><h3>{{ ['Surat Persetujuan'=>'SURAT PERSETUJUAN','SKRD'=>'SURAT KETETAPAN RETRIBUSI DAERAH','STBP'=>'SURAT TANDA BUKTI PENERIMAAN','STS'=>'SURAT TANDA SETORAN'][$type] }}</h3><p class="text-center small mb-4">Nomor contoh: DEMO/{{ $r['tracking'] }}/{{ $type==='Surat Persetujuan'?'SP':$type }}</p><dl class="detail-grid mb-4"><div><dt>Pemohon / lembaga</dt><dd>{{ $r['name'] }}</dd></div><div><dt>Penanggung jawab</dt><dd>{{ $r['pic'] }}</dd></div><div><dt>Lokasi</dt><dd>{{ $locations[$r['location_id']]['name'] }}</dd></div><div><dt>Pelaksanaan</dt><dd>{{ \App\Support\DemoData::date($r['date']) }} · {{ $r['days'] }} hari</dd></div></dl>@if($type==='Surat Persetujuan')<div class="draft-preview">{{ \App\Support\DemoData::draft($r,$locations[$r['location_id']]['name']) }}</div>@else<div class="summary-line"><span>Subtotal</span><strong>{{ \App\Support\DemoData::rupiah($r['bill']['subtotal']) }}</strong></div><div class="summary-line"><span>Keringanan</span><strong>{{ $r['bill']['percent'] }}%</strong></div><div class="total-line"><span>{{ $type==='SKRD'?'Jumlah ditetapkan':'Jumlah dicatat' }}</span><strong>{{ \App\Support\DemoData::rupiah($r['bill']['total']) }}</strong></div><p class="small">{{ $type==='STS'?'Dokumen internal dinas. Contoh pencatatan setoran, bukan bukti penyetoran bank yang sah.':'Contoh dokumen untuk pemohon. Nominal berasal dari penetapan pada sesi demo.' }}</p>@endif<div class="paper-sign">Pangkalpinang, {{ \App\Support\DemoData::date(date('Y-m-d')) }}<div class="signature-demo">{{ $r['signed']&&$type==='Surat Persetujuan'?'SIMULASI TANDA TANGAN SELESAI':'AREA TANDA TANGAN CONTOH' }}</div><strong>{{ $type==='Surat Persetujuan'?'Kepala Dinas Pariwisata':'Pejabat sesuai kewenangan dokumen' }}</strong><p class="small text-secondary">Tanpa tanda tangan atau sertifikat elektronik asli.</p></div></article>
+@include('dokumen.letter')
 @endif
 @endsection
```

## resources/views/dokumen/skrd.blade.php

```diff
diff --git a/resources/views/dokumen/skrd.blade.php b/resources/views/dokumen/skrd.blade.php
new file mode 100644
--- /dev/null
+++ b/resources/views/dokumen/skrd.blade.php
@@ -0,0 +1,27 @@
+<article class="financial-paper print-area skrd-reference finance-skrd" aria-label="Pratinjau SKRD">
+<div class="paper-note">DEMO — BUKAN DOKUMEN RESMI</div>
+<div class="skrd-frame">
+<header class="skrd-head"><div><strong>PEMERINTAH KOTA PANGKALPINANG<br>DINAS PARIWISATA</strong><small>Jalan Rasa Kunda, Kel. Sriwijaya</small></div><h3>SURAT KETETAPAN RETRIBUSI DAERAH<br>(SKRD)</h3></header>
+<div class="skrd-top"><div class="skrd-data"><dl class="skrd-period"><dt>MASA</dt><dd>: <span data-period-month>{{ \Carbon\Carbon::parse($cfg['period'].'-01')->locale('id')->translatedFormat('F') }}</span></dd><dt>TAHUN</dt><dd>: <span data-period-year>{{ substr($cfg['period'],0,4) }}</span></dd></dl><dl class="skrd-identities"><dt>NAMA</dt><dd>: <span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd>: <span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NOMOR POKOK WAJIB RETRIBUSI (NPWR)</dt><dd>: <span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd><dt>TANGGAL JATUH TEMPO</dt><dd>: <span data-template-value="due">{{ $cfg['due'] }}</span></dd></dl></div><aside>NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></aside></div>
+<table class="skrd-grid"><colgroup><col style="width:6%">@for($j=0;$j<8;$j++)<col style="width:4.25%">@endfor<col style="width:35%"><col style="width:25%"></colgroup><thead><tr><th>NO.</th><th colspan="8">KODE REKENING</th><th>URAIAN RETRIBUSI</th><th><span class="skrd-money">JUMLAH <span>(Rp)</span></span></th></tr></thead><tbody data-finance-rows data-skrd-rows>
+@foreach($cfg['rows'] as $row)
+@for($line=0;$line<5;$line++)
+<tr><td>{{ $loop->index*5+$line+1 }}</td>
+@for($j=0;$j<8;$j++)<td>{{ $line===0?(explode('.',$row['code'])[$j]??''):'' }}</td>@endfor
+@if($line===0)<td class="skrd-account"><u>{{ $row['label'] }}</u></td><td></td>
+@elseif($line===1)<td rowspan="3" class="skrd-description"><span data-template-value="description">{{ $cfg['description'] }}</span><br>Tempat: {{ $loc['name'] }}</td><td rowspan="3"><span class="skrd-money">Rp <span>{{ number_format($row['amount'],0,',','.') }}</span></span></td>
+@elseif($line===4)<td>PEMBAYARAN SEWA UNTUK TANGGAL {{ $D::date($r['date']) }} · {{ $r['days'] }} hari</td><td></td>
+@endif
+</tr>
+@endfor
+@endforeach
+</tbody><tbody>
+<tr><td rowspan="{{ $cfg['percent']>0?6:4 }}"></td><td colspan="8" rowspan="{{ $cfg['percent']>0?6:4 }}"></td><td>Jumlah Ketetapan Pokok Retribusi</td><td><span class="skrd-money">Rp <span>{{ number_format($cfg['base'],0,',','.') }}</span></span></td></tr>
+@if($cfg['percent']>0)<tr><td>Keringanan disetujui ({{ $cfg['percent'] }}%)</td><td>− {{ $D::rupiah($cfg['base']-$cfg['total']) }}</td></tr><tr><td>Pokok setelah keringanan</td><td>{{ $D::rupiah($cfg['total']) }}</td></tr>@endif
+<tr><td>Jumlah Sanksi <span class="skrd-sanksi">a. &nbsp; Denda</span></td><td class="text-end">0%</td></tr><tr><td><span class="skrd-sanksi">b. &nbsp; Kenaikan</span></td><td>Rp &nbsp; 0</td></tr><tr class="skrd-total"><td>Jumlah Keseluruhan</td><td><span class="skrd-money">Rp <span>{{ number_format($cfg['total'],0,',','.') }}</span></span></td></tr>
+</tbody></table>
+<section class="skrd-attention"><p>Dengan huruf &nbsp; : &nbsp; {{ strtoupper($cfg['words']) }}</p><u>PERHATIAN</u> &nbsp; :<div class="finance-preserve"><span data-template-value="attention">{{ $cfg['attention'] }}</span></div></section>
+<section class="skrd-sign"><div>PANGKALPINANG, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>A.N. KEPALA DINAS PARIWISATA<br>KOTA PANGKALPINANG,<br><strong><span data-template-value="signer_title">{{ $cfg['signer_title'] }}</span></strong><div class="skrd-sign-space">RUANG TANDA TANGAN DEMO</div><u><span data-template-value="signer_name">{{ $cfg['signer_name'] }}</span></u><br><span data-template-value="signer_rank">{{ $cfg['signer_rank'] }}</span><br>NIP. <span data-template-value="signer_nip">{{ $cfg['signer_nip'] }}</span></div></section>
+<div class="skrd-cut">──────────── POTONG DI SINI ────────────</div>
+<section class="skrd-receipt"><div class="skrd-stub-number">NO. URUT<br><span data-template-value="number">{{ $cfg['number'] }}</span></div><strong>TANDA TERIMA</strong><dl class="skrd-stub-fields"><dt>NAMA</dt><dd>: <span data-template-value="payer">{{ $cfg['payer'] }}</span></dd><dt>ALAMAT</dt><dd>: <span data-template-value="address">{{ $cfg['address'] }}</span></dd><dt>NPWR</dt><dd>: <span data-template-value="npwr">{{ $cfg['npwr'] }}</span></dd></dl><div class="skrd-stub-sign">Pangkalpinang, <span data-template-value="issued_date">{{ \Carbon\Carbon::parse($cfg['issued_date'])->locale('id')->translatedFormat('d F Y') }}</span><br>Yang menerima,<div class="skrd-receiver-space"></div><span data-template-value="payer">{{ $cfg['payer'] }}</span></div></section>
+</div></article>
```

## resources/views/kalender/index.blade.php

```diff
diff --git a/resources/views/kalender/index.blade.php b/resources/views/kalender/index.blade.php
--- a/resources/views/kalender/index.blade.php
+++ b/resources/views/kalender/index.blade.php
@@ -1,12 +1,12 @@
 @extends('layouts.app')
 @section('content')
 @php
-$events=$requests->whereNotIn('status',['ditolak'])->filter(fn($r)=>in_array($r['status'],['ttd','serah','selesai'])&&(!$selectedLocation||$r['location_id']==$selectedLocation));
+$events=$requests->whereNotIn('status',['ditolak'])->filter(fn($r)=>(!$selectedLocation||$r['location_id']==$selectedLocation));
 $selectedDay=(int)request('day'); $agenda=[];
 for($day=1;$day<=$month->daysInMonth;$day++){ $date=$month->day($day)->toDateString();$dayEvents=$events->filter(fn($r)=>$date>=$r['date']&&$date<=\Carbon\CarbonImmutable::parse($r['date'])->addDays($r['days']-1)->toDateString());if($dayEvents->count())$agenda[$day]=$dayEvents; }
 $url=fn($changes)=>route('kalender',array_merge(['month'=>$month->format('Y-m'),'lokasi'=>$selectedLocation],$changes));
 @endphp
 <div class="row g-5"><div class="col-lg min-w-0 vstack gap-4"><div class="d-flex flex-wrap align-items-center justify-content-between gap-3"><div class="d-flex align-items-center gap-2"><a class="cal-nav" href="{{ $url(['month'=>$month->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" size="18"/></a><span class="fw-semibold text-center" style="width:160px">{{ $month->translatedFormat('F Y') }}</span><a class="cal-nav" href="{{ $url(['month'=>$month->addMonth()->format('Y-m')]) }}" aria-label="Bulan berikutnya"><x-icon name="chevron-right" size="18"/></a></div><form method="get"><input type="hidden" name="month" value="{{ $month->format('Y-m') }}"><select name="lokasi" class="form-select" aria-label="Lokasi" onchange="this.form.requestSubmit()"><option value="">Semua lokasi</option>@foreach($locations as $loc)<option value="{{ $loc['id'] }}" @selected($selectedLocation==$loc['id'])>{{ $loc['name'] }}</option>@endforeach</select></form></div>
 <div class="card overflow-hidden p-2"><div class="cal-grid">@foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $label)<div class="cal-dow">{{ $label }}</div>@endforeach @for($i=0;$i<($month->dayOfWeekIso-1);$i++)<div></div>@endfor @for($day=1;$day<=$month->daysInMonth;$day++)<a class="cal-cell {{ $selectedDay===$day?'act':(isset($agenda[$day])?'has':'') }}" href="{{ $url(['day'=>$selectedDay===$day?null:$day]) }}"><span class="t-12 fw-medium">{{ $day }}</span><span class="cal-dots">@foreach(($agenda[$day]??collect())->take(3) as $ev)<span class="cal-dot ev-{{ $ev['id']%10 }}"></span>@endforeach</span></a>@endfor</div></div><p class="t-12 text-secondary">Setiap warna mewakili kegiatan yang berbeda. Klik tanggal untuk menyaring agenda; klik lagi untuk menampilkan semua.</p></div>
-<aside class="col-side"><section class="card agenda-card"><div class="d-flex justify-content-between align-items-center p-4 border-bottom"><h2 class="card-title">{{ $selectedDay?$selectedDay.' '.$month->translatedFormat('F Y'):'Agenda '.$month->translatedFormat('F') }}</h2>@if($selectedDay)<a class="t-12" href="{{ $url([]) }}">Semua tanggal</a>@endif</div><div class="agenda-scroll">@forelse($agenda as $day=>$items)@if(!$selectedDay||$selectedDay===$day)<section class="p-4"><p class="t-12 text-secondary mb-2">{{ $day }} {{ $month->translatedFormat('F') }}</p><div class="vstack gap-2">@foreach($items as $ev)<a class="ev-item text-start" href="{{ route('detail',$ev['id']) }}"><p class="t-13 text-body fw-semibold">{{ $ev['name'] }}</p><p class="t-12 text-secondary mt-1">{{ $ev['activity'] }} · {{ $ev['days'] }} hari</p><p class="mono t-11 text-body-tertiary mt-1">{{ $ev['tracking'] }}</p><span class="t-12">{{ $locations[$ev['location_id']]['name'] }}</span></a>@endforeach</div></section>@endif @empty<div class="p-5 text-center text-secondary">Belum ada jadwal</div>@endforelse @if($selectedDay&&!isset($agenda[$selectedDay]))<div class="p-5 text-center text-secondary">Tidak ada kegiatan pada tanggal ini.</div>@endif</div></section></aside></div>
+<aside class="col-side"><section class="card agenda-card"><div class="d-flex justify-content-between align-items-center p-4 border-bottom"><h2 class="card-title">{{ $selectedDay?$selectedDay.' '.$month->translatedFormat('F Y'):'Agenda '.$month->translatedFormat('F') }}</h2>@if($selectedDay)<a class="t-12" href="{{ $url([]) }}">Semua tanggal</a>@endif</div><div class="agenda-scroll">@forelse($agenda as $day=>$items)@if(!$selectedDay||$selectedDay===$day)<section class="p-4"><p class="t-12 text-secondary mb-2">{{ $day }} {{ $month->translatedFormat('F') }}</p><div class="vstack gap-2">@foreach($items as $ev)<a class="ev-item text-start" href="{{ route('detail',$ev['id']) }}"><p class="t-13 text-body fw-semibold">{{ $ev['name'] }}</p><p class="t-12 text-secondary mt-1">{{ $ev['activity'] }} · {{ $ev['days'] }} hari</p><p class="mono t-11 text-body-tertiary mt-1">{{ $ev['tracking'] }}</p><x-status-badge :status="$ev['status']"/><small class="d-block">{{ $ev['signed']?'Disetujui · surat ditandatangani':'Tentatif · masih diproses' }}</small><span class="t-12">{{ $locations[$ev['location_id']]['name'] }}</span></a>@endforeach</div></section>@endif @empty<div class="p-5 text-center text-secondary">Belum ada jadwal</div>@endforelse @if($selectedDay&&!isset($agenda[$selectedDay]))<div class="p-5 text-center text-secondary">Tidak ada kegiatan pada tanggal ini.</div>@endif</div></section></aside></div>
 @endsection
```

## resources/views/laporan/index.blade.php

```diff
diff --git a/resources/views/laporan/index.blade.php b/resources/views/laporan/index.blade.php
--- a/resources/views/laporan/index.blade.php
+++ b/resources/views/laporan/index.blade.php
@@ -5,4 +5,5 @@
  $revenue=$paid->sum(fn($r)=>$r['bill']['total']??0); $avg=$done->count()?$done->avg(fn($r)=>\App\Support\DemoData::elapsed($r)):null;
 @endphp
 <div class="page-heading"><div><span class="eyebrow">MONITORING</span><h1>Statistik & Laporan</h1><p>Rekap dihitung dari seluruh data pada sesi demo ini.</p></div><a href="{{ route('export') }}" class="btn btn-outline-primary"><x-icon name="download" size="17"/> Unduh rekap CSV</a></div><div class="row g-3 mb-4">@foreach([['Total permohonan',$requests->count(),'Termasuk selesai dan ditolak'],['Retribusi diterima',\App\Support\DemoData::rupiah($revenue),'Pembayaran yang dikonfirmasi'],['Rata-rata penyelesaian',$avg!==null?number_format($avg,1,',','.').' hari':'—','Hari kerja sejak pengajuan']] as [$label,$value,$caption])<div class="col-md-4"><div class="stat-card"><div class="stat-top">{{ $label }}</div><strong style="font-size:26px">{{ $value }}</strong><small>{{ $caption }}</small></div></div>@endforeach</div><div class="row g-4"><div class="col-lg-6"><section class="panel p-4 h-100"><h2 class="mb-4">Permohonan per lokasi</h2>@foreach($locations as $loc)@php($count=$requests->where('location_id',$loc['id'])->count())<div class="bar-row"><span>{{ $loc['name'] }}</span><div class="bar-track"><div class="bar-fill" style="width:{{ $requests->count()?round($count/$requests->count()*100):0 }}%"></div></div><strong>{{ $count }}</strong></div>@endforeach</section></div><div class="col-lg-6"><section class="panel p-4 h-100"><h2 class="mb-4">Distribusi status</h2>@foreach($requests->groupBy('status') as $status=>$group)<div class="d-flex align-items-center justify-content-between py-2 border-bottom"><x-status-badge :status="$status"/><strong class="small">{{ $group->count() }}</strong></div>@endforeach</section></div></div><div class="info-strip mt-4"><x-icon name="clock"/><span>Target layanan: 3 hari kerja sejak permohonan disubmit, bukan 3 hari per tahap. Demo menghitung Senin–Jumat; kalender hari libur resmi belum dimasukkan.</span></div>
+<section class="panel p-4 mt-4"><h2 class="mb-3">Kinerja Layanan & Rekap Lokasi</h2><p>{{ $done->filter(fn($r)=>\App\Support\DemoData::elapsed($r)<=3)->count() }} dari {{ $done->count() }} permohonan selesai dalam target 3 hari kerja.</p><div class="table-responsive"><table class="table"><thead><tr><th>Lokasi</th><th>Permohonan</th><th>Selesai</th><th>Tagihan ditetapkan</th><th>Diterima</th><th>Belum dibayar</th></tr></thead><tbody>@foreach($locations as $loc)@php($group=$requests->where('location_id',$loc['id']))<tr><td>{{ $loc['name'] }}</td><td>{{ $group->count() }}</td><td>{{ $group->where('status','selesai')->count() }}</td><td>{{ \App\Support\DemoData::rupiah($group->sum(fn($r)=>$r['bill']['total']??0)) }}</td><td>{{ \App\Support\DemoData::rupiah($group->filter(fn($r)=>$r['payment'])->sum(fn($r)=>$r['bill']['total']??0)) }}</td><td>{{ \App\Support\DemoData::rupiah($group->filter(fn($r)=>!$r['payment'])->sum(fn($r)=>$r['bill']['total']??0)) }}</td></tr>@endforeach</tbody></table></div></section>
 @endsection
```

## resources/views/layouts/app.blade.php

```diff
diff --git a/resources/views/layouts/app.blade.php b/resources/views/layouts/app.blade.php
--- a/resources/views/layouts/app.blade.php
+++ b/resources/views/layouts/app.blade.php
@@ -18,7 +18,7 @@
 if(in_array($role,['kadis','admin'])) $nav[]=['laporan','Statistik & Laporan','bar-chart-3',route('laporan')];
 $nav[]=['riwayat','Log & Riwayat','history',route('listing','riwayat')];
 if($role==='admin') $nav=array_merge($nav,[['pengguna','Kelola User & Role','users',route('pengguna')],['master','Data Master','database',route('master')]]);
-$titles=['ringkasan'=>'Ringkasan Pelayanan','antrean'=>'Antrean Permohonan','dashboard'=>'Antrean Permohonan','baru'=>'Permohonan Baru','detail'=>'Detail Permohonan','draft'=>'Editor Draft Surat','verifikasi'=>'Verifikasi Draft Surat','rilis'=>'Rilis Surat Persetujuan','retribusi'=>'Penetapan & Pembayaran Retribusi','tanda-tangan'=>'Tanda Tangan Digital','serah-terima'=>'Serah Terima & Pengambilan Berkas','arsip'=>'Arsip Digital','kalender'=>'Kalender Jadwal','riwayat'=>'Log & Riwayat','pengguna'=>'Kelola User & Role','master'=>'Data Master','laporan'=>'Statistik & Laporan'];
+$titles=['pratinjau'=>'Pratinjau Dokumen','ringkasan'=>'Ringkasan Pelayanan','antrean'=>'Antrean Permohonan','dashboard'=>'Antrean Permohonan','baru'=>'Permohonan Baru','detail'=>'Detail Permohonan','draft'=>'Editor Draft Surat','verifikasi'=>'Verifikasi Draft Surat','rilis'=>'Rilis Surat Persetujuan','retribusi'=>'Penetapan & Pembayaran Retribusi','tanda-tangan'=>'Tanda Tangan Digital','serah-terima'=>'Serah Terima & Pengambilan Berkas','arsip'=>'Arsip Digital','kalender'=>'Kalender Jadwal','riwayat'=>'Log & Riwayat','pengguna'=>'Kelola User & Role','master'=>'Data Master','laporan'=>'Statistik & Laporan'];
 $activePage=($page??'')==='dashboard'||($page??'')==='detail'?'antrean':($page??'');
 @endphp
 <a class="visually-hidden-focusable" href="#main">Lewati navigasi</a>
```

## resources/views/permohonan/action.blade.php

```diff
diff --git a/resources/views/permohonan/action.blade.php b/resources/views/permohonan/action.blade.php
--- a/resources/views/permohonan/action.blade.php
+++ b/resources/views/permohonan/action.blade.php
@@ -2,12 +2,13 @@
 <section class="panel action-panel p-4 mb-4"><div class="panel-title"><div><h2 class="mt-2">{{ $statuses[$r['status']] }}</h2></div><x-icon name="edit"/></div>
 <form method="post" action="{{ route('demo.act',$r['id']) }}">@csrf
 @if($r['status']==='masuk')<p class="text-secondary">Tinjau kelengkapan dan keabsahan berkas sebelum diteruskan ke Petugas Layanan.</p><label class="form-label" for="note">Catatan pemeriksaan <small>(wajib untuk penolakan)</small></label><textarea class="form-control mb-3" name="note" id="note" rows="3" maxlength="2000"></textarea><div class="action-buttons"><button class="btn btn-outline-danger" name="action" value="reject" data-note-required>Tolak permohonan</button><button class="btn btn-primary" name="action" value="approve">Setujui & teruskan <x-icon name="arrow" size="16"/></button></div>
-@elseif($r['status']==='draft')<p class="text-secondary">Versi {{ $r['version'] }} · Simpan perubahan sebelum meninggalkan halaman.</p><div class="row g-4"><div class="col-lg-6"><label class="form-label" for="draft">Narasi draft surat</label><textarea id="draft" class="form-control draft-editor mb-3" name="draft" rows="13" minlength="30" maxlength="15000" required>{{ old('draft',$D::draft($r,$locations[$r['location_id']]['name'])) }}</textarea></div><div class="col-lg-6"><p class="form-label">Pratinjau Surat</p><div class="draft-preview" data-live-draft>{{ old('draft',$D::draft($r,$locations[$r['location_id']]['name'])) }}</div></div></div><div class="action-buttons"><button class="btn btn-outline-primary" name="action" value="save">Simpan draft</button><button class="btn btn-primary" name="action" value="send">Kirim ke Kabid <x-icon name="arrow" size="16"/></button></div>
-@elseif(in_array($r['status'],['review_draft','verifikasi']))<div class="draft-preview mb-3">{{ $D::draft($r,$locations[$r['location_id']]['name']) }}</div><label for="note" class="form-label">Catatan <small>(wajib jika dikembalikan)</small></label><textarea name="note" id="note" class="form-control mb-3" rows="3" maxlength="2000"></textarea><div class="action-buttons"><button class="btn btn-outline-primary" name="action" value="revise" data-note-required>Kembalikan untuk revisi</button><button class="btn btn-primary" name="action" value="approve">{{ $r['status']==='verifikasi'?'Verifikasi draft':'Setujui draft' }} <x-icon name="check" size="16"/></button></div>
-@elseif($r['status']==='rilis')<div class="draft-preview mb-3">{{ $D::draft($r,$locations[$r['location_id']]['name']) }}</div><p class="text-secondary">Rilis meneruskan surat ke penetapan retribusi. Surat belum ditandatangani dan belum menjadi izin final.</p><button name="action" value="release" class="btn btn-primary">Rilis ke Bendahara <x-icon name="arrow" size="16"/></button>
-@elseif($r['status']==='tagihan')@php($subtotal=$locations[$r['location_id']]['rate']*$r['days'])<div class="summary-line"><span>Tarif contoh × {{ $r['days'] }} hari</span><strong>{{ $D::rupiah($subtotal) }}</strong></div><div class="summary-line"><span>Keringanan disetujui</span><strong>{{ $r['discount']==='disetujui'?'25%':'0%' }}</strong></div><div class="total-line"><span>Total penetapan</span><strong>{{ $D::rupiah($subtotal*($r['discount']==='disetujui'?0.75:1)) }}</strong></div>@if($r['discount']==='diajukan')<div class="alert alert-warning">Keputusan keringanan belum tersedia. Penetapan menunggu keputusan pejabat berwenang; role pemberi keputusan tidak diasumsikan dalam demo.</div>@endif<button class="btn btn-primary" name="action" value="invoice" @disabled($r['discount']==='diajukan')>Terbitkan SKRD</button>
+@elseif($r['status']==='draft')
+@include('persuratan.draft.editor')
+@elseif(in_array($r['status'],['review_draft','verifikasi']))@include('dokumen.letter')<label for="note" class="form-label">Catatan <small>(wajib jika dikembalikan)</small></label><textarea name="note" id="note" class="form-control mb-3" rows="3" maxlength="2000"></textarea><div class="action-buttons"><button class="btn btn-outline-primary" name="action" value="revise" data-note-required>Kembalikan untuk revisi</button><button class="btn btn-primary" name="action" value="approve">{{ $r['status']==='verifikasi'?'Verifikasi draft':'Setujui draft' }} <x-icon name="check" size="16"/></button></div>
+@elseif($r['status']==='rilis')@include('dokumen.letter')<p class="text-secondary">Rilis meneruskan surat ke penetapan retribusi. Surat belum ditandatangani dan belum menjadi izin final.</p><button name="action" value="release" class="btn btn-primary">Rilis ke Bendahara <x-icon name="arrow" size="16"/></button>
+@elseif($r['status']==='tagihan')@php($subtotal=$locations[$r['location_id']]['rate']*$r['days'])<div class="summary-line"><span>Tarif contoh × {{ $r['days'] }} hari</span><strong>{{ $D::rupiah($subtotal) }}</strong></div><div class="summary-line"><span>Keringanan disetujui</span><strong>{{ $r['discount']==='disetujui'?'25%':'0%' }}</strong></div><div class="total-line"><span>Total penetapan</span><strong>{{ $D::rupiah($subtotal*($r['discount']==='disetujui'?0.75:1)) }}</strong></div>@if($r['discount']==='diajukan')<div class="alert alert-warning">Keputusan keringanan belum tersedia. Penetapan menunggu keputusan pejabat berwenang; role pemberi keputusan tidak diasumsikan dalam demo.</div>@endif<label class="form-label">Tarif per hari untuk penetapan (Rp)</label><input class="form-control mb-2" type="number" name="rate" min="0" max="100000000" step="1" value="{{ $locations[$r['location_id']]['rate'] }}"><label class="form-label">Dasar tarif / alasan penyesuaian</label><input class="form-control mb-3" name="rate_reason" maxlength="500" value="Menggunakan tarif lokasi pada data master demo" required><p class="small text-secondary">Total akhir dihitung ulang saat diterbitkan: tarif × durasi, dikurangi potongan yang disetujui. Setelah terbit nominal dikunci.</p><button class="btn btn-primary" name="action" value="invoice" @disabled($r['discount']==='diajukan')>Terbitkan SKRD</button>
 @elseif($r['status']==='bayar')<div class="total-line"><span>Tagihan ditetapkan</span><strong>{{ $D::rupiah($r['bill']['total']) }}</strong></div><label class="form-label" for="paymentDate">Tanggal Pembayaran</label><input type="date" class="form-control mb-3" name="payment_date" id="paymentDate" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required><div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="paymentChecked" name="payment_checked" value="1" required><label class="form-check-label" for="paymentChecked">Simulasikan bukti pembayaran sudah diperiksa dan setoran sudah dicatat.</label></div><p class="small text-secondary">Membuat contoh STBP dan STS pada sesi demo, tanpa transaksi uang atau pengiriman tagihan.</p><button class="btn btn-primary" name="action" value="pay">Konfirmasi Pembayaran</button>
-@elseif($r['status']==='ttd')<div class="draft-preview mb-3">{{ $D::draft($r,$locations[$r['location_id']]['name']) }}</div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="signConfirmed" name="confirmed" value="1" required><label class="form-check-label" for="signConfirmed">Saya memahami ini simulasi persetujuan, bukan tanda tangan elektronik tersertifikasi.</label></div><button class="btn btn-primary" name="action" value="sign">Tanda Tangani <x-icon name="edit" size="16"/></button>
+@elseif($r['status']==='ttd')@include('dokumen.letter')<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="signConfirmed" name="confirmed" value="1" required><label class="form-check-label" for="signConfirmed">Saya memahami ini simulasi persetujuan, bukan tanda tangan elektronik tersertifikasi.</label></div><button class="btn btn-primary" name="action" value="sign">Tanda Tangani <x-icon name="edit" size="16"/></button>
 @elseif($r['status']==='serah')<p>Diserahkan kepada pemohon: <strong>Surat Persetujuan, SKRD, dan STBP</strong>. STS tetap sebagai dokumen internal dinas.</p><label for="recipient" class="form-label">Nama penerima berkas</label><input class="form-control mb-3" id="recipient" name="recipient" maxlength="100" required placeholder="Nama penerima contoh"><div class="form-check mb-3"><input class="form-check-input" id="handoverConfirmed" type="checkbox" name="confirmed" value="1" required><label for="handoverConfirmed" class="form-check-label">Simulasikan pemeriksaan kelengkapan dan penyerahan dokumen fisik telah selesai.</label></div><button class="btn btn-primary" name="action" value="handover">Konfirmasi Serah Terima <x-icon name="check" size="16"/></button>
 @endif
 </form></section>
```

## resources/views/permohonan/filters.blade.php

```diff
diff --git a/resources/views/permohonan/filters.blade.php b/resources/views/permohonan/filters.blade.php
--- a/resources/views/permohonan/filters.blade.php
+++ b/resources/views/permohonan/filters.blade.php
@@ -2,4 +2,5 @@
 <div class="collapse {{ request('lokasi')||request('status')?'show':'' }}" id="filterOptions"><div class="d-flex flex-wrap gap-3 py-4"><select class="form-select w-auto" name="lokasi" aria-label="Lokasi"><option value="">Semua lokasi</option>@foreach($locations as $loc)<option value="{{ $loc['id'] }}" @selected(request('lokasi')==$loc['id'])>{{ $loc['name'] }}</option>@endforeach</select><select name="status" class="form-select w-auto" aria-label="Status"><option value="">Semua status</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select><button class="btn btn-primary">Terapkan</button><a class="btn btn-outline-secondary" href="{{ route('listing',$kind) }}">Reset</a></div></div>
 @if(request('sla'))<input type="hidden" name="sla" value="{{ request('sla') }}">@endif
 <input type="hidden" name="period" value="{{ request('period','all') }}">@if(request('mine'))<input type="hidden" name="mine" value="1">@endif
-<div class="ref-periods">@foreach(['all'=>'Semua waktu','today'=>'Hari ini','week'=>'7 hari','month'=>'Bulan ini','lastmonth'=>'Bulan lalu'] as $key=>$label)<a class="{{ request('period','all')===$key?'active':'' }}" href="{{ $url(['period'=>$key]) }}">{{ $label }}</a>@endforeach<a class="{{ request('mine')?'active':'' }}" href="{{ $url(['mine'=>request('mine')?null:1]) }}">Perlu tindakan saya</a></div></form>
+<div class="ref-periods">@foreach(['all'=>'Semua waktu','today'=>'Hari ini','week'=>'7 hari','month'=>'Bulan ini','lastmonth'=>'Bulan lalu'] as $key=>$label)<a class="{{ request('period','all')===$key?'active':'' }}" href="{{ $url(['period'=>$key]) }}">{{ $label }}</a>@endforeach
+@if($kind==='antrean')<a class="{{ request('mine')?'active':'' }}" href="{{ $url(['mine'=>request('mine')?null:1]) }}">Perlu tindakan saya</a>@endif</div></form>
```

## resources/views/permohonan/index.blade.php

```diff
diff --git a/resources/views/permohonan/index.blade.php b/resources/views/permohonan/index.blade.php
--- a/resources/views/permohonan/index.blade.php
+++ b/resources/views/permohonan/index.blade.php
@@ -18,7 +18,11 @@
 @endif
 @if(request('sla'))<div class="sla-filter-note"><x-icon name="clock" size="16"/><span>{{ request('sla')==='overdue'?'Menampilkan permohonan melewati 3 hari kerja sejak pengajuan.':'Menampilkan permohonan pada hari kerja ke-3 sejak pengajuan.' }}</span><a href="{{ $url(['sla'=>null]) }}">Hapus filter SLA</a></div>@endif
 @include('permohonan.filters')
-<x-request-table :rows="$rows" :locations="$locations"/>
+@if($kind==='arsip')
+<section class="panel table-responsive"><table class="table"><thead><tr><th>Pemohon</th><th>Penyerahan / penerima</th><th>Dokumen lengkap</th><th>Tindakan</th></tr></thead><tbody>@foreach($rows as $record)<tr><td><strong>{{ $record['name'] }}</strong><small class="d-block">{{ $record['tracking'] }}</small></td><td>{{ $record['completed'] }}<small class="d-block">{{ $record['recipient'] }}</small></td><td><div class="d-flex gap-2 flex-wrap">@foreach(['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $slug=>$label)@if(in_array($label,$record['documents']))<a class="btn btn-light btn-sm" href="{{ route('document',[$record['id'],$slug,'from'=>'arsip']) }}">{{ $label }}</a>@endif @endforeach</div></td><td><a href="{{ route('detail',$record['id']) }}">Detail</a></td></tr>@endforeach</tbody></table></section>
+@elseif($kind==='riwayat')
+<section class="panel table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Permohonan</th><th>Pelaku / peran</th><th>Tindakan & catatan</th></tr></thead><tbody>@foreach($rows as $record)@foreach(array_reverse($record['history']) as $entry)<tr><td class="text-nowrap">{{ $entry['at'] }}</td><td><a href="{{ route('detail',$record['id']) }}">{{ $record['tracking'] }}</a><small class="d-block">{{ $record['name'] }}</small></td><td>{{ $roles[$entry['role']]??$entry['role'] }}<small class="d-block">Pengguna demo</small></td><td><strong>{{ $entry['action'] }}</strong><small class="d-block">{{ $entry['note'] }}</small></td></tr>@endforeach @endforeach</tbody></table></section>
+@else<x-request-table :rows="$rows" :locations="$locations"/>@endif
 <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 t-12 text-secondary"><span>Menampilkan {{ $rows->firstItem()??0 }}–{{ $rows->lastItem()??0 }} dari {{ $rows->total() }} permohonan</span><div class="btn-group"><a class="btn btn-outline-secondary btn-sm {{ $rows->onFirstPage()?'disabled':'' }}" href="{{ $rows->previousPageUrl() }}">Sebelumnya</a><span class="btn btn-light btn-sm">{{ $rows->currentPage() }} / {{ max(1,$rows->lastPage()) }}</span><a class="btn btn-outline-secondary btn-sm {{ !$rows->hasMorePages()?'disabled':'' }}" href="{{ $rows->nextPageUrl() }}">Berikutnya</a></div></div>
 </div>
 @endsection
```

## resources/views/permohonan/work.blade.php

```diff
diff --git a/resources/views/permohonan/work.blade.php b/resources/views/permohonan/work.blade.php
--- a/resources/views/permohonan/work.blade.php
+++ b/resources/views/permohonan/work.blade.php
@@ -3,12 +3,14 @@
 @php($D=\App\Support\DemoData::class)
 @if(!$r)<div class="empty-state"><x-icon name="inbox" size="40"/><h2>Tidak ada permohonan menunggu tindakan</h2><p>Permohonan akan muncul setelah mencapai tahap ini.</p></div>
 @else
-<div class="row g-5"><aside class="col-queue"><section class="card overflow-hidden"><div class="p-4 border-bottom fw-semibold">{{ ['draft'=>'Menunggu Draft','verifikasi'=>'Menunggu Verifikasi','rilis'=>'Siap Dirilis','retribusi'=>'Antrean Retribusi','tanda-tangan'=>'Menunggu Tanda Tangan','serah-terima'=>'Siap Diambil'][$kind] }} ({{ $queue->count() }})</div><form method="get" class="p-3 border-bottom"><label for="work-search" class="visually-hidden">Cari pemohon atau kode</label><input id="work-search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari pemohon / kode…"><button class="btn btn-light btn-sm mt-2">Cari</button>@if(request('q'))<a class="btn btn-link btn-sm" href="{{ route('listing',$kind) }}">Reset</a>@endif</form>@foreach($queue as $item)<a class="queue-option {{ $r['id']===$item['id']?'selected':'' }}" href="{{ route('listing',['kind'=>$kind,'selected'=>$item['id']]) }}"><strong>{{ $item['name'] }}</strong><small class="mono">{{ $item['tracking'] }}</small><x-status-badge :status="$item['status']"/></a>@endforeach</section>@if($kind==='serah-terima')<section class="panel p-3 mt-4"><h3>Baru Diserahkan</h3>@forelse($requests->where('status','selesai')->sortByDesc('completed')->take(4) as $done)<a class="queue-option" href="{{ route('detail',$done['id']) }}"><strong>{{ $done['name'] }}</strong><small>Diterima: {{ $done['recipient'] }}</small></a>@empty<p class="small text-secondary">Belum ada penyerahan.</p>@endforelse<a href="{{ route('listing','arsip') }}" class="small">Lihat semua di Arsip Digital</a></section>@endif</aside>
+<div class="row g-4"><aside class="col-queue"><section class="card overflow-hidden"><div class="p-4 border-bottom fw-semibold">{{ ['draft'=>'Menunggu Draft','verifikasi'=>'Menunggu Verifikasi','rilis'=>'Siap Dirilis','retribusi'=>'Antrean Retribusi','tanda-tangan'=>'Menunggu Tanda Tangan','serah-terima'=>'Siap Diambil'][$kind] }} ({{ $queue->count() }})</div><form method="get" class="p-3 border-bottom"><label for="work-search" class="visually-hidden">Cari pemohon atau kode</label><input id="work-search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari pemohon / kode…"><button class="btn btn-light btn-sm mt-2">Cari</button>@if(request('q'))<a class="btn btn-link btn-sm" href="{{ route('listing',$kind) }}">Reset</a>@endif</form>@foreach($queue as $item)<a class="queue-option {{ $r['id']===$item['id']?'selected':'' }}" href="{{ route('listing',['kind'=>$kind,'selected'=>$item['id']]) }}"><strong>{{ $item['name'] }}</strong><small class="mono">{{ $item['tracking'] }}</small><x-status-badge :status="$item['status']"/></a>@endforeach</section>@if($kind==='serah-terima')<section class="panel p-3 mt-4"><h3>Baru Diserahkan ({{ $requests->where('status','selesai')->count() }})</h3>@forelse($requests->where('status','selesai')->sortByDesc('completed')->take(4) as $done)<a class="queue-option" href="{{ route('detail',$done['id']) }}"><strong>{{ $done['name'] }}</strong><small>Diterima: {{ $done['recipient'] }}</small></a>@empty<p class="small text-secondary">Belum ada penyerahan.</p>@endforelse<a href="{{ route('listing','arsip') }}" class="small">Lihat semua di Arsip Digital</a></section>@endif</aside>
 <div class="col-lg min-w-0 vstack gap-4"><div class="d-flex justify-content-between align-items-start"><div><h2 class="section-heading">{{ $r['name'] }}</h2><p class="mono text-secondary">{{ $r['tracking'] }}</p></div><a class="btn btn-outline-secondary btn-sm" href="{{ route('detail',$r['id']) }}">Detail Permohonan <x-icon name="chevron-right" size="16"/></a></div>
-@if($kind!=='draft')<section class="panel p-5"><div class="panel-title"><h2>Ringkasan &amp; Kelengkapan Berkas</h2><x-status-badge :status="$r['status']"/></div><dl class="detail-grid"><div><dt>Pemohon</dt><dd>{{ $r['name'] }}</dd></div><div><dt>Kegiatan</dt><dd>{{ $r['activity'] }}</dd></div><div><dt>Lokasi</dt><dd>{{ $locations[$r['location_id']]['name'] }}</dd></div><div><dt>Waktu</dt><dd>{{ $r['date'] }} · {{ $r['days'] }} hari</dd></div></dl></section>@endif
+@if($kind!=='draft')<section class="panel p-4"><div class="panel-title"><h2>Ringkasan &amp; Kelengkapan Berkas</h2><x-status-badge :status="$r['status']"/></div><dl class="detail-grid"><div><dt>Pemohon</dt><dd>{{ $r['name'] }}</dd></div><div><dt>Kegiatan</dt><dd>{{ $r['activity'] }}</dd></div><div><dt>Lokasi</dt><dd>{{ $locations[$r['location_id']]['name'] }}</dd></div><div><dt>Waktu</dt><dd>{{ $r['date'] }} · {{ $r['days'] }} hari</dd></div></dl></section>@endif
 @if($kind==='retribusi')@include('permohonan.financial-documents')@endif
+
+@if(in_array($kind,['serah-terima','tanda-tangan']))<section class="panel p-4"><h2 class="mb-4">Cetak Berkas Final</h2><p class="small text-secondary mb-3">Surat Persetujuan, SKRD, dan STBP untuk pemohon. STS merupakan arsip internal.</p><div class="row g-3">@foreach(['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $slug=>$label)<div class="col-sm-6"><div class="document-row"><x-icon name="file-text"/><div class="flex-grow-1"><strong>{{ $label }}</strong><small>{{ $slug==='sts'?'Internal dinas':'Salinan pemohon' }}</small></div>@if(in_array($label,$r['documents']))<a class="btn btn-outline-secondary btn-sm" href="{{ route('document',[$r['id'],$slug,'from'=>$kind]) }}">Pratinjau / Cetak</a>@else<span class="text-secondary t-12">Belum terbit</span>@endif</div></div>@endforeach</div></section>@endif
 @if($canAct)@include('permohonan.action')@endif
-@if(in_array($kind,['serah-terima','tanda-tangan']))<section class="panel p-5"><h2 class="mb-4">Cetak Berkas Final</h2><p class="small text-secondary mb-3">Surat Persetujuan, SKRD, dan STBP untuk pemohon. STS merupakan arsip internal.</p><div class="row g-3">@foreach(['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'] as $slug=>$label)<div class="col-sm-6"><div class="document-row"><x-icon name="file-text"/><div class="flex-grow-1"><strong>{{ $label }}</strong><small>{{ $slug==='sts'?'Internal dinas':'Salinan pemohon' }}</small></div>@if(in_array($label,$r['documents']))<a class="btn btn-outline-secondary btn-sm" href="{{ route('document',[$r['id'],$slug]) }}">Pratinjau / Cetak</a>@else<span class="text-secondary t-12">Belum terbit</span>@endif</div></div>@endforeach</div></section>@endif
+<section class="panel p-4"><h2 class="mb-3">Riwayat Pemeriksaan</h2><ul class="timeline">@foreach($r['history'] as $entry)<li><strong>{{ $entry['action'] }}</strong><small>{{ $roles[$entry['role']]??$entry['role'] }} · {{ $entry['at'] }}</small><p>{{ $entry['note'] }}</p></li>@endforeach</ul></section>
 </div></div>
 @endif
 @endsection
```

## resources/views/persuratan/draft/editor.blade.php

```diff
diff --git a/resources/views/persuratan/draft/editor.blade.php b/resources/views/persuratan/draft/editor.blade.php
new file mode 100644
--- /dev/null
+++ b/resources/views/persuratan/draft/editor.blade.php
@@ -0,0 +1,8 @@
+@php($letter=\App\Support\DemoData::letter($r))
+<p class="text-secondary">Versi {{ $r['version'] }} · Petugas Layanan menyusun surat; Kabid meninjau dan memberi catatan.</p>
+<div class="draft-layout"><div><section class="mb-4"><h3 class="fs-6">Identitas surat</h3><p class="small text-secondary">Kop dan pejabat mengikuti template dinas. Nomor berikut adalah nomor demo.</p>
+@foreach(['number'=>'Nomor surat demo','date'=>'Tanggal surat','subject'=>'Perihal','attachment'=>'Lampiran'] as $key=>$label)<label class="form-label" for="letter-{{ $key }}">{{ $label }}</label><input id="letter-{{ $key }}" class="form-control mb-3" name="letter[{{ $key }}]" type="{{ $key==='date'?'date':'text' }}" value="{{ old('letter.'.$key,$letter[$key]) }}" maxlength="200" required data-letter-input="{{ $key }}">@endforeach</section>
+<section class="panel p-3 mb-4"><h3 class="fs-6">Data dari permohonan</h3><dl class="detail-grid"><div><dt>Pemohon</dt><dd>{{ $r['name'] }}</dd></div><div><dt>Penanggung jawab</dt><dd>{{ $r['pic'] }}</dd></div><div><dt>Lokasi</dt><dd>{{ $locations[$r['location_id']]['name'] }}</dd></div><div><dt>Kegiatan / waktu</dt><dd>{{ $r['activity'] }} · {{ $r['date'] }} · {{ $r['days'] }} hari</dd></div></dl><a class="small d-block mt-3" href="{{ route('detail',$r['id']) }}" target="_blank" rel="noopener">Tinjau data sumber di tab baru</a><p class="small text-secondary mb-0">Jika data sumber keliru, koordinasikan koreksi dengan Sekretariat. Identitas tidak diketik ulang dalam narasi surat.</p></section>
+@foreach(['opening'=>'Pembukaan','body'=>'Isi persetujuan','conditions'=>'Ketentuan pelaksanaan','closing'=>'Penutup'] as $key=>$label)<label class="form-label" for="letter-{{ $key }}">{{ $label }}</label><textarea id="letter-{{ $key }}" class="form-control mb-3" name="letter[{{ $key }}]" rows="{{ $key==='conditions'?7:4 }}" maxlength="5000" required data-letter-input="{{ $key }}">{{ old('letter.'.$key,$letter[$key]) }}</textarea>@endforeach
+<div class="action-buttons"><button class="btn btn-outline-primary" name="action" value="save">Simpan draft</button><button class="btn btn-primary" name="action" value="send">Kirim ke Kabid</button></div></div>
+<div><h3 class="fs-6">Pratinjau seluruh surat</h3><p class="small text-secondary">Mengikuti perubahan isian. Simpan sebelum meninggalkan halaman.</p>@include('dokumen.letter')</div></div>
```

## resources/views/riwayat/index.blade.php

```diff
diff --git a/resources/views/riwayat/index.blade.php b/resources/views/riwayat/index.blade.php
new file mode 100644
--- /dev/null
+++ b/resources/views/riwayat/index.blade.php
@@ -0,0 +1,8 @@
+@extends('layouts.app')
+@section('content')
+<nav class="d-flex gap-2 mb-4"><a class="btn btn-primary" href="{{ route('listing','riwayat') }}">Riwayat Aktivitas</a>@if(in_array($role,['sekretariat','admin']))<a class="btn btn-outline-secondary" href="{{ route('listing',['kind'=>'riwayat','tab'=>'hapus']) }}">Riwayat Hapus</a>@endif</nav>
+<a class="btn btn-light btn-sm mb-3" href="{{ route('export',['kind'=>'riwayat']) }}">Unduh Rekap Permohonan CSV</a><p class="text-secondary">Catatan aktivitas seluruh permohonan, diurutkan berdasarkan waktu kejadian.</p>
+<form class="d-flex flex-wrap gap-2 mb-4" method="get"><input class="form-control flex-grow-1 w-auto" name="q" value="{{ request('q') }}" placeholder="Cari pemohon, kode, tindakan atau catatan…" aria-label="Cari aktivitas"><select class="form-select w-auto" name="actor" aria-label="Pelaku / peran"><option value="">Semua peran</option>@foreach($roles as $key=>$label)<option value="{{ $key }}" @selected(request('actor')===$key)>{{ $label }}</option>@endforeach</select><select class="form-select w-auto" name="period" aria-label="Periode">@foreach(['all'=>'Semua waktu','today'=>'Hari ini','week'=>'7 hari','month'=>'Bulan ini','lastmonth'=>'Bulan lalu'] as $key=>$label)<option value="{{ $key }}" @selected(request('period','all')===$key)>{{ $label }}</option>@endforeach</select><select class="form-select w-auto" name="sort" aria-label="Urutan"><option value="newest">Terbaru</option><option value="oldest" @selected(request('sort')==='oldest')>Terlama</option></select><button class="btn btn-primary">Terapkan</button><a class="btn btn-light" href="{{ route('listing','riwayat') }}">Reset</a></form>
+<section class="panel table-responsive"><table class="table audit-table"><thead><tr><th>No.</th><th>Permohonan</th><th>Aktivitas</th><th>Pelaku / peran</th><th>Waktu</th><th>Catatan</th></tr></thead><tbody>@forelse($logs as $entry)<tr data-event-at="{{ $entry['at'] }}"><td>{{ $logs->firstItem()+$loop->index }}</td><td><strong>{{ $entry['record']['name'] }}</strong><a class="d-block small" href="{{ route('detail',$entry['record']['id']) }}">{{ $entry['record']['tracking'] }}</a></td><td>{{ $entry['action'] }}</td><td>{{ $roles[$entry['role']]??$entry['role'] }}</td><td class="text-nowrap">{{ \Carbon\CarbonImmutable::parse($entry['at'])->translatedFormat('d M Y') }}<small class="d-block">{{ substr($entry['at'],11,5) }}</small></td><td>{{ $entry['note']?:'—' }}</td></tr>@empty<tr><td colspan="6" class="p-5 text-center">Tidak ada aktivitas sesuai filter.</td></tr>@endforelse</tbody></table></section>
+<div class="d-flex justify-content-between align-items-center mt-3"><span class="small">Menampilkan {{ $logs->firstItem()??0 }}–{{ $logs->lastItem()??0 }} dari {{ $logs->total() }} aktivitas</span><div class="btn-group"><a class="btn btn-light {{ $logs->onFirstPage()?'disabled':'' }}" href="{{ $logs->previousPageUrl() }}">Sebelumnya</a><span class="btn btn-light">{{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span><a class="btn btn-light {{ !$logs->hasMorePages()?'disabled':'' }}" href="{{ $logs->nextPageUrl() }}">Berikutnya</a></div></div>
+@endsection
\ No newline at end of file
```
