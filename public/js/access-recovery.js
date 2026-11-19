(() => {
 const action=document.querySelector('[data-recovery-action]'),status=document.querySelector('[data-recovery-status]');
 const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),10000);
 fetch('/akses/status',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},signal:controller.signal})
 .then(response=>{if(!response.ok)throw new Error();return response.json();})
 .then(access=>{action.textContent=access.authenticated?'Kembali ke formulir':'Masuk & lanjutkan ke formulir';status.textContent=access.authenticated?'Akses tersedia. Anda dapat kembali ke formulir asal.':'Anda perlu masuk kembali. Setelah masuk, formulir asal akan dibuka.';})
 .catch(()=>{status.textContent='Akses belum dapat diperiksa. Pastikan koneksi tersedia, lalu tekan tombol untuk melanjutkan.';})
 .finally(()=>clearTimeout(timer));
})();
