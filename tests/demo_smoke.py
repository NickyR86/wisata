"""Integration checks for the local frontend demo (stdlib only).
Run: python tests/demo_smoke.py [http://127.0.0.1:8017]
Creates its own browser session; never touches another user's demo session.
"""
import datetime as dt
import html
import http.cookiejar
import re
import sys
import os
import json
import subprocess
from pathlib import Path
import urllib.error
import urllib.parse
import urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8017'
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(jar))
token = ''
checks = 0
kernel_cookies = {}
PHP = os.environ.get('DEMO_TEST_PHP')

def request(path, data=None, expected=200):
    global token, checks
    if data is not None:
        data = dict(data, _token=token)
        payload = urllib.parse.urlencode(data).encode()
    else:
        payload = None
    if PHP:
        url = BASE + path
        method = 'POST' if data is not None else 'GET'
        for _ in range(10):
            result = subprocess.run([PHP, str(Path(__file__).with_name('kernel_request.php'))], input=json.dumps({'url':url,'method':method,'data':data or {},'cookies':kernel_cookies,'referer':BASE+('/admin/lokasi' if path.startswith('/admin/lokasi/') else path.removesuffix('/aksi'))}), text=True, capture_output=True, check=True)
            response_data = json.loads(result.stdout)
            kernel_cookies.update(response_data['cookies'])
            if response_data['status'] in (301,302,303):
                url=urllib.parse.urljoin(url,response_data['location']); method='GET'; data=None
                continue
            break
        body=response_data['body']
        assert response_data['status']==expected, (path,response_data['status'],body[:400])
    else:
        req = urllib.request.Request(BASE+path, payload, headers={'Referer':BASE+('/admin/lokasi' if path.startswith('/admin/lokasi/') else path.removesuffix('/aksi')), 'Accept':'text/html'})
        try:
            response=client.open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response=error
        body=response.read().decode('utf-8-sig')
        assert response.status==expected, (path,response.status,body[:400])
        url=response.url
    match=re.search(r'name="_token" value="([^"]+)"',body)
    if match: token=html.unescape(match.group(1))
    checks += 1
    return body,url

def role(name):
    return request('/demo/masuk',{'role':name})[0]

request('/login')
# Setelah login semua peran masuk ke Dashboard; antrean kerja dibuka dari tombol di Dashboard.
for name in ['sekretariat','kabid','petugas','sekretaris','bendahara','kadis','admin']:
    body,url=request('/demo/masuk',{'role':name})
    assert url.endswith('/dashboard'), (name,url)
    summary,_=request('/dashboard')
    assert 'Kondisi pelayanan hari ini' in summary and 'Sebaran tahap pelayanan' in summary
    assert ('Ringkasan retribusi' in summary) == (name in ['bendahara','kadis','admin'])
    _body,final=request('/ringkasan')
    assert final.endswith('/dashboard'), 'Tautan lama /ringkasan harus diarahkan ke /dashboard'
role('sekretariat')
for query,expected in [('overdue','Melewati'),('due','Batas')]:
    body,_=request('/layanan/antrean?sla='+query)
    assert 'Hapus filter SLA' in body
body,_=request('/layanan/antrean')
assert 'Pekerjaan Sekretariat' in body and 'hero-band' not in body

for page in ['/','/layanan/antrean','/layanan/arsip','/layanan/riwayat','/kalender','/permohonan/baru','/layanan/serah-terima']:
    request(page)
