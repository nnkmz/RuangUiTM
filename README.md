# Fasiliti Suite — Fullstack Ruang & Operasi

React + PHP 8.3 (PDO REST API) + MariaDB. Versi 0.4.0-rc.1, 9 November 2026.

**STATUS HOLD:** checkpoint pembangunan, belum serahan akhir. Baca `docs/RELEASE_GATE.md`; pengguna meminta muat turun hanya selepas semua syarat pelepasan lulus.

Ini pelaksanaan fullstack bagi **inventori ruang dan modul operasi teras**, mengikut aliran Space Console yang telah dirujuk dalam dokumentasi ARCHIBUS. Ia bukan keseluruhan suite ARCHIBUS dan bukan kod ARCHIBUS. Backend menggunakan PHP native modular, bukan Laravel; tiada Composer diperlukan.

## Fungsi yang telah dibina

Selain senarai berikut, modul aset, aduan/arahan kerja, PM/SLA, stok, pembekal, kontrak, tempahan, bajet/caj ruang, meter/utiliti, notifikasi dan lampiran telah dibina. Lihat `docs/MODULES.md`.

- Login/logout sebenar, kata laluan hash, sesi HTTP-only, CSRF, had cubaan login, sesi tamat dan tukar kata laluan.
- Peranan pentadbir, pengurus ruang dan pembaca, dikuatkuasakan dalam API.
- Daftar kampus, bangunan, tingkat dan dimensi pelan. Tambah pengguna; ubah peranan atau aktif/nyahaktif.
- Daftar/sunting/arkib ruang; identiti, lokasi, kategori, jenis, standard, bahagian, PTJ, pegawai, kapasiti, penghuni, luas manual, tinggi, kemasan, siling, akses OKU, pendingin udara, agihan kos, tarikh ukur dan catatan.
- Pelan SVG interaktif 2D/isometrik dihubungkan dengan rekod MariaDB; zoom, pan, label, warna dan jadual daftar.
- Tambah sempadan segi empat atau sunting koordinat bucu poligon. Keluasan dikira semula di server, tanpa menerima nilai luas poligon daripada client.
- Tolak sempadan bersilang, bertindih, terlalu kecil atau berada di luar tingkat. Dinding yang dikongsi dibenarkan.
- Kawalan versi rekod untuk mengesan suntingan serentak; transaksi perubahan dan audit.
- Carian, penapis, pilihan kolum, ringkasan keluasan, eksport CSV dan pelan SVG.
- Migrasi awal, seed demo pilihan, Docker Compose, skrip backup/restore serta ujian API.

## Jalankan menggunakan Docker

Perlu Docker Engine/Desktop dengan Compose plugin.

1. Salin `.env.example` menjadi `.env`.
2. Isi `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ADMIN_EMAIL` dan `ADMIN_PASSWORD` (12–72 bait). Gunakan nilai berbeza yang dijana sendiri. Tiada akaun/kata laluan lalai.
3. Bina dan hidupkan perkhidmatan:

```sh
docker compose up -d --build
```

4. Wujudkan jadual dan akaun pertama. Pilih salah satu:

```sh
# Lokasi asas dan daftar ruang kosong
docker compose --profile tools run --rm setup

# ATAU dengan 96 ruang contoh pada 2 bangunan × 3 tingkat
docker compose --profile tools run --rm setup --demo
```

5. Buka `http://localhost:8080`, login menggunakan akaun yang anda tetapkan.

Setup idempotent: ia tidak menetapkan semula kata laluan pengguna sedia ada dan tidak menimpa ruang dengan kod yang sama. Seed demo hanya sesuai untuk database pembangunan; jangan campurkan data demonstrasi dengan data ukur sebenar.

`.env` mengandungi rahsia. Jangan masukkan ke Git atau kongsi dalam eksport projek. Selepas akaun pertama dibuat, simpan kata laluan pentadbir dengan selamat dan padam `ADMIN_PASSWORD` daripada fail operasi jika setup tidak lagi diperlukan. Skrip setup berikutnya dengan pengguna sedia ada tidak mengubah kata laluan tersebut.

## PHP tempatan / Herd / server sedia ada

1. Sediakan PHP 8.3+ dengan `pdo_mysql` dan MariaDB 10.11+; cipta database utf8mb4 serta pengguna database khusus.
2. Isi `.env`: tambah `DB_HOST=127.0.0.1`, `DB_PORT=3306` mengikut server. Tetapkan `COOKIE_SECURE=0` hanya pada HTTP localhost.
3. Jalankan `php backend/bin/setup.php --demo` dari root projek.
4. Backend pembangunan: `php -S 127.0.0.1:8081 -t backend/public backend/public/router.php`.
5. Terminal lain: `cd frontend`, `npm ci`, `npm run dev`. Buka URL Vite. Proxy `/api` menuju PHP pada port 8081.

