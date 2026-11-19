<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('permohonan', function(Blueprint $t){ $t->json('data_surat')->nullable(); });
  Schema::table('draft_surat', function(Blueprint $t){ $t->json('struktur_surat')->nullable(); });
 }
 public function down(): void {
  Schema::table('draft_surat', fn(Blueprint $t)=>$t->dropColumn('struktur_surat'));
  Schema::table('permohonan', fn(Blueprint $t)=>$t->dropColumn('data_surat'));
 }
};
