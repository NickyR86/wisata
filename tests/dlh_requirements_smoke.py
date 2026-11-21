"""Conditional DLH intake; independent Laravel session and genuine uploads."""
import os
from pathlib import Path
os.environ['SIAP_DATABASE_BUSINESS']='false'
os.environ['SIAP_DATABASE_ACCOUNTS']='false'
harness=Path(__file__).with_name('finance_format_smoke.py').read_text(encoding='utf-8').split('import base64')[0]
harness=harness.replace("else path.removesuffix('/aksi')","else '/permohonan/baru' if path=='/permohonan' else path.removesuffix('/aksi')")
exec(compile(harness,__file__,'exec'))
assert PHP
request('/login');role('sekretariat');body,_=request('/permohonan/baru')
if os.environ.get('DLH_PREVIEW'):Path(os.environ['DLH_PREVIEW']).write_text(body,encoding='utf-8')
png='iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jhZkAAAAASUVORK5CYII='
files={f'requirements.{i}':{'name':f'berkas-{i}.png','base64':png} for i in range(3)}
data={'name':'Uji lokasi DLH','pic':'Penguji','phone':'0800000000','activity':'Bazar UMKM','date':(dt.date.today()+dt.timedelta(days=20)).isoformat(),'days':'1','location_id':'3'}
body,url=request('/permohonan',data,files=files)
assert '/permohonan/' in url and 'berkas-2.png' in body and 'Surat Rekomendasi dari Dinas Lingkungan Hidup' not in body, re.findall(r'<li>(.*?)</li>',body,re.S)
for location in ['1','2']:
 request('/permohonan/baru');data['location_id']=location
 body,url=request('/permohonan',data,files=files)
 assert 'requirements.3' in body or 'requirements 3' in body
 files['requirements.3']={'name':'rekomendasi-dlh.png','base64':png}
 body,url=request('/permohonan',data,files=files)
 assert '/permohonan/' in url and 'rekomendasi-dlh.png' in body
 del files['requirements.3']
request('/permohonan/baru');data['location_id']='3';data['files']='1'
body,url=request('/permohonan',data)
assert '/permohonan/' in url and 'Surat Rekomendasi dari Dinas Lingkungan Hidup' not in body
request('/demo/reset',{})
print(f'PASS: {checks} requests; three ordinary requirements (names follow the physical checklist), DLH mandatory at both locations, sample files filtered')