request('/admin/lokasi',expected=403)
today=dt.date.today()
body,url=request('/permohonan',{'name':'Yayasan Uji Alur','pic':'PIC Demo','phone':'0800000000','email':'demo@example.test','activity':'Festival budaya','location_id':'1','date':(today+dt.timedelta(days=70)).isoformat(),'days':'2','files':'1'})
record_id=int(url.rsplit('/',1)[-1])
action_path=f'/permohonan/{record_id}/aksi'
request(action_path,{'action':'approve'},expected=403)
role('kabid')
request('/layanan/rilis')
body,_=request(action_path,{'action':'reject','note':''})
assert 'Periksa kembali' in body
request(action_path,{'action':'approve'})
role('petugas')
request('/layanan/draft')
locked,_=request('/permohonan/230')
assert 'Berkas belum tersedia untuk peran ini' in locked
draft='Ini adalah narasi surat contoh untuk pengujian alur revisi dan persetujuan.'
request(action_path,{'action':'send','draft':draft})
role('kabid')
request(action_path,{'action':'revise','note':'Lengkapi rincian waktu kegiatan.'})
role('petugas')
request(action_path,{'action':'send','draft':draft+' Rincian telah diperbaiki.'})
role('kabid')
request(action_path,{'action':'approve'})
role('sekretaris')
request('/layanan/verifikasi')
request(action_path,{'action':'approve'})
role('kabid')
request(action_path,{'action':'release'})
role('bendahara')
request('/layanan/retribusi')
body,_=request(action_path,{'action':'invoice'})
assert 'Rp 1.500.000' in body
# Approved fixture must use 25%, never 50%.
body,_=request('/permohonan/236/aksi',{'action':'invoice'})
assert 'Rp 337.500' in body and '25%' in body
role('admin')
for path in ['/laporan','/admin/pengguna','/admin/lokasi']:
    request(path)
request('/admin/lokasi/1',{'rate':'999999'})
body,_=request(f'/permohonan/{record_id}')
assert 'Rp 1.500.000' in body, 'Issued bill changed with master tariff'
request('/admin/pengguna',{'name':'Profil Demo Tambahan','role':'petugas'})
role('bendahara')
request(action_path,{'action':'pay','payment_date':today.isoformat(),'payment_checked':'1'})
role('kadis')
request('/layanan/tanda-tangan')
request('/laporan')
request(action_path,{'action':'sign','confirmed':'1'})
role('sekretariat')
body,_=request(action_path,{'action':'handover','recipient':'Penerima Uji','confirmed':'1'})
assert 'Permohonan selesai.' in body and 'Riwayat disposisi' in body
archive,_=request('/layanan/arsip')
assert 'Yayasan Uji Alur' in archive
for doc in ['surat','skrd','stbp','sts']:
    body,_=request(f'/dokumen/{record_id}/{doc}')
    assert 'BUKAN DOKUMEN RESMI' in body
body,_=request('/kalender?month='+ (today+dt.timedelta(days=45)).strftime('%Y-%m'))
assert 'Komunitas Musik Kota' not in body, 'Rejected request is shown in calendar'
body,_=request('/ekspor?kind=arsip')
assert 'Yayasan Uji Alur' in body
# Keringanan diputuskan saat peninjauan berkas awal: 25% disetujui lalu tagihan memakai potongan.
body,url=request('/permohonan',{'name':'Uji Keringanan 25','pic':'PIC','phone':'0800','activity':'Festival budaya','location_id':'2','date':(today+dt.timedelta(days=61)).isoformat(),'days':'1','files':'1','discount':'25','discount_reason':'Dasar keringanan contoh untuk uji alur.'})
relief25=int(url.rsplit('/',1)[-1]); path=f'/permohonan/{relief25}/aksi'
role('kabid'); request(path,{'action':'approve','relief_decision':'approve'})
for name,act,data in [('petugas','send',{'draft':draft}),('kabid','approve',{}),('sekretaris','approve',{}),('kabid','release',{})]:
    role(name); request(path,dict(action=act,**data))
