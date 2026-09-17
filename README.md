# Laporan — Web Pengolah Laporan

Aplikasi web untuk mengolah data dari file Excel menjadi laporan dalam format **PDF, Word, Excel, dan Print**. User mengupload file Excel, data otomatis tersimpan ke database MySQL, lalu diolah menjadi laporan.

## Stack

- Laravel 13 · PHP 8.5 · MySQL/MariaDB
- Livewire 4 · Alpine.js · Tailwind CSS 3
- maatwebsite/excel (export) · PhpSpreadsheet (import) · DomPDF · PHPWord

## Persyaratan

- PHP 8.5 dengan extension: `mbstring`, `xml`, `mysql`, `zip`, `gd`, `curl`, `bcmath`
- Composer 2 · Node.js · MySQL/MariaDB

## Instalasi

```bash
git clone https://github.com/farhaanrobbani/laporankua.git laporan
cd laporan

# Gunakan PHP 8.5 (sesuaikan path bila perlu)
/usr/bin/php85 /usr/local/bin/composer install

cp .env.example .env
php85 artisan key:generate
# Sesuaikan DB_DATABASE / DB_USERNAME / DB_PASSWORD / DB_PREFIX di .env

php85 artisan migrate --force
php85 artisan storage:link
php85 artisan icons:cache
php85 artisan livewire:publish --assets

npm install && npm run build
```

## Menjalankan

```bash
# Web server (port default 7012)
php85 artisan serve --port=7012

# Queue worker — WAJIB jalan untuk proses import Excel & generate laporan
php85 artisan queue:work --tries=3 --sleep=3 --timeout=120
```

## Testing

```bash
php85 artisan test        # 98 test: unit service, auth, dashboard, import, data, report, template, policy, security, performa, UI polish
vendor/bin/pint --test    # code style

# Coverage (membangun pcov otomatis bila belum ada, tanpa sudo)
bash bin/coverage.sh
bash bin/coverage.sh --min=80   # gagal bila coverage < 80%
```

Coverage saat ini: **~90% statement** (file scaffolding auth Breeze adalah penyumbang utama yang belum tertutup).

## Struktur Modul

| Modul | Route | Controller | Komponen |
|---|---|---|---|
| Dashboard | `/dashboard` | DashboardController | status-badge, empty-state, toast |
| Upload/Import | `/imports/*` | ImportController | ⚡import-uploader |
| Data | `/data*` | DataController | ⚡data-table |
| Laporan | `/reports/*` | ReportsController | ⚡report-builder, reports/pdf, reports/print |
| Template | `/templates/*` | TemplateController | — |

Dokumentasi lengkap: `PRD.md`, `ARCHITECTURE.md`, `DESIGN.md`, `TASKS.md`, `AGENTS.md`.

## Catatan Produksi

- Simpan kredensial hanya di `.env` (jangan commit `.env`).
- `config/database.php` menggunakan `DB_PREFIX` (mis. `laporan_`) dan `DB_MIGRATIONS_TABLE` bila database dipakai bersama aplikasi lain.
- Gunakan Supervisor/systemd untuk `queue:work` dan cron untuk task terjadwal.

## Deploy ke Production

```bash
# 1. Clone & install
git clone https://github.com/farhaanrobbani/laporankua.git laporan && cd laporan
cp .env.example .env
php85 artisan key:generate
composer install --optimize-autoloader
npm install && npm run build

# 2. Update .env (WAJIB)
#    APP_URL=https://domain-anda.com
#    APP_ENV=production
#    APP_DEBUG=false
#    SESSION_SECURE=true        ← wajib untuk HTTPS
#    DB_*, MAIL_*, TURNSTILE_*  ← sesuaikan

# 3. Database & assets
php85 artisan migrate --force
php85 artisan livewire:publish --assets
php85 artisan icons:cache

# 4. Cache config (setelah .env final)
php85 artisan config:cache && php85 artisan route:cache && php85 artisan view:cache
```

### Nginx

Contoh config ada di `deploy/nginx/laporan.conf`. Jika menggunakan **BT Panel / Baota Panel**, pastikan blok `location ~ .*\.(js|css)?$` punya `try_files`:

```nginx
location ~ .*\.(js|css)?$ {
    try_files $uri $uri/ /index.php?$query_string;
    expires 12h;
}
```

Tanpa `try_files`, Livewire JS akan 404 (nginx coba serve statis → gagal → status tetap 404 meskipun body file lengkap).

### Cloudflare

Setelah deploy pertama, **purge cache** untuk path `livewire-*` agar Cloudflare tidak meng-cache 404 lama:

```
https://domain-anda.com/livewire-*/livewire.min.js
```
