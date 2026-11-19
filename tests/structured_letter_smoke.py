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
role('petugas')
body,_=request('/layanan/draft');assert 'letter[subject]' in body and 'Data dari permohonan' in body
fields={'action':'save','letter[number]':'DEMO/UJI-STRUKTUR','letter[date]':'2026-10-23','letter[subject]':'Perihal pengujian','letter[attachment]':'4 berkas','letter[opening]':'Pembukaan hasil penyusunan','letter[body]':'Isi hasil penyusunan yang lengkap','letter[conditions]':'Ketentuan uji <script>alert(1)</script>','letter[closing]':'Penutup hasil penyusunan'}
saved,url=request('/permohonan/332/aksi',{'action':fields['action'],'letter':{k[7:-1]:v for k,v in fields.items() if k.startswith('letter[')}})
assert fields['action']!='save' or ('/layanan/draft?selected=332' in url and 'DEMO/UJI-STRUKTUR' in saved)
body,_=request('/layanan/draft?selected=332');assert 'DEMO/UJI-STRUKTUR' in body and '&lt;script&gt;' in body
fields['action']='send';request('/permohonan/332/aksi',{'action':fields['action'],'letter':{k[7:-1]:v for k,v in fields.items() if k.startswith('letter[')}})
role('kabid')
body,_=request('/permohonan/332');assert 'DEMO/UJI-STRUKTUR' in body and 'letter[subject]' not in body
request('/permohonan/332/aksi',{'action':'save','draft':'Tidak boleh mengedit surat dari peran Kabid.'},expected=403)
request('/permohonan/332/aksi',{'action':'revise','note':'Mohon perbaiki bagian penutup.'})
role('petugas');body,_=request('/layanan/draft?selected=332');assert 'Penutup hasil penyusunan' in body and 'Versi 2' in body
request('/demo/reset',{})
print(f'PASS: {checks} structured letter checks')
