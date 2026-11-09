# API operasi v1

Awalan `/api/v1/ops`. Semua operasi tulis memerlukan cookie sesi dan `X-CSRF-Token`. Kontrak data utama disimpan dalam `definitions()` di `backend/src/Operations.php` dan dikongsi dengan UI bagi borang generik.

| Endpoint | Method | Akses / fungsi |
|---|---|---|
| `/` | GET | Dataset mengikut peranan dan pemilikan; tiada password hash |
| `/suppliers`, `/assets`, `/contracts`, `/parts`, `/policies`, `/budgets`, `/meters` | POST | Admin/manager, daftar |
| Endpoint katalog di atas + `/{id}` | PUT | Admin/manager, rekod penuh dan versi wajib; policies menggunakan kod priority |
| `/assets/{id}/move` | POST | `{version,room_id,reason}`; rekod lokasi dan audit |
| `/requests` | POST | `{room_id,asset_id?,subject,description,priority}` |
| `/requests/{id}/review` | POST | Pengurus: `{version,action:accept/reject,reason}` |
| `/work` | POST | Pengurus: `{request_id?,asset_id?,room_id,title,assigned_to,contract_id?,due_at?}` |
| `/work/{id}/status` | POST | `{version,action:start/submit/verify/return/cancel,reason?,checks?}` |
| `/work/{id}/logs` | POST | Juruteknik ditugaskan/pengurus: `{minutes,hourly_rate,note}` |
| `/stock` | POST | `{part_id,work_order_id?,quantity,unit_cost,reason}`; kuantiti negatif untuk penggunaan |
| `/plans`, `/plans/{id}` | POST | Pengurus: `{name,asset_id,assigned_to,interval_days,next_due,checklist,active,version?}` |
| `/scheduler` | POST | Pengurus: jana kerja matang dengan occurrence unik |
| `/bookings` | POST | `{room_id,title,starts,ends,attendees}` |
| `/bookings/{id}/status` | POST | `{version,action:approve/reject/cancel,reason}` |
| `/charges` | POST | Pengurus: `{room_id,period,rate}`; snapshot luas dan amount di server |
| `/readings` | POST | Pengurus/juruteknik: `{meter_id,recorded_at,value}` |
| `/notifications/read` | POST | Tandakan notifikasi pengguna semasa dibaca |
| `/reports?period=YYYY-MM` | GET | Pengurus/auditor: kos, bajet, caj, status, SLA dan stok minimum |
| `/attachments` | POST multipart | `entity_type`, `entity_id`, `file`; JPEG/PNG/WebP/PDF, ≤5 MB |
| `/attachments/{id}` | GET | Semak akses setiap muat turun; Content-Disposition attachment |

## Masa dan wang

Borang operasi menyatakan UTC secara jelas. `datetime-local` dihantar sebagai `YYYY-MM-DDTHH:MM` dan ditafsirkan UTC; tarikh tanpa masa menggunakan `YYYY-MM-DD`. SLA adalah jam elapsed 24/7, bukan waktu pejabat/cuti.

Wang menerima maksimum dua perpuluhan dan disimpan DECIMAL. Bahan menggunakan kos purata stok semasa pada masa keluar. Work order menyimpan rekod buruh dan penggunaan bahan secara immutable. Caj ruang bukan lejar kewangan atau invois pembayaran.

## Lampiran

Lampiran berada di `backend/storage/uploads` atau `UPLOAD_DIR`; bukan folder awam. Nama storan rawak, MIME sebenar diperiksa, SVG/HTML tidak diterima. Endpoint mengesahkan pemilikan aduan / penugasan kerja / peranan pengurus sebelum baca atau tulis. Audit menyimpan ID fail, bukan kandungan fail.

## Import ruang

`POST /api/v1/rooms/import` menerima `{"rooms":[...]}` (1–100 rekod lengkap, had payload JSON 256 KB). Pengesahan dan audit berada dalam satu transaksi. Tiada upsert senyap; kod ruang sedia ada menyebabkan keseluruhan kelompok rollback. Template CSV dan parser disediakan dalam UI.
