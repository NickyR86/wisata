<?php
namespace App\Services;

use App\Models\Pengguna;
use App\Models\Role;
use App\Support\DemoData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function listing(): array
    {
        return Pengguna::with('roles')->orderBy('nama')->get()->map(fn($u)=>[
            'id'=>(string)$u->getKey(), 'name'=>$u->nama, 'nip'=>$u->nip,
            'username'=>$u->username, 'role'=>$u->roles->first()?->name,
            'active'=>$u->status_akun==='aktif',
        ])->all();
    }

    public function save(array $input, ?int $id, ?int $actor): Pengguna
    {
        $input['username']=mb_strtolower(trim($input['username']??''));
        $input['nip']=trim($input['nip']??'') ?: null;
        $data=Validator::make($input,[
            'name'=>'required|string|max:150', 'username'=>'required|string|min:6|max:100|regex:/^[a-z0-9._-]+$/',
            'nip'=>'nullable|string|max:20', 'role'=>['required',Rule::in(array_keys(DemoData::ROLES))],
            'password'=>[$id?'nullable':'required','string','min:12','max:128'], 'active'=>'required|boolean',
        ])->validate();
        return DB::transaction(function()use($data,$id,$actor){
            // Serialize account writes and protect the last administrator.
            $accounts=Pengguna::orderBy('pengguna_id')->lockForUpdate()->get();
            $user=$id ? $accounts->firstWhere('pengguna_id',$id) : new Pengguna;
            abort_unless($user,404);
            foreach($accounts as $other){
                if($other->getKey()===$id)continue;
                $values=array_filter([$other->username,$other->nip]);
                if(in_array($data['username'],$values,true)||($data['nip']&&in_array($data['nip'],$values,true)))
                    throw ValidationException::withMessages(['username'=>'Username/NIP sudah dipakai akun lain.']);
            }
            $old=$id ? $this->snapshot($user) : null;
            if($id && $user->roles()->where('name','admin')->exists() && (!$data['active']||$data['role']!=='admin')){
                $remaining=Pengguna::where('status_akun','aktif')->where('pengguna_id','<>',$id)
                    ->whereHas('roles',fn($q)=>$q->where('name','admin'))->exists();
                if(!$remaining)throw ValidationException::withMessages(['role'=>'Minimal satu administrator aktif harus dipertahankan.']);
            }
            $user->nama=$data['name'];$user->nip=$data['nip'];$user->username=$data['username'];
            $user->status_akun=$data['active']?'aktif':'nonaktif';
            if(!empty($data['password']))$user->password=Hash::make($data['password']);
            $user->save();
            $role=Role::firstOrCreate(['name'=>$data['role'],'guard_name'=>'web']);
            DB::table('model_has_roles')->where('model_id',$user->getKey())->where('model_type',Pengguna::class)->delete();
            DB::table('model_has_roles')->insert(['model_id'=>$user->getKey(),'model_type'=>Pengguna::class,'role_id'=>$role->getKey()]);
            DB::table('audit_aktivitas')->insert([
                'aktor_id'=>$actor??$user->getKey(),'aksi'=>$id?'akun_diubah':'akun_dibuat',
                'jenis_objek'=>'pengguna','objek_id'=>$user->getKey(),
                'sebelum'=>$old?json_encode($old):null,'sesudah'=>json_encode($this->snapshot($user)),
                'alasan'=>$actor?'Pengelolaan akun':'Pembuatan akun awal melalui CLI','terjadi_pada'=>now(),
            ]);
            return $user;
        });
    }

    private function snapshot(Pengguna $u): array
    {
        return ['nama'=>$u->nama,'username'=>$u->username,'nip'=>$u->nip,'status_akun'=>$u->status_akun,
            'roles'=>$u->roles()->pluck('name')->all()];
    }

    public function authenticate(string $identifier,string $password): ?Pengguna
    {
        $identifier=mb_strtolower(trim($identifier));
        $user=Pengguna::where('username',$identifier)->orWhere('nip',$identifier)->first();
        if(!$user || $user->status_akun!=='aktif'){ Hash::make($password); return null; }
        if(!Hash::check($password,$user->password))return null;
        return $user;
    }
}
