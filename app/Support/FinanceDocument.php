<?php
namespace App\Support;

class FinanceDocument
{
    public static function source(array $r): array
    {
        $loc=collect(Workspace::locations())->firstWhere('id',$r['location_id']);
        if($loc && Workspace::database()) { $tariff=app(\App\Services\BusinessStore::class)->tariff($r['location_id'],$r['activity'],$r['date']); $loc['rate']=(float)($tariff->tarif??0); }
        abort_if($loc===null,422,'Lokasi permohonan tidak ditemukan pada data master demo.');
        return ['name'=>$r['name'],'pic'=>$r['pic'],'location'=>$loc['name'],
            'activity'=>$r['activity'],'date'=>$r['date'],'days'=>$r['days'],
            'rate'=>$loc['rate'],'managed'=>$loc['managed'],'discount'=>$r['discount']];
    }

    public static function assessment(array $r): array
    {
        if (!empty($r['bill'])) return $r['bill'];
        $source=self::source($r);
        $draft=$r['templates']['skrd']['assessment']??[];
        $rate=$draft['rate']??$source['rate'];
        $subtotal=round($rate*$r['days'],2);
        $percent=$r['discount']==='disetujui'&&$source['managed']?25:0;
        return ['rate'=>$rate,'subtotal'=>$subtotal,'percent'=>$percent,
            'total'=>$subtotal-round($subtotal*$percent/100,2),
            'note'=>$draft['note']??'Menggunakan tarif lokasi dan jenis kegiatan pada data master'];
    }

    public static function sourceChanges(array $r): array
    {
        $previous=$r['skrd_source']??null;
        if(!$previous) return [];
        $changes=[];
        foreach(self::source($r) as $key=>$value) if($key==='rate' ? (float)($previous[$key]??-1)!==(float)$value : ($previous[$key]??null)!==$value)
            $changes[$key]=['before'=>$previous[$key]??'—','after'=>$value];
        return $changes;
    }

    public static function freezeSkrd(array &$r, string $reason): void
    {
        $data=self::data($r,'skrd',true);
        $r['skrd_versions'][]=['at'=>date('Y-m-d H:i:s'),'reason'=>$reason,'data'=>$data];
        $r['skrd_snapshot']=$data;
        $r['skrd_source']=self::source($r);
    }

    public static function data(array $r, string $type, bool $editing=false): array
    {
        if(!$editing && $type!=='skrd' && isset($r['_document_snapshots'][$type]))return $r['_document_snapshots'][$type];
        if($type==='skrd'&&!$editing&&isset($r['skrd_snapshot'])) return $r['skrd_snapshot'];
        // IDs are not array offsets; use the same session master as invoice creation.
        $loc=collect(Workspace::locations())
            ->firstWhere('id', $r['location_id']);
        if($loc && Workspace::database()) { $tariff=app(\App\Services\BusinessStore::class)->tariff($r['location_id'],$r['activity'],$r['date']); $loc['rate']=(float)($tariff->tarif??0); }
        abort_if($loc===null, 422, 'Lokasi permohonan tidak ditemukan pada data master demo.');
        $assessment=self::assessment($r);
        $base=$assessment['subtotal'];
        $percent=$assessment['percent'];
        $total=$assessment['total'];
        $defaults=[
            'number'=>'DEMO/'.$r['tracking'].'/'.strtoupper($type),
            'payer'=>$r['pic'].' / '.$r['name'], 'address'=>'Alamat contoh, Pangkalpinang',
            'npwr'=>'—', 'period'=>substr($r['date'],0,7),
            'issued_date'=>$r['payment']['date']??date('Y-m-d'),
            'rental_date'=>$r['date'],
            'due'=>'2 hari sebelum pelaksanaan kegiatan',
            'bank'=>'Bank contoh (demo)', 'account'=>'0000000000', 'method'=>'Non Tunai',
            'description'=>'Pelaksanaan kegiatan '.$r['activity'].' di '.$loc['name'],
            'signer_rank'=>'Penata Tingkat I / III.d (demo)', 'signer_name'=>'Pejabat Contoh (fiktif)', 'signer_nip'=>'000000000000000000',
            'signer_title'=>$type==='skrd'?'Kabid Destinasi Pariwisata dan Industri Pariwisata':'Bendahara Penerimaan',
            'authority_name'=>'Kepala Dinas Contoh (fiktif)', 'authority_nip'=>'000000000000000000',
            'attention'=>"Harap penyetoran dilakukan pada bank / Bendahara Penerimaan Dinas Pariwisata Kota Pangkalpinang.\nPembayaran dilakukan sesuai tanggal jatuh tempo yang tercantum pada ketetapan.",
            'rows'=>[['code'=>'4.1.02.02.009.00001','label'=>'Retribusi Pelayanan Tempat Rekreasi dan Olahraga','amount'=>$type==='skrd'?$base:$total]],
        ];
        $saved=array_filter($r['templates'][$type]??[],fn($v)=>$v!==null&&$v!=='');
        if($type==='skrd') {
            // Identity is owned by the application; administrative fields and prose remain editable.
            unset($saved['payer']);
            // Legacy template amounts cannot override the assessment.
            if (!empty($saved['rows']) && abs(array_sum(array_column($saved['rows'],'amount'))-$base)>0.005) {
                $saved['rows']=[array_replace($saved['rows'][0],['amount'=>$base])];
            }
            if(array_key_exists('attention',$r['templates']['skrd']??[])) $saved['attention']=$r['templates']['skrd']['attention']??'';
            $defaults['source']=self::source($r);
        }
        return array_replace($defaults,$saved,['base'=>$base,'percent'=>$percent,'total'=>$total,'words'=>self::words((int)round($total)).' rupiah']);
    }

    public static function words(int $n): string
    {
        $w=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
        if($n<12)return $w[$n];
        if($n<20)return self::words($n-10).' belas';
        if($n<100)return self::words(intdiv($n,10)).' puluh'.($n%10?' '.self::words($n%10):'');
        if($n<200)return 'seratus'.($n%100?' '.self::words($n%100):'');
        if($n<1000)return self::words(intdiv($n,100)).' ratus'.($n%100?' '.self::words($n%100):'');
        if($n<2000)return 'seribu'.($n%1000?' '.self::words($n%1000):'');
        foreach([1000000000000=>'triliun',1000000000=>'miliar',1000000=>'juta',1000=>'ribu'] as $unit=>$label)
            if($n>=$unit)return self::words(intdiv($n,$unit)).' '.$label.($n%$unit?' '.self::words($n%$unit):'');
        return '';
    }
}
