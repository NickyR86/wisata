from pathlib import Path
harness=Path(__file__).with_name('finance_format_smoke.py').read_text(encoding='utf-8').split('import base64')[0]
harness=harness.replace("else path.removesuffix('/aksi')", "else '/admin/lokasi' if path.startswith('/admin/lokasi/') else path.removesuffix('/aksi')")
exec(compile(harness, __file__, 'exec'))
assert PHP, 'Use DEMO_TEST_PHP for isolated kernel checks'
def source_token(body):
    return re.search(r'name="source_token" value="([^"]+)"',body).group(1)
request('/login'); role('bendahara')
body,_=request('/permohonan/336/template/skrd')
data={'description':'Uraian petugas tetap','attention':'','source_token':source_token(body)}
request('/permohonan/336/template/skrd',data)
body,_=request('/permohonan/336/template/skrd')
assert 'Uraian petugas tetap' in body
old_token=source_token(body)
role('admin')
# Change the same master read by SKRD and invoice creation.
request('/admin/lokasi/3',{'rate':500000})
role('bendahara')
body,_=request('/permohonan/336/template/skrd')
assert 'Data sumber berubah sejak penyimpanan sebelumnya' in body
data['source_token']=old_token
body,_=request('/permohonan/336/template/skrd',data)
assert 'Data permohonan berubah sejak editor dibuka' in body
data['source_token']=source_token(body)
request('/permohonan/336/template/skrd',data)
body,_=request('/permohonan/336/template/skrd')
assert 'Data sumber berubah sejak penyimpanan sebelumnya' in body
data['source_reviewed']='1'
body,_=request('/permohonan/336/template/skrd',data)
assert 'Uraian petugas tetap' in body and 'Data sumber berubah sejak penyimpanan sebelumnya' not in body
request('/permohonan/336/aksi',{'action':'invoice','rate':500000,'rate_reason':'Tarif master teruji'})
original,_=request('/dokumen/336/skrd')
assert 'Uraian petugas tetap' in original
body,_=request('/permohonan/336/template/skrd')
data['source_token']=source_token(body); data['description']='Revisi SKRD teruji'
request('/permohonan/336/template/skrd',data)
unchanged,_=request('/dokumen/336/skrd')
assert 'Revisi SKRD teruji' not in unchanged
data['revision_reason']='Perbaikan uraian demonstrasi'
request('/permohonan/336/template/skrd',data)
revised,_=request('/dokumen/336/skrd')
assert 'Revisi SKRD teruji' in revised
previous,_=request('/dokumen/336/skrd?version=1')
assert 'Uraian petugas tetap' in previous and 'Revisi SKRD teruji' not in previous
data['reset_description']='1'
request('/permohonan/336/template/skrd',data)
automatic,_=request('/dokumen/336/skrd')
assert 'Pelaksanaan kegiatan' in automatic and 'Revisi SKRD teruji' not in automatic
prior,_=request('/dokumen/336/skrd?version=2')
assert 'Revisi SKRD teruji' in prior
# Issued monetary values cannot be edited through the document template.
body,_=request('/permohonan/336/template/skrd')
amount=float(re.search(r'data-row-field="amount"[^>]+value="([0-9.]+)"',body).group(1))
data.update({'source_token':source_token(body),'rental_date':'2026-12-15',
 'rows':[{'code':'4.1.02.02.009.00001','label':'Retribusi uji','amount':amount+1}],
 'revision_reason':'Koreksi tanggal sewa'})
body,_=request('/permohonan/336/template/skrd',data)
assert 'Nominal SKRD mengikuti Penetapan Retribusi' in body
data['rows'][0]['amount']=amount
request('/permohonan/336/template/skrd',data)
printed,_=request('/dokumen/336/skrd')
assert '15 Des 2026' in printed
previous,_=request('/dokumen/336/skrd?version=1')
assert '15 Des 2026' not in previous
request('/permohonan/336/aksi',{'action':'pay','payment_checked':'1','payment_date':dt.date.today().isoformat()})
body,_=request('/permohonan/336/template/skrd')
data['source_token']=source_token(body);data['rows'][0]['amount']=amount+1
body,_=request('/permohonan/336/template/skrd',data)
assert 'Nominal SKRD mengikuti Penetapan Retribusi' in body
request('/demo/reset',{})
print(f'PASS: {checks} SKRD behavior integration requests')
