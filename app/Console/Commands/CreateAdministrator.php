<?php
namespace App\Console\Commands;
use App\Models\Pengguna;
use App\Services\AccountService;
use Illuminate\Console\Command;
class CreateAdministrator extends Command
{
    protected $signature='siap:buat-admin';
    protected $description='Buat administrator awal database (password tidak ditampilkan).';
    public function handle(AccountService $accounts): int
    {
        if(Pengguna::where('status_akun','aktif')->exists()){$this->error('Akun awal sudah tersedia. Gunakan pengelolaan akun.');return self::FAILURE;}
        $name=$this->ask('Nama administrator');$username=$this->ask('Username (minimal 6 karakter)');
        $password=$this->secret('Password (minimal 12 karakter)');
        if($password!==$this->secret('Ulangi password')){$this->error('Password tidak sama.');return self::FAILURE;}
        $accounts->save(['name'=>$name,'username'=>$username,'nip'=>null,'password'=>$password,'role'=>'admin','active'=>true],null,null);
        $this->info('Administrator dibuat. Aktifkan SIAP_DATABASE_ACCOUNTS=true lalu php artisan config:clear.');
        return self::SUCCESS;
    }
}
