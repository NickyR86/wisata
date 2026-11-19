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

def request(path, data=None, expected=200, files=None):
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
            result = subprocess.run([PHP, str(Path(__file__).with_name('kernel_request.php'))], input=json.dumps({'url':url,'method':method,'data':data or {},'files':files or {},'cookies':kernel_cookies,'referer':BASE+('/admin/pengguna' if path.startswith('/admin/pengguna/') else '/layanan/retribusi' if ('/unggah/' in path or '/ingatkan' in path) else path.removesuffix('/aksi'))}), text=True, capture_output=True, check=True)
            response_data = json.loads(result.stdout)
            kernel_cookies.update(response_data['cookies'])
            if response_data['status'] in (301,302,303):
                url=urllib.parse.urljoin(url,response_data['location']); method='GET'; data=None; files=None
                continue
            break
        body=response_data['body']
        assert response_data['status']==expected, (path,response_data['status'],body[:400])
    else:
        req = urllib.request.Request(BASE+path, payload, headers={'Referer':BASE+('/admin/pengguna' if path.startswith('/admin/pengguna/') else '/layanan/retribusi' if ('/unggah/' in path or '/ingatkan' in path) else path.removesuffix('/aksi')), 'Accept':'text/html'})
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


import base64
assert PHP, 'Set DEMO_TEST_PHP for file integration checks'



from io import BytesIO
from reportlab.pdfgen import canvas
request('/login');role('petugas')
body,_=request('/permohonan/332/cetak-surat/draft');assert 'DRAFT · BELUM' in body
request('/permohonan/332/cetak-surat/manual',expected=403)
body,_=request('/permohonan/338');assert 'Cetak untuk Tanda Tangan Basah' in body
source=html.unescape(re.search(r'name="source_token" value="([^"]+)"',body).group(1))
body,_=request('/permohonan/338/cetak-surat/manual');assert 'DRAFT · BELUM' not in body and 'Versi disetujui untuk tanda tangan basah' in body
Path('../work/manual-print.html').write_text(body,encoding='utf-8')
buf=BytesIO();c=canvas.Canvas(buf);c.drawString(40,800,'DEMO - tanda tangan manual');c.save()
files={'signed_file':{'name':'signed-demo.pdf','base64':base64.b64encode(buf.getvalue()).decode()}}
data={'signed_date':dt.date.today().isoformat(),'reason':'Layanan TTE tidak tersedia','confirmed':'1','source_token':source,'signer_id':'kadis'}
request('/permohonan/338/tanda-tangan-manual',dict(data,source_token='stale'),expected=409,files=files)
role('kadis');request('/permohonan/338/tanda-tangan-manual',data,expected=403,files=files)
role('petugas');body,_=request('/permohonan/338/tanda-tangan-manual',data,files=files);assert 'Siap diserahkan' in body and 'surat-manual' in body
request('/permohonan/338/tanda-tangan-manual',data,expected=409,files=files)
role('kadis');body,_=request('/layanan/tanda-tangan');assert 'selected=338' not in body
role('sekretariat');body,_=request('/layanan/serah-terima?selected=338');assert 'selected=338' in body
body,_=request('/dokumen/338/surat');assert 'Buka / cetak PDF bertanda tangan' in body
request('/permohonan/338/aksi',{'action':'handover','confirmed':'1','recipient':'Penerima demo manual'})
body,_=request('/dokumen/338/surat');assert 'Buka / cetak PDF bertanda tangan' in body
request('/demo/reset',{})
print(f'PASS: {checks} manual signature HTTP checks')
