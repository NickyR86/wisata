/*
 * Tahap 10: kenyamanan formulir.
 * - Mencegah pengiriman ganda (klik dua kali, Enter berulang) pada formulir yang sama.
 * - Dimuat terpisah dari app.js agar tetap jalan walau app.js gagal pada suatu halaman.
 * Pengecualian: formulir dengan target lain (tab baru) atau atribut data-no-guard.
 */
(() => {
    const UNLOCK_AFTER_MS = 20000;
    const unlock = form => {
        delete form.dataset.submitting;
        form.classList.remove('is-submitting');
        form.removeAttribute('aria-busy');
    };
    // Fase bubble: pendengar milik formulir (mis. konfirmasi hapus) berjalan lebih dulu dan dapat membatalkan.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        if ((form.target && form.target !== '_self') || form.hasAttribute('data-no-guard')) return;
        if (form.dataset.submitting === '1') { event.preventDefault(); return; }
        form.dataset.submitting = '1';
        form.classList.add('is-submitting');
        form.setAttribute('aria-busy', 'true');
        window.setTimeout(() => unlock(form), UNLOCK_AFTER_MS);
    });
    // Kembali lewat tombol Back (bfcache): buka kunci agar formulir dapat dipakai lagi.
    window.addEventListener('pageshow', () => document.querySelectorAll('form[data-submitting]').forEach(unlock));
})();
