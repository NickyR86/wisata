"""419 origin and login continuation through the Laravel kernel; session-only data."""
import os
from pathlib import Path
os.environ['SIAP_DATABASE_BUSINESS']='false'
os.environ['SIAP_DATABASE_ACCOUNTS']='false'
harness=Path(__file__).with_name('finance_format_smoke.py').read_text(encoding='utf-8').split('import base64')[0]
exec(compile(harness,__file__,'exec'))
assert PHP
request('/login');role('sekretariat');request('/permohonan/baru')
token='stale'
body,_=request('/permohonan',{},expected=419)
assert 'history.back()' not in body and 'return_to=%2Fpermohonan%2Fbaru' in body
assert 'access-recovery.css' in body and 'recovery-card' in body
if os.environ.get('RECOVERY_PREVIEW'):Path(os.environ['RECOVERY_PREVIEW']).write_text(body,encoding='utf-8')
body,url=request('/akses/pulihkan?return_to=%2Fpermohonan%2Fbaru')
assert url.endswith('/permohonan/baru')
request('/demo/keluar',{})
body,url=request('/akses/pulihkan?return_to=%2Fpermohonan%2Fbaru')
assert '/login?' in url and 'name="return_to" value="/permohonan/baru"' in body
body,url=request('/demo/masuk',{'role':'sekretariat','return_to':'/permohonan/baru'})
assert url.endswith('/permohonan/baru')
body,url=request('/akses/pulihkan?return_to=https%3A%2F%2Fevil.test')
assert '/login' in url and 'name="return_to"' not in body
token='stale'
body,_=request('/demo/masuk',{'role':'sekretariat'},expected=419)
assert 'Kembali ke halaman masuk' in body and 'access-recovery.js' not in body
request('/login');request('/demo/keluar',{})
print(f'PASS: {checks} requests; original form, login continuation, external redirect blocked, expired login distinguished')
