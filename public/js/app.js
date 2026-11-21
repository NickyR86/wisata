/* Interaksi tampilan saja. Keputusan demo diproses melalui sesi Laravel. */
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
document.querySelectorAll('[data-note-required]').forEach(button => button.addEventListener('click', event => {
    const note = button.form.querySelector('[name="note"]');
    if (!note || note.value.trim().length < 5) {
        event.preventDefault();
        if (note) { note.setCustomValidity('Isi catatan minimal 5 karakter agar alasan dapat dipahami.'); note.reportValidity(); note.focus(); }
    }
}));
document.querySelectorAll('[name="note"]').forEach(note => note.addEventListener('input', () => note.setCustomValidity('')));
const locationInput = document.getElementById('location');
const daysInput = document.getElementById('days');
function updateEstimate() {
    if (!locationInput || !daysInput) return;
    const option = locationInput.selectedOptions[0];
    const rate = Number(option?.dataset.rate || 0);
    const days = Math.max(1, Math.min(30, Number(daysInput.value) || 1));
    const rupiah = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
    document.getElementById('estimateRate') && (document.getElementById('estimateRate').textContent = rate ? rupiah(rate) : '—');
    if(document.getElementById('estimateDays')) document.getElementById('estimateDays').textContent = `${days} hari`;
    if(document.getElementById('estimateTotal')) document.getElementById('estimateTotal').textContent = rate ? rupiah(rate * days) : '—';
    const discount = document.getElementById('discount');
    if (discount) {
        if (option?.dataset.managed === '0') { discount.value = ''; discount.disabled = true; } else { discount.disabled = false; }
        const requested = discount.value !== '';
        const reason = document.getElementById('discount_reason');
        if (reason) reason.required = requested;
        const reliefRow = document.querySelector('[data-relief-upload]');
        const reliefInput = reliefRow?.querySelector('input[type="file"]');
        const useSample = !!document.getElementById('files')?.checked;
        if (reliefRow) reliefRow.hidden = !requested;
        if (reliefInput) {
            if (!requested) reliefInput.value = '';
            reliefInput.disabled = !requested || useSample;
            reliefInput.required = requested && !useSample;
        }
    }
}
locationInput?.addEventListener('change', updateEstimate);
daysInput?.addEventListener('input', updateEstimate);
document.getElementById('discount')?.addEventListener('change', updateEstimate);
document.getElementById('files')?.addEventListener('change', updateEstimate);
updateEstimate();
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));

document.querySelectorAll('[data-href]').forEach(row=>row.addEventListener('click',e=>{if(!e.target.closest('a,button,input'))location.href=row.dataset.href;}));
const draftEditor=document.getElementById('draft'), draftPreview=document.querySelector('[data-live-draft]');
if(draftEditor&&draftPreview)draftEditor.addEventListener('input',()=>draftPreview.textContent=draftEditor.value);

document.querySelectorAll('[data-template-input]').forEach(input=>input.addEventListener('input',()=>{
 if(input.name==='attention')document.querySelectorAll('[data-attention-list]').forEach(list=>{
  list.replaceChildren();
  input.value.split(/\r\n|[\n\r\u0085\u2028\u2029]/).map(line=>line.trim()).filter(Boolean).forEach(line=>{
   const item=document.createElement('li');item.textContent=line.replace(/^\s*\d+[.)]\s*/, '');list.append(item);
  });
 });
 document.querySelectorAll('[data-template-value="'+input.name+'"]').forEach(node=>node.textContent=(input.value&&['issued_date','period'].includes(input.name)?new Date(input.value+(input.name==='period'?'-01':'')+'T12:00:00').toLocaleDateString('id-ID',input.name==='period'?{month:'long',year:'numeric'}:{day:'2-digit',month:'long',year:'numeric'}):input.value)||'—');
}));

const sampleFiles=document.getElementById('files');
function syncRequirementUploads(){
 const needsDlh=locationInput?.selectedOptions[0]?.dataset.dlh==='1';
 const dlh=document.querySelector('[data-dlh-upload]'),notice=document.querySelector('[data-dlh-notice]');
 if(dlh){
  const input=dlh.querySelector('input');const cleared=!needsDlh&&!!input.value;
  if(!needsDlh)input.value='';dlh.hidden=!needsDlh;dlh.style.display=needsDlh?'':'none';
  if(notice){if(cleared)notice.textContent='Pilihan berkas DLH dibersihkan karena lokasi ini tidak memerlukannya.';else if(needsDlh)notice.textContent='Rekomendasi DLH wajib untuk lokasi yang dipilih.';else if(!notice.textContent.startsWith('Pilihan berkas DLH'))notice.textContent='';}
 }
 document.querySelectorAll('[data-requirement-upload]').forEach(input=>{
  const applicable=!input.closest('[data-dlh-upload]')||needsDlh;
  input.disabled=!!sampleFiles?.checked||!applicable;input.required=!sampleFiles?.checked&&applicable;
 });
}
locationInput?.addEventListener('change',syncRequirementUploads);
locationInput?.addEventListener('input',syncRequirementUploads);
sampleFiles?.addEventListener('change',syncRequirementUploads);syncRequirementUploads();

