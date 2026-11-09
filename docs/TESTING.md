# Keputusan ujian v0.4 RC

Runtime sebenar: PHP 8.3.6/PDO MySQL + MariaDB 10.11.14. Database ujian berasingan, bukan mock DB. Tiada data produksi digunakan.

## Lulus

- **13 assertion geometri:** luas segi empat/L, arah bucu, dinding kongsi, persilangan, containment, lekuk, sifar, bucu pendua dan batas tingkat.
- **48 assertion ruang/identiti:** autentikasi/CSRF/role, simpanan, versi konflik, arkib, audit, kata laluan, nyahaktif sesi dan lokasi.
- **Operasi:** lihat bilangan akhir dalam `TEST_RESULTS.txt`. Aliran pelapor → pengurus → juruteknik → pengesah dengan dua kerja, stok tidak negatif/rollback, kos bahan+buruh, pindah aset, scheduler idempotent/checklist, kelulusan tempahan/pertindihan/kapasiti, caj snapshot, bajet, bacaan kumulatif, lampiran, pemilikan, notifikasi dan import atomik.
- **5 ujian CSV:** roundtrip koma/petikan/baris baharu/poligon; input salah/medan pendua/saiz kelompok.
- **28 ujian render React:** 14 paparan operasi, 10 panel rekod, konsol ruang, login, importer dan pentadbiran. Ini ujian React render pada server; ia tidak menguji event browser, hydration atau rupa responsif.
- **Backup/restore:** `BACKUP_TEST.json` merekod bilangan jadual, perbandingan kandungan setiap baris dan perbandingan byte setiap lampiran yang dipulihkan.
- **Fresh install/idempotency:** `INSTALL_TEST.json` merekod 96 ruang, satu admin dan dua migrasi selepas setup dijalankan dua kali pada DB kosong.
- Sintaks semua PHP, production build Vite, YAML Compose lima servis dan sintaks skrip backup/restore lulus.
- Audit npm dependency produksi: keputusan mesin disimpan dalam `NPM_AUDIT.json`. Ini semakan advisori dependency, bukan jaminan keselamatan aplikasi.

## Tertangguh — jangan nyatakan lulus

Docker image/Compose, HTTPS reverse proxy, volum/scheduler selepas restart, browser desktop/mobile, aksesibiliti interaktif dan UAT institusi belum lulus. Browser automation ditolak polisi persekitaran; tiada alternatif browser digunakan untuk memintasnya. Penetration test dan ujian beban juga belum dilakukan.

Lihat `RELEASE_GATE.md`. `.github/workflows/ci.yml` ialah fail workflow untuk ujian Docker kelak; kewujudannya bukan bukti CI telah berjaya.
