#!/usr/bin/env node
/*
 * Audit antarmuka otomatis pada aplikasi yang SUDAH berjalan (butuh Node dan Playwright).
 *
 *   php artisan serve --port=8017
 *   node tests/ui_audit.cjs http://127.0.0.1:8017
 *
 * Variabel lingkungan:
 *   ROLES=sekretariat,kabid,...   peran yang diuji lewat login demo (bawaan: tujuh peran). Kosongkan dan isi
 *                                 AUDIT_PATHS untuk menguji halaman tanpa login.
 *   AUDIT_PATHS=/a,/b             daftar alamat yang dikunjungi (bawaan: halaman utama aplikasi)
 *   AUDIT_OUT=ui-audit            folder keluaran (laporan REPORT.md dan tangkapan layar)
 *   PLAYWRIGHT_PATH=...           lokasi modul playwright bila tidak terpasang global
 *
 * Yang diperiksa per halaman dan per lebar layar: kode HTTP, galat konsol, geser horizontal, kontrol tanpa nama,
 * gambar tanpa alt, kontras teks (WCAG AA), teks kecil (<11px), target sentuh kecil, penanda fokus keyboard, jumlah h1.
 * Ini pemeriksaan otomatis; tidak menggantikan uji dengan pengguna sungguhan.
 */
const fs = require('fs'), path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_PATH || 'playwright');
const BASE = (process.argv[2] || 'http://127.0.0.1:8017').replace(/\/$/, '');
const OUT = process.env.AUDIT_OUT || 'ui-audit';
const ROLES = (process.env.ROLES === undefined ? 'sekretariat,kabid,petugas,sekretaris,bendahara,kadis,admin' : process.env.ROLES).split(',').filter(Boolean);
const PATHS = (process.env.AUDIT_PATHS || '/dashboard,/layanan/antrean,/permohonan/baru,/kalender,/layanan/arsip,/layanan/riwayat,/laporan,/admin/lokasi,/admin/pengguna').split(',').filter(Boolean);
const VIEWPORTS = [{ name: 'desktop', width: 1280, height: 800 }, { name: 'tablet', width: 768, height: 1024 }, { name: 'ponsel', width: 390, height: 844 }];

function inPage() {
    const parse = c => { const m = (c.match(/[\d.]+/g) || [0, 0, 0]).map(Number); return { r: m[0], g: m[1], b: m[2], a: m.length > 3 ? m[3] : 1 }; };
    const lum = ({ r, g, b }) => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
    const cr = (a, b) => (Math.max(lum(a), lum(b)) + 0.05) / (Math.min(lum(a), lum(b)) + 0.05);
    const bgOf = el => { while (el) { const c = parse(getComputedStyle(el).backgroundColor); if (c.a > 0.5) return c; el = el.parentElement; } return { r: 255, g: 255, b: 255, a: 1 }; };
    const visible = el => { const r = el.getBoundingClientRect(), s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity > 0; };
    const name = el => (el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || el.getAttribute('title') || (el.labels && el.labels.length ? 'label' : '') || (el.innerText || '').trim() || (el.querySelector && el.querySelector('img[alt]:not([alt=""])') ? 'img' : '') || el.getAttribute('value') || '').toString().trim();
    const sel = el => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : '');
    const out = { contrast: [], tiny: [], unnamed: [], noAlt: 0, smallTargets: 0, smallTouch: 0, overflow: document.documentElement.scrollWidth > window.innerWidth + 1, h1: document.querySelectorAll('h1').length };
    for (const el of document.querySelectorAll('body *')) {
        if (!visible(el)) continue;
        const hasText = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim());
        if (hasText) {
            const s = getComputedStyle(el), fg = parse(s.color), bg = bgOf(el), size = parseFloat(s.fontSize), bold = +s.fontWeight >= 700;
            const need = (size >= 24 || (size >= 18.66 && bold)) ? 3 : 4.5, ratio = cr(fg, bg);
            if (ratio < need) out.contrast.push(`${ratio.toFixed(2)}:1 ${sel(el)} "${el.textContent.trim().slice(0, 24)}"`);
            if (size < 11 && !el.closest('.paper, .approval-letter, .financial-paper, [class*="skrd"], [class*="receipt"]')) out.tiny.push(`${size}px ${sel(el)}`);
        }
        if (el.matches('a[href],button,input:not([type=hidden]),select,textarea,summary,[role=button]')) {
            if (!name(el)) out.unnamed.push(sel(el));
            const r = el.getBoundingClientRect();
            if (r.height < 24 || r.width < 24) out.smallTargets++;
            if (window.innerWidth <= 768 && r.height < 44) out.smallTouch++;
        }
        if (el.tagName === 'IMG' && !el.hasAttribute('alt')) out.noAlt++;
    }
    return out;
}

