# REST API v1

Base: `/api/v1`. Semua respons JSON. Semua operasi tulis memerlukan cookie sesi dan header `X-CSRF-Token` daripada `GET /csrf`. Tiada token/rahsia dalam URL.

| Method | Endpoint | Akses | Tujuan |
|---|---|---|---|
| GET | `/health` | Awam | Semak sambungan database |
| GET | `/csrf` | Awam | Cipta sesi dan dapatkan CSRF |
| POST | `/login` | Awam + CSRF | `{email,password}` |
| GET | `/me` | Login | Pengguna semasa + token CSRF |
| POST | `/logout` | CSRF | Tamatkan sesi |
| GET | `/bootstrap` | Semua peranan | Lokasi, pengguna semasa, ruang aktif |
| GET | `/rooms` | Semua peranan | Ruang aktif |
| POST | `/rooms` | Admin/manager | Daftar rekod |
| PUT | `/rooms/{id}` | Admin/manager | Ganti medan ruang, perlu `version` |
| DELETE | `/rooms/{id}` | Admin/manager | Arkib: `{version,reason}` |
| GET | `/audit?before={id}` | Admin/manager | Maksimum 100 per halaman, `next` untuk halaman seterusnya |
| POST | `/campuses` | Admin | `{code,name}` |
| POST | `/buildings` | Admin | `{code,name,campus}` |
| POST | `/floors` | Admin | `{code,name,building,width,height}` dalam meter |
| GET | `/users` | Admin | Daftar pengguna, tiada hash |
| POST | `/users` | Admin | `{name,email,password,role}` |
| PATCH | `/users/{id}` | Admin | `{role,active}`; membatalkan sesi lama |
| POST | `/password` | Login | `{current,password}`; membatalkan sesi lain |

## Ruang

Contoh payload untuk tingkat kosong 42 × 21 m:

```json
{
  "building": "AKD", "floor": "01", "code": "01-A",
  "name": "Pejabat Pentadbiran", "type": "Pejabat",
  "category": "OCCUPIABLE", "department": "Pentadbiran",
  "division": "Pengurusan", "standard": "OFF-02",
  "capacity": 2, "occupants": 1, "status": "Digunakan",
  "manual": 12, "height": 3.2,
  "finish": "Jubin seramik", "ceiling": "Siling akustik",
  "ac": true, "accessible": true, "prorate": "Jabatan",
  "survey": "2026-11-09", "pic": "Pegawai ruang", "notes": "",
  "points": [[0,0],[4,0],[4,3],[0,3]]
}
```

Respons: `{"room":{...,"id":"rm_...","area":12,"version":1}}`. `area`, `source`, `id`, `updated`, `archived` ditentukan server. PUT ialah payload lengkap dengan versi terakhir, bukan patch separa. Lokasi tidak boleh dipindahkan melalui PUT; daftar rekod di lokasi baharu dan arkib rekod lama dengan catatan.

`points: null` dibenarkan; luas efektif menggunakan `manual`. `category`: `OCCUPIABLE`, `SERV`, `CIRC`, `VERT`. `status`: `Digunakan`, `Tidak digunakan`. `prorate`: `Jabatan`, `Guna sama tingkat`, `Guna sama bangunan`.

## Ralat

Bentuk: `{"error":"mesej","details":[]}`. Status: 401 login diperlukan; 403 peranan ditolak; 404 rekod tiada; 409 kod pendua/versi berubah; 419 CSRF tidak sah; 422 validasi; 429 had login; 503 masalah database. Ralat dalaman tidak memulangkan SQL atau maklumat sambungan kepada browser.
