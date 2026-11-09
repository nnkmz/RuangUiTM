# Kemajuan — 9 November 2026, v0.4 RC

Pengguna meminta modul lain dilengkapkan serta diuji sebelum serahan. Kerja v0.3 diteruskan kepada suite operasi teras. **Status pelepasan kekal HOLD** sehingga ujian Docker dan browser staging lulus.

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

Docker CLI tidak tersedia, maka image/Compose belum dijalankan di sini. Workflow CI acceptance telah disediakan untuk menjalankan stack sebenar apabila repositori/staging dipilih. Browser preview dicuba; browser kemudian menolak tindakan atas polisi akses/protokol. Tiada usaha memintas polisi dan tiada lulus browser didakwa.

Kod disimpan sebagai checkpoint, bukan muat turun akhir. Rujuk `RELEASE_GATE.md` dan `UAT.md` untuk perkara yang mesti selesai.
