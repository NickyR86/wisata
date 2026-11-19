<?php
// Isolated in-memory session: never reads or writes the browser's demo session.
require dirname(__DIR__).'/vendor/autoload.php';
$app=require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array']);

use App\Support\DemoData;
use App\Support\FinanceDocument;

$checks=0;
function check($condition, $message) {
    global $checks;
    if(!$condition) throw new RuntimeException($message);
    $checks++;
}
$r=['location_id'=>1,'bill'=>null,'days'=>2,'discount'=>'tidak_diajukan',
    'tracking'=>'DEMO-TEST','pic'=>'PJ demo','name'=>'Organisasi demo',
    'date'=>'2026-10-25','payment'=>null,'activity'=>'Kegiatan demo'];
foreach(DemoData::locations() as $loc) {
    $r['location_id']=$loc['id'];
    foreach(['skrd','sts','stbp'] as $type) {
        $doc=FinanceDocument::data($r,$type);
        check($doc['description']==='Pelaksanaan kegiatan Kegiatan demo di '.$loc['name'], 'Wrong location '.$loc['id']);
        check($doc['base']==$loc['rate']*2, 'Wrong base '.$loc['id']);
        check((float)$doc['total']===(float)($loc['rate']*2), 'Wrong total '.$loc['id']);
    }
}
// Reordering and non-contiguous IDs must not affect lookup.
$master=array_reverse(DemoData::locations());
$master[]=['id'=>19,'name'=>'Lokasi tambahan','rate'=>123000,'managed'=>true,'active'=>true];
session(['demo.locations'=>$master]);
$r['location_id']=19; $r['discount']='disetujui';
$doc=FinanceDocument::data($r,'skrd');
check($doc['base']==246000 && (float)$doc['total']===184500.0, 'Session master / relief not used');
check(str_contains($doc['description'],'Lokasi tambahan'), 'Additional location not resolved');
foreach($master as &$entry) if($entry['id']===1) $entry['rate']=800000;
unset($entry);
session(['demo.locations'=>$master]);
$r['location_id']=1;
$doc=FinanceDocument::data($r,'skrd');
check($doc['base']==1600000 && (float)$doc['total']===1200000.0, 'Updated master tariff not reflected');
// Changed master affects estimates, but must not rewrite issued bill amounts.
$r['bill']=['subtotal'=>400000,'percent'=>25,'total'=>300000];
$r['templates']['skrd']=['description'=>'Uraian tersimpan'];
$doc=FinanceDocument::data($r,'skrd');
check($doc['base']==400000 && $doc['total']===300000 && $doc['percent']===25, 'Issued bill overwritten');
check($doc['description']==='Uraian tersimpan', 'Saved description overwritten');
$r['location_id']=999;
try {
    FinanceDocument::data($r,'skrd');
    throw new RuntimeException('Unknown location silently accepted');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    check($e->getStatusCode()===422, 'Unknown location must return controlled error');
}
echo "PASS: $checks finance location regression checks\n";
