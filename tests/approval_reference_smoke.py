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
body,_=request('/layanan/draft?selected=332')
assert 'data-letter-list="legal_basis"' in body
source={'event_name':'Reuni Uji Alumni','pic_position':'Ketua Panitia','request_number':'12/REUNI/2026','request_date':'2026-11-01','start_time':'09:00','end_time':'17:00'}
request('/permohonan/332/sumber-surat',{'address':'Alamat uji','letter_source':source,'note':'Melengkapi surat pemohon'},expected=403)
role('sekretariat')
request('/permohonan/332/sumber-surat',{'address':'Alamat uji','letter_source':source,'note':'Melengkapi surat pemohon'})
role('petugas')
letter={'number':'DEMO/STRUKTUR/2026','date':'2026-11-15','subject':'Uji','attachment':'4 berkas','opening':'','body':'','conditions':'','closing':'','structure':{'about':'KEGIATAN {kegiatan}','purpose':'{kegiatan} di {lokasi}','request_reference':'Surat {nomor_permohonan} tanggal {tanggal_permohonan}','legal_basis':['Acuan pertama','Acuan kedua'],'terms':['Mulai {tanggal}, {jam_mulai} WIB','Uji <script>alert(1)</script>'],'copies':['Instansi pertama','Instansi kedua'],'source':{'pic':'FORGED'}}}
body,_=request('/permohonan/332/aksi',{'action':'save','letter':letter,'sync_letter_source':'1'})
assert 'Reuni Uji Alumni' in body and 'FORGED' not in body and '&lt;script&gt;' in body
body,_=request('/layanan/draft?selected=332')
assert '12/REUNI/2026' in body and 'Acuan kedua' in body and 'Instansi kedua' in body
Path('../work/approval-editor.html').write_text(body,encoding='utf-8')
# Source correction must not silently rewrite the previously saved draft.
role('sekretariat');source['event_name']='Reuni Uji Diperbarui'
request('/permohonan/332/sumber-surat',{'address':'Alamat uji','letter_source':source,'note':'Mengoreksi nama acara'})
role('petugas');body,_=request('/layanan/draft?selected=332');assert 'KEGIATAN Reuni Uji Alumni' in body
body,_=request('/permohonan/332/aksi',{'action':'save','letter':letter,'sync_letter_source':'1'})
assert 'KEGIATAN Reuni Uji Diperbarui' in body
request('/permohonan/332/aksi',{'action':'send','letter':letter})
role('sekretariat');request('/permohonan/332/sumber-surat',{'address':'Alamat uji','letter_source':source,'note':'Dilarang saat review'},expected=403)
role('kabid');body,_=request('/permohonan/332');assert 'Acuan kedua' in body
request('/permohonan/332/aksi',{'action':'save','letter':letter},expected=403)
request('/permohonan/332/aksi',{'action':'revise','note':'Periksa ketentuan kegiatan'})
role('petugas');body,_=request('/layanan/draft?selected=332');assert 'Instansi kedua' in body
request('/demo/reset',{})
print(f'PASS: {checks} approval reference workflow checks')
