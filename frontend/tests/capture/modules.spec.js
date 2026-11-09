import {test,expect} from '@playwright/test';
import {mkdirSync,writeFileSync} from 'node:fs';
const items=[];
test('capture every module from the running application',async({page})=>{
 mkdirSync('module-screenshots',{recursive:true});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 async function capture(title){
  await page.evaluate(()=>document.fonts.ready);
  const filename=String(items.length+1).padStart(2,'0')+'_'+title.replace(/[^a-zA-Z0-9]+/g,'_')+'.png';
  await page.screenshot({path:'module-screenshots/'+filename,fullPage:true,animations:'disabled',scale:'css'});
  items.push({title,filename});
 }
 await page.goto('/');await expect(page.getByRole('button',{name:'Log masuk',exact:true})).toBeVisible();await capture('Log masuk');
 await page.getByLabel('E-mel',{exact:true}).fill(process.env.TEST_ADMIN_EMAIL);await page.getByLabel('Kata laluan',{exact:true}).fill(process.env.TEST_ADMIN_PASSWORD);await page.getByRole('button',{name:'Log masuk',exact:true}).click();await expect(page.getByRole('heading',{name:/Space Console/})).toBeVisible();
 await capture('Konsol ruang - pelan 2D');
 await page.getByRole('button',{name:'Isometrik',exact:true}).click();await capture('Konsol ruang - isometrik');
 await page.locator('.header nav').getByRole('button',{name:'Daftar ruang',exact:true}).click();await capture('Daftar ruang');
 await page.getByRole('button',{name:'Ringkasan keluasan',exact:true}).click();await capture('Ringkasan keluasan');
 await page.locator('.title-actions').getByRole('button',{name:'Daftar ruang',exact:true}).click();await expect(page.getByRole('dialog',{name:'Borang daftar ruang'})).toBeVisible();await capture('Borang daftar ruang');await page.getByRole('button',{name:'Tutup dialog',exact:true}).click();
 await page.getByRole('button',{name:'Import ruang',exact:true}).click();await expect(page.getByRole('dialog',{name:'Import ruang CSV atau JSON'})).toBeVisible();await capture('Import ruang CSV JSON');await page.getByRole('button',{name:'Tutup import',exact:true}).click();
 await page.getByRole('button',{name:'Operasi fasiliti',exact:true}).click();await expect(page.locator('.ops-main h1')).toHaveText('Ringkasan');
 for(const name of ['Ringkasan','Daftar aset','Aduan','Arahan kerja','Penyelenggaraan','Tempahan ruang','Stok alat ganti','Pembekal','Kontrak','Kos & caj ruang','Meter utiliti','Laporan','Dasar SLA','Notifikasi']){
  if(name==='Laporan'){
   const ready=page.waitForResponse(r=>r.url().includes('/api/v1/ops/reports')&&r.status()===200);
   await page.locator('.ops-nav').getByRole('button',{name,exact:true}).click();await ready;await expect(page.locator('.ops-stats')).toBeVisible();
  }else await page.locator('.ops-nav').getByRole('button',{name,exact:true}).click();
  await expect(page.locator('.ops-main h1')).toHaveText(name);await expect(page.getByRole('alert')).toHaveCount(0);await capture(name==='Ringkasan'?'Dashboard operasi':name);
 }
 await page.getByRole('button',{name:'Akaun',exact:true}).click();const d=page.getByRole('dialog',{name:'Pentadbiran sistem'});await expect(d).toBeVisible();
 for(const name of ['Kampus','Bangunan','Tingkat']){await d.getByRole('button',{name,exact:true}).click();await capture('Pentadbiran lokasi - '+name);}
 for(const name of ['Pengguna','Audit','Akaun']){
  const ready=name==='Akaun'?null:page.waitForResponse(r=>r.url().includes('/api/v1/'+(name==='Pengguna'?'users':'audit'))&&r.status()===200);
  await d.getByRole('button',{name,exact:true}).click();if(ready)await ready;
  if(name==='Pengguna')await expect(d.locator('tbody tr').first()).toBeVisible();
  if(name==='Audit')await expect(d.locator('tbody tr').first()).toBeVisible();
  await capture('Pentadbiran - '+name);
 }
 expect(errors).toEqual([]);expect(items).toHaveLength(27);
 writeFileSync('module-screenshots/index.json',JSON.stringify({source:'Running React/PHP/MariaDB application; disposable demo and acceptance-test data',viewport:'1440 x 1000',items},null,2));
});
