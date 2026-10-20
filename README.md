# FTS Real Estate AI

Situs agen properti interaktif dengan **AI Property Advisor**. Pengunjung menjelajahi listing seperti berjalan di showroom (beranda, listing, detail properti, area sekitar, panduan membeli, agen, jadwal survei) sambil mengobrol dengan advisor AI. Advisor menjawab dari data agensi yang terkontrol, mencari properti sesuai kebutuhan, menghitung simulasi KPR, membuat permintaan jadwal survei, dan menyerahkan percakapan ke agen bila perlu. Agen mengelola semuanya lewat panel admin.

Satu aplikasi bisa melayani beberapa agensi. Setiap agensi punya halaman sendiri di `/{agencySlug}`.

## Fitur

### Sisi pengunjung
- **Halaman bertema "showroom"**: beranda, daftar listing, detail properti, area sekitar, panduan membeli, agen, dan jadwal survei, dengan narrator dan efek suara (tombol bisa dimatikan).
- **Tiga bahasa**: Indonesia (`id`), Inggris (`en`), dan Jepang (`ja`), dipilih lewat parameter `?lang=`.
- **Chat dengan AI Property Advisor**
  - Tahu di halaman mana pengunjung berada (properti atau tempat yang sedang dilihat, form survei yang sedang diisi), jadi bisa menjawab "rumah ini".
  - Menjawab dalam bahasa yang ditulis pengunjung.
  - Menampilkan kartu properti dan tempat langsung di dalam chat.
- **Simulasi KPR**: cicilan bulanan, total bunga, dan perkiraan penghasilan minimum per properti.
- **Jadwal survei**: pilih tanggal dan slot waktu, lalu kirim permintaan (status awal `pending`).

### Sisi admin (`/admin`)
- Dashboard ringkasan (listing aktif, survei menunggu, handover terbuka).
- CRUD **properti** (jual atau sewa, tipe, harga, spesifikasi, stok unit).
- CRUD **knowledge items**, yaitu basis pengetahuan yang menjadi sumber jawaban advisor (profil agensi, lingkungan sekitar, proses pembelian, KPR, legal, FAQ).
- Daftar **jadwal survei** dan ubah statusnya (`confirmed`, `completed`, `cancelled`).
- **Handover**: percakapan yang diserahkan advisor ke agen. Agen bisa membalas langsung ke pengunjung dan menandainya selesai.
- Peran pengguna per agensi: `owner` dan `agent`.

## Cara kerja AI Property Advisor

Kode ada di [app/Services/Advisor/](app/Services/Advisor).

- **[AdvisorService](app/Services/Advisor/AdvisorService.php)** menjalankan satu giliran percakapan. Ia memanggil endpoint chat yang kompatibel dengan OpenAI (LM Studio atau Ollama yang di-host sendiri) dan menjalankan loop tool-calling (maksimal 6 putaran per giliran). Riwayat percakapan disimpan di database. Request dikirim dengan `reasoning_effort=none` agar model tidak melakukan fase berpikir panjang. Seluruh giliran dibatasi 85 detik supaya muat dalam batas 100 detik Cloudflare.
- **[PropertyAdvisorTools](app/Services/Advisor/PropertyAdvisorTools.php)** berisi alat yang bisa dipanggil model. Semua fakta agensi, properti, harga, cicilan, dan slot survei harus lewat sini, karena model sendiri tidak dipercaya menyimpan data apa pun.

  | Tool | Fungsi |
  |---|---|
  | `search_knowledge` | Mencari di knowledge base agensi |
  | `search_properties` | Mencari listing sesuai kebutuhan (jual/sewa, tipe, budget, kamar tidur, area) |
  | `get_property_detail` | Detail satu properti |
  | `simulate_mortgage` | Simulasi KPR dari harga, uang muka, dan tenor |
  | `check_viewing_slots` | Cek slot survei yang masih tersedia pada tanggal tertentu |
  | `create_viewing_request` | Membuat permintaan jadwal survei |
  | `request_human_handover` | Menyerahkan percakapan ke agen |

- **[ContentGuard](app/Services/Advisor/ContentGuard.php)** adalah pengaman deterministik di kode. Pesan yang kasar, bersifat seksual, atau ilegal (Indonesia, Inggris, Jepang) dijawab dengan balasan baku tanpa sampai ke model. Balasan model yang masih memuat kata-kata tersebut juga diganti. Pola dibuat sempit supaya pertanyaan properti yang wajar tidak ikut terblokir.
- **Harga tanpa sumber ditolak**: balasan yang memuat angka harga atau placeholder yang tidak berasal dari tool akan ditantang, supaya model tidak mengarang harga.
- **Handover**: alasan yang didukung adalah `price_negotiation`, `mortgage_help`, `legal_question`, `special_request`, `complaint`, `payment_issue`, dan `low_confidence`.

## Teknologi

- PHP 8.3+ (dikembangkan di 8.4), Laravel 13
- SQLite sebagai database bawaan. Session, cache, dan queue memakai driver `database`
- Vite 8 dan Tailwind CSS 4 untuk frontend, dengan JavaScript vanilla (tanpa framework) di `resources/js/`
- LLM lokal lewat HTTP (endpoint kompatibel OpenAI)
- PHPUnit 12 untuk tes, Laravel Pint untuk format kode

