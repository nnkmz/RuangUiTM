# GitHub Actions — persediaan dan pelaksanaan

Tarikh: 9 November 2026.

- Akaun GitHub disahkan: `nnkmz`.
- Repositori private: `nnkmz/RuangUiTM`, dengan README awal.
- Akses sambungan kepada repo kini disahkan, termasuk pull dan push.
- Kod dan workflow disediakan untuk push pertama. Keputusan CI belum tersedia pada masa commit ini.

## Workflow yang siap disemak

`.github/workflows/ci.yml`:

1. Checkout dan setup Node menggunakan versi action yang dirujuk dalam dokumentasi rasmi semasa.
2. Build/start Docker Compose dengan database, PHP, web dan scheduler.
3. Jalankan setup dua kali untuk menguji idempotency; hentikan scheduler semasa ujian terkawal.
4. Tunggu endpoint health sebelum ujian.
5. Jalankan ujian geometri, API ruang dan operasi, parser CSV, render React dan build produksi.
6. Backup SQL + lampiran; restore dan semak health.
7. Log stack jika gagal, kemudian bersihkan container/volum CI.

Ralat `printf 'RESTORE\\n'` telah dibetulkan kepada satu escape newline. Output input restore diperiksa byte demi byte sebagai `RESTORE` diikuti LF. Semua blok shell lulus `bash -n`; YAML workflow diparse dan permission token dihadkan kepada `contents: read`.

## Perlu diselesaikan

Pantau run selepas push hingga tamat dan baiki kegagalan sebelum syarat Docker ditandakan lulus. Kekalkan repo private.

Fail ini merekod persediaan, bukan keputusan CI berjaya. Gate pelepasan kekal HOLD.
