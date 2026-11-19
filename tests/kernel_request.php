<?php
// CLI-only integration transport: runs the real HTTP kernel without a TCP socket.
if (PHP_SAPI !== 'cli') exit(1);
define('LARAVEL_START', microtime(true));
require dirname(__DIR__).'/vendor/autoload.php';
$input = json_decode(stream_get_contents(STDIN), true);
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$files=[]; $temporary=[];
foreach(($input['files']??[]) as $key=>$spec) {
    $path=tempnam(sys_get_temp_dir(),'siptr-test-'); file_put_contents($path,base64_decode($spec['base64'])); $temporary[]=$path;
    $upload=new Illuminate\Http\UploadedFile($path,$spec['name'],null,null,true);
    if(preg_match('/^requirements\.(\d+)$/',$key,$m)) $files['requirements'][(int)$m[1]]=$upload; else $files[$key]=$upload;
}
$request = Illuminate\Http\Request::create($input['url'], $input['method'], $input['data'] ?? [], $input['cookies'] ?? [], $files, ['HTTP_ACCEPT'=>'text/html', 'HTTP_REFERER'=>$input['referer'] ?? $input['url']]);
$response = $kernel->handle($request);
$cookies = [];
foreach ($response->headers->getCookies() as $cookie) $cookies[$cookie->getName()] = $cookie->getValue();
ob_start();
$response->sendContent();
$body = ob_get_clean();
$kernel->terminate($request, $response);
foreach($temporary as $path) if(is_file($path)) unlink($path);
echo json_encode(['status'=>$response->getStatusCode(), 'body'=>$body, 'location'=>$response->headers->get('Location'), 'cookies'=>$cookies]);