document.querySelectorAll('[data-letter-input]').forEach(input=>{input.addEventListener('input',()=>{document.querySelectorAll('[data-letter-value="'+input.dataset.letterInput+'"]').forEach(target=>target.textContent=input.value);});});

function financeWords(n){
 const w=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
 if(n<12)return w[n];
 if(n<20)return financeWords(n-10)+' belas';
 const join=(base,rest)=>base+(rest?' '+financeWords(rest):'');
 if(n<100)return join(financeWords(Math.floor(n/10))+' puluh',n%10);
 if(n<200)return join('seratus',n%100);
 if(n<1000)return join(financeWords(Math.floor(n/100))+' ratus',n%100);
 if(n<2000)return join('seribu',n%1000);
 for(const [unit,label] of [[1e12,'triliun'],[1e9,'miliar'],[1e6,'juta'],[1000,'ribu']])if(n>=unit)return join(financeWords(Math.floor(n/unit))+' '+label,n%unit);
 return '';
}
// Receipt rows use textContent so entered descriptions remain plain text.
const financeRows=document.querySelector('[data-finance-input-rows]');
if(financeRows){
 const preview=document.querySelector('[data-finance-rows]');
 const skrd=preview.hasAttribute('data-skrd-rows');
 const rentalDate=document.getElementById('rental_date');
 const money=value=>'Rp '+Number(value||0).toLocaleString('id-ID',{maximumFractionDigits:0});
 function syncFinanceRows(){
  preview.replaceChildren();let sum=0;
  financeRows.querySelectorAll('fieldset').forEach((row,index)=>{
   row.querySelector('legend').textContent=skrd?'Rekening retribusi':'Rincian '+(index+1);
   const tr=document.createElement('tr');const num=document.createElement('td');num.textContent=index+1;tr.append(num);
   row.querySelectorAll('[data-row-field]').forEach(input=>{
    input.name=`rows[${index}][${input.dataset.rowField}]`;
    input.id=`row-${index}-${input.dataset.rowField}`;input.previousElementSibling.htmlFor=input.id;
    const td=document.createElement('td');td.textContent=input.dataset.rowField==='amount'?money(input.value):input.value;if(input.dataset.rowField==='code'&&document.querySelector('.finance-skrd')){td.textContent='';const group=document.createElement('span');group.className='account-code-cells';input.value.split('.').forEach(part=>{const cell=document.createElement('span');cell.textContent=part;group.append(cell);});td.append(group);}tr.append(td);
    if(input.dataset.rowField==='amount')sum+=Number(input.value||0);
   });preview.append(tr);
   row.querySelector('[data-remove-finance-row]').disabled=financeRows.children.length===1;
  });
  if(skrd){
   preview.replaceChildren();
   financeRows.querySelectorAll('fieldset').forEach((row,index)=>{
    const code=row.querySelector('[data-row-field="code"]').value.split('.');
    const label=row.querySelector('[data-row-field="label"]').value;
    const amount=row.querySelector('[data-row-field="amount"]').value;
    for(let line=0;line<5;line++){
     const tr=document.createElement('tr');const add=(text)=>{const td=document.createElement('td');td.textContent=text;tr.append(td);return td;};
     add(index*5+line+1);for(let j=0;j<8;j++)add(line===0?(code[j]||''):'');
     if(line===0){const td=add('');const u=document.createElement('u');u.textContent=label;td.append(u);add('');}
     if(line===1){const td=add('');td.rowSpan=3;td.className='skrd-description';const span=document.createElement('span');span.dataset.templateValue='description';span.textContent=document.getElementById('description').value;td.append(span);const amt=add(money(amount));amt.rowSpan=4;}
     if(line===4){add('PEMBAYARAN SEWA UNTUK TANGGAL '+(rentalDate?.value?new Date(rentalDate.value+'T12:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}):'—'));}preview.append(tr);
    }
   });
  }
  if(skrd){
   const percent=Number(document.querySelector('[data-skrd-percent]').dataset.skrdPercent);
   const discount=Math.round(sum*percent)/100, total=sum-discount;
   const put=(selector,value)=>document.querySelectorAll(selector).forEach(node=>node.textContent=value);
   put('[data-skrd-base]',sum.toLocaleString('id-ID'));
   put('[data-skrd-total]',total.toLocaleString('id-ID',{maximumFractionDigits:0}));
   put('[data-skrd-net]',money(total));put('[data-skrd-discount]','− '+money(discount));
   put('[data-skrd-words]',(financeWords(Math.round(total))+' rupiah').toUpperCase());
  }
  document.querySelector('[data-finance-row-total]').textContent='Jumlah rincian yang diisi: '+money(sum);
  document.querySelector('[data-add-finance-row]').disabled=financeRows.children.length>=20;
 }
 rentalDate?.addEventListener('input',syncFinanceRows);
 financeRows.addEventListener('input',syncFinanceRows);
 financeRows.addEventListener('click',event=>{if(event.target.closest('[data-remove-finance-row]')&&financeRows.children.length>1){event.target.closest('fieldset').remove();syncFinanceRows();}});
 document.querySelector('[data-add-finance-row]').addEventListener('click',()=>{const row=financeRows.firstElementChild.cloneNode(true);row.querySelectorAll('input').forEach(input=>input.value=input.dataset.rowField==='amount'?'0':'');financeRows.append(row);syncFinanceRows();});
 document.querySelectorAll('[data-template-input]').forEach(input=>input.dispatchEvent(new Event('input')));
 syncFinanceRows();
}

const financePeriod=document.querySelector('[data-template-editor] #period');
function updateFinancePeriod(){if(!financePeriod?.value)return;document.querySelectorAll('[data-period-month]').forEach(el=>el.textContent=new Date(financePeriod.value+'-01T12:00:00').toLocaleDateString('id-ID',{month:'long'}));document.querySelectorAll('[data-period-year]').forEach(el=>el.textContent=financePeriod.value.slice(0,4));}
financePeriod?.addEventListener('input',updateFinancePeriod);updateFinancePeriod();

// Structured approval letter: textContent only; user content is never interpreted as HTML.
document.querySelectorAll('[data-approval-editor]').forEach(editor=>{
 const preview=editor.querySelector('[data-letter-source]');
 const source=JSON.parse(preview.dataset.letterSource);
 const render=text=>text.replace(/\{([^{}]+)\}/g,(match,key)=>source[key]??match);
 const update=()=>{
  editor.querySelectorAll('[data-structured-input]').forEach(input=>preview.querySelectorAll('[data-structured-value="'+input.dataset.structuredInput+'"]').forEach(el=>el.textContent=render(input.value)));
  editor.querySelectorAll('[data-letter-list]').forEach(section=>{
   const list=preview.querySelector('[data-structured-list="'+section.dataset.letterList+'"]');
   list.replaceChildren();
   section.querySelectorAll('textarea').forEach(input=>{if(input.value.trim()){const li=document.createElement('li');li.textContent=render(input.value);list.append(li);}});
   if(section.dataset.letterList==='legal_basis'){const li=document.createElement('li');li.dataset.structuredValue='request_reference';li.textContent=render(editor.querySelector('[data-structured-input="request_reference"]').value);list.append(li);}
  });
 };
 editor.addEventListener('input',update);
 editor.addEventListener('click',event=>{
  const button=event.target.closest('button');if(!button)return;
  const section=button.closest('[data-letter-list]');if(!section)return;
  const row=button.closest('.letter-edit-row');
  if(button.hasAttribute('data-item-remove'))row.remove();
  if(button.hasAttribute('data-item-up')&&row.previousElementSibling)row.previousElementSibling.before(row);
  if(button.hasAttribute('data-item-down')&&row.nextElementSibling)row.nextElementSibling.after(row);
  if(button.hasAttribute('data-item-add')){
   if(section.querySelectorAll('.letter-edit-row').length>=30)return;
   const item=document.createElement('div');item.className='letter-edit-row mb-3';
   const textarea=document.createElement('textarea');textarea.className='form-control';textarea.rows=3;textarea.maxLength=3000;textarea.name='letter[structure]['+section.dataset.letterList+'][]';textarea.setAttribute('aria-label','Isi butir '+section.dataset.letterList);item.append(textarea);
   const actions=document.createElement('div');actions.className='d-flex gap-2 mt-1';
   [['up','Naik'],['down','Turun'],['remove','Hapus']].forEach(([action,label])=>{const b=document.createElement('button');b.type='button';b.className='btn btn-sm btn-outline-secondary';b.dataset['item'+action[0].toUpperCase()+action.slice(1)]='';b.textContent=label;actions.append(b);});
   item.append(actions);section.querySelector('[data-list-items]').append(item);textarea.focus();
  }
  update();
 });
 update();
});

// The assessment is the only editable source of SKRD amounts.
document.querySelectorAll('[data-assessment]').forEach(panel=>{
 const rate=panel.querySelector('[name="rate"]');
 const money=n=>'Rp '+n.toLocaleString('id-ID',{maximumFractionDigits:2});
 const update=()=>{
  const subtotal=Math.round(Number(rate.value||0)*Number(panel.dataset.days)*100)/100;
  const discount=Math.round(subtotal*Number(panel.dataset.percent))/100;
  panel.querySelector('[data-assessment-subtotal]').textContent=money(subtotal);
  panel.querySelector('[data-assessment-discount]').textContent=money(discount);
  panel.querySelector('[data-assessment-total]').textContent=money(subtotal-discount);
 };
 rate.addEventListener('input',update);update();
});

document.addEventListener('change', event => {
    const control = event.target && event.target.closest ? event.target.closest('[data-autosubmit]') : null;
    if (control && control.form) control.form.requestSubmit();
});
