#!/usr/bin/env python3
"""Uji asap Tahap 1 (fondasi keamanan). Berjalan terhadap server yang SUDAH hidup:
    php artisan serve --port=8017
    python3 tests/security_baseline_smoke.py http://127.0.0.1:8017

Variabel lingkungan untuk menyatakan konfigurasi server yang sedang diuji:
    EXPECT_ENV=local|production   (bawaan: local)  nilai APP_ENV server
    EXPECT_CSP=report-only|enforce|off (bawaan: report-only)  nilai SIAP_CSP server
    EXPECT_ACCOUNTS=true|false    (bawaan: false)  nilai SIAP_DATABASE_ACCOUNTS server
Hanya memakai pustaka standar Python.
"""
import os, re, sys, urllib.request, urllib.parse, urllib.error, http.cookiejar

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8017').rstrip('/')
ENV = os.environ.get('EXPECT_ENV', 'local')
CSP = os.environ.get('EXPECT_CSP', 'report-only')
ACCOUNTS = os.environ.get('EXPECT_ACCOUNTS', 'false') == 'true'
DEMO_ALLOWED = ENV in ('local', 'testing') and not ACCOUNTS

jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(jar))
passed = failed = 0


def check(name, ok, detail=''):
    global passed, failed
    if ok:
        passed += 1; print('  LULUS  ' + name)
    else:
        failed += 1; print('  GAGAL  ' + name + (' :: ' + detail if detail else ''))


def get(path):
    try:
        r = opener.open(BASE + path, timeout=30)
        return r.status, r.geturl(), r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.geturl(), e.headers, e.read().decode('utf-8', 'replace')


def post(path, data):
    body = urllib.parse.urlencode(data).encode()
    try:
        r = opener.open(urllib.request.Request(BASE + path, data=body), timeout=30)
        return r.status, r.geturl(), r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.geturl(), e.headers, e.read().decode('utf-8', 'replace')


def token(html):
    m = re.search(r'name="_token"\s+value="([^"]+)"', html) or re.search(r'value="([^"]+)"\s+name="_token"', html)
    return m.group(1) if m else ''


print(f'Menguji {BASE}  (EXPECT_ENV={ENV}, EXPECT_CSP={CSP}, EXPECT_ACCOUNTS={ACCOUNTS})')

print('\n[1] Header keamanan pada halaman HTML')
st, url, h, html = get('/login')
check('GET /login = 200', st == 200, str(st))
check('X-Content-Type-Options: nosniff', h.get('X-Content-Type-Options') == 'nosniff')
check('X-Frame-Options: SAMEORIGIN', h.get('X-Frame-Options') == 'SAMEORIGIN')
check('Referrer-Policy terpasang', bool(h.get('Referrer-Policy')))
check('Permissions-Policy terpasang', bool(h.get('Permissions-Policy')))
if CSP == 'off':
    check('CSP tidak dikirim (SIAP_CSP=off)', not h.get('Content-Security-Policy') and not h.get('Content-Security-Policy-Report-Only'))
elif CSP == 'enforce':
    check('Content-Security-Policy (enforce) terpasang', bool(h.get('Content-Security-Policy')))
else:
    check('Content-Security-Policy-Report-Only terpasang', bool(h.get('Content-Security-Policy-Report-Only')))
    check('CSP enforce BELUM aktif pada mode report-only', not h.get('Content-Security-Policy'))
if BASE.startswith('https://') and ENV not in ('local', 'testing'):
    check('HSTS terkirim lewat HTTPS non-lokal', bool(h.get('Strict-Transport-Security')))
else:
    check('HSTS tidak dikirim pada HTTP/lokal', not h.get('Strict-Transport-Security'))

print('\n[2] Halaman login tidak membocorkan akun demo di luar lokal')
if DEMO_ALLOWED:
    check('mode demo lokal: halaman login boleh berisi isian contoh', True)
else:
    check('tidak ada isian otomatis demo1234', 'demo1234' not in html)
    check('tidak ada isian otomatis NIP demo', '198703122010011002' not in html)
if ENV not in ('local', 'testing') and not ACCOUNTS:
    check('peringatan "Mode demo tidak tersedia" tampil', 'Mode demo tidak tersedia' in html)

print('\n[3] Login demo tanpa kata sandi')
tok = token(html)
check('token CSRF ditemukan di form login', bool(tok))
st, url, h, body = post('/demo/masuk', {'_token': tok, 'role': 'admin', 'nip': '198703122010011002', 'password': 'apa-saja', 'login_form': '1'} if not ACCOUNTS else {'_token': tok, 'nip': 'tidak-ada', 'password': 'salah-salah-salah', 'login_form': '1'})
st2, url2, h2, body2 = get('/ringkasan')
if DEMO_ALLOWED:
    check('lokal: login demo berhasil, /ringkasan = 200', st2 == 200 and not url2.rstrip('/').endswith('/login'), f'{st2} {url2}')
else:
    check('login demo tanpa sandi DITOLAK: /ringkasan kembali ke /login', url2.rstrip('/').endswith('/login'), f'{st2} {url2}')
    check('pesan penolakan tampil atau halaman login dimuat ulang', st in (200, 302, 422) )

if DEMO_ALLOWED and st2 == 200:
    print('\n[4] Perubahan UI untuk CSP (hanya mode demo lokal)')
    st, url, h, body = get('/layanan/antrean')
    check('antrean: tidak ada atribut onchange inline', 'onchange=' not in body)
    check('antrean: pengurutan memakai data-autosubmit', 'data-autosubmit' in body)
    st, url, h, body = get('/kalender')
    check('kalender: tidak ada atribut onchange inline', 'onchange=' not in body)
    check('kalender: filter lokasi memakai data-autosubmit', 'data-autosubmit' in body)
    st, url, h, csv = get('/ekspor')
    check('ekspor CSV = 200 bertipe text/csv', st == 200 and 'text/csv' in (h.get('Content-Type') or ''))
    check('respons CSV tidak membawa CSP (hanya HTML yang membawa)', not h.get('Content-Security-Policy-Report-Only') and not h.get('Content-Security-Policy'))
    check('respons CSV tetap membawa nosniff', h.get('X-Content-Type-Options') == 'nosniff')
    check('sel CSV berawalan tab/CR/=/+/@/- dinetralkan (tidak ada baris yang diawali = + @)', not re.search(r'(^|\n)[=+@]', csv.replace('\ufeff', '')))

print(f'\nRingkasan: {passed} lulus, {failed} gagal')
sys.exit(1 if failed else 0)
