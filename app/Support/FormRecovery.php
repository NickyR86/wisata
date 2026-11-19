<?php
namespace App\Support;
use Illuminate\Http\Request;
class FormRecovery
{
    public static function destination(mixed $value): ?string
    {
        if(!is_string($value)||preg_match('/[\\\\\x00-\x20]/',$value)) return null;
        return preg_match('~^/(?:permohonan/baru|permohonan/\d+(?:/template/(?:skrd|sts|stbp))?|layanan/[a-z-]+|admin/(?:pengguna|lokasi)|ringkasan|kalender|laporan)(?:\?[a-zA-Z0-9_=&%-]*)?$~D',$value)?$value:null;
    }
    public static function origin(Request $request): ?string
    {
        if($request->is('demo/masuk','demo/keluar')) return null;
        if($request->is('permohonan')) return '/permohonan/baru';
        if(preg_match('~^permohonan/\d+/template/(skrd|sts|stbp)$~',$request->path())) return '/'.$request->path();
        $parts=parse_url($request->headers->get('referer',''));
        if($parts&&($parts['host']??null)===$request->getHost()&&($parts['scheme']??null)===$request->getScheme()
            &&($parts['port']??($request->isSecure()?443:80))===$request->getPort())
            return self::destination(($parts['path']??'').(isset($parts['query'])?'?'.$parts['query']:''));
        return null;
    }
}
