<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('permohonan',fn(Blueprint $t)=>$t->unsignedInteger('lock_version')->default(0));
  Schema::create('pengaturan_dokumen_permohonan',function(Blueprint $t){
   $t->bigIncrements('pengaturan_dokumen_permohonan_id');
   $t->unsignedBigInteger('permohonan_id');$t->string('jenis',20);
   $t->json('pengaturan');$t->json('sumber_ditinjau')->nullable();
   $t->string('sumber',20)->default('template');
   $t->string('file_path',500)->nullable();$t->string('nama_asli',255)->nullable();
   $t->unsignedBigInteger('ukuran_byte')->nullable();$t->string('mime_type',100)->nullable();$t->char('sha256',64)->nullable();
   $t->timestamps();$t->unique(['permohonan_id','jenis'],'uq_pengaturan_permohonan_jenis');
   $t->foreign('permohonan_id','fk_pengaturan_permohonan')->references('permohonan_id')->on('permohonan')->restrictOnDelete();
  });
 }
 public function down(): void {
  Schema::dropIfExists('pengaturan_dokumen_permohonan');
  Schema::table('permohonan',fn(Blueprint $t)=>$t->dropColumn('lock_version'));
 }
};
