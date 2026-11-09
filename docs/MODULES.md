# Modul keluaran teras v0.4 RC

| Modul | Fungsi dibina | Peraturan utama |
|---|---|---|
| Identiti & pentadbiran | Login, sesi, CSRF, pengguna, peranan, tukar kata laluan | Hash; tamat sesi; pembatalan sesi ketika nyahaktif/role berubah |
| Inventori ruang | Lokasi, pelbagai medan, pelan 2D/isometrik, import CSV/JSON, eksport | FK, versi, geometri disahkan server, import atomik |
| Aset | Daftar, keadaan, waranti, perolehan, lokasi dan sejarah pindah | Tiada pindah ketika kerja terbuka |
| Aduan | Lokasi/aset, keutamaan, semakan, sebab penolakan, lampiran | Pemilik/pengurus/auditor sahaja boleh membaca aduan |
| Arahan kerja | Penugasan, log masa, bahan, pengesahan/pulangan/pembatalan | Status terkawal; juruteknik ditugaskan; pengurus mengesahkan |
| PM & SLA | Pelan berulang, checklist, scheduler, snapshot tarikh akhir | Occurrence unik; checklist wajib; SLA jam berlalu 24/7 |
| Stok | Daftar, terima, laras dan guna pada kerja; kos purata | Row lock; stok tidak negatif; kos transaksi disimpan |
| Pembekal/kontrak | Daftar, tarikh dan nilai; pautan kerja | Kontrak aktif dalam tempoh sebelum digunakan |
| Tempahan | Permohonan, kelulusan, penolakan, pembatalan | Lock ruang, pertindihan masa dan kapasiti disemak |
| Kos/bajet/caj ruang | Buruh+bahan, bajet bulan, caj per m² | Snapshot luas, pembundaran wang, caj sekali per ruang/bulan |
| Utiliti | Meter, bacaan kumulatif, penggunaan beza bacaan | Bacaan mengikut masa dan tidak menurun |
| Audit/notifikasi | Jejak perubahan; notifikasi dalam aplikasi | Bukan penghantaran e-mel/WhatsApp; private ikut pengguna |
| Bukti & pemulihan | Lampiran private, backup database+fail, restore | MIME/saiz; hak akses setiap muat turun; checksum |

## Peranan

Admin: semua pengurusan. Manager: operasi dan ruang; tidak mengurus pengguna/lokasi. Technician: kerja ditugaskan, log/bahan, bacaan dan laporan kerosakan. Reporter/viewer: aduan sendiri dan tempahan sendiri, inventori baca sahaja. Auditor: laporan/audit serta operasi baca sahaja.

Inventori lokasi/aset ialah satu organisasi bersama. Scoping berasingan antara kampus/jabatan belum dilaksanakan. Jangan gunakan untuk organisasi yang memerlukan partition data sebelum ciri itu dibina.
