<?php
namespace App\Http\Middleware;

use App\Support\BrowserAccess;
use Closure;
use Illuminate\Http\Request;

class RestoreBrowserAccess
{
    public function handle(Request $request,Closure $next)
    {
        // Restore only on a safe request. POST still requires the current CSRF token.
        if($request->isMethod('get')&&!session()->has('demo.role')&&($data=BrowserAccess::read($request))) {
            session()->regenerate();
            foreach($data['session'] as $key=>$value) session([$key=>$value]);
        }
        $response=$next($request);
        if(session()->has('demo.role')) {
            if(!$request->cookie(BrowserAccess::COOKIE)&&!$request->attributes->get('browser_access_revoked')) BrowserAccess::issue($request);
            else BrowserAccess::refresh($request);
        }
        return $response;
    }
}
