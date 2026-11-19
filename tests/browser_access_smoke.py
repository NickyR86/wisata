"""Real Laravel kernel, independent cookies and session data; no development DB writes."""
import os
from pathlib import Path
os.environ['SIAP_DATABASE_BUSINESS']='false'
os.environ['SIAP_DATABASE_ACCOUNTS']='false'
os.environ['SESSION_LIFETIME']='120'
harness=Path(__file__).with_name('finance_format_smoke.py').read_text(encoding='utf-8').split('import base64')[0]
exec(compile(harness,__file__,'exec'))
assert PHP, 'Set DEMO_TEST_PHP to the PHP executable'
request('/login');role('sekretariat')
body,_=request('/permohonan/baru')
stale=token
before=json.loads(request('/akses/status')[0])
assert before['authenticated'] and before['role']=='sekretariat'
assert 'siptr_browser_access' in kernel_cookies
# Expire only this test session; the browser credential must recover it on GET.
os.environ['SESSION_LIFETIME']='-1'
after=json.loads(request('/akses/status')[0])
os.environ['SESSION_LIFETIME']='120'
assert after['authenticated'] and after['identity']==before['identity']
assert after['token']!=stale
data={'name':'Uji pemulihan sesi','pic':'Penguji','phone':'0800000000',
      'activity':'Bazar UMKM','location_id':'1','date':(dt.date.today()+dt.timedelta(days=20)).isoformat(),'days':'1','files':'1'}
token=stale
body,_=request('/permohonan',data,expected=419)
assert 'Pengiriman belum diproses' in body
token=json.loads(request('/akses/status')[0])['token']
body,url=request('/permohonan',data)
assert 'Uji pemulihan sesi' in body and '/permohonan/' in url
# A subsequent expiry must retain session-mode changes, not reset demo records.
os.environ['SESSION_LIFETIME']='-1'
restored=json.loads(request('/akses/status')[0])
os.environ['SESSION_LIFETIME']='120'
body,_=request(urllib.parse.urlparse(url).path)
assert 'Uji pemulihan sesi' in body
# The server-side credential is revoked by logout, even if a stale cookie is replayed.
ticket=kernel_cookies['siptr_browser_access']
token=restored['token'];request('/demo/keluar',{})
kernel_cookies['siptr_browser_access']=ticket
access=json.loads(request('/akses/status')[0])
assert not access['authenticated']
print(f'PASS: {checks} requests; expiry recovery, stale CSRF rejected, submit, data retained, logout revocation')
