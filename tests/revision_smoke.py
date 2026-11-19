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

request('/login')
role('sekretariat')
body,_=request('/layanan/serah-terima');assert body.index('Cetak Berkas Final')<body.index('Konfirmasi Serah Terima')
body,_=request('/dokumen/339/surat?from=serah-terima');assert 'Kembali ke Serah Terima' in body and 'Aditya Pratama' in body and 'print-area' in body
body,_=request('/dokumen/339/skrd');assert 'print-area' in body and 'SIMULASI TTE' in body
body,_=request('/kalender');assert 'Tentatif' in body and 'Disetujui' in body
body,_=request('/layanan/arsip');assert 'Penyerahan / penerima' in body and 'from=arsip' in body
body,_=request('/layanan/riwayat');assert 'Pelaku / peran' in body
body,_=request('/layanan/riwayat?sort=newest'); times=re.findall(r'data-event-at="([^"]+)"',body);assert times==sorted(times,reverse=True) and len(times)==20
body,_=request('/layanan/riwayat?sort=oldest'); times=re.findall(r'data-event-at="([^"]+)"',body);assert times==sorted(times)
body,_=request('/layanan/riwayat?actor=kadis');assert 'Kepala Dinas' in body
body,_=request('/layanan/arsip?selected=240');assert 'Dokumen Terbit' in body and 'Berkas Persyaratan (4)' in body
role('kabid');body,_=request('/layanan/rilis');assert 'approval-letter' in body
role('petugas');body,_=request('/layanan/draft');assert 'data-letter-input' in body and 'letter-identities' in body
role('sekretaris');body,_=request('/layanan/verifikasi');assert 'approval-letter' in body
role('bendahara')
request('/permohonan/336/template/skrd',{'description':'Penetapan kegiatan demo','period':'2026-11','issued_date':'2026-10-18','npwr':'DEMO-9'})
request('/permohonan/336/aksi',{'action':'invoice','rate':'100000','rate_reason':'Tarif skenario uji'})
body,_=request('/dokumen/336/skrd');assert '75.000' in body and '2026-11' in body
role('kadis')
body,_=request('/layanan/tanda-tangan');assert 'PENYELENGGARAAN KEGIATAN DI TEMPAT REKREASI' in body
request('/permohonan/338/aksi',{'action':'sign','confirmed':'1'})
body,_=request('/dokumen/338/surat');assert 'Aditya Pratama' in body and 'SIM-DPR-' in body
body,_=request('/laporan');assert 'Kinerja Layanan' in body
request('/demo/reset',{})
print(f'PASS: {checks} revision checks')
