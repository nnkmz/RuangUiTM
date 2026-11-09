# Browser acceptance

Tarikh: 9 November 2026.

Playwright Chromium menjalankan 7 senario pada setiap konfigurasi: desktop 1440 × 1000, emulasi telefon 360 × 800 dan 430 × 932 (21 ujian). Semua permintaan menggunakan aplikasi binaan produksi dalam Docker, API PHP serta MariaDB sebenar. Tiada API mock.

## Senario

1. Login gagal/sah, submit melalui keyboard, sesi selepas reload dan logout.
2. Pilihan ruang melalui keyboard, konsistensi keluasan 2D/isometrik, zoom/pan, eksport SVG/CSV dan lebar penapis.
3. Cipta/kemaskini ruang dan semak persistence selepas reload.
4. Import CSV dengan petikan/koma serta tolak CSV rosak.
5. Navigasi semua modul operasi dengan data hidup dan semakan overflow dokumen.
6. Cipta/terima aduan serta upload/baca lampiran private melalui sesi sah.
7. Cipta akaun viewer, login dan semak tindakan tulis dihalang dalam UI.

Setiap ujian menyemak tiada ralat JavaScript tidak dikendalikan. Screenshot disimpan pada semua ujian; jejak disimpan jika gagal. Artifact `browser-evidence` mengandungi laporan HTML serta screenshot, dengan retention 14 hari.

## Kecacatan dan pembetulan

Run #2: 12 lulus, 9 gagal. Label bagi select berbalut label tidak dapat dipadankan tepat; nama butang Notifikasi turut mengandungi bilangan belum dibaca. Tambah nama akses eksplisit kepada select dan navigasi. Run #3 (commit `43efcaa`) lulus 21/21.

Semakan visual juga mengesan penapis jenis/jabatan terlalu sempit pada telefon. Susunan grid dua lajur ditambah, dengan assertion kedua-dua select sekurang-kurangnya 100 px.

## Had bukti

Emulasi telefon Chromium bukan ujian peranti Android/iPhone fizikal. Edge, Safari dan Firefox belum diuji. Ujian keyboard meliputi login dan pilihan ruang; audit aksesibiliti penuh (termasuk focus trap semua dialog) belum dijalankan. HTTPS, cookie Secure dan operasi scheduler/restart berterusan masih memerlukan staging. Rujuk `RELEASE_GATE.md` dan `UAT.md`; jangan tandakan semua UAT selesai berdasarkan keputusan ini sahaja.

## Keputusan akhir

[Run #4](https://github.com/nnkmz/RuangUiTM/actions/runs/42556336774), commit `df64c17d917f6863dd0f40b51caf95c07b0f6cab`: **completed / success**, 21/21 browser tests (40.8s). Semua langkah Docker, backend, frontend dan backup/restore turut lulus.

Run #7 (`42576424220`, commit `e38ec61`) mengulang semua 21 browser tests dengan konfigurasi proksi Docker yang dibaiki; semuanya lulus. HTTPS, restart dan scheduler kini turut diuji secara automatik dalam CI berasingan pada langkah deployment; lihat `DEPLOYMENT_TESTING.md`. Had dev server/peranti fizikal kekal.
