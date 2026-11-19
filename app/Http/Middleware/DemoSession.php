<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class DemoSession
{
    public function handle(Request $request,Closure $next)
    {
        if(config('siap.database_business')&&!config('siap.database_accounts')) abort_unless(app()->environment('local','testing'),403,'Login akun database diperlukan.');
        if (!config('siap.database_accounts') && !app()->environment('local','testing')) { session()->forget('demo.role'); return redirect()->route('login'); }
        if (config('siap.database_accounts')) {
            $user=\App\Models\Pengguna::find(session('account_id'));
            if (!$user || $user->status_akun!=='aktif' || !hash_equals(hash('sha256',$user->password),(string)session('account_password_fingerprint'))) {
                \App\Support\BrowserAccess::revoke($request);
                session()->invalidate(); session()->regenerateToken(); return redirect()->route('login');
            }
            $role=$user->roles()->whereIn('name',array_keys(\App\Support\DemoData::ROLES))->orderBy('role_id')->value('name');
            if (!$role) { \App\Support\BrowserAccess::revoke($request); session()->invalidate(); session()->regenerateToken(); return redirect()->route('login'); }
            session(['demo.role'=>$role]);
        }
        if(!array_key_exists(session('demo.role',''),\App\Support\DemoData::ROLES)) return redirect()->route('login');
        return $next($request);
    }
}
