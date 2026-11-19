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
for kind in ['skrd','sts','stbp']:
    body,_=request('/permohonan/338/template/'+kind)
    assert 'rows[0][code]' in body and 'signer_name' in body
    import re
    amount=int(re.search(r'data-row-field="amount"[^>]+value="(\d+)"',body).group(1))
    data={'description':'Kegiatan uji <script>tidak dijalankan</script>','number':'DEMO/UJI/'+kind,'payer':'Yayasan Contoh Uji','issued_date':'2026-10-23','period':'2026-10','address':'Alamat uji','npwr':'NPWR-DEMO','due':'2026-10-30','bank':'Bank Uji','account':'0000123','method':'Tunai','signer_name':'Nama Fiktif Uji','signer_nip':'00000','signer_title':'Bendahara Demo','authority_name':'Pengguna Anggaran Demo','authority_nip':'00001','attention':'Perhatian uji','rows':[{'code':'4.1.02','label':'Uraian rekening uji A','amount':amount//2},{'code':'4.1.03','label':'Uraian rekening uji B','amount':amount-amount//2}]}
    if kind=='skrd':
        data['source_token']=re.search(r'name="source_token" value="([^"]+)"',body).group(1)
        data['revision_reason']='Perbaikan dokumen untuk pengujian'
    body,url=request('/permohonan/338/template/'+kind,data)
    assert '/template/'+kind in url and 'DEMO/UJI/'+kind in body and 'Uraian rekening uji B' in body
    assert '&lt;script&gt;' in body
    if kind=='stbp': assert 'g. Sebagai Pembayaran' in body and 'f. Terbilang' in body
    if kind=='sts': assert '5. Penerimaan Tanggal' in body and 'PENGGUNA ANGGARAN' in body
    if kind=='skrd': assert 'POTONG DI SINI' in body and 'Jumlah Sanksi' in body
    preview,_=request('/dokumen/338/'+kind+'?from=retribusi'); Path('../work/finance-'+kind+'.html').write_text(preview,encoding='utf-8'); assert 'DEMO/UJI/'+kind in preview and 'Uraian rekening uji B' in preview
    data['rows'][0]['amount']+=1
    body,_=request('/permohonan/338/template/'+kind,data)
    assert ('Nominal SKRD mengikuti Penetapan Retribusi' if kind=='skrd' else 'Jumlah rincian harus sama') in body
    body,_=request('/permohonan/338/template/'+kind)
    assert 'Uraian rekening uji A' in body
role('kabid');request('/permohonan/338/template/skrd',expected=403)
request('/demo/reset',{})
print(f'PASS: {checks} financial document integration requests')
