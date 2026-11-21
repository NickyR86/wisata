<?php
namespace App\Http\Controllers;

use App\Support\DemoData as D;
use App\Support\Workspace as W;
use App\Services\BusinessStore;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Penghubung Blade dan sesi demo; bukan implementasi autentikasi/backend produksi. */
class DemoController extends Controller
{
    private function init(): void
    {
        if (W::database()) { if(!session()->has('demo.users'))session(['demo.users'=>D::users()]); return; }
        if (!session()->has('demo.requests')) {
            session(['demo.requests'=>D::requests(),'demo.locations'=>D::locations(),'demo.users'=>D::users()]);
        }
        if (!session('demo.enriched_v2')) {
            $existing=collect(W::requests())->keyBy('id');
            foreach(D::requests() as $sample) {
                if(!$existing->has($sample['id'])) $existing->put($sample['id'],$sample);
                else { $old=$existing[$sample['id']]; foreach(['templates','signature'] as $key) if(empty($old[$key])&&isset($sample[$key])) $old[$key]=$sample[$key]; $existing->put($sample['id'],$old); }
            }
            session(['demo.requests'=>$existing->values()->all(),'demo.enriched_v2'=>true]);
        }
        session(['demo.requests'=>array_map(function($r) { $r['tracking']=preg_replace('/^PKP-/', 'DPR-', $r['tracking']); return $r; },W::requests())]);
        session(['demo.requests'=>array_map(function($r) {
            if(in_array('SKRD',$r['documents'])&&!isset($r['skrd_snapshot']))
                \App\Support\FinanceDocument::freezeSkrd($r,'Snapshot awal data demo lama; bukan rekonstruksi saat penerbitan.');
            return $r;
        },W::requests())]);
    }
    private function role(): string { return session('demo.role',''); }
    private function requireRole(array $roles): void { abort_unless(in_array($this->role(),$roles),403,'Halaman atau tindakan ini tidak tersedia untuk role demo Anda.'); }
    private function record(int $id): array { $this->init(); $r=collect(W::requests())->firstWhere('id',$id) ?? abort(404); if(W::database()&&request()->isMethod('post')&&request()->has('record_version'))abort_unless((string)$r['_v']===(string)request('record_version'),409,'Data telah berubah. Muat ulang halaman.'); abort_if(!empty($r['deleted_at'])&&!in_array($this->role(),['sekretariat','admin']),404); return $r; }
    private function save(array $r): void { if(W::database()){app(BusinessStore::class)->save($r,W::actor());return;}  session(['demo.requests'=>collect(W::requests())->map(fn($v)=>$v['id']===$r['id']?$r:$v)->all()]); }
    private function view(string $view,array $data=[])
    {
        $this->init();
        return view($view,array_merge(['role'=>$this->role(),'roles'=>D::ROLES,'statuses'=>D::STATUS,'locations'=>collect(W::locations())->keyBy('id'),'requests'=>collect(W::requests())->filter(fn($r)=>empty($r['deleted_at']))],$data));
    }
    public function login() { return $this->view('auth.login',['title'=>'Masuk ke ruang kerja']); }
    public function enter(Request $q)
    {
        if (config('siap.database_accounts')) {
            $q->validate(['nip'=>'required|string|max:100','password'=>'required|string|max:128']);
            $key='login:'.hash('sha256',mb_strtolower(trim($q->nip)).'|'.$q->ip());
            $ipKey='login-ip:'.hash('sha256',(string)$q->ip());
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key,5) || \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($ipKey,20))
                throw \Illuminate\Validation\ValidationException::withMessages(['nip'=>'Terlalu banyak percobaan. Tunggu satu menit.']);
            $user=app(\App\Services\AccountService::class)->authenticate($q->nip,$q->password);
            $role=$user?->roles()->whereIn('name',array_keys(D::ROLES))->orderBy('role_id')->value('name');
            if (!$user || !$role) {
                \Illuminate\Support\Facades\RateLimiter::hit($key,60); \Illuminate\Support\Facades\RateLimiter::hit($ipKey,60);
                throw \Illuminate\Validation\ValidationException::withMessages(['nip'=>'Akun atau kata sandi tidak sesuai.']);
            }
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            session()->regenerate();
            session(['account_id'=>$user->getKey(),'account_password_fingerprint'=>hash('sha256',$user->password),'demo.role'=>$role]);
            \App\Support\BrowserAccess::issue($q);
            return redirect()->to(\App\Support\FormRecovery::destination($q->input('return_to'))??route('dashboard'));
        }
        if (!app()->environment('local','testing'))
            throw \Illuminate\Validation\ValidationException::withMessages(['nip'=>'Mode demo dinonaktifkan di lingkungan ini. Aktifkan akun database (SIAP_DATABASE_ACCOUNTS=true) atau hubungi administrator.']);
        if ($q->boolean('login_form')) {
            $q->validate(['nip'=>'required|string|min:6|max:100','password'=>'required|string'], [
                'nip.required'=>'NIP/username wajib diisi.',
                'nip.min'=>'NIP/username minimal 6 karakter.',
                'password.required'=>'Kata sandi wajib diisi.',
            ]);
        }
        $q->validate(['role'=>['required',Rule::in(array_keys(D::ROLES))]]);
        session()->regenerate(); session(['demo.role'=>$q->role]); $this->init();
        \App\Support\BrowserAccess::issue($q);
        return redirect()->to(\App\Support\FormRecovery::destination($q->input('return_to'))??route('dashboard'));
    }
    public function logout(Request $q) { \App\Support\BrowserAccess::revoke($q); session()->invalidate(); session()->regenerateToken(); return redirect()->route('login'); }
    public function reset() { abort_if(config('siap.database_accounts')||W::database(),403,'Reset sesi tidak boleh menghapus database bersama.'); \App\Support\BrowserAccess::revoke(request()); session()->forget('demo'); return redirect()->route('login')->with('success','Seluruh data sesi demo telah dikembalikan ke contoh awal.'); }
    public function dashboard(Request $q)
    {
        return match($this->role()) {
            'kadis','admin' => redirect()->route('summary'),
            'petugas' => redirect()->route('listing','draft'),
            'sekretaris' => redirect()->route('listing','verifikasi'),
            'bendahara' => redirect()->route('listing','retribusi'),
            'kabid' => redirect()->route('listing',['kind'=>'antrean','mine'=>1]),
            default => redirect()->route('listing','antrean'),
        };
    }
    public function summary()
    {
        return $this->view('dashboard.index',['title'=>'Ringkasan Pelayanan','page'=>'ringkasan']);
    }
    public function listing(Request $q,string $kind='antrean')
    {
        $map=['antrean'=>['Antrean permohonan',null],'draft'=>['Editor draft',['petugas']],'verifikasi'=>['Verifikasi draft',['sekretaris']],'rilis'=>['Rilis surat',['kabid']],'retribusi'=>['Retribusi & pembayaran',['bendahara']],'tanda-tangan'=>['Tanda tangan',['kadis']],'serah-terima'=>['Serah terima',['sekretariat']],'arsip'=>['Arsip digital',null],'riwayat'=>['Log & riwayat',null]];
        abort_unless(isset($map[$kind]),404); if($map[$kind][1]) $this->requireRole($map[$kind][1]); $this->init();
        $stage=['draft'=>['draft'],'verifikasi'=>['verifikasi'],'rilis'=>['rilis'],'retribusi'=>['tagihan','bayar','ttd'],'tanda-tangan'=>['ttd'],'serah-terima'=>['serah'],'arsip'=>['selesai']];
        if($kind==='riwayat'&&$q->query('tab')==='hapus') { $this->requireRole(['sekretariat','admin']); return $this->view('permohonan.deleted',['title'=>'Riwayat Hapus','page'=>'riwayat','deleted'=>collect(W::requests())->filter(fn($r)=>!empty($r['deleted_at']))]); }
        $rows=collect(W::requests())->filter(function($r) use($kind,$stage,$q) {
            if(!empty($r['deleted_at'])) return false;
            if(isset($stage[$kind])&&!in_array($r['status'],$stage[$kind])) return false;
            if($kind==='antrean'&&in_array($r['status'],['selesai','ditolak'])) return false;
            if($q->filled('q')&&!str_contains(mb_strtolower($r['name'].' '.$r['tracking'].' '.$r['pic'].' '.collect(W::locations())->firstWhere('id',$r['location_id'])['name']),mb_strtolower($q->string('q')->toString()))) return false;
            if($q->filled('status')&&$r['status']!==$q->status) return false;
            if($q->filled('lokasi')&&$r['location_id']!==(int)$q->lokasi) return false;
            if($q->boolean('mine')&&(D::OWNER[$r['status']]??null)!==$this->role()) return false;
            if($q->query('sla')==='overdue'&&D::elapsed($r)<=3) return false;
            if($q->query('sla')==='due'&&D::elapsed($r)!==3) return false;
            $date=CarbonImmutable::parse($kind==='arsip'?($r['completed']??$r['submitted']):$r['submitted']); $today=CarbonImmutable::today();
            $period=$q->query('period','all');
            if($period==='today'&&!$date->isSameDay($today)) return false;
            if($period==='week'&&($date->lt($today->subDays(6))||$date->gt($today))) return false;
            if($period==='month'&&$date->format('Y-m')!==$today->format('Y-m')) return false;
            if($period==='lastmonth'&&$date->format('Y-m')!==$today->subMonthNoOverflow()->format('Y-m')) return false;
            return true;
        });
        $rows=($q->query('sort')==='oldest'?$rows->sortBy('id'):$rows->sortByDesc('id'))->values();
        if($kind==='arsip') {
            $rows=($q->query('sort')==='oldest'?$rows->sortBy('completed'):$rows->sortByDesc('completed'))->values();
            return $this->view('arsip.index',['title'=>'Arsip digital','page'=>'arsip','kind'=>$kind,'rows'=>$rows,'r'=>$rows->firstWhere('id',(int)$q->query('selected'))??$rows->first()]);
        }
        if($kind==='riwayat') {
            $events=collect(W::requests())->filter(fn($r)=>empty($r['deleted_at'])||in_array($this->role(),['sekretariat','admin']))->flatMap(function($r) { return collect($r['history'])->map(fn($e)=>array_merge($e,['record'=>$r])); });
            $events=$events->filter(function($e) use($q) {
                if($q->filled('q')&&!str_contains(mb_strtolower($e['record']['name'].' '.$e['record']['tracking'].' '.$e['action'].' '.$e['note']),mb_strtolower($q->q))) return false;
                if($q->filled('actor')&&$e['role']!==$q->actor) return false;
                $date=CarbonImmutable::parse($e['at']); $today=CarbonImmutable::today();
                return match($q->query('period','all')) { 'today'=>$date->isSameDay($today),'week'=>$date->gte($today->subDays(6))&&$date->lte($today->endOfDay()),'month'=>$date->format('Y-m')===$today->format('Y-m'),'lastmonth'=>$date->format('Y-m')===$today->subMonthNoOverflow()->format('Y-m'),default=>true };
            });
            $events=($q->query('sort')==='oldest'?$events->sortBy('at'):$events->sortByDesc('at'))->values();
            $number=max(1,(int)$q->query('page',1));
            $logs=new \Illuminate\Pagination\LengthAwarePaginator($events->forPage($number,20)->values(),$events->count(),20,$number,['path'=>$q->url(),'query'=>$q->query()]);
            return $this->view('riwayat.index',['title'=>'Log & riwayat','page'=>'riwayat','logs'=>$logs]);
        }
        if(in_array($kind,['draft','verifikasi','rilis','retribusi','tanda-tangan','serah-terima'])) {
            $selected=$rows->firstWhere('id',(int)$q->query('selected'))??$rows->first();
            return $this->view('permohonan.work',['title'=>$map[$kind][0],'page'=>$kind,'kind'=>$kind,'queue'=>$rows,'r'=>$selected,'canAct'=>$selected&&(D::OWNER[$selected['status']]??'')===$this->role(),'canSeeFiles'=>$selected&&D::canSeeFiles($this->role(),$selected)]);
        }
        $paginator=new \Illuminate\Pagination\LengthAwarePaginator($rows->forPage(max(1,(int)$q->query('page',1)),15)->values(),$rows->count(),15,max(1,(int)$q->query('page',1)),['path'=>$q->url(),'query'=>$q->query()]);
        return $this->view('permohonan.index',['title'=>$map[$kind][0],'page'=>$kind,'rows'=>$paginator,'kind'=>$kind]);
    }
    public function create() { $this->requireRole(['sekretariat']); return $this->view('permohonan.create',['title'=>'Permohonan baru','page'=>'baru']); }
    public function store(Request $q)
    {
        $this->requireRole(['sekretariat']); $this->init();
        $data=$q->validate(['name'=>'required|string|max:150','pic'=>'required|string|max:100','phone'=>'required|string|max:30','email'=>'nullable|email|max:150','address'=>'nullable|string|max:1000','activity'=>['required',Rule::in(W::activities())],'location_id'=>['required',Rule::in(collect(W::locations())->where('active',true)->pluck('id')->all())],'date'=>'required|date|after_or_equal:today','days'=>'required|integer|min:1|max:30','files'=>'nullable|boolean','discount'=>'nullable|in:25,free','discount_reason'=>'required_if:discount,25,free|nullable|string|min:5|max:1000']);
        $sourceRules=[];
        foreach(\App\Support\ApprovalLetter::SOURCE_FIELDS as $field) $sourceRules['letter_source.'.$field]='nullable|string|max:200';
        $sourceRules['letter_source.request_date']='nullable|date_format:Y-m-d';
        foreach(['start_time','end_time'] as $field) $sourceRules['letter_source.'.$field]='nullable|date_format:H:i';
        $source=$q->validate($sourceRules);
        $data['letter_source']=array_filter($source['letter_source']??[],fn($v)=>$v!==null&&$v!=='');
        $uploadedRequirements=[];
        $loc=collect(W::locations())->firstWhere('id',(int)$data['location_id']);
        $requirements=\App\Support\PermitRequirements::forLocation($loc);
        $relief=in_array($q->discount,['25','free'],true)?$q->discount:null;
        if($relief&&!$loc['managed']) return back()->withErrors(['discount'=>'Keringanan atau bebas biaya hanya dapat diajukan untuk lokasi yang dikelola Dispar.'])->withInput();
        try {
        if(!($q->boolean('files')&&app()->environment('local','testing'))) {
            $rules=[]; foreach($requirements as $i=>$label) $rules['requirements.'.$i]='required|file|mimes:pdf,jpg,jpeg,png|max:2048';
            if($relief) $rules['relief_doc']='required|file|mimes:pdf,jpg,jpeg,png|max:2048';
            $rules['promoter_doc']='nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
            $q->validate($rules);
            foreach($requirements as $i=>$label) {
                $file=$q->file('requirements.'.$i); $stored=bin2hex(random_bytes(20)).'.'.$file->extension();
                $dir=storage_path('app/demo-uploads'); if(!is_dir($dir)) mkdir($dir,0775,true); abort_if(file_put_contents($dir.'/'.$stored,file_get_contents($file->getRealPath()))===false,500,'Berkas tidak dapat disimpan.');
                $uploadedRequirements[]=['label'=>$label,'name'=>basename($file->getClientOriginalName()),'sample'=>false,'stored'=>$stored,'size'=>filesize($dir.'/'.$stored)];
            }
            if($q->hasFile('promoter_doc')) {
                $file=$q->file('promoter_doc'); $stored=bin2hex(random_bytes(20)).'.'.$file->extension();
                $dir=storage_path('app/demo-uploads'); if(!is_dir($dir)) mkdir($dir,0775,true);
                abort_if(file_put_contents($dir.'/'.$stored,file_get_contents($file->getRealPath()))===false,500,'Berkas tidak dapat disimpan.');
                $uploadedRequirements[]=['label'=>D::PROMOTER_REQUIREMENT,'name'=>basename($file->getClientOriginalName()),'sample'=>false,'stored'=>$stored,'size'=>filesize($dir.'/'.$stored)];
            }
            if($relief) {
                $file=$q->file('relief_doc'); $stored=bin2hex(random_bytes(20)).'.'.$file->extension();
                $dir=storage_path('app/demo-uploads'); if(!is_dir($dir)) mkdir($dir,0775,true);
                abort_if(file_put_contents($dir.'/'.$stored,file_get_contents($file->getRealPath()))===false,500,'Berkas tidak dapat disimpan.');
                $uploadedRequirements[]=['label'=>D::RELIEF_REQUIREMENT,'name'=>basename($file->getClientOriginalName()),'sample'=>false,'stored'=>$stored,'size'=>filesize($dir.'/'.$stored)];
            }
        }
        $all=W::requests(); $id=(int)collect($all)->max('id')+1;
        $r=array_merge(['email'=>null,'discount_reason'=>null],$data,['id'=>$id,'tracking'=>'DPR-'.date('Y').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT),'location_id'=>(int)$data['location_id'],'days'=>(int)$data['days'],'submitted'=>date('Y-m-d'),'status'=>'masuk','files'=>array_map(fn($v)=>['label'=>$v,'name'=>'contoh_'.str_replace(' ','_',strtolower($v)).'.pdf','sample'=>true],$requirements),'discount'=>$relief?'diajukan':'tidak_diajukan','relief'=>$relief==='free'?'pembebasan':($relief==='25'?'pengurangan':'normal'),'discount_percent'=>0,'draft'=>'','version'=>1,'bill'=>null,'payment'=>null,'documents'=>[],'signed'=>false,'recipient'=>null,'completed'=>null,'history'=>[['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Permohonan dicatat','note'=>'Berkas contoh digunakan dalam demo.']]]);
        if($relief&&!$uploadedRequirements) $r['files'][]=['label'=>D::RELIEF_REQUIREMENT,'name'=>'contoh_dokumen_pendukung_keringanan.pdf','sample'=>true];
        if($uploadedRequirements) { $r['files']=$uploadedRequirements; $r['history'][0]['note']='Berkas diunggah untuk demo lokal.'; }
        if(W::database()) { $r=app(BusinessStore::class)->create($r,W::actor()); $id=$r['id']; } else { $all[]=$r; session(['demo.requests'=>$all]); }
        } catch (\Throwable $e) {
            foreach($uploadedRequirements as $u) @unlink(storage_path('app/demo-uploads/'.basename((string)($u['stored']??''))));
            throw $e;
        }
        return redirect()->route('detail',$id)->with('success',W::database()?'Permohonan tersimpan di database lokal.':'Permohonan demo berhasil didaftarkan.');
    }
    public function updateLetterSource(Request $q,int $id)
    {
        $this->requireRole(['sekretariat']); $r=$this->record($id);
        abort_unless(empty($r['deleted_at'])&&in_array($r['status'],['masuk','draft']),403,'Data sumber hanya dapat dikoreksi sebelum review atau setelah dikembalikan.');
        $rules=['address'=>'required|string|max:1000','note'=>'required|string|min:5|max:1000'];
        foreach(\App\Support\ApprovalLetter::SOURCE_FIELDS as $field) $rules['letter_source.'.$field]='nullable|string|max:200';
        $rules['letter_source.request_date']='nullable|date_format:Y-m-d';
        foreach(['start_time','end_time'] as $field) $rules['letter_source.'.$field]='nullable|date_format:H:i';
        $data=$q->validate($rules);
        $r['address']=$data['address'];
        $r['letter_source']=array_filter($data['letter_source']??[],fn($v)=>$v!==null&&$v!=='');
        $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Koreksi sumber surat','note'=>$data['note']];
        $this->save($r);
        return redirect()->route('detail',$id)->with('success','Data sumber surat tersimpan. Petugas dapat mengambil pembaruan dari editor draft.');
    }
    public function detail(int $id)
    {
        $r=$this->record($id);
        return $this->view('permohonan.show',['title'=>'Detail permohonan','page'=>'detail','r'=>$r,'canAct'=>empty($r['deleted_at'])&&(D::OWNER[$r['status']]??'')===$this->role(),'canSeeFiles'=>D::canSeeFiles($this->role(),$r)]);
    }
    public function act(Request $q,int $id)
    {
        $r=$this->record($id); abort_if(!empty($r['deleted_at']),403); $this->requireRole([D::OWNER[$r['status']]??'none']);
        $q->validate(['action'=>'required|string','note'=>'nullable|string|max:2000']);
        $action=$q->action; $note=$q->input('note',''); $from=$r['status'];
        $allowed=['masuk'=>['approve','reject'],'draft'=>['save','send'],'review_draft'=>['approve','revise'],'verifikasi'=>['approve','revise'],'rilis'=>['release'],'tagihan'=>['save_assessment','invoice'],'bayar'=>['pay'],'ttd'=>['sign'],'serah'=>['handover']];
        abort_unless(in_array($action,$allowed[$from]??[]),403);
        if(in_array($action,['reject','revise'])) $q->validate(['note'=>'required|string|min:5|max:2000']);
        $label='';
        switch($action) {
            case 'reject': $r['status']='ditolak'; $label='Permohonan ditolak'; break;
            case 'revise': $r['status']='draft'; $r['version']++; $label='Draft dikembalikan untuk revisi'; break;
            case 'approve':
                $r['status']=['masuk'=>'draft','review_draft'=>'verifikasi','verifikasi'=>'rilis'][$from]; $label=['masuk'=>'Review berkas disetujui','review_draft'=>'Draft disetujui Kabid','verifikasi'=>'Draft diverifikasi Sekretaris'][$from];
                if($from==='masuk'&&$r['discount']==='diajukan') {
                    $q->validate(['relief_decision'=>'required|in:approve,reject']);
                    $granted=$q->relief_decision==='approve';
                    if(!$granted) $q->validate(['note'=>'required|string|min:5|max:2000']);
                    $name=D::reliefName($r);
                    $r['discount']=$granted?'disetujui':'ditolak';
                    $r['discount_percent']=$granted?(($r['relief']??'')==='pembebasan'?100:25):0;
                    $r['relief_note']=trim((string)$q->note)!==''?trim((string)$q->note):null;
                    $label=$granted?'Review disetujui; '.mb_strtolower($name).' disetujui':'Review disetujui; '.mb_strtolower($name).' ditolak';
                }
                break;
            case 'save': case 'send':
                if($q->has('letter')) {
                    $rules=['letter'=>'required|array'];
                    foreach(['number','subject','attachment'] as $key) $rules['letter.'.$key]='required|string|max:200';
                    $rules['letter.number']='required|string|max:150';
                    $rules['letter.date']='required|date_format:Y-m-d';
                    foreach(['opening','body','conditions','closing'] as $key) $rules['letter.'.$key]='nullable|string|max:5000';
                    $rules['letter.structure']='sometimes|array';
                    foreach(['about','purpose','request_reference'] as $key) $rules['letter.structure.'.$key]='required_with:letter.structure|string|max:2000';
                    foreach(['legal_basis','terms','copies'] as $key) {
                        $rules['letter.structure.'.$key]='sometimes|array|max:30';
                        $rules['letter.structure.'.$key.'.*']='nullable|string|max:3000';
                    }
                    $valid=$q->validate($rules);
                    $previousStructure=\App\Support\ApprovalLetter::structured($r);
                    $r['letter']=array_intersect_key($valid['letter'],D::letter($r));
                    foreach(['opening','body','conditions','closing'] as $key) $r['letter'][$key]=$r['letter'][$key]??'';
                    $structure=$previousStructure;
                    if(isset($valid['letter']['structure'])) {
                        $structure=array_replace($structure,array_intersect_key($valid['letter']['structure'],array_flip(['about','purpose','request_reference','legal_basis','terms','copies'])));
                        foreach(['legal_basis','terms','copies'] as $key) $structure[$key]=array_values(array_filter($valid['letter']['structure'][$key]??[],fn($v)=>is_string($v)&&trim($v)!==''));
                    }
                    if(empty($structure['source'])||$q->boolean('sync_letter_source')) $structure['source']=\App\Support\ApprovalLetter::source($r);
                    $r['letter']['structure']=$structure;
                    $r['draft']=implode("\n\n",array_intersect_key($r['letter'],array_flip(['opening','body','conditions','closing'])));
                } else { $q->validate(['draft'=>'required|string|min:30|max:15000']); $r['draft']=$q->draft; if(isset($r['letter'])) $r['letter']['body']=$q->draft; }
                if($action==='send') $r['status']='review_draft';
                $label=$action==='send'?'Draft dikirim ke Kabid':'Draft disimpan'; break;
            case 'release':
                $r['documents'][]='Surat Persetujuan';
                if(D::isFree($r)) { $r['status']='ttd'; $label='Surat dirilis; bebas biaya, retribusi dilewati'; }
                else { $r['status']='tagihan'; $label='Surat dirilis untuk proses retribusi'; }
                break;
            case 'save_assessment':
            case 'invoice':
                abort_if(D::isFree($r),403,'Permohonan bebas biaya tidak memiliki penetapan retribusi.');
                if(\App\Support\FinanceDocument::tariffMissing($r)) return back()->withErrors(['action'=>'Tarif belum ditetapkan untuk lokasi dan jenis kegiatan ini pada tanggal kegiatan. Minta admin menetapkannya di Data Master sebelum penetapan.']);
                if(\App\Support\FinanceDocument::sourceChanges($r)) return back()->withErrors(['action'=>'Sumber SKRD berubah. Tinjau dan simpan template SKRD sebelum penerbitan.']);
                if($action==='invoice'&&$r['discount']==='diajukan') return back()->withErrors(['action'=>'Keputusan keringanan belum tersedia. Penetapan ditunda. Gunakan contoh pengajuan yang sudah disetujui untuk demo diskon; kewenangan keputusan belum diasumsikan.']);
                $q->validate(['rate'=>'required|numeric|decimal:0,2|min:0|max:100000000','rate_reason'=>'required|string|max:500']);
                $r['templates']['skrd']['assessment']=['rate'=>(float)$q->rate,'note'=>$q->rate_reason];
                $note='Tarif per hari: '.D::rupiah((float)$q->rate).'; '.$q->rate_reason;
                $assessment=\App\Support\FinanceDocument::assessment($r);
                if($action==='save_assessment') {
                    $label='Draft penetapan retribusi disimpan';
                    break;
                }
                $r['bill']=$assessment;
                \App\Support\FinanceDocument::freezeSkrd($r,'Penerbitan SKRD');
                $r['documents'][]='SKRD'; $r['status']='bayar'; $label='SKRD demo diterbitkan'; break;
            case 'pay':
                $q->validate(['payment_checked'=>'accepted','payment_date'=>'required|date|before_or_equal:today']);
                abort_unless($r['bill'],422);
                $r['payment']=['date'=>$q->payment_date,'proof'=>$r['uploads']['proof']['name']??'bukti_pembayaran_contoh.pdf']; $r['status']='ttd';
                $r['documents']=array_unique(array_merge($r['documents'],['STBP','STS'])); $label='Pembayaran demo dikonfirmasi; STBP dan STS demo dibuat'; break;
            case 'sign':
                $q->validate(['confirmed'=>'accepted']); abort_unless($r['payment']||D::isFree($r),422); $r['signed']=true; $r['signature']=['name'=>W::database()?\Illuminate\Support\Facades\DB::table('pengguna')->where('pengguna_id',W::actor())->value('nama'):'Drs. Aditya Pratama (fiktif)','at'=>date('Y-m-d H:i'),'reference'=>'SIM-'.$r['tracking']]; $r['status']='serah'; $label='Simulasi tanda tangan selesai'; break;
            case 'handover':
                $q->validate(['confirmed'=>'accepted','recipient'=>'required|string|max:100']);
                $needed=D::isFree($r)?['Surat Persetujuan']:['Surat Persetujuan','SKRD','STBP'];
                abort_unless($r['signed']&&count(array_intersect($needed,$r['documents']))===count($needed),422);
                $r['recipient']=$q->recipient; $r['completed']=date('Y-m-d'); $r['status']='selesai'; $label='Dokumen diserahkan; permohonan selesai'; break;
        }
        $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>$label,'note'=>$note]; $this->save($r);
        if($action==='save_assessment') return redirect()->route('listing',['kind'=>'retribusi','selected'=>$id])->with('success','Penetapan disimpan. Nominal draft SKRD mengikuti penetapan ini.');
        if($action==='save') return redirect()->route('listing',['kind'=>'draft','selected'=>$id])->with('success','Draft '.$r['tracking'].' berhasil disimpan. Anda tetap berada di editor; draft belum dikirim ke Kabid.');
        return redirect()->route('detail',$id)->with('success',$label.(W::database()?'. Tersimpan di database lokal.':'. Perubahan hanya tersimpan di sesi demo ini.'));
    }
    public function printLetter(int $id,string $mode)
    {
        $r=$this->record($id);abort_if(!empty($r['deleted_at']),403);
        if($mode==='draft') { $this->requireRole(['petugas']); abort_unless(in_array($r['status'],['draft','review_draft','verifikasi','rilis']),403); }
        else { $this->requireRole(['petugas','kadis']); abort_unless($mode==='manual'&&\App\Support\ManualSignature::eligible($r),403); }
        if($mode==='manual')$r['signature']['name']=\App\Support\ManualSignature::signers()[0]['name']??'Kepala Dinas Pariwisata';
        return $this->view('dokumen.print-letter',['r'=>$r,'mode'=>$mode,'title'=>'Cetak surat','page'=>'pratinjau']);
    }
    public function recordManualSignature(Request $q,int $id)
    {
        $this->requireRole(['petugas']);$r=$this->record($id);
        abort_unless(\App\Support\ManualSignature::eligible($r),409,'Permohonan tidak lagi menunggu tanda tangan atau prasyarat belum lengkap.');
        $data=$q->validate(['signed_file'=>'required|file|mimes:pdf|max:10240','signed_date'=>'required|date_format:Y-m-d|before_or_equal:today','reason'=>'required|string|min:5|max:1000','confirmed'=>'accepted','source_token'=>'required|string','signer_id'=>['required',Rule::in(array_column(\App\Support\ManualSignature::signers(),'id'))]]);
        abort_unless(hash_equals(\App\Support\ManualSignature::token($r),$data['source_token']),409,'Versi surat berubah. Muat ulang dan gunakan cetakan versi terbaru.');
        $signer=collect(\App\Support\ManualSignature::signers())->first(fn($s)=>(string)$s['id']===(string)$data['signer_id']);
        $file=$q->file('signed_file');$stored='signed-'.bin2hex(random_bytes(20)).'.pdf';
        $dir=storage_path('app/demo-uploads');if(!is_dir($dir))mkdir($dir,0775,true);
        $path=$dir.'/'.$stored;abort_if(file_put_contents($path,file_get_contents($file->getRealPath()))===false,500,'Dokumen tidak dapat disimpan.');
        $r['manual_letter']=['stored'=>$stored,'name'=>basename($file->getClientOriginalName()),'size'=>filesize($path)];
        $r['signed']=true;$r['status']='serah';
        $r['signature']=['name'=>$signer['name'],'signer_id'=>$signer['id'],'method'=>'unggahan_manual','at'=>$data['signed_date'].' 00:00:00','reference'=>'MANUAL-'.$r['tracking'],'reason'=>$data['reason'],'draft_version'=>$r['version']];
        $r['history'][]=['at'=>date('Y-m-d H:i:s'),'role'=>$this->role(),'action'=>'Tanda tangan basah dicatat','note'=>'Penandatangan: '.$signer['name'].'; versi draft '.$r['version'].'; '.$data['reason']];
        try {$this->save($r);}catch(\Throwable $e){if(is_file($path))unlink($path);throw $e;}
        return redirect()->route('detail',$id)->with('success','Surat bertanda tangan basah tersimpan. Permohonan masuk antrean serah terima Sekretariat.');
    }
    public function checklist(int $id)
    {
        $r=$this->record($id);
        abort_unless(D::canSeeFiles($this->role(),$r),403);
        $location=collect(W::locations())->firstWhere('id',$r['location_id']);
        return $this->view('dokumen.checklist',['r'=>$r,'rows'=>\App\Support\PermitRequirements::checklist($r,$location),'title'=>'Lembar periksa kelengkapan','page'=>'pratinjau']);
    }
    public function document(int $id,string $type)
    {
        $r=$this->record($id); $types=['surat'=>'Surat Persetujuan','skrd'=>'SKRD','stbp'=>'STBP','sts'=>'STS'];
        abort_unless(isset($types[$type])&&in_array($types[$type],$r['documents']),404);
        abort_unless(D::canSeeFiles($this->role(),$r),403);
        if($type==='skrd'&&request()->filled('version')) {
            $version=filter_var(request('version'),FILTER_VALIDATE_INT);
            abort_unless($version&&isset($r['skrd_versions'][$version-1]),404);
            $r['skrd_snapshot']=$r['skrd_versions'][$version-1]['data'];
            $r['document_sources']['skrd']='template';
        }
        return $this->view('dokumen.preview',['r'=>$r,'type'=>$types[$type],'title'=>'Pratinjau dokumen demo','page'=>'pratinjau']);
    }
    public function template(Request $q,int $id,string $type)
    {
        $this->requireRole(['bendahara']); $r=$this->record($id);
        abort_unless(in_array($type,['skrd','sts','stbp'])&&in_array($r['status'],['tagihan','bayar','ttd']),403);
        if($q->isMethod('post')) {
            if($type==='skrd') {
                $q->validate(['source_token'=>'required|string']);
                if(!hash_equals(hash('sha256',json_encode(\App\Support\FinanceDocument::source($r))),$q->source_token))
                    return back()->withErrors(['source'=>'Data permohonan berubah sejak editor dibuka. Muat ulang dan tinjau kembali.'])->withInput();
                if(\App\Support\FinanceDocument::sourceChanges($r)) $q->validate(['source_reviewed'=>'accepted']);
                if(isset($r['skrd_snapshot'])) $q->validate(['revision_reason'=>'required|string|min:5|max:500']);
            }
            $data=$q->validate(['description'=>'required|string|max:3000','address'=>'nullable|string|max:300','bank'=>'nullable|string|max:100','account'=>'nullable|string|max:100','due'=>'nullable|string|max:200','period'=>'nullable|date_format:Y-m','issued_date'=>'nullable|date','npwr'=>'nullable|string|max:100',
                'rental_date'=>'nullable|date_format:Y-m-d',
                'number'=>'nullable|string|max:150','payer'=>'nullable|string|max:200','method'=>'nullable|in:Tunai,Non Tunai',
                'signer_rank'=>'nullable|string|max:100','signer_name'=>'nullable|string|max:150','signer_nip'=>'nullable|string|max:30','signer_title'=>'nullable|string|max:200',
                'authority_name'=>'nullable|string|max:150','authority_nip'=>'nullable|string|max:30','attention'=>'nullable|string|max:2000',
                'rows'=>'sometimes|required|array|min:1|max:20','rows.*.code'=>'required|string|max:100',
                'rows.*.label'=>'required|string|max:300','rows.*.amount'=>'required|numeric|decimal:0,2|min:0|max:999999999999']);
            if(isset($data['rows'])) {
                $finance=\App\Support\FinanceDocument::data($r,$type,true);
                $expected=$type==='skrd'?$finance['base']:$finance['total'];
                $amount=array_sum(array_column($data['rows'],'amount'));
                if(abs($amount-$expected)>0.005) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['rows'=>$type==='skrd'
                        ?'Nominal SKRD mengikuti Penetapan Retribusi. Ubah tarif pada penetapan sebelum penerbitan.'
                        :'Jumlah rincian harus sama dengan '.\App\Support\DemoData::rupiah($expected).'.']);
                }
            }
            $r['templates'][$type]=array_replace($r['templates'][$type]??[],$data); $r['document_sources'][$type]='template';
            if($type==='skrd') {
                if($q->boolean('reset_description')) {
                    $source=\App\Support\FinanceDocument::source($r);
                    $r['templates']['skrd']['description']='Pelaksanaan kegiatan '.$source['activity'].' di '.$source['location'];
                }
                unset($r['templates']['skrd']['payer']);
                $r['skrd_source']=\App\Support\FinanceDocument::source($r);
                if(isset($r['skrd_snapshot'])) \App\Support\FinanceDocument::freezeSkrd($r,$q->revision_reason);
                $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>isset($r['skrd_snapshot'])?'Revisi SKRD dicatat':'Sumber draft SKRD ditinjau','note'=>$q->revision_reason??'Data sumber terkini digunakan; uraian petugas dipertahankan.'];
            }
            $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Template '.strtoupper($type).' diperbarui','note'=>$type==='skrd'?'Informasi surat dan tanggal sewa SKRD disimpan; nominal mengikuti penetapan.':'Uraian untuk permohonan ini; nominal pembayaran tidak berubah.']; $this->save($r);
            return redirect()->to('/permohonan/'.$id.'/template/'.$type)->with('success','Template '.strtoupper($type).' disimpan untuk permohonan ini.');
        }
        return $this->view('dokumen.editor',['r'=>$r,'docKey'=>$type,'title'=>'Edit Template '.strtoupper($type),'page'=>'retribusi']);
    }
    public function upload(Request $q,int $id,string $type)
    {
        $this->requireRole(['bendahara']); $r=$this->record($id);
        abort_unless(in_array($type,['skrd','sts','stbp','proof'])&&in_array($r['status'],['tagihan','bayar','ttd']),403);
        if($type==='proof') abort_unless($r['status']==='bayar',403);
        abort_if($type==='skrd'&&isset($r['skrd_snapshot']),422,'SKRD sudah terbit. Gunakan revisi template beralasan; penggantian unggahan final belum tersedia.');
        $q->validate(['file'=>'required|file|mimes:pdf,jpg,jpeg,png|max:2048']);
        $file=$q->file('file'); $name=bin2hex(random_bytes(20)).'.'.$file->extension();
        $dir=storage_path('app/demo-uploads'); if(!is_dir($dir)) mkdir($dir,0775,true);
        abort_if(file_put_contents($dir.'/'.$name,file_get_contents($file->getRealPath()))===false,500,'Berkas tidak dapat disimpan.');
        $r['uploads'][$type]=['stored'=>$name,'name'=>basename($file->getClientOriginalName()),'size'=>filesize($dir.'/'.$name)];
        if($type!=='proof') $r['document_sources'][$type]='upload';
        $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Berkas '.($type==='proof'?'bukti pembayaran':strtoupper($type)).' diunggah','note'=>'Unggah tidak otomatis menerbitkan dokumen atau mengonfirmasi pembayaran.']; $this->save($r);
        return redirect()->route('listing',['kind'=>'retribusi','selected'=>$id])->with('success','Berkas disimpan. Silakan periksa sebelum melanjutkan.');
    }
    public function uploaded(int $id,string $type)
    {
        $r=$this->record($id); abort_unless(D::canSeeFiles($this->role(),$r),403);
        if($type==='surat-manual') {
            $file=$r['manual_letter']??null;abort_unless($file&&!empty($r['signed']),404);
            $path=storage_path('app/demo-uploads/'.basename($file['stored']));abort_unless(is_file($path),404);
            return response()->file($path,['Content-Type'=>'application/pdf','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
        }
        if($type==='proof') $this->requireRole(['bendahara','admin']);
        if(preg_match('/^requirement-(\d+)$/',$type,$m)) {
            $file=$r['files'][(int)$m[1]]??null; abort_unless($file&&isset($file['stored']),404);
            $path=storage_path('app/demo-uploads/'.basename($file['stored'])); abort_unless(is_file($path),404);
            return response(file_get_contents($path),200,['Content-Type'=>mime_content_type($path),'Content-Disposition'=>'inline','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
        }
        abort_unless(isset($r['uploads'][$type]),404);
        if($this->role()!=='bendahara') abort_unless(in_array(strtoupper($type),$r['documents'])||$type==='proof',403);
        $file=$r['uploads'][$type]; $path=storage_path('app/demo-uploads/'.basename($file['stored'])); abort_unless(is_file($path),404);
        return response(file_get_contents($path),200,['Content-Type'=>mime_content_type($path),'Content-Disposition'=>'inline','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
    }
    public function remind(int $id)
    {
        $this->requireRole(['bendahara']); $r=$this->record($id); abort_unless($r['status']==='bayar'&&$r['bill'],403);
        $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Simulasi kirim ulang tagihan','note'=>'Tidak ada WhatsApp/email yang dikirim.']; $this->save($r);
        return back()->with('success','Simulasi kirim ulang tercatat. Tidak ada pesan eksternal yang dikirim.');
    }
    public function calendar(Request $q)
    {
        $month=$q->query('month',date('Y-m')); if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)) $month=date('Y-m');
        $base=CarbonImmutable::parse($month.'-01');
        return $this->view('kalender.index',['title'=>'Kalender kegiatan','page'=>'kalender','month'=>$base,'selectedLocation'=>$q->query('lokasi')]);
    }
    public function reports() { $this->requireRole(['kadis','admin']); return $this->view('laporan.index',['title'=>'Statistik & laporan','page'=>'laporan']); }
    public function users() { $this->requireRole(['admin']); return $this->view('admin.pengguna.index',['title'=>'Pengguna & role','page'=>'pengguna','users'=>config('siap.database_accounts') ? app(\App\Services\AccountService::class)->listing() : session('demo.users')]); }
    public function addUser(Request $q)
    {
        if (config('siap.database_accounts')) {
            $this->requireRole(['admin']);
            app(\App\Services\AccountService::class)->save(array_merge($q->only(['name','username','nip','role','password']),['active'=>true]),null,(int)session('account_id'));
            return back()->with('success','Akun tersimpan di database.');
        }
        $this->requireRole(['admin']); $data=$q->validate(['name'=>'required|string|max:100','nip'=>'nullable|string|max:30','role'=>['required',Rule::in(array_keys(D::ROLES))]]);
        $users=session('demo.users'); $users[]=['id'=>uniqid('demo_'),'name'=>$data['name'],'nip'=>$q->input('nip',''),'role'=>$data['role'],'active'=>true]; session(['demo.users'=>$users]);
        return back()->with('success','Profil contoh ditambahkan. Tidak membuat akun login sungguhan.');
    }
    public function removeRequest(Request $q,int $id)
    {
        $this->requireRole(['sekretariat','admin']); $r=$this->record($id);
        $q->validate(['reason'=>'required|string|min:5|max:1000','tracking'=>['required',Rule::in([$r['tracking']])]]);
        abort_if(!empty($r['deleted_at']),409);
        $r['deleted_at']=date('Y-m-d H:i'); $r['deleted_by']=$this->role(); $r['delete_reason']=$q->reason;
        $r['history'][]=['at'=>$r['deleted_at'],'role'=>$this->role(),'action'=>'Permohonan dihapus sementara','note'=>$q->reason]; $this->save($r);
        return redirect()->route('listing',['kind'=>'riwayat','tab'=>'hapus'])->with('success','Permohonan dipindahkan ke Riwayat Hapus dan dapat dipulihkan.');
    }
    public function restoreRequest(int $id)
    {
        $this->requireRole(['sekretariat','admin']); $r=$this->record($id); abort_if(empty($r['deleted_at']),409);
        $r['deleted_at']=null; $r['history'][]=['at'=>date('Y-m-d H:i'),'role'=>$this->role(),'action'=>'Permohonan dipulihkan','note'=>'Tahap terakhir dipertahankan.']; $this->save($r);
        return redirect()->route('detail',$id)->with('success','Permohonan dipulihkan.');
    }
    public function editUser(Request $q,string $id)
    {
        if (config('siap.database_accounts')) {
            $this->requireRole(['admin']); abort_unless(ctype_digit($id),404);
            app(\App\Services\AccountService::class)->save($q->only(['name','username','nip','role','password','active']),(int)$id,(int)session('account_id'));
            return back()->with('success','Akun diperbarui di database.');
        }
        $this->requireRole(['admin']); $this->init();
        $data=$q->validate(['name'=>'required|string|max:100','nip'=>'nullable|string|max:30','role'=>['required',Rule::in(array_keys(D::ROLES))],'active'=>'required|boolean']);
        abort_unless(collect(session('demo.users'))->contains('id',$id),404);
        session(['demo.users'=>collect(session('demo.users'))->map(fn($u)=>$u['id']===$id?array_merge($u,$data,['active'=>$q->boolean('active')]):$u)->all()]);
        return back()->with('success','Profil demo diperbarui. Pilihan peran demo tidak diubah menjadi autentikasi nyata.');
    }
    public function archivePackage(Request $q)
    {
        $this->init(); $rows=collect(W::requests())->filter(fn($r)=>empty($r['deleted_at']))->where('status','selesai');
        if($q->filled('id')) $rows=$rows->where('id',(int)$q->id);
        abort_if($rows->isEmpty(),404);
        $data=$rows->map(function($r) {
            unset($r['uploads']);
            $r['files']=array_map(function($f){unset($f['stored']);return $f;},$r['files']);
            return $r;
        })->values();
        return response()->streamDownload(fn()=>print(json_encode(['keterangan'=>'Cadangan metadata demo; tidak memuat berkas biner atau kredensial.','permohonan'=>$data],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)),'arsip-metadata-demo.json',['Content-Type'=>'application/json']);
    }
    public function master() { $this->requireRole(['admin']); return $this->view('admin.lokasi.index',['title'=>'Lokasi & tarif','page'=>'master','activities'=>W::database()?app(BusinessStore::class)->activities(false):array_map(fn($n)=>['id'=>0,'name'=>$n,'active'=>true],D::ACTIVITIES)]); }
    private function masterAction(callable $work,string $ok)
    {
        $this->requireRole(['admin']);
        if(!W::database()) return back()->withErrors(['master'=>'Pengelolaan data master hanya tersedia pada mode database.']);
        try { $work(app(BusinessStore::class)); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { if($e->getStatusCode()!==422) throw $e; return back()->withErrors(['master'=>$e->getMessage()])->withInput(); }
        return back()->with('success',$ok);
    }
    public function addLocation(Request $q)
    {
        $this->requireRole(['admin']);
        $d=$q->validate(['name'=>'required|string|min:3|max:150','rate'=>'required|integer|min:0|max:100000000','managed'=>'nullable|boolean','dlh'=>'nullable|boolean']);
        return $this->masterAction(fn($s)=>$s->createLocation(['name'=>$d['name'],'rate'=>(int)$d['rate'],'managed'=>$q->boolean('managed'),'dlh'=>$q->boolean('dlh')],W::actor()),'Lokasi ditambahkan beserta tarif awal.');
    }
    public function editLocation(Request $q,int $id)
    {
        $this->requireRole(['admin']);
        $d=$q->validate(['name'=>'required|string|min:3|max:150','managed'=>'nullable|boolean','dlh'=>'nullable|boolean','active'=>'nullable|boolean']);
        return $this->masterAction(fn($s)=>$s->updateLocation($id,['name'=>$d['name'],'managed'=>$q->boolean('managed'),'dlh'=>$q->boolean('dlh'),'active'=>$q->boolean('active')],W::actor()),'Data lokasi diperbarui.');
    }
    public function removeLocation(int $id) { return $this->masterAction(fn($s)=>$s->deleteLocation($id,W::actor()),'Lokasi dihapus.'); }
    public function addActivity(Request $q)
    {
        $this->requireRole(['admin']);
        $d=$q->validate(['name'=>'required|string|min:3|max:150']);
        return $this->masterAction(fn($s)=>$s->addActivity($d['name'],W::actor()),'Jenis kegiatan ditambahkan; tarif lokasi yang ada disalin ke jenis kegiatan ini.');
    }
    public function toggleActivity(Request $q,int $id)
    {
        $this->requireRole(['admin']);
        $q->validate(['active'=>'required|boolean']);
        return $this->masterAction(fn($s)=>$s->setActivityActive($id,$q->boolean('active'),W::actor()),'Status jenis kegiatan diperbarui.');
    }
    public function updateRate(Request $q,int $id)
    {
        $this->requireRole(['admin']); $q->validate(['rate'=>'required|integer|min:0|max:100000000']);
        if(W::database()){app(BusinessStore::class)->updateRate($id,(int)$q->rate,W::actor());return back()->with('success','Tarif tersimpan di database.');}
        $all=W::locations(); foreach($all as &$loc) if($loc['id']===$id) $loc['rate']=(int)$q->rate;
        session(['demo.locations'=>$all]); return back()->with('success','Tarif contoh diperbarui. Tagihan yang sudah terbit tetap memakai nominal penetapan sebelumnya.');
    }
    public function export(Request $q)
    {
        $this->init(); $rows=collect(W::requests())->filter(fn($r)=>empty($r['deleted_at'])); if($q->query('kind')==='arsip') $rows=$rows->where('status','selesai');
        return response()->streamDownload(function() use($rows) {
            $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['DATA DEMO — BUKAN DOKUMEN RESMI']); fputcsv($out,['Nomor','Pemohon','Status','Tanggal kegiatan','Jumlah tagihan']);
            foreach($rows as $r) {
                $safe=fn($s)=>preg_match('/^[=+@\-\t\r]/',(string)$s)?"'".$s:$s;
                fputcsv($out,array_map($safe,[$r['tracking'],$r['name'],D::STATUS[$r['status']],$r['date'],$r['bill']['total']??'Belum ditetapkan']));
            } fclose($out);
        },'rekap-demo-siap-wisata.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}