role('bendahara')
body,_=request(path,{'action':'invoice'})
assert 'Rp 375.000' in body and '25%' in body, 'Potongan 25% harus diterapkan setelah disetujui saat peninjauan awal'
# Bebas biaya: disetujui saat peninjauan awal, tahap retribusi dilewati, serah terima hanya Surat Persetujuan.
body,url=request('/permohonan',{'name':'Uji Bebas Biaya','pic':'PIC','phone':'0800','activity':'Festival budaya','location_id':'2','date':(today+dt.timedelta(days=62)).isoformat(),'days':'1','files':'1','discount':'free','discount_reason':'Kegiatan sosial contoh untuk uji alur.'})
free=int(url.rsplit('/',1)[-1]); path=f'/permohonan/{free}/aksi'
role('kabid'); request(path,{'action':'approve','relief_decision':'approve'})
for name,act,data in [('petugas','send',{'draft':draft}),('kabid','approve',{}),('sekretaris','approve',{}),('kabid','release',{})]:
    role(name); request(path,dict(action=act,**data))
role('bendahara'); request(path,{'action':'invoice'},expected=403)
role('kadis'); request(path,{'action':'sign','confirmed':'1'})
role('sekretariat')
body,_=request(path,{'action':'handover','recipient':'Penerima Bebas Biaya','confirmed':'1'})
assert 'Permohonan selesai.' in body and 'Bebas biaya' in body, 'Jalur bebas biaya harus selesai tanpa SKRD, STS, dan STBP'
# Lembar periksa kelengkapan mengikuti formulir fisik dinas.
body,_=request(f'/permohonan/{free}/lembar-periksa')
assert 'LEMBAR PERIKSA KELENGKAPAN' in body and 'Petugas Pelayanan Perizinan' in body and 'bebas biaya' in body.lower(), 'Lembar periksa bebas biaya harus menandai SKRD/STS/STBP/bukti TF tidak berlaku'
assert 'Fotokopi jasa impresariat/promotor' in body and 'Kepala Bidang Destinasi Pariwisata dan Industri Pariwisata' in body
body,_=request(f'/permohonan/{record_id}/lembar-periksa')
assert 'Surat permohonan persetujuan menyelenggarakan acara' in body and 'Proposal Kegiatan/Rundown' in body
# Keringanan ditolak saat peninjauan awal: tarif normal berlaku.
body,url=request('/permohonan',{'name':'Uji Keringanan Ditolak','pic':'PIC','phone':'0800','activity':'Festival budaya','location_id':'2','date':(today+dt.timedelta(days=63)).isoformat(),'days':'1','files':'1','discount':'25','discount_reason':'Dasar keringanan contoh untuk uji penolakan.'})
rejected=int(url.rsplit('/',1)[-1]); path=f'/permohonan/{rejected}/aksi'
role('kabid'); request(path,{'action':'approve','relief_decision':'reject','note':'Dokumen pendukung belum memenuhi syarat.'})
for name,act,data in [('petugas','send',{'draft':draft}),('kabid','approve',{}),('sekretaris','approve',{}),('kabid','release',{})]:
    role(name); request(path,dict(action=act,**data))
role('bendahara')
body,_=request(path,{'action':'invoice'})
assert 'Rp 500.000' in body, 'Keringanan ditolak: tagihan harus memakai tarif normal'
# Cek bentrok jadwal pada lokasi yang sama.
role('sekretariat')
base=(today+dt.timedelta(days=90)).isoformat(); overlap=(today+dt.timedelta(days=91)).isoformat(); apart=(today+dt.timedelta(days=100)).isoformat()
form=lambda name,loc,date,days='1',**extra: dict({'name':name,'pic':'PIC','phone':'0800','activity':'Festival budaya','location_id':loc,'date':date,'days':days,'files':'1'},**extra)
body,url=request('/permohonan',form('Uji Bentrok Pertama','2',base,'3')); first=int(url.rsplit('/',1)[-1])
body,_=request('/permohonan/baru'); assert 'occupiedData' in body and 'conflictBox' in body and 'Uji Bentrok Pertama' in body
try:
    body,_=request('/permohonan',form('Uji Bentrok Kedua','2',overlap)); assert 'Jadwal bentrok' in body
