<?php
namespace App\Support;

use Carbon\CarbonImmutable;

/** Data contoh untuk presentasi frontend. Tidak terhubung ke database Dispar. */
class DemoData
{
    public const ROLES = [
        'sekretariat' => 'Sekretariat / Bagian Umum', 'kabid' => 'Kepala Bidang',
        'petugas' => 'Petugas Layanan', 'sekretaris' => 'Sekretaris',
        'bendahara' => 'Bendahara Penerimaan', 'kadis' => 'Kepala Dinas', 'admin' => 'Admin',
    ];
    public const STATUS = [
        'masuk' => 'Menunggu review', 'draft' => 'Penyusunan draft',
        'review_draft' => 'Persetujuan draft', 'verifikasi' => 'Verifikasi Sekretaris',
        'rilis' => 'Siap rilis', 'tagihan' => 'Penetapan retribusi',
        'bayar' => 'Menunggu pembayaran', 'ttd' => 'Menunggu tanda tangan',
        'serah' => 'Siap diserahkan', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak',
    ];
    public const OWNER = [
        'masuk' => 'kabid', 'draft' => 'petugas', 'review_draft' => 'kabid',
        'verifikasi' => 'sekretaris', 'rilis' => 'kabid', 'tagihan' => 'bendahara',
        'bayar' => 'bendahara', 'ttd' => 'kadis', 'serah' => 'sekretariat',
    ];
    public const ACTIVITIES = ['Festival budaya', 'Seminar / workshop', 'Bazar UMKM', 'Kegiatan olahraga', 'Fotografi / prewedding', 'Konser / pertunjukan'];
    public const RELIEF_REQUIREMENT = 'Dokumen pendukung keringanan';
    /** Lokasi bawaan untuk kegiatan di luar lokasi yang dikelola Dispar (nama dan alamat diisi manual pada permohonan). */
    public const OTHER_LOCATION_ID = 5;
    /** Bebas biaya yang sudah disetujui: tidak ada SKRD, STS, STBP, maupun bukti transfer. */
    /** Tanggal terakhir kegiatan (YYYY-MM-DD). */
    public static function endDate(array $r): string
    {
        return (new \DateTimeImmutable($r['date']))->modify('+'.max(0,(int)$r['days']-1).' days')->format('Y-m-d');
    }
    /**
     * Permohonan lain yang jadwalnya beririsan di lokasi yang sama.
     * Yang ditolak atau dihapus sementara tidak menempati lokasi.
     */
    public static function conflicts(array $all,array $cand,?int $ignoreId=null): array
    {
        if(!empty($cand['other'])) return [];
        $from=$cand['date']; $to=self::endDate($cand); $out=[];
        foreach($all as $r){
            if(($ignoreId&&$r['id']===$ignoreId)||self::isOther($r)||!empty($r['deleted_at'])||$r['status']==='ditolak'||(int)$r['location_id']!==(int)$cand['location_id']) continue;
            if($r['date']<=$to&&self::endDate($r)>=$from) $out[]=$r;
        }
        return $out;
    }
    public static function isFree(array $r): bool { return ($r['relief']??'')==='pembebasan' && ($r['discount']??'')==='disetujui'; }
    public static function isOther(array $r): bool { return !empty($r['location_other_name']); }
    /** Tidak ada retribusi: bebas biaya yang disetujui, atau lokasi di luar Dispar. Tahap tagihan dan pembayaran dilewati. */
    public static function noFee(array $r): bool { return self::isFree($r) || self::isOther($r); }
    public static function noFeeReason(array $r): string { return self::isOther($r) ? 'Lokasi di luar Dispar' : 'Bebas biaya'; }
    public static function locationName(array $r,$locations): string
    {
        if(self::isOther($r)) return (string)$r['location_other_name'];
        return (string)($locations[$r['location_id']]['name']??'-');
    }
    public static function reliefName(array $r): string { return ($r['relief']??'')==='pembebasan' ? 'Bebas biaya' : 'Keringanan 25%'; }
    // Daftar persyaratan mengikuti lembar periksa kelengkapan dinas.
    public const REQUIREMENTS = ['Surat permohonan persetujuan menyelenggarakan acara', 'Proposal Kegiatan/Rundown', 'Fotokopi KTP pemohon', 'Surat Rekomendasi dari Dinas Lingkungan Hidup'];
    public const DLH_REQUIREMENT = 'Surat Rekomendasi dari Dinas Lingkungan Hidup';
    public const PROMOTER_REQUIREMENT = 'Fotokopi jasa impresariat/promotor dari PTSP (jika menggunakan jasa)';

