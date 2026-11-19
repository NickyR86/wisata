/* A4 preview uses the browser's own column fragmentation, including long paragraphs.
   Keep the editable source separate from visual page copies so saving stays unchanged. */
(() => {
 document.querySelectorAll('.approval-reference:not(.a4-flow)').forEach(source => {
  const wrapper=document.createElement('section');wrapper.className='a4-preview';wrapper.setAttribute('aria-label','Pratinjau surat A4');
  const summary=document.createElement('p');summary.className='a4-summary';summary.setAttribute('aria-live','polite');
  const pages=document.createElement('div');pages.className='a4-pages';pages.setAttribute('aria-hidden','true');
  source.before(wrapper);wrapper.append(summary,pages,source);source.classList.add('a4-flow','a4-source');
  let timer;
  function scale(){
   const width=wrapper.clientWidth;const sheet=pages.querySelector('.a4-sheet');if(!sheet)return;
   const ratio=Math.min(1,width/sheet.offsetWidth);
   pages.querySelectorAll('.a4-sheet-slot').forEach(slot=>{const page=slot.firstElementChild;slot.style.width=page.offsetWidth*ratio+'px';slot.style.height=page.offsetHeight*ratio+'px';page.style.transform=`scale(${ratio})`;});
  }
  function paginate(){
   const width=source.getBoundingClientRect().width;
   const gap=parseFloat(getComputedStyle(source).columnGap);
   if(!width)return;
   const count=Math.max(1,Math.ceil((source.scrollWidth+gap-1)/(width+gap)));
   const fragment=document.createDocumentFragment();
   for(let i=0;i<count;i++){
    const slot=document.createElement('div');slot.className='a4-sheet-slot';
    const sheet=document.createElement('div');sheet.className='a4-sheet';
    const window=document.createElement('div');window.className='a4-window';
    const copy=source.cloneNode(true);copy.classList.remove('a4-source');copy.removeAttribute('data-letter-source');
    [copy,...copy.querySelectorAll('*')].forEach(el=>{el.removeAttribute('id');[...el.attributes].filter(a=>a.name.startsWith('data-')).forEach(a=>el.removeAttribute(a.name));});
    copy.style.transform=`translateX(-${i*(width+gap)}px)`;
    window.append(copy);const label=document.createElement('div');label.className='a4-page-label';label.textContent=`${i+1} / ${count}`;
    sheet.append(window,label);slot.append(sheet);fragment.append(slot);
   }
   pages.replaceChildren(fragment);summary.textContent=`A4 · 210 × 297 mm · ${count} halaman`;scale();
  }
  const schedule=()=>{clearTimeout(timer);timer=setTimeout(paginate,120);};
  new MutationObserver(schedule).observe(source,{childList:true,subtree:true,characterData:true});
  new ResizeObserver(scale).observe(wrapper);
  window.addEventListener('beforeprint',paginate);
  document.fonts?.ready.then(paginate);paginate();
 });
})();
