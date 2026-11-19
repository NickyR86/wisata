from pathlib import Path
s=Path('tests/finance_format_smoke.py').read_text(encoding='utf-8').split("request('/login');role")[0]
exec(s)
request('/login');role('bendahara')
for kind in ['skrd','sts','stbp']:
    body,_=request('/dokumen/338/'+kind+'?from=retribusi');Path('../work/finance-'+kind+'.html').write_text(body,encoding='utf-8')
body,_=request('/permohonan/338/template/skrd');Path('../work/finance-editor.html').write_text(body,encoding='utf-8')
print('Exported clean demo previews')
