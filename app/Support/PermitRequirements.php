<?php
namespace App\Support;

class PermitRequirements
{
    public static function needsDlh(array $location): bool
    {
        if (array_key_exists('dlh',$location)) return (bool)$location['dlh'];
        $normalize=fn($name)=>mb_strtolower(preg_replace('/\s+/u',' ',trim($name)));
        return in_array($normalize($location['name']),array_map($normalize,config('persyaratan.dlh_locations',[])),true);
    }

    public static function forLocation(array $location): array
    {
        return array_filter(DemoData::REQUIREMENTS,fn($label)=>$label!==DemoData::DLH_REQUIREMENT||self::needsDlh($location));
    }

    /**
     * Baris lembar periksa kelengkapan sesuai urutan formulir dinas.
     * state: ada (terpenuhi), belum (wajib tetapi belum ada), tidak_ada (tidak berlaku / tidak diunggah, ditulis "-").
     */
    public static function checklist(array $r,array $location): array
    {
        $files=array_column($r['files']??[],null,'label');
        $free=DemoData::noFee($r); $why=DemoData::isOther($r)?'lokasi di luar Dispar':'bebas biaya';
        $row=fn(string $label,bool $has,bool $required,string $note='')=>['label'=>$label,'state'=>$has?'ada':($required?'belum':'tidak_ada'),'note'=>$has?'ada':($required?'belum ada':($note!==''?$note:'-'))];
        $rows=[
            $row(DemoData::REQUIREMENTS[0],isset($files[DemoData::REQUIREMENTS[0]]),true),
            $row(DemoData::REQUIREMENTS[1],isset($files[DemoData::REQUIREMENTS[1]]),true),
            $row(DemoData::PROMOTER_REQUIREMENT,isset($files[DemoData::PROMOTER_REQUIREMENT]),false),
            $row(DemoData::REQUIREMENTS[2],isset($files[DemoData::REQUIREMENTS[2]]),true),
            $row(DemoData::DLH_REQUIREMENT,isset($files[DemoData::DLH_REQUIREMENT]),self::needsDlh($location)),
        ];
        if(($r['discount']??'tidak_diajukan')!=='tidak_diajukan') $rows[]=$row(DemoData::RELIEF_REQUIREMENT,isset($files[DemoData::RELIEF_REQUIREMENT]),true);
        $money=fn(string $label,bool $has)=>$free?['label'=>$label,'state'=>'tidak_ada','note'=>'- ('.$why.')']:$row($label,$has,false,'belum ada');
        $rows[]=$money('Bukti transfer (TF)',!empty($r['uploads']['proof']));
        $rows[]=$money('SKRD (Surat Ketetapan Retribusi Daerah)',in_array('SKRD',$r['documents']??[],true));
        $rows[]=$money('STS (Surat Tanda Setoran)',in_array('STS',$r['documents']??[],true));
        $rows[]=$money('STBP (Surat Tanda Bukti Penerimaan)',in_array('STBP',$r['documents']??[],true));
        return $rows;
    }
}