    public static function locations(): array
    {
        return [
            ['id'=>1,'name'=>'Pantai Pasir Padi','area'=>'Bukit Intan','rate'=>750000,'managed'=>true,'active'=>true],
            ['id'=>2,'name'=>'Jeramba Gantung','area'=>'Pangkalpinang','rate'=>500000,'managed'=>true,'active'=>true],
            ['id'=>3,'name'=>'Taman Sari','area'=>'Taman Sari','rate'=>450000,'managed'=>true,'active'=>true],
            ['id'=>4,'name'=>'Alun-alun Taman Merdeka','area'=>'Taman Sari','rate'=>900000,'managed'=>true,'active'=>true],
            ['id'=>self::OTHER_LOCATION_ID,'name'=>'Lainnya (di luar lokasi Dispar)','area'=>'-','rate'=>0,'managed'=>false,'active'=>true,'other'=>true],
        ];
    }

    public static function users(): array
    {
        return collect(self::ROLES)->map(fn($role,$key)=>['id'=>$key,'name'=>'Demo '.$role,'role'=>$key,'active'=>true])->values()->all();
    }

    public static function requests(): array
    {
        $names = ['Yayasan Pelita Bahari','Komunitas Sepeda Pangkalpinang','SMA Negeri 1 Pangkalpinang','Forum UMKM Selindung','Sanggar Seni Serumpun','Yayasan Pendidikan Mentari','Komunitas Fotografi Babel','Dinas Pemuda dan Olahraga (demo)','Panitia Festival Pesisir','Komunitas Literasi Bangka','Yayasan Cahaya Nusantara','Komunitas Musik Kota'];
        $statuses = ['masuk','masuk','draft','review_draft','verifikasi','rilis','tagihan','bayar','ttd','serah','selesai','ditolak'];
        $today = CarbonImmutable::today();
        $rows=[];
        foreach ($names as $i=>$name) {
            $status=$statuses[$i]; $days=($i%3)+1; $loc=self::locations()[$i%4];
            $submitted=$today->subWeekdays(($i%4)+1);
            $approved=$i===6; $percent=$approved?25:0;
            $issued=in_array($status,['bayar','ttd','serah','selesai']);
            $paid=in_array($status,['ttd','serah','selesai']);
            $rows[]=[
                'id'=>230+$i,'tracking'=>'DPR-'.date('Y').'-'.str_pad((string)(230+$i),4,'0',STR_PAD_LEFT),
                'name'=>$name,'pic'=>['Rani Pratiwi','Dimas Saputra','Nadia Putri','Fajar Pratama'][$i%4],
                'phone'=>'08xx-xxxx-'.(1000+$i),'email'=>'pemohon'.($i+1).'@example.test',
                'activity'=>self::ACTIVITIES[$i%count(self::ACTIVITIES)],'location_id'=>$loc['id'],
                'date'=>$today->addDays(12+$i*3)->toDateString(),'days'=>$days,
                'submitted'=>$submitted->toDateString(),'status'=>$status,
                'files'=>array_map(fn($v)=>['label'=>$v,'name'=>str_replace(' ','_',strtolower($v)).'.pdf','sample'=>true],self::REQUIREMENTS),
                'discount'=>$approved?'disetujui':'tidak_diajukan','discount_reason'=>$approved?'Contoh pengajuan keringanan yang telah disetujui.':'',
                'discount_percent'=>$percent,'draft'=>'','version'=>1,'bill'=>$issued?['rate'=>$loc['rate'],'subtotal'=>$loc['rate']*$days,'percent'=>$percent,'total'=>$loc['rate']*$days*(1-$percent/100)]:null,
                'payment'=>$paid?['date'=>$submitted->addWeekday()->toDateString(),'proof'=>'bukti_pembayaran_contoh.pdf']:null,
                'documents'=>array_values(array_filter([in_array($status,['tagihan','bayar','ttd','serah','selesai'])?'Surat Persetujuan':null, $issued?'SKRD':null,$paid?'STBP':null,$paid?'STS':null],fn($v)=>$v!==null)),
                'signed'=>in_array($status,['serah','selesai']), 'recipient'=>$status==='selesai'?'Perwakilan pemohon (demo)':null,
                'completed'=>$status==='selesai'?$submitted->addWeekdays(2)->toDateString():null,
                'history'=>[['at'=>$submitted->format('Y-m-d').' 09:00','role'=>'sekretariat','action'=>'Permohonan dicatat','note'=>'Data contoh untuk demonstrasi.'],['at'=>$submitted->format('Y-m-d').' 10:15','role'=>self::OWNER[$status]??'sekretariat','action'=>'Posisi awal demo: '.self::STATUS[$status],'note'=>'Riwayat sebelum posisi ini diringkas untuk data contoh.']],
            ];
        }
        $extraNames=['Yayasan Pendidikan Cendekia','Komunitas Lari Babel','Universitas Bangka Belitung (demo)','Sanggar Tari Melati','Panitia Pekan Literasi','Forum Pemuda Rangkui','Yayasan Bina Anak','Komunitas Bahari Sejahtera','Panitia Pameran UMKM','Sanggar Musik Nusantara','Yayasan Lentera Bangka','Forum Seni Kota'];
        foreach (array_values($rows) as $i=>$source) {
            $source['id']=330+$i; $source['tracking']='DPR-'.date('Y').'-'.str_pad((string)(330+$i),4,'0',STR_PAD_LEFT); $source['name']=$extraNames[$i];
            $rows[]=$source;
        }
        foreach ($rows as $i=>&$row) {
            $row['date']=$today->startOfMonth()->addDays(2+($i%12)*2)->toDateString();
            $row['draft']='';
            $row['draft']=self::draft($row,self::locations()[($row['location_id']-1)]['name']);
            foreach(['skrd','sts','stbp'] as $type) $row['templates'][$type]=[
                'address'=>'Jl. Contoh Bahari No. '.($i+1).', Pangkalpinang (alamat fiktif)',
                'npwr'=>'DEMO-'.($i+100),'period'=>$today->format('Y-m'),'issued_date'=>$today->toDateString(),
                'due'=>$today->addDays(7)->toDateString(),'bank'=>'Bank Contoh','account'=>'DEMO-000123',
                'description'=>'Pelaksanaan '.$row['activity'].' oleh '.$row['name']
            ];
            if($row['signed']) $row['signature']=['name'=>'Drs. Aditya Pratama (fiktif)','at'=>$row['submitted'].' 12:00','reference'=>'SIM-'.$row['tracking']];
            $steps=['masuk','draft','review_draft','verifikasi','rilis','tagihan','bayar','ttd','serah','selesai'];
            $row['history']=[];
            $last=array_search($row['status'],$steps,true);
            foreach(array_slice($steps,0,$last===false?1:$last+1) as $n=>$step) $row['history'][]=[
                'at'=>$row['submitted'].' '.sprintf('%02d:%02d',8+intdiv($n,2),($n%2)*30),
                'role'=>self::OWNER[$step]??'sekretariat','action'=>self::STATUS[$step],
                'note'=>$n?'Berkas dan hasil tahap sebelumnya telah diperiksa (skenario demo).':'Permohonan beserta empat berkas persyaratan diterima.'
            ];
        }
        unset($row);
        return $rows;
    }

