import {test, expect} from '@playwright/test';
const email=process.env.TEST_ADMIN_EMAIL, password=process.env.TEST_ADMIN_PASSWORD;
async function login(page,mail=email,pass=password){
  await page.goto('/');
  await page.getByLabel('E-mel',{exact:true}).fill(mail);
  await page.getByLabel('Kata laluan',{exact:true}).fill(pass);
  await page.getByRole('button',{name:'Log masuk',exact:true}).click();
  await expect(page.getByRole('heading',{name:/Space Console/})).toBeVisible();
}
async function operations(page){await page.getByRole('button',{name:'Operasi fasiliti',exact:true}).click();await expect(page.locator('.ops-main h1')).toHaveText('Ringkasan');}
async function shot(page,info,name){await page.screenshot({path:info.outputPath(name+'.png'),fullPage:true});}
async function fits(page){expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);}
function unique(info){return info.project.name+'-'+Date.now();}
test.beforeEach(async({page})=>{page._errors=[];page.on('pageerror',e=>page._errors.push(e.message));});
test.afterEach(async({page},info)=>{await shot(page,info,'result');expect(page._errors).toEqual([]);});

test('login errors, keyboard submit, session persistence and logout',async({page})=>{
  await page.goto('/');await page.getByLabel('E-mel',{exact:true}).fill(email);await page.getByLabel('Kata laluan',{exact:true}).fill('Incorrect-password-123');
  await page.getByRole('button',{name:'Log masuk',exact:true}).click();await expect(page.getByRole('alert')).toBeVisible();
  await page.getByLabel('Kata laluan',{exact:true}).fill(password);await page.getByLabel('Kata laluan',{exact:true}).press('Enter');
  await expect(page.getByRole('heading',{name:/Space Console/})).toBeVisible();await page.reload();await expect(page.getByRole('heading',{name:/Space Console/})).toBeVisible();
  await page.getByRole('button',{name:'Pentadbiran',exact:true}).click();const dialog=page.getByRole('dialog');await dialog.getByRole('button',{name:'Akaun',exact:true}).click();await dialog.getByRole('button',{name:'Log keluar',exact:true}).click();
  await expect(page.getByRole('button',{name:'Log masuk',exact:true})).toBeVisible();await fits(page);
});

test('2D and isometric selection, keyboard, zoom, pan and SVG/CSV exports',async({page},info)=>{
  await login(page);await fits(page);
  const rooms=page.locator('svg [data-room]');await expect(rooms.first()).toBeVisible();const code=(await rooms.nth(1).getAttribute('aria-label')).split(' ')[0];
  await rooms.nth(1).focus();await page.keyboard.press('Enter');await expect(page.locator('.inspector h2')).toHaveText(code);
  const area=await page.locator('.area-box strong').innerText();await shot(page,info,'plan-2d');
  await page.getByRole('button',{name:'Isometrik',exact:true}).click();await expect(page.locator('.drawing-tag')).toContainText('UNJURAN ISOMETRIK');await expect(page.locator('.area-box strong')).toHaveText(area);
  await page.getByRole('button',{name:'Zum masuk',exact:true}).click();await expect(page.locator('.canvas-tools')).toContainText('120%');
  await page.getByRole('button',{name:'Muatkan pelan penuh'}).click();await expect(page.locator('.canvas-tools')).toContainText('100%');
  await page.getByRole('button',{name:'Gerakkan pelan',exact:true}).click();const svg=page.getByRole('img',{name:/Pelan interaktif/});const box=await svg.boundingBox();const g=svg.locator(':scope > g').first(),before=await g.getAttribute('transform');
  await page.mouse.move(box.x+box.width/2,box.y+box.height/2);await page.mouse.down();await page.mouse.move(box.x+box.width/2+25,box.y+box.height/2+15);await page.mouse.up();await expect(g).not.toHaveAttribute('transform',before);
  await shot(page,info,'plan-isometric');
  const svgDownload=page.waitForEvent('download');await page.getByRole('button',{name:'Eksport pelan SVG',exact:true}).click();expect((await svgDownload).suggestedFilename()).toMatch(/Isometrik\.svg$/);
  const csvDownload=page.waitForEvent('download');await page.getByRole('button',{name:'Eksport daftar',exact:true}).click();expect((await csvDownload).suggestedFilename()).toMatch(/\.csv$/);
});

test('room creation, update and database persistence after reload',async({page},info)=>{
  await login(page);const code='PW-'+unique(info);await page.getByRole('button',{name:'Daftar ruang',exact:true}).last().click();const d=page.getByRole('dialog',{name:'Borang daftar ruang'});
  await d.getByLabel('Kod ruang',{exact:true}).fill(code);await d.getByLabel('Nama ruang',{exact:true}).fill('Bilik ujian browser');await d.getByLabel('Keluasan manual (m²)',{exact:true}).fill('12.5');
  await d.getByRole('button',{name:'Simpan ruang',exact:true}).click();await expect(d).toBeHidden();await expect(page.locator('.inspector h2')).toHaveText(code);await expect(page.locator('.area-box strong')).toContainText('12.50');
  await page.getByRole('button',{name:/Kemaskini semua medan/}).click();await d.getByLabel('Nama ruang',{exact:true}).fill('Bilik dikemaskini '+code);await d.getByRole('button',{name:'Simpan ruang',exact:true}).click();await expect(d).toBeHidden();
  await page.reload();await page.getByRole('textbox',{name:'Cari ruang',exact:true}).fill(code);await page.getByRole('button',{name:code,exact:true}).click();await expect(page.locator('.inspector h3')).toHaveText('Bilik dikemaskini '+code);await fits(page);
});

