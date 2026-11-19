/* Restore access on demand; never automatically replay a business POST. */
(() => {
 const role=document.body.dataset.accessRole, identity=document.body.dataset.accessIdentity;
 if(!identity)return;
 const prefix='siptr-draft:'+identity+':', pending=prefix+'pending';
 const read=key=>{try{return JSON.parse(sessionStorage.getItem(key));}catch{return null;}};
 const write=(key,value)=>{try{sessionStorage.setItem(key,JSON.stringify(value));}catch{}};
 const remove=key=>{try{sessionStorage.removeItem(key);}catch{}};
 const pendingKey=read(pending);
 if(document.body.dataset.saveSuccess==='1'&&pendingKey)remove(pendingKey);
 remove(pending);
 let banner;
 function message(text,login=false){
  if(!banner){banner=document.createElement('div');banner.className='alert alert-warning';banner.setAttribute('role','alert');document.querySelector('main')?.prepend(banner);}
  banner.replaceChildren(document.createTextNode(text));
  if(login){const link=document.createElement('a');link.href='/login';link.target='_blank';link.rel='noopener';link.className='ms-2';link.textContent='Buka login, lalu kembali ke halaman ini';banner.append(link);}
 }
 const draftKeys=new WeakMap();let leavingByLogout=false;
 document.querySelectorAll('main form[method="post"]').forEach((form,index)=>{
  const fields=[...form.elements].filter(el=>el.name&&!['hidden','password','file','submit','button'].includes(el.type));
  if(!fields.length)return;
  const key=prefix+location.pathname+location.search+':'+index;
  draftKeys.set(form,key);
  const saved=read(key);
  if(saved&&Date.now()-saved.at<86400000){
   const restore=document.createElement('button');restore.type='button';restore.className='btn btn-outline-secondary mb-3';restore.textContent='Pulihkan isian yang belum disimpan';
   restore.addEventListener('click',()=>{fields.forEach(el=>{const value=saved.values[el.name];if(value===undefined)return;if(['checkbox','radio'].includes(el.type))el.checked=value.includes(el.value);else el.value=value;el.dispatchEvent(new Event('input',{bubbles:true}));});restore.remove();message('Isian dipulihkan. Berkas yang belum terkirim perlu dipilih kembali.');});
   form.prepend(restore);
  }else if(saved)remove(key);
  let timer,dirty=false;
  const save=()=>{if(leavingByLogout||!dirty)return;const values={};fields.forEach(el=>{if(['checkbox','radio'].includes(el.type)){values[el.name]??=[];if(el.checked)values[el.name].push(el.value);}else values[el.name]=el.value;});write(key,{at:Date.now(),values});};
  form.addEventListener('input',()=>{dirty=true;clearTimeout(timer);timer=setTimeout(save,400);});
  window.addEventListener('pagehide',save);
 });
 let checking;
 function check(){
  if(checking)return checking;
  checking=(async()=>{
   const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),15000);
   try{
    const response=await fetch('/akses/status',{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'},signal:controller.signal});
    if(!response.ok)throw new Error('connection');
    const access=await response.json();
    if(!access.authenticated){message('Akses tidak dapat dipulihkan. Isian tetap berada di halaman ini.',true);return false;}
    if(access.identity!==identity||access.role!==role){message('Akun atau peran aktif berbeda dari halaman ini. Pengiriman dibatalkan; isian tetap dipertahankan.');return false;}
    document.querySelectorAll('input[name="_token"]').forEach(el=>el.value=access.token);
    if(banner){banner.remove();banner=null;}return true;
   }catch{message('Koneksi belum tersedia. Isian tetap dipertahankan; coba kirim kembali setelah terhubung.');return false;}
   finally{clearTimeout(timer);}
  })().finally(()=>checking=null);
  return checking;
 }
 const busy=new WeakSet();
 document.addEventListener('submit',async event=>{
  const form=event.target;
  if(event.defaultPrevented||form.method.toLowerCase()!=='post')return;
  event.preventDefault();if(busy.has(form))return;busy.add(form);let submitted=false;
  try{
   if(!await check())return;
   if(!form.reportValidity())return;
   if(new URL(form.action,location.href).pathname==='/demo/keluar'){
    leavingByLogout=true;
    try{for(const key of Object.keys(sessionStorage))if(key.startsWith('siptr-draft:'))sessionStorage.removeItem(key);}catch{}
   }else if(draftKeys.has(form))write(pending,draftKeys.get(form));
   if(event.submitter?.name){const input=document.createElement('input');input.type='hidden';input.name=event.submitter.name;input.value=event.submitter.value;form.append(input);}
   submitted=true;HTMLFormElement.prototype.submit.call(form);
   // Keep busy until navigation: a second click must not submit a second request.
   return;
  }finally{if(!submitted)busy.delete(form);}
 });
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible')check();});
 window.addEventListener('online',check);window.addEventListener('pageshow',check);
})();