async function focusCheck(page) {
    let hidden = 0, total = 0;
    await page.evaluate(() => document.activeElement && document.activeElement.blur());
    for (let i = 0; i < 20; i++) {
        await page.keyboard.press('Tab');
        const r = await page.evaluate(() => { const e = document.activeElement; if (!e || e === document.body) return null; if (e.dataset.auditSeen) return { seen: true }; e.dataset.auditSeen = '1'; const s = getComputedStyle(e); return { w: parseFloat(s.outlineWidth) * (s.outlineStyle === 'none' ? 0 : 1), shadow: s.boxShadow && s.boxShadow !== 'none' && !/rgba\(0, 0, 0, 0\)/.test(s.boxShadow), el: e.tagName }; });
        if (!r) continue; if (r.seen) break; total++; if (r.w === 0 && !r.shadow) hidden++;
    }
    return { hidden, total };
}

(async () => {
    fs.mkdirSync(OUT, { recursive: true });
    const browser = await chromium.launch();
    const rows = [], totals = { halaman: 0, contrast: 0, tiny: 0, unnamed: 0, noAlt: 0, overflow: 0, errors: 0, focusHidden: 0, bad: 0 };
    for (const role of (ROLES.length ? ROLES : [''])) {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } }), page = await ctx.newPage();
        if (role) {
            await page.goto(BASE + '/login');
            if (await page.$('select[name=role]')) await page.selectOption('select[name=role]', role);
            await Promise.all([page.waitForLoadState('load'), page.click('form button[type=submit]')]);
        }
        for (const p of PATHS) for (const vp of VIEWPORTS) {
            const errors = []; const onErr = m => { if (m.type() === 'error') errors.push(m.text().slice(0, 120)); };
            page.on('console', onErr); page.on('pageerror', e => errors.push('JS: ' + e.message.slice(0, 120)));
            await page.setViewportSize({ width: vp.width, height: vp.height });
            const res = await page.goto(BASE + p, { waitUntil: 'load' }).catch(e => ({ status: () => 'ERR ' + e.message.slice(0, 40) }));
            const status = res.status(); if (status !== 200) { rows.push(`| ${role || '-'} | ${p} | ${vp.name} | HTTP ${status} | | | | | | | |`); page.off('console', onErr); continue; }
            const r = await page.evaluate(inPage), f = vp.name === 'desktop' ? await focusCheck(page) : { hidden: 0, total: 0 };
            await page.screenshot({ path: path.join(OUT, `${role || 'tamu'}-${p.replace(/\W+/g, '_')}-${vp.name}.png`), fullPage: true });
            totals.halaman++; totals.contrast += r.contrast.length; totals.tiny += r.tiny.length; totals.unnamed += r.unnamed.length; totals.noAlt += r.noAlt; totals.overflow += r.overflow ? 1 : 0; totals.errors += errors.length; totals.focusHidden += f.hidden;
            rows.push(`| ${role || '-'} | ${p} | ${vp.name} | 200 | ${r.contrast.length} | ${r.tiny.length} | ${r.unnamed.length} | ${r.noAlt} | ${r.overflow ? 'YA' : '-'} | ${errors.length} | ${vp.name === 'desktop' ? f.hidden + '/' + f.total : '-'} |`);
            if (r.contrast.length || r.unnamed.length || errors.length) rows.push(`|  |  |  | contoh |  |  |  |  |  |  | ${[...r.contrast.slice(0, 2), ...r.unnamed.slice(0, 2), ...errors.slice(0, 1)].join('; ').replace(/\|/g, '/')} |`);
            page.off('console', onErr);
        }
        await ctx.close();
    }
    await browser.close();
    const md = ['# Laporan audit antarmuka', '', `Dibuat: ${new Date().toISOString()}  |  Alamat: ${BASE}`, '',
        '| Peran | Halaman | Layar | Status | Kontras <AA | Teks <11px | Kontrol tanpa nama | Gambar tanpa alt | Geser horizontal | Galat konsol | Fokus tak terlihat |', '|---|---|---|---|---|---|---|---|---|---|---|', ...rows, '',
        `Ringkasan: ${totals.halaman} halaman diperiksa; kontras gagal ${totals.contrast}; teks kecil ${totals.tiny}; kontrol tanpa nama ${totals.unnamed}; gambar tanpa alt ${totals.noAlt}; halaman dengan geser horizontal ${totals.overflow}; galat konsol ${totals.errors}; fokus tak terlihat ${totals.focusHidden}.`];
    fs.writeFileSync(path.join(OUT, 'REPORT.md'), md.join('\n') + '\n');
    console.log(md.slice(-1)[0]); console.log('Laporan:', path.join(OUT, 'REPORT.md'));
})().catch(e => { console.error('GAGAL:', e.message); process.exit(1); });
