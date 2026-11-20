<?php
namespace App\Support;
use App\Services\BusinessStore;
class Workspace
{
 public static function database(): bool { return (bool)config('siap.database_business'); }
 public static function requests(): array { return self::database()?app(BusinessStore::class)->all():session('demo.requests',[]); }
 public static function activities(): array { return self::database()?array_column(app(BusinessStore::class)->activities(),'name'):DemoData::ACTIVITIES; }
 public static function locations(): array { return self::database()?app(BusinessStore::class)->locations():session('demo.locations',DemoData::locations()); }
 public static function actor(): int {
  if(config('siap.database_accounts'))return (int)session('account_id');
  abort_unless(app()->environment('local','testing'),403,'Login akun database diperlukan.');
  $id=\Illuminate\Support\Facades\DB::table('pengguna')->where('username','demo.'.session('demo.role'))->value('pengguna_id');
  abort_unless($id,409,'Jalankan siap:siapkan-demo-db dahulu.');return (int)$id;
 }
}