test('CSV import with quoted comma and invalid input rejection',async({page},info)=>{
  await login(page);const boot=await (await page.request.get('/api/v1/bootstrap')).json();const room=boot.rooms[0],code='CSV-'+unique(info);
  await page.getByRole('button',{name:'Import ruang',exact:true}).click();const d=page.getByRole('dialog',{name:'Import ruang CSV atau JSON'});
  await d.locator('input[type=file]').setInputFiles({name:'invalid.csv',mimeType:'text/csv',buffer:Buffer.from('code,name\nA,"unclosed')});await expect(d.getByRole('alert')).toContainText('Petikan');await expect(d.getByRole('button',{name:'Sahkan & import'})).toBeDisabled();
  const csv='building,floor,code,name,type,category,department,manual\n'+`${room.building},${room.floor},${code},"Bilik, ujian CSV",Pejabat,OCCUPIABLE,Pentadbiran,15\n`;
  await d.locator('input[type=file]').setInputFiles({name:'rooms.csv',mimeType:'text/csv',buffer:Buffer.from(csv)});await expect(d.getByRole('cell',{name:'Bilik, ujian CSV',exact:true})).toBeVisible();await d.getByRole('button',{name:'Sahkan & import'}).click();await expect(d).toBeHidden();
  await page.getByRole('textbox',{name:'Cari ruang',exact:true}).fill(code);await expect(page.getByRole('button',{name:code,exact:true})).toBeVisible();
});

test('all operations modules and responsive navigation load live data',async({page},info)=>{
  await login(page);await operations(page);
  for(const name of ['Daftar aset','Aduan','Arahan kerja','Penyelenggaraan','Tempahan ruang','Stok alat ganti','Pembekal','Kontrak','Kos & caj ruang','Meter utiliti','Laporan','Dasar SLA','Notifikasi']){
    await page.locator('.ops-nav').getByRole('button',{name,exact:true}).click();await expect(page.locator('.ops-main h1')).toHaveText(name);await expect(page.getByRole('alert')).toHaveCount(0);await fits(page);
  }
  await shot(page,info,'operations');
  await page.getByRole('button',{name:'Inventori & pelan ruang',exact:true}).click();await expect(page.getByRole('heading',{name:/Space Console/})).toBeVisible();
});

test('submit maintenance request, review it and upload a private attachment',async({page},info)=>{
  await login(page);await operations(page);await page.locator('.ops-nav').getByRole('button',{name:'Aduan',exact:true}).click();await page.getByRole('button',{name:'Aduan baharu'}).click();const d=page.getByRole('dialog',{name:'Lapor kerosakan'}),subject='Browser '+unique(info);
  await d.getByLabel('Ruang',{exact:true}).selectOption({index:1});await d.getByLabel('Tajuk aduan',{exact:true}).fill(subject);await d.getByLabel('Keterangan',{exact:true}).fill('Lampu perlu diperiksa melalui aliran browser.');await d.getByLabel('Keutamaan',{exact:true}).selectOption('Rendah');await d.getByRole('button',{name:'Simpan & sahkan'}).click();await expect(d).toBeHidden();
  await page.getByRole('row').filter({hasText:subject}).click();const drawer=page.getByRole('dialog',{name:'Butiran rekod operasi'});await expect(drawer.getByRole('heading',{name:subject,exact:true})).toBeVisible();await drawer.getByRole('button',{name:'Terima aduan',exact:true}).click();const review=page.getByRole('dialog',{name:'Terima aduan'});await review.getByLabel('Catatan semakan').fill('Diterima melalui ujian browser');await review.getByRole('button',{name:'Simpan & sahkan'}).click();await expect(review).toBeHidden();await expect(drawer).toContainText('Diterima');
  await drawer.locator('input[type=file]').setInputFiles({name:'browser.png',mimeType:'image/png',buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+afo0AAAAASUVORK5CYII=','base64')});await expect(drawer.getByRole('link',{name:'browser.png'})).toBeVisible();
  const href=await drawer.getByRole('link',{name:'browser.png'}).getAttribute('href');const response=await page.request.get(href);expect(response.status()).toBe(200);expect(response.headers()['content-type']).toContain('image/png');
});

test('viewer has no write access and administration supports account/logout',async({page},info)=>{
  await login(page);await page.getByRole('button',{name:'Pentadbiran',exact:true}).click();const d=page.getByRole('dialog',{name:'Pentadbiran sistem'});await d.getByRole('button',{name:'Pengguna',exact:true}).click();const mail=unique(info)+'@example.test';
  await d.getByLabel('Nama',{exact:true}).fill('Browser Viewer');await d.getByLabel('E-mel',{exact:true}).fill(mail);await d.getByLabel('Kata laluan awal',{exact:true}).fill('Browser-Viewer-Pass-2026');await d.getByLabel('Peranan',{exact:true}).selectOption('viewer');await d.getByRole('button',{name:'Tambah pengguna',exact:true}).click();await expect(d.getByRole('status')).toBeVisible();
  await d.getByRole('button',{name:'Akaun',exact:true}).click();await d.getByRole('button',{name:'Log keluar',exact:true}).click();await login(page,mail,'Browser-Viewer-Pass-2026');await expect(page.getByRole('button',{name:'Import ruang',exact:true})).toHaveCount(0);await expect(page.getByRole('button',{name:/Kemaskini semua medan/})).toBeDisabled();
  await page.getByRole('button',{name:'Daftar ruang',exact:true}).last().click();await expect(page.getByRole('status')).toContainText('tidak dibenarkan');await expect(page.getByRole('dialog')).toHaveCount(0);
});
