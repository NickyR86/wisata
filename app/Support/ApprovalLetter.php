<?php
namespace App\Support;

class ApprovalLetter
{
 public const SOURCE_FIELDS = ['event_name','pic_position','request_number','request_date','start_time','end_time'];
 public static function source(array $r): array {
  $location=collect(Workspace::locations())->firstWhere('id',$r['location_id']);
  $extra=$r['letter_source']??[];
  return [
   'pemohon'=>$r['name'], 'pic'=>$r['pic'], 'alamat'=>$r['address']??'Belum diisi',
   'jabatan'=>$extra['pic_position']??'Belum diisi',
   'kegiatan'=>$extra['event_name']??$r['activity'], 'lokasi'=>$location['name']??'Belum diisi',
   'tanggal'=>DemoData::date($r['date']), 'durasi'=>(string)$r['days'].' hari',
   'jam_mulai'=>$extra['start_time']??'Belum diisi', 'jam_selesai'=>$extra['end_time']??'Belum diisi',
   'nomor_permohonan'=>$extra['request_number']??'Belum diisi',
   'tanggal_permohonan'=>empty($extra['request_date'])?'Belum diisi':DemoData::date($extra['request_date']),
  ];
 }
 public static function defaults(array $r): array {
  return ['format'=>2,
   'about'=>'PELAKSANAAN KEGIATAN {kegiatan}',
   'purpose'=>'Kegiatan {kegiatan} di {lokasi}, Kota Pangkal Pinang',
   'legal_basis'=>[
    'Undang-Undang Nomor 9 Tahun 2015 tentang Perubahan Kedua atas Undang-Undang Nomor 23 Tahun 2014 tentang Pemerintahan Daerah;',
    'Undang-Undang Nomor 1 Tahun 2022 tentang Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah;',
    'Undang-Undang Nomor 18 Tahun 2025 tentang Perubahan Ketiga atas Undang-Undang Nomor 10 Tahun 2009 tentang Kepariwisataan;',
    'Peraturan Pemerintah Nomor 18 Tahun 2016 tentang Perangkat Daerah;',
    'Peraturan Daerah Kota Pangkal Pinang Nomor 1 Tahun 2020 tentang Perubahan atas Peraturan Daerah Nomor 18 Tahun 2016 tentang Pembentukan dan Susunan Perangkat Daerah Kota Pangkal Pinang;',
    'Peraturan Daerah Nomor 1 Tahun 2024 tentang Pajak Daerah dan Retribusi Daerah;',
    'Peraturan Wali Kota Pangkal Pinang Nomor 27 Tahun 2024 tentang Tata Cara Pemungutan Retribusi Penyediaan Tempat Penginapan/Pesanggrahan/Vila dan Retribusi Pelayanan Tempat Rekreasi, Pariwisata, dan Olahraga;',
   ],
   'request_reference'=>'Surat permohonan dari {pemohon}, Nomor: {nomor_permohonan}, tanggal {tanggal_permohonan}.',
   'terms'=>[
    'Waktu penyelenggaraan pada tanggal {tanggal}, selama {durasi}, pukul {jam_mulai} sampai {jam_selesai} WIB.',
    'Untuk kebersihan dikoordinasikan kepada Dinas Pariwisata dan Dinas Lingkungan Hidup Kota Pangkal Pinang.',
    'Untuk keamanan dan ketertiban, pelaksana kegiatan berkoordinasi dengan Polresta dan Satpol PP Kota Pangkal Pinang.',
    'Berkoordinasi dengan Polresta dan Dinas Perhubungan terkait lalu lintas jalan dan parkir.',
    'Segala akibat yang ditimbulkan dari penyelenggaraan acara menjadi tanggung jawab pihak penyelenggara.',
    'Persetujuan berlaku pada tanggal {tanggal}, selama {durasi}, pukul {jam_mulai} sampai {jam_selesai} WIB.',
   ],
   'copies'=>['Kapolresta Pangkal Pinang;','Kepala Dinas Lingkungan Hidup Kota Pangkal Pinang;','Kepala Dinas Perhubungan Kota Pangkal Pinang;','Kepala Satuan Polisi Pamong Praja Kota Pangkal Pinang;'],
  ];
 }
 public static function render(string $text,array $source): string {
  return strtr($text,array_combine(array_map(fn($k)=>'{'.$k.'}',array_keys($source)),array_values($source)));
 }
 public static function structured(array $r): array {
  $saved=$r['letter']['structure']??[];
  $data=array_replace(self::defaults($r),$saved);
  if(!$saved&&!empty($r['letter']['conditions']))
   $data['terms']=array_values(array_filter(array_map(fn($s)=>preg_replace('/^\s*\d+[.)]\s*/u','',trim($s)),preg_split('/\R/u',$r['letter']['conditions']))));
  $data['source']=$saved['source']??self::source($r);
  return $data;
 }
}
