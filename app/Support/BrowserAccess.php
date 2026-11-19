<?php
namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;

/** Revocable browser credential, independent of the short-lived form session. */
class BrowserAccess
{
    public const COOKIE='siptr_browser_access';

    private static function path(string $token): string
    {
        return storage_path('app/browser-access/'.hash('sha256',$token).'.json');
    }

    public static function read(Request $request): ?array
    {
        $token=$request->cookie(self::COOKIE);
        if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
        $path=self::path($token);
        if(!is_file($path)) return null;
        $data=json_decode(File::sharedGet($path),true);
        if(!is_array($data)||($data['expires']??0)<time()) { File::delete($path); return null; }
        if(($data['accounts']??null)!==(bool)config('siap.database_accounts')) return null;
        if($data['accounts']) {
            $user=\App\Models\Pengguna::find($data['session']['account_id']??null);
            if(!$user||$user->status_akun!=='aktif'||!hash_equals(hash('sha256',$user->password),$data['session']['account_password_fingerprint']??'')) {
                self::revoke($request); return null;
            }
        } elseif(!app()->environment('local','testing')) return null;
        return $data;
    }

    public static function issue(Request $request): void
    {
        if(!config('siap.database_accounts')&&!app()->environment('local','testing')) return;
        self::revoke($request);
        $token=bin2hex(random_bytes(32));
        self::write($request,$token);
        $request->attributes->set('browser_access_issued',true);
    }

    public static function refresh(Request $request): void
    {
        if($request->attributes->get('browser_access_issued')||$request->attributes->get('browser_access_revoked')) return;
        $token=$request->cookie(self::COOKIE);
        if(is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token)&&is_file(self::path($token))) self::write($request,$token);
    }

    private static function write(Request $request,string $token): void
    {
        $minutes=(int)config('siap.browser_access_minutes',43200);
        $data=['accounts'=>(bool)config('siap.database_accounts'),'expires'=>time()+$minutes*60,
            'session'=>array_intersect_key(session()->all(),array_flip(['account_id','account_password_fingerprint','demo']))];
        File::ensureDirectoryExists(dirname(self::path($token)));
        File::put(self::path($token),json_encode($data,JSON_THROW_ON_ERROR),true);
        Cookie::queue(Cookie::make(self::COOKIE,$token,$minutes,'/',null,$request->isSecure(),true,false,'lax'));
        // Remove abandoned credentials occasionally, never during every page request.
        if(random_int(1,100)===1) foreach(File::files(dirname(self::path($token))) as $file)
            if($file->getMTime()<time()-$minutes*60) File::delete($file->getPathname());
    }

    public static function revoke(Request $request): void
    {
        $token=$request->cookie(self::COOKIE);
        if(is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token)) File::delete(self::path($token));
        Cookie::queue(Cookie::forget(self::COOKIE));
        $request->attributes->set('browser_access_revoked',true);
    }
}