except AssertionError as error:
    assert '405' in str(error), error   # sebagian harness kembali ke /permohonan (GET tidak ada): 405
body,_=request('/layanan/antrean'); assert 'Uji Bentrok Kedua' not in body, 'Pendaftaran bentrok tanpa persetujuan harus ditolak'
body,url=request('/permohonan',form('Uji Bentrok Kedua','2',overlap,ignore_conflict='1')); second=int(url.rsplit('/',1)[-1])
body,_=request(f'/permohonan/{second}'); assert 'bentrok disadari' in body.lower(), 'Riwayat harus mencatat bentrok yang disadari'
body,url=request('/permohonan',form('Uji Lokasi Lain Sama Tanggal','1',overlap)); assert '/permohonan/' in url
body,url=request('/permohonan',form('Uji Tanggal Berbeda','2',apart)); assert '/permohonan/' in url
body,_=request('/kalender?month='+(today+dt.timedelta(days=91)).strftime('%Y-%m'))
assert 'Bentrok jadwal' in body and 'cal-conflict' in body, 'Kalender harus menandai bentrok jadwal'
# Lokasi di luar Dispar: nama dan alamat diisi manual, tanpa retribusi, tanpa pemeriksaan bentrok.
role('sekretariat')
od=(today+dt.timedelta(days=110)).isoformat()
other_form=lambda name,**extra: dict({'name':name,'pic':'PIC','phone':'0800','activity':'Festival budaya','location_id':'5','location_other_name':'Aula Desa Contoh','location_other_address':'Jl. Contoh No. 1, Pangkalpinang','date':od,'days':'1','files':'1'},**extra)
body,_=request('/permohonan/baru'); assert 'Lainnya (di luar lokasi Dispar)' in body and 'otherLocationBox' in body
body,url=request('/permohonan',other_form('Uji Lokasi Lain Pertama')); other=int(url.rsplit('/',1)[-1])
body,_=request(f'/permohonan/{other}'); assert 'Aula Desa Contoh' in body and 'Jl. Contoh No. 1' in body
body,url=request('/permohonan',other_form('Uji Lokasi Lain Kedua')); assert '/permohonan/' in url, 'Lokasi di luar Dispar tidak boleh diperiksa bentrok'
for label,form_data in [('Uji Lokasi Lain Tanpa Nama',dict(other_form('Uji Lokasi Lain Tanpa Nama'),location_other_name='')),('Uji Lokasi Lain Keringanan',dict(other_form('Uji Lokasi Lain Keringanan'),discount='25',discount_reason='Dasar contoh uji.'))]:
    try:
        request('/permohonan',form_data)
    except AssertionError as error:
        assert '405' in str(error), error
    body,_=request('/layanan/antrean'); assert label not in body, label+' harus ditolak'
path=f'/permohonan/{other}/aksi'
role('kabid'); request(path,{'action':'approve'})
for name,act,data in [('petugas','send',{'draft':draft}),('kabid','approve',{}),('sekretaris','approve',{}),('kabid','release',{})]:
    role(name); request(path,dict(action=act,**data))
role('bendahara'); request(path,{'action':'invoice'},expected=403)
role('kadis'); request(path,{'action':'sign','confirmed':'1'})
role('sekretariat')
body,_=request(path,{'action':'handover','recipient':'Penerima Lokasi Lain','confirmed':'1'})
assert 'Permohonan selesai.' in body and 'Aula Desa Contoh' in body, 'Lokasi di luar Dispar harus selesai tanpa SKRD, STS, dan STBP'
body,_=request(f'/permohonan/{other}/lembar-periksa')
assert 'Aula Desa Contoh' in body and 'lokasi di luar dispar' in body.lower(), 'Lembar periksa harus menandai retribusi tidak berlaku'
request('/demo/reset',{})
print(f'PASS: {checks} request checks; seven roles, lifecycle, revision, guards, archive, document preview, frozen invoice, relief decided at first review (25% approved, free, rejected).')


