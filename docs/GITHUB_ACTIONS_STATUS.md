# GitHub Actions — keputusan CI

Tarikh: 9 November 2026.

- Repo private: `nnkmz/RuangUiTM`.
- Commit diuji: `e38ec6100826137b8f9cb4d00a519b0f04acabc6`.
- [Run #7](https://github.com/nnkmz/RuangUiTM/actions/runs/42576424220): **completed / success**.
- Docker Compose build/start, setup dua kali, PHP 13 geometri + 48 integrasi + 153 operasi: lulus.
- Frontend: 5 parser + 28 render, build produksi: lulus.
- Browser Chromium: 21/21 pada desktop dan emulasi telefon 360/430 px; screenshot/report dalam artifact `browser-evidence`.
- Backup SQL dan lampiran, checksum, restore, health selepas restore: lulus.
- Deployment: 22 semakan HTTPS/cookie, restart, scheduler, semua 27 jadual dan 5 upload selepas recovery, serta container recreation: lulus. Rujuk `DEPLOYMENT_TESTING.md` dan `DEPLOYMENT_RESULTS.json`.
- Cleanup stack dan volum CI: lulus.

Keputusan ini membuktikan aliran browser automatik terpilih; HTTPS, restart dan scheduler diuji pada CI Docker. Domain/sijil/rangkaian sebenar dan operasi berterusan pada dev server masih memerlukan pengesahan sasaran. Rujuk `BROWSER_TESTING.md` untuk liputan dan had. Gate pelepasan kekal HOLD sehingga UAT staging selesai. Rujuk `RELEASE_GATE.md` dan `UAT.md`.
