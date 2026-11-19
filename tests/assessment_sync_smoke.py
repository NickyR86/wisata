from pathlib import Path
exec(compile(Path(__file__).with_name('finance_format_smoke.py').read_text(encoding='utf-8').split('import base64')[0], __file__, 'exec'))
assert PHP
request('/login');role('bendahara')
body,_=request('/permohonan/336')
assert 'Simpan penetapan' in body
body,_=request('/permohonan/336/aksi',{'action':'save_assessment','rate':1600000,'rate_reason':'Tarif uji penetapan'})
assert 'Penetapan disimpan' in body
body,_=request('/permohonan/336/template/skrd')
amount=float(re.search(r'data-row-field="amount"[^>]+value="([0-9.]+)"',body).group(1))
assert amount>=1600000 and 'readonly' in re.search(r'<input[^>]+data-row-field="amount"[^>]*>',body).group(0)
source=re.search(r'name="source_token" value="([^"]+)"',body).group(1)
payload={'description':'Uraian uji tersimpan','source_token':source,'rows':[{'code':'4.1.02','label':'Retribusi uji','amount':amount+1}]}
body,_=request('/permohonan/336/template/skrd',payload)
assert 'Nominal SKRD mengikuti Penetapan Retribusi' in body
payload['rows'][0]['amount']=amount
body,_=request('/permohonan/336/template/skrd',payload)
assert 'Template SKRD disimpan' in body and 'Uraian uji tersimpan' in body
request('/permohonan/336/aksi',{'action':'invoice','rate':1600000,'rate_reason':'Tarif uji penetapan'})
body,_=request('/dokumen/336/skrd')
assert 'Retribusi uji' in body and '1.600.000' in body
request('/permohonan/336/aksi',{'action':'save_assessment','rate':1,'rate_reason':'Tidak boleh'},expected=403)
role('sekretariat')
request('/permohonan/337/aksi',{'action':'save_assessment','rate':1,'rate_reason':'Tidak boleh'},expected=403)
print(f'PASS: {checks} assessment HTTP checks (save, reload, template guard, issue, role and issued lock)')