PHP built-in server untuk pembangunan sahaja. Produksi menggunakan Nginx + PHP-FPM; rujuk `docker/nginx.conf`. Sesi mesti mempunyai direktori simpanan yang boleh ditulis oleh pengguna PHP.

## Setup server produksi

- Gunakan HTTPS; tetapkan `COOKIE_SECURE=1`. Docker contoh hanya mendedahkan web pada `127.0.0.1:8080`, untuk reverse proxy HTTPS di hos.
- Database dan PHP-FPM tidak diterbitkan sebagai port awam. Gunakan pengguna DB khusus, backup berkala dan volum sesi.
- Deploy daripada imej yang dibina, jalankan setup sekali dan uji login/aliran simpanan sebelum membuka akses.
- Halaman Sites yang dibina sebelum ini kekal mockup. Pakej PHP/MariaDB ini **belum dideploy ke server produksi**. Hosting Sites tidak menjalankan backend PHP dalam pakej ini.
- Uji pemulihan backup pada database berasingan sebelum penggunaan sebenar. Jangan jalankan skrip ujian integrasi terhadap data produksi.

## Ujian

```sh
php backend/tests/geometry.php
# Terhadap server ujian yang mempunyai seed demo dan akaun ujian:
TEST_ADMIN_EMAIL=... TEST_ADMIN_PASSWORD=... python3 backend/tests/integration.py
cd frontend && npm ci && npm run build
```

Lihat `docs/TESTING.md` untuk keputusan dan had pengesahan. Test integrasi menambah serta mengarkibkan data ujian; gunakan database khusus ujian.

## Skop berikutnya

Import DWG/DXF/BIM, lukisan CAD latar, penyunting geometri melalui tetikus, standard/kategori organisasi yang boleh dikonfigurasi, peranan berskop kampus dan SSO belum disediakan. Import CSV/JSON sehingga 100 rekod setiap kelompok, lampiran, tempahan, aset, PM/SLA serta caj ruang kini dibina dalam modul operasi. Inventori mempunyai skop satu organisasi; pelapor/viewer boleh mencipta aduan serta tempahan sendiri, tetapi tidak boleh mengubah inventori.

Paparan perabot dan pintu ialah ilustrasi UI, bukan lukisan as-built. Sempadan ruang dan dimensi boleh disimpan sebenar, tetapi mesti disahkan melalui proses pengukuran institusi sebelum dijadikan rekod rasmi. Nilai penghuni ialah headcount berdaftar, bukan data sensor semasa.

## Dokumentasi

- `docs/ARCHITECTURE.md`: struktur, model data dan keselamatan.
- `docs/API.md`: endpoint dan kontrak data.
- `docs/PROGRESS.md`: kemajuan dan backlog.
- `docs/TESTING.md`: bukti ujian.
- `docs/RELEASE_GATE.md`: status HOLD dan baki syarat.
- `docs/MODULES.md`, `docs/OPERATIONS_API.md`: modul serta endpoint operasi.
- `docs/UAT.md`: senarai ujian penerimaan staging.
- `.github/workflows/ci.yml`: workflow Docker acceptance yang telah ditulis, belum dijalankan.
- `scripts/backup.sh`, `scripts/restore.sh`: operasi database.

## Rujukan & lesen komponen

- ARCHIBUS Space Console: https://help.archibus.com/user_en/Subsystems/webc/Content/web_user/space/quick_start/content/space/general_tools/space_console_overview.htm
- Room area fields: https://help.archibus.com/user_en/Subsystems/webc/Content/web_user/space/concepts/room_list_room_plan.htm
- PHP password hashing: https://www.php.net/manual/en/function.password-hash.php
- PHP sessions: https://www.php.net/manual/en/session.security.ini.php
- MariaDB JSON: https://mariadb.com/docs/server/reference/data-types/string-data-types/json

React/React DOM dan Vite: MIT. lucide-react: ISC. UI menggunakan fallback font sistem; tiada permintaan Google Fonts atau aset CDN diperlukan. PHP: PHP License. MariaDB Server: GPLv2. Nginx: BSD-style. Kod aplikasi ini ditulis untuk projek pengguna; lesen penerbitan/komersial bagi kod sendiri belum diputuskan. Semak lesen dan notis setiap dependency/imej sebelum pengedaran produk.