## Memulai

### Prasyarat
PHP 8.3+ dengan ekstensi SQLite, Composer, dan Node.js 20.19+ (atau 22.12+) dengan npm. Untuk fitur chat AI, Anda juga perlu server LLM yang kompatibel dengan OpenAI (lihat bagian berikutnya).

### Instalasi

```bash
composer setup
```

Perintah itu menjalankan `composer install`, menyalin `.env.example` ke `.env`, membuat `APP_KEY`, menjalankan migrasi, lalu `npm install` dan `npm run build`.

Isi data demo (hanya jalan di environment `local` dan `testing`):

```bash
php artisan db:seed
```

Seeder membuat agensi demo **FTS Realty** (slug `fts-realty`) dengan beberapa properti (rumah, townhouse, apartemen jual dan sewa) dan knowledge items.

### Menjalankan

```bash
composer dev
```

Perintah ini menjalankan semua proses development lewat `php artisan dev`. Daftar prosesnya bisa dilihat dengan `php artisan dev:list`.

| Halaman | URL |
|---|---|
| Halaman pembuka | `http://localhost:8000/` |
| Agensi demo | `http://localhost:8000/fts-realty` |
| Admin | `http://localhost:8000/admin` |

Akun admin demo (hanya untuk lokal, jangan dipakai di produksi):

```
email    : owner@ftsrealty.test
password : password
```

### Konfigurasi LLM

Atur di `.env`:

```dotenv
LOCAL_LLM_BASE_URL=   # mis. http://127.0.0.1:1234/v1 (LM Studio) atau http://127.0.0.1:11434/v1 (Ollama)
LOCAL_LLM_API_KEY=    # kosongkan jika server tidak memakai autentikasi
LOCAL_LLM_MODEL=      # nama model yang dimuat di server
```

Model harus mendukung function calling. Tanpa konfigurasi ini, halaman agensi tetap jalan tetapi chat advisor tidak bisa menjawab.

Kalau perlu, ubah juga `APP_NAME`, `APP_URL`, dan `APP_TIMEZONE` (bawaan `Asia/Jakarta`).

### Mencoba advisor dari terminal

```bash
php artisan advisor:chat fts-realty --locale=id
```

Pilihan `--locale` adalah `id`, `en`, atau `ja`. Cara ini berguna untuk menguji loop tool-calling tanpa membuka browser.

## Endpoint utama

| Method | Path | Keterangan |
|---|---|---|
| GET | `/{agencySlug}` | Beranda agensi |
| GET | `/{agencySlug}/listings`, `/listings/{propertySlug}` | Daftar dan detail properti |
| GET | `/{agencySlug}/area`, `/area/{placeId}` | Area sekitar |
| GET | `/{agencySlug}/guide`, `/agents`, `/viewing` | Panduan, agen, jadwal survei |
| POST | `/{agencySlug}/viewing/slots` | Cek slot survei |
| POST | `/{agencySlug}/viewing` | Kirim permintaan survei |
| POST | `/{agencySlug}/mortgage` | Simulasi KPR |
| POST | `/{agencySlug}/advisor/start` | Mulai percakapan |
| POST | `/{agencySlug}/advisor/message` | Kirim pesan ke advisor |
| GET | `/{agencySlug}/advisor/history` | Riwayat percakapan |

Endpoint advisor, survei, dan KPR dibatasi laju (rate limit). Pembatas advisor didefinisikan di [AppServiceProvider](app/Providers/AppServiceProvider.php), dan login admin dibatasi 5 percobaan per menit.

## Struktur proyek

```
app/
  Console/Commands/     advisor:chat
  Http/Controllers/     halaman publik, chat, survei, KPR, dan Admin/
  Models/               Agency, Property, ViewingAppointment, Conversation, HandoverRequest, ...
  Services/Advisor/     AdvisorService, PropertyAdvisorTools, ContentGuard
  Services/Viewing/     ViewingService, MortgageCalculator, ContactLinks
database/
  migrations/           skema (agensi, properti, knowledge, percakapan, survei, handover)
  seeders/              DemoAgencySeeder
resources/
  js/                   page, advisor, listings, viewing, narrator, sound
  views/                halaman publik (site/), admin (admin/), komponen
tests/
  Feature/ dan Unit/
```

## Pengujian

```bash
composer test
```

Atau jalankan satu berkas:

```bash
php artisan test --compact tests/Feature/AdminAccessTest.php
```

Cakupan tes: akses admin (isolasi data antar agensi, handover, rate limit login) dan `ContentGuard`.

## Gaya kode

```bash
vendor/bin/pint --dirty
```

## Deployment

- Jalankan `npm run build` dan `php artisan migrate --force`.
- Set `APP_ENV=production` dan `APP_DEBUG=false`.
- Seeder demo tidak jalan di produksi. Buat akun owner dan agensi Anda sendiri.
- Pastikan server aplikasi bisa menjangkau `LOCAL_LLM_BASE_URL`. Pada setup saat ini endpoint LLM diakses lewat Tailscale.
- Karena ada batas 100 detik dari Cloudflare, jangan menaikkan batas waktu respons advisor melebihi 85 detik.

## Lisensi

Project ini dibangun di atas [Laravel](https://laravel.com), yang berlisensi [MIT](https://opensource.org/licenses/MIT).
