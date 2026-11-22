"""Real HTTP-kernel CRUD test; requires a disposable migrated/seeded MySQL test DB.
DB_DATABASE must start with siap_wisata_test_. Does not touch the normal demo DB.
"""
import os,json,subprocess,re,html,datetime as dt,urllib.parse
from pathlib import Path
assert os.environ.get('DB_DATABASE','').startswith('siap_wisata_test_')
PHP=os.environ['DEMO_TEST_PHP'];ROOT=Path(__file__).resolve().parents[1]
BASE='http://localhost';cookies={};token='';checks=0

def request(path,data=None,expected=200):
 global token,checks
 url=BASE+path;method='POST' if data is not None else 'GET';payload=dict(data or {})
 if data is not None:payload['_token']=token
 for _ in range(8):
  result=subprocess.run([PHP,str(ROOT/'tests/kernel_request.php')],input=json.dumps({'url':url,'method':method,'data':payload,'cookies':cookies,'referer':BASE+path.removesuffix('/aksi')}),text=True,capture_output=True,check=True)
  try:r=json.loads(result.stdout)
  except Exception:raise AssertionError(result.stdout[:600]+result.stderr[:600])
  cookies.update(r['cookies'])
  if r['status'] in [301,302,303]:url=urllib.parse.urljoin(url,r['location']);method='GET';payload={};continue
  break
 assert r['status']==expected,(path,r['status'],r['body'][:300])
 m=re.search(r'name="_token" value="([^"]+)"',r['body'])
 if m:token=html.unescape(m[1])
 checks+=1
 return r['body'],url

def role(name,new_session=False):
 global cookies,token
 if new_session:cookies={};token=''
 request('/login');return request('/demo/masuk',{'role':name})

role('sekretariat',True)
body,url=request('/permohonan',{'name':'Yayasan Uji Database Lintas Sesi','pic':'Rani Uji','phone':'080000000','email':'uji@example.test','address':'Alamat pengujian','activity':'Festival budaya','location_id':1,'date':(dt.date.today()+dt.timedelta(days=79)).isoformat(),'days':2,'files':1})
id=int(url.rsplit('/',1)[-1]);action=f'/permohonan/{id}/aksi'
request(action,{'action':'approve'},403)
role('kabid',True);body,_=request(f'/permohonan/{id}');assert 'Yayasan Uji Database Lintas Sesi' in body
request(action,{'action':'approve'})
role('petugas',True)
request(action,{'action':'save','draft':'Draft pertama disimpan secara permanen untuk pengujian lintas sesi pengguna.'})
role('petugas',True);body,_=request(f'/layanan/draft?selected={id}');assert 'Draft pertama disimpan' in body
request(action,{'action':'send','draft':'Draft kedua disimpan secara permanen dan dikirim untuk persetujuan Kabid.'})
role('kabid',True);request(action,{'action':'revise','note':'Ubah narasi untuk pengujian revisi.'})
role('petugas',True);request(action,{'action':'send','draft':'Draft final hasil revisi, disimpan permanen dan siap diperiksa kembali.'})
role('kabid',True);request(action,{'action':'approve'})
role('sekretaris',True);request(action,{'action':'approve'})
role('kabid',True);request(action,{'action':'release'})
role('bendahara',True)
body,_=request(f'/permohonan/{id}/template/skrd')
m=re.search(r'name="source_token" value="([^"]+)"',body);assert m
request(f'/permohonan/{id}/template/skrd',{'description':'Uraian SKRD tersimpan lintas sesi','address':'Alamat uji','period':dt.date.today().strftime('%Y-%m'),'source_token':html.unescape(m[1])})
role('bendahara',True);body,_=request(f'/permohonan/{id}/template/skrd');assert 'Uraian SKRD tersimpan lintas sesi' in body
body,_=request(action,{'action':'invoice'});assert 'SKRD demo diterbitkan' in body,body[-3000:]
body,_=request(f'/permohonan/{id}');assert '1.500.000' in body
request(action,{'action':'pay','payment_checked':1,'payment_date':dt.date.today().isoformat()})
role('kadis',True);request(action,{'action':'sign','confirmed':1})
role('sekretariat',True);request(action,{'action':'handover','confirmed':1,'recipient':'Penerima Database'})
role('sekretariat',True)
body,_=request('/layanan/arsip');assert 'Yayasan Uji Database Lintas Sesi' in body
for doc in ['surat','skrd','stbp','sts']:request(f'/dokumen/{id}/{doc}')
body,_=request('/layanan/riwayat');assert 'Permohonan' in body
request('/kalender');request('/ekspor?kind=arsip')
request('/demo/reset',{},403)
body,_=request(f'/permohonan/{id}');tracking=re.search(r'DPR-\d{4}-\d+',body)[0]
request(f'/permohonan/{id}/hapus',{'reason':'Uji soft delete database','tracking':tracking})
request(f'/permohonan/{id}/pulihkan',{})
role('kadis',True);request('/laporan')
print(f'PASS: {checks} HTTP-kernel requests; CRUD full workflow persists across fresh sessions; role denial and reset protection.',flush=True)
