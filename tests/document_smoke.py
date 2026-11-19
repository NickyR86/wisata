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
request('/login');role('bendahara')
for key in ['skrd','sts','stbp']:
    path=f'/permohonan/238/template/{key}'
    request(path)
    request(path,{'description':'Uraian uji template tersimpan <script>alert(1)</script>','address':'Alamat uji','bank':'Bank contoh','account':'REKENING-DEMO','due':'Sesuai penetapan'})
    body,_=request('/dokumen/238/'+key)
    assert 'Uraian uji template tersimpan &lt;script&gt;' in body
    assert 'Rp 2.250.000' in body
# Uploaded files are real private local files, and do not advance status.
pdf=base64.b64encode(b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF').decode()
file={'name':'dokumen-demo.pdf','base64':pdf}
for key in ['skrd','sts','stbp']:
    body,_=request('/permohonan/238/unggah/'+key,{},files={'file':file})
    assert 'Berkas disimpan.' in body
    body,_=request('/dokumen/238/'+key)
    assert 'Dokumen manual' in body
    body,_=request('/permohonan/238/berkas/'+key)
    assert body.startswith('%PDF')
    request('/permohonan/238/template/'+key,{'description':'Kembali ke template sistem'})
    body,_=request('/dokumen/238/'+key);assert 'financial-paper' in body
request('/permohonan/237/unggah/proof',{},files={'file':file})
body,_=request('/permohonan/237/berkas/proof');assert body.startswith('%PDF')
body,_=request('/permohonan/237/ingatkan',{});assert 'Tidak ada pesan eksternal' in body
request('/permohonan/238/unggah/proof',{},expected=403,files={'file':file})
body,_=request('/permohonan/238/unggah/skrd',{},files={'file':{'name':'bad.html','base64':base64.b64encode(b'<html>bad</html>').decode()}})
assert 'Berkas disimpan.' not in body
role('sekretariat')
request('/permohonan/238/template/skrd',expected=403)
request('/permohonan/237/berkas/proof',expected=403)
body,_=request('/permohonan/238');assert 'RETRIBUSI</span>' in body
assert 'DPR-' in body and 'Berkas Persyaratan (4)' in body and 'permit-progress' in body
form={'name':'Yayasan Uji Berkas','pic':'PIC contoh','phone':'0800','activity':'Festival budaya','location_id':'1','date':(dt.date.today()+dt.timedelta(days=30)).isoformat(),'days':'1'}
body,url=request('/permohonan',form,files={f'requirements.{i}':file for i in range(4)})
id=int(url.rsplit('/',1)[-1]);assert 'Lihat berkas' in body
body,_=request(f'/permohonan/{id}/berkas/requirement-0');assert body.startswith('%PDF')
role('bendahara');request(f'/permohonan/{id}/berkas/requirement-0',expected=403)
role('sekretariat')
body,_=request(f'/permohonan/{id}/hapus',{'reason':'Duplikasi untuk pengujian','tracking':f'DPR-{dt.date.today().year}-{id:04d}'})
assert 'Duplikasi untuk pengujian' in body
body,_=request('/layanan/antrean');assert 'Yayasan Uji Berkas' not in body
request(f'/permohonan/{id}/pulihkan',{})
body,_=request('/layanan/antrean');assert 'Yayasan Uji Berkas' in body
role('admin')
request('/admin/pengguna/sekretariat',{'name':'Profil uji sekretariat','nip':'DEMO-123456','role':'sekretariat','active':'0'})
body,_=request('/admin/pengguna');assert 'Profil uji sekretariat' in body and 'DEMO-123456' in body
body,_=request('/arsip/cadangan');assert 'Cadangan metadata demo' in body
request('/demo/reset',{})
print(f'PASS: {checks} targeted checks for templates, escaped output, private uploads, role guards, intake and document previews.')
