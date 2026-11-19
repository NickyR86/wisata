// Headless browser; mocked server. Run with Playwright on NODE_PATH.
const {chromium}=require('playwright');
const fs=require('fs'), assert=require('node:assert/strict');
(async()=>{
 const browser=await chromium.launch({headless:true,...(process.env.TEST_BROWSER?{executablePath:process.env.TEST_BROWSER}:{})});
 try{
 const page=await browser.newPage();let posts=0, body='', authenticated=true, accessRole='sekretariat';
 const script=fs.readFileSync(require('path').join(__dirname,'../public/js/browser-access.js'),'utf8');
 const html=success=>`<body data-access-role="sekretariat" data-access-identity="demo:sekretariat" data-save-success="${success?'1':'0'}"><main><form method="post" action="/save"><input name="_token" type="hidden" value="old"><input name="name" required><input name="file" type="file"><button>Simpan</button></form></main><script>${script}</script>`;
 await page.route('http://siptr.test/**',async route=>{
  const req=route.request();
  if(req.url().endsWith('/akses/status')){await new Promise(r=>setTimeout(r,100));return route.fulfill({json:{authenticated,role:accessRole,identity:'demo:'+accessRole,token:'renewed'}});}
  if(req.method()==='POST'){posts++;body=req.postData();return route.fulfill({contentType:'text/html',body:html(true)});}
  return route.fulfill({contentType:'text/html',body:html(false)});
 });
 await page.goto('http://siptr.test/form');await page.locator('[name=name]').fill('Isian sebelum sleep');
 await page.waitForTimeout(500);await page.reload();
 await page.getByText('Pulihkan isian yang belum disimpan',{exact:true}).click();
 assert.equal(await page.locator('[name=name]').inputValue(),'Isian sebelum sleep');
 authenticated=false;await page.getByText('Simpan',{exact:true}).click();
 await page.getByText('Buka login, lalu kembali ke halaman ini',{exact:true}).waitFor();assert.equal(posts,0);
 assert.equal(await page.locator('[name=name]').inputValue(),'Isian sebelum sleep');
 authenticated=true;accessRole='kadis';await page.getByText('Simpan',{exact:true}).click();
 await page.getByRole('alert').filter({hasText:'Akun atau peran aktif berbeda'}).waitFor();assert.equal(posts,0);
 accessRole='sekretariat';
 await page.evaluate(()=>{const f=document.querySelector('form');f.requestSubmit();f.requestSubmit();});
 await page.waitForURL('**/save');assert.equal(posts,1);assert.match(body,/_token=renewed/);
 assert.equal(await page.getByText('Pulihkan isian yang belum disimpan',{exact:true}).count(),0);
 console.log('PASS: draft recovery, expired/revoked access blocked, role mismatch blocked, fresh CSRF, single submit');
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
