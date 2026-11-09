# Kemajuan — 9 November 2026, v0.4 RC

Pengguna meminta modul lain dilengkapkan serta diuji sebelum serahan. Kerja v0.3 diteruskan kepada suite operasi teras. **Status pelepasan kekal HOLD** sehingga UAT staging, HTTPS dan operasi berterusan disahkan. Docker dan browser automatik terpilih kini lulus dalam GitHub Actions.

## Dibina dan diuji pada API sebenar

Ruang/geometri/import, identiti/peranan, aset/sejarah lokasi, aduan/arahan kerja, masa/bahan/kos, PM/checklist/scheduler, SLA elapsed, stok/pembekal/kontrak, tempahan/kelulusan, bajet/caj ruang, meter/bacaan/penggunaan, audit/notifikasi dan lampiran private.

- 13 assertion geometri.
- 48 assertion integrasi ruang/identiti.
- Lihat hasil operasi terkini dalam `TEST_RESULTS.txt`.
- 5 ujian parser CSV.
- 28 ujian render React (semakan server, bukan interaksi browser).
- Backup/restore database dan lampiran: kandungan disemak, bukan sekadar fail wujud.
- Pemasangan fresh serta setup berulang diuji pada PHP/MariaDB native.
- Frontend production build, sintaks PHP, struktur Compose dan skrip shell disemak.

## Belum lulus gate

GitHub Actions run #4 (`42556336774`, commit `df64c17`) lulus Docker Compose, 214 assertion backend, 33 ujian frontend, 21 ujian Chromium desktop/emulasi telefon dan backup/restore. Screenshot/report disimpan sebagai artifact CI. Rujuk `BROWSER_TESTING.md` untuk had liputan. HTTPS, restart/persistence dan scheduler berterusan pada staging sasaran masih belum disahkan.

Kod disimpan sebagai checkpoint, bukan muat turun akhir. Rujuk `RELEASE_GATE.md` dan `UAT.md` untuk perkara yang mesti selesai.
