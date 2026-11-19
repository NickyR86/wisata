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



request('/login');role('sekretariat')
body,_=request('/layanan/antrean');assert '<th>Retribusi</th>' in body and 'Belum ditetapkan' in body and 'Rp 1.800.000' in body
body,_=request('/permohonan/236');assert 'Belum ditetapkan' in body and 'Rp ' not in body
body,_=request('/permohonan/237');assert 'Rp 1.800.000' in body and 'Menunggu pembayaran' in body
body,_=request('/permohonan/238');assert 'Rp 2.250.000' in body and 'Lunas' in body
request('/demo/reset',{})
print(f'PASS: {checks} retribution visibility checks.')
