# UAT dan pemasangan akhir

Semua langkah ini perlu dilaksanakan pada staging sebelum status HOLD ditutup. Ujian ini bukan bukti telah lulus; tandakan keputusan sebenar dan simpan tangkapan skrin/log apabila dijalankan.

## Pemasangan

- [ ] Isi `.env` dengan rahsia baharu; `docker compose up -d --build` berjaya.
- [ ] Setup/migrasi kosong dan naik taraf v0.3 berjaya tanpa menimpa pengguna/ruang.
- [ ] HTTPS + cookie Secure berfungsi; DB/PHP tiada port awam.
- [ ] Restart web/php/db tidak kehilangan rekod atau lampiran; sesi mengikut polisi.
- [ ] Scheduler automatik menjana kerja matang sekali sahaja, selepas restart juga tiada pendua.

## Ruang

- [ ] Login pentadbir; daftar kampus/bangunan/tingkat dan pengguna bagi setiap peranan.
- [ ] Daftar ruang, kapasiti, kategori, PTJ dan sempadan; refresh browser mengekalkan rekod.
- [ ] Klik ruang dalam pelan 2D/isometrik; butiran/jadual konsisten; zoom/pan dan eksport SVG berfungsi.
- [ ] Import CSV dengan koma, petikan dan baris baharu; import tidak sah tiada rekod separa.
- [ ] Dua browser menyunting ruang sama: versi lama ditolak, perubahan sah kekal.

## Operasi

- [ ] Daftar pembekal, kontrak, aset dan stok; penerimaan menambah baki.
- [ ] Pelapor menghantar aduan dan foto/PDF. Pelapor lain tidak boleh membaca lampiran aduan tersebut.
- [ ] Pengurus menerima aduan dan mengagih dua work order.
- [ ] Juruteknik hanya boleh mengubah kerja ditugaskan; rekod masa dan bahan; stok tidak cukup ditolak.
- [ ] Juruteknik tidak boleh mengesahkan sendiri. Pengurus boleh pulangkan kerja untuk pembetulan.
- [ ] Aduan belum ditutup selepas kerja pertama sahaja; ditutup selepas semua kerja wajib disahkan.
- [ ] Pindah aset selepas kerja selesai; sejarah lokasi dan snapshot aduan kekal.
- [ ] Pelan berkala/checklist: item tidak lengkap menghalang penghantaran.
- [ ] Tempahan bertindih dan kapasiti berlebihan ditolak; kelulusan dan pembatalan pemilik berfungsi.
- [ ] Caj ruang menggunakan luas snapshot; caj pendua bulan sama ditolak; laporan kos sepadan bahan/buruh.
- [ ] Bacaan meter mengikut masa, tidak menurun; penggunaan beza bacaan tepat.
- [ ] Notifikasi aplikasi dan audit memaparkan tindakan sebenar; tiada kata laluan/token dalam audit.

## Operasi pemulihan dan UI

- [ ] `scripts/backup.sh` menghasilkan SQL, tar lampiran dan checksum.
- [ ] Restore pada database/volum staging kosong; semua rekod dan lampiran boleh dibaca.
- [ ] Uji Chrome/Edge desktop dan telefon pada lebar 360–430 px; borang dan jadual boleh digunakan.
- [ ] Uji navigasi keyboard, label borang, mesej ralat, modal dan butang simpan berganda.
- [ ] Catat kecacatan, baiki dan ulang ujian yang terkesan.