    public static function rupiah(float|int $n): string { return 'Rp '.number_format($n,0,',','.'); }
    public static function date(string $date): string { return CarbonImmutable::parse($date)->translatedFormat('d M Y'); }
    public static function elapsed(array $r): int
    {
        $start=CarbonImmutable::parse($r['submitted'])->startOfDay();
        $end=CarbonImmutable::parse($r['completed']??CarbonImmutable::today()->toDateString())->startOfDay();
        return max(0,(int)$start->diffInDaysFiltered(fn($day)=>$day->isWeekday(),$end));
    }
    public static function canSeeFiles(string $role,array $r): bool
    {
        if (in_array($role,['sekretariat','kabid','sekretaris','kadis','admin']) || $r['status']==='selesai') return true;
        $order=array_keys(self::STATUS); $stage=array_search($r['status'],$order,true);
        if ($r['status']==='ditolak') return false;
        return $role==='petugas'?$stage>=1:($role==='bendahara'&&$stage>=5);
    }
    public static function letter(array $r): array
    {
        $legacy=empty($r['letter'])&&!empty($r['draft'])&&!str_contains($r['draft'],'Dokumen ini merupakan contoh draft')?$r['draft']:null;
        return array_merge([
            'structure'=>ApprovalLetter::structured($r),
            'number'=>'DEMO/'.$r['tracking'].'/DISPAR/'.date('Y'),
            'date'=>substr($r['signature']['at']??$r['submitted'],0,10),
            'subject'=>'Persetujuan penyelenggaraan kegiatan di tempat rekreasi',
            'attachment'=>count($r['files']).' berkas persyaratan',
            'opening'=>'',
            'body'=>$legacy??'',
            'conditions'=>"1. Menjaga kebersihan, keamanan, dan ketertiban lokasi.\n2. Mengembalikan fasilitas dalam kondisi semula.\n3. Memenuhi kewajiban retribusi sesuai SKRD.\n4. Berkoordinasi dengan petugas lokasi sebelum kegiatan.",
            'closing'=>'',
        ],$r['letter']??[]);
    }

    public static function draft(array $r,string $location): string
    {
        return $r['draft']?:"Dengan hormat,\n\nSehubungan dengan permohonan dari {$r['name']}, pada prinsipnya kegiatan {$r['activity']} di {$location} pada ".self::date($r['date'])." selama {$r['days']} hari dapat diproses lebih lanjut.\n\nKetentuan pelaksanaan:\n1. Pemohon menjaga kebersihan, keamanan, dan ketertiban lokasi.\n2. Fasilitas dikembalikan dalam kondisi semula setelah kegiatan.\n3. Kewajiban retribusi dipenuhi sesuai SKRD yang diterbitkan.\n4. Penanggung jawab berkoordinasi dengan petugas lokasi sebelum kegiatan.\n\nDemikian surat ini disusun untuk dipergunakan dalam demonstrasi alur pelayanan.\n\nDokumen ini merupakan contoh draft untuk demonstrasi, bukan surat persetujuan yang sah.";
    }
}
