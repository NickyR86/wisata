#!/usr/bin/env python3
"""Uji asap Tahap 3 (Data Master) pada MODE DATABASE.
JALANKAN HANYA PADA DATABASE UJI: tes ini membuat satu jenis kegiatan (tidak bisa dihapus, hanya dinonaktifkan)
dan satu lokasi (dihapus lagi di akhir).

Persiapan: server hidup dengan SIAP_DATABASE_ACCOUNTS=true dan SIAP_DATABASE_BUSINESS=true, akun admin dan sekretariat
sudah ada (php artisan siap:buat-admin, lalu buat akun sekretariat dari menu Kelola User).

    export SIAP_ADMIN_USER=... SIAP_ADMIN_PASS=... SIAP_SEKRETARIAT_USER=... SIAP_SEKRETARIAT_PASS=...
    python3 tests/master_data_smoke.py http://127.0.0.1:8017
"""
import os, re, sys, time, urllib.request, urllib.parse, urllib.error, http.cookiejar, html as htmllib

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8017').rstrip('/')
need = ['SIAP_ADMIN_USER', 'SIAP_ADMIN_PASS', 'SIAP_SEKRETARIAT_USER', 'SIAP_SEKRETARIAT_PASS']
if any(not os.environ.get(k) for k in need):
    print('Isi variabel lingkungan dulu: ' + ', '.join(need)); sys.exit(2)
passed = failed = 0


def check(name, ok, detail=''):
    global passed, failed
    if ok: passed += 1; print('  LULUS  ' + name)
    else: failed += 1; print('  GAGAL  ' + name + (' :: ' + detail if detail else ''))


class Session:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def _do(self, req):
        try:
            r = self.opener.open(req, timeout=30); return r.status, r.geturl(), r.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            return e.code, e.geturl(), e.read().decode('utf-8', 'replace')
    def get(self, path): return self._do(BASE + path)
    def post(self, path, data, ref_page='/admin/lokasi'):
        st, url, body = self.get(ref_page)
        tok = re.search(r'name="_token"\s+value="([^"]+)"', body)
        payload = urllib.parse.urlencode(dict(data, _token=tok.group(1) if tok else '')).encode()
        return self._do(urllib.request.Request(BASE + path, data=payload, headers={'Referer': BASE + ref_page}))
    def login(self, user, pw):
        st, url, body = self.get('/login')
        tok = re.search(r'name="_token"\s+value="([^"]+)"', body)
        payload = urllib.parse.urlencode({'_token': tok.group(1) if tok else '', 'nip': user, 'password': pw, 'login_form': '1'}).encode()
        self._do(urllib.request.Request(BASE + '/demo/masuk', data=payload))
        st, url, body = self.get('/ringkasan')
        return st == 200 and not url.rstrip('/').endswith('/login')


def text(body): return htmllib.unescape(body)

stamp = str(int(time.time()))
loc_name = 'Lokasi Uji ' + stamp
act_name = 'Kegiatan Uji ' + stamp
admin, sek = Session(), Session()
print(f'Menguji {BASE}')
check('login admin', admin.login(os.environ['SIAP_ADMIN_USER'], os.environ['SIAP_ADMIN_PASS']))
check('login sekretariat', sek.login(os.environ['SIAP_SEKRETARIAT_USER'], os.environ['SIAP_SEKRETARIAT_PASS']))

print('\n[1] Halaman Data Master (mode database)')
st, url, body = admin.get('/admin/lokasi')
check('GET /admin/lokasi = 200', st == 200, str(st))
check('ada form "Tambah lokasi" dan "Tambah jenis kegiatan"', 'Tambah lokasi' in body and 'Tambah jenis kegiatan' in body)
check('sekretariat tidak boleh membuka Data Master (403)', sek.get('/admin/lokasi')[0] == 403)

print('\n[2] Tambah jenis kegiatan lalu lokasi')
st, url, body = admin.post('/admin/kegiatan', {'name': act_name})
check('tambah jenis kegiatan berhasil', act_name in text(admin.get('/admin/lokasi')[2]))
st, url, body = admin.post('/admin/kegiatan', {'name': act_name.upper()})
check('nama kegiatan ganda (beda huruf besar) ditolak dengan pesan', 'Jenis kegiatan sudah ada' in text(body))
st, url, body = admin.post('/admin/lokasi', {'name': loc_name, 'rate': '123000', 'managed': '1', 'dlh': '1'})
page = text(admin.get('/admin/lokasi')[2])
check('lokasi baru tampil di Data Master', loc_name in page)
st, url, body = admin.post('/admin/lokasi', {'name': loc_name, 'rate': '1', 'managed': '1', 'dlh': '0'})
check('nama lokasi ganda ditolak dengan pesan', 'Nama lokasi sudah ada' in text(body))

print('\n[3] Lokasi dan kegiatan muncul otomatis di formulir pendaftaran (sekretariat)')
st, url, body = sek.get('/permohonan/baru')
check('GET /permohonan/baru = 200', st == 200, str(st))
form = text(body)
check('nama lokasi baru ada di dropdown', loc_name in form)
m = re.search(r'<option value="\d+"[^>]*data-rate="([^"]*)"[^>]*data-dlh="([^"]*)"[^>]*data-managed="([^"]*)"[^>]*>\s*' + re.escape(loc_name), body)
check('atribut lokasi otomatis: tarif 123000, DLH=1, dikelola=1', bool(m) and m.group(1).split('.')[0] == '123000' and m.group(2) == '1' and m.group(3) == '1', m.group(0)[:200] if m else 'opsi tidak ditemukan')
check('jenis kegiatan baru ada di dropdown', act_name in form)

print('\n[4] Nonaktifkan lokasi: hilang dari formulir')
page_html = admin.get('/admin/lokasi')[2]
idx = page_html.find(loc_name)
m = re.search(r'/admin/lokasi/(\d+)/ubah', page_html[idx:]) if idx >= 0 else None
loc_id = m.group(1) if m else None
check('id lokasi ditemukan di halaman', bool(loc_id))
if loc_id:
    admin.post(f'/admin/lokasi/{loc_id}/ubah', {'name': loc_name, 'managed': '1', 'dlh': '1', 'active': '0'})
    check('lokasi nonaktif tidak ada di dropdown', loc_name not in text(sek.get('/permohonan/baru')[2]))
    admin.post(f'/admin/lokasi/{loc_id}/ubah', {'name': loc_name, 'managed': '1', 'dlh': '1', 'active': '1'})
    check('diaktifkan lagi: muncul kembali', loc_name in text(sek.get('/permohonan/baru')[2]))

print('\n[5] Hapus lokasi yang belum dipakai, lalu bersihkan')
if loc_id:
    admin.post(f'/admin/lokasi/{loc_id}/hapus', {})
    check('lokasi terhapus dari Data Master', loc_name not in text(admin.get('/admin/lokasi')[2]))
    check('lokasi terhapus dari formulir', loc_name not in text(sek.get('/permohonan/baru')[2]))
page_html = admin.get('/admin/lokasi')[2]
idx = page_html.find(act_name)
m = re.search(r'/admin/kegiatan/(\d+)/status', page_html[idx:]) if idx >= 0 else None
check('id jenis kegiatan uji ditemukan', bool(m))
if m:
    admin.post(f'/admin/kegiatan/{m.group(1)}/status', {'active': '0'})
    check('kegiatan uji dinonaktifkan (tidak tampil di formulir)', act_name not in text(sek.get('/permohonan/baru')[2]))
print(f'\nRingkasan: {passed} lulus, {failed} gagal')
sys.exit(1 if failed else 0)
