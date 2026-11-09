# Release gate — v0.4.0-rc.1

**Status: HOLD — belum diluluskan untuk pengguna memuat turun atau go-live.**

Pengguna meminta semua modul dilengkapkan dan diuji sebelum serahan. Fail v0.3 terdahulu tidak mewakili keluaran lengkap. Keluaran dalaman ini menambah modul operasi kepada inventori ruang, dan CI Docker kini lulus. Ujian browser automatik terpilih kini lulus; pengesahan staging masih diperlukan.

| Syarat | Bukti | Status |
|---|---|---|
| Ruang, geometri, role & sesi | 13 assertion geometri + 48 assertion integrasi | Lulus |
| Aliran aset, aduan/kerja, PM, stok, kontrak, tempahan, kos dan utiliti | 153 assertion operasi pada PHP/MariaDB sebenar | Lulus |
| Import CSV/JSON | 5 ujian parser + import atomik di API | Lulus |
| Paparan React | 28 ujian render server, build produksi Vite | Lulus untuk semakan kod; bukan browser |
| Backup/restore | 27 jadual dipulihkan, kandungan setiap baris sepadan; fail private sepadan byte demi byte | Lulus pada runtime ujian |
| Pemasangan Docker Compose | GitHub Actions run #1, commit `52a8176`: build/start, setup dua kali, API, frontend, backup/restore dan health | Lulus |
| Browser Chromium desktop/mobile | Actions run #4: 21 ujian desktop/emulasi 360/430 px, screenshot dan laporan; rujuk BROWSER_TESTING.md | Lulus untuk aliran diuji; UAT staging/peranti fizikal berbaki |
| HTTPS/cookie, restart dan penciptaan semula container, scheduler, pemulihan selepas data diubah | Actions run #7: 22 semakan deployment; 27 jadual dan 5 fail upload sepadan | Lulus dalam CI Docker |
| Domain/sijil/rangkaian dev server, UAT institusi dan peranti fizikal | Memerlukan server sasaran serta pengesahan pengguna | BELUM DISAHKAN |

## Keputusan serahan

Jangan label keluaran ini lengkap, production-ready atau setara penuh dengan ARCHIBUS. Jangan serahkan pakej sebagai muat turun akhir sebelum syarat yang tertangguh diluluskan. Checkpoint kod dibenarkan untuk menjaga hasil kerja.

## Keperluan untuk menyelesaikan gate

CI Docker kini melepasi ujian teknikal yang disenaraikan. Sediakan URL dan konfigurasi dev server untuk pengesahan sasaran; ini belum diberikan. Jalankan `docs/UAT.md`, termasuk refresh/persistence, restart servis, backup/restore, akses silang peranan, desktop dan mobile. Sebarang kegagalan perlu dibetulkan dan diuji semula.

## Integrasi berasingan

Keluaran teras ini tidak menyediakan DWG/DXF/BIM, SSO institusi, e-mel/push luaran, sensor live, accounting pajakan atau partition data berbilang organisasi/kampus. Ini memerlukan skop, fail contoh atau perkhidmatan sasaran yang sebenar. Modul kos ialah rekod operasi dan caj ruang, bukan lejar perakaunan.
