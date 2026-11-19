<?php
namespace App\Support;

class PermitRequirements
{
    public static function needsDlh(array $location): bool
    {
        $normalize=fn($name)=>mb_strtolower(preg_replace('/\s+/u',' ',trim($name)));
        return in_array($normalize($location['name']),array_map($normalize,config('persyaratan.dlh_locations',[])),true);
    }

    public static function forLocation(array $location): array
    {
        return array_filter(DemoData::REQUIREMENTS,fn($label)=>$label!=='Rekomendasi DLH'||self::needsDlh($location));
    }
}
