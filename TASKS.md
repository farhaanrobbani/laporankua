# Development Tasks

## Phase 0 — Project Setup

- [ ] Create Laravel 13 project with PHP 8.5
- [ ] Configure MySQL database
- [ ] Configure `.env`
- [ ] Set timezone Asia/Jakarta
- [ ] Configure `php` binary to `/usr/bin/php85`
- [ ] Install Laravel Breeze for authentication
- [ ] Install Tailwind CSS
- [ ] Install Livewire
- [ ] Install Laravel Pint
- [ ] Configure storage and symlink
- [ ] Install Laravel Boost
- [ ] Install maatwebsite/excel
- [ ] Install barryvdh/laravel-dompdf
- [ ] Install phpoffice/phpword
- [ ] Configure queue connection
- [ ] Configure testing
- [ ] Create project repository

---

# Phase 1 — Authentication

- [ ] Install Laravel Breeze with Blade + Tailwind
- [ ] Registration
- [ ] Login
- [ ] Logout
- [ ] Forgot password
- [ ] Reset password
- [ ] Profile page
- [ ] Change password
- [ ] Email verification (optional)
- [ ] Authentication tests
- [ ] Authorization policies setup

---

# Phase 2 — Dashboard

- [ ] Dashboard migration
- [ ] Dashboard controller
- [ ] Dashboard view (Blade + Livewire)
- [ ] Statistics cards component
- [ ] Recent imports list component
- [ ] Recent reports list component
- [ ] Quick actions component
- [ ] Dashboard tests

---

# Phase 3 — Excel Import

- [ ] Upload migration (file storage)
- [ ] Import model
- [ ] Import factory
- [ ] Import seeder
- [ ] Import controller
- [ ] Upload form (Livewire)
- [ ] File validation
- [ ] Excel reader (Maatwebsite)
- [ ] Sheet selection UI
- [ ] Column mapping UI
- [ ] Data preview component
- [ ] Import processing job (ProcessExcelImport)
- [ ] Import status tracking
- [ ] Import history page
- [ ] Import detail page
- [ ] Import error log
- [ ] Import tests

---

# Phase 4 — Data Management

- [ ] ImportData model
- [ ] Data table component (Livewire)
- [ ] Data listing page
- [ ] Data filtering
- [ ] Data searching
- [ ] Data sorting
- [ ] Data pagination
- [ ] Data detail view
- [ ] Data delete functionality
- [ ] Bulk delete functionality
- [ ] Data export to Excel
- [ ] Data tests

---

# Phase 5 — Report Generator

- [ ] Report model
- [ ] Report controller
- [ ] Report builder UI (Livewire)
- [ ] Data source selection
- [ ] Field selection component
- [ ] Filter builder component
- [ ] Sorting configuration component
- [ ] Output format selection
- [ ] Report preview component
- [ ] PDF generation service (PdfService)
- [ ] Word generation service (WordService)
- [ ] Excel export service (ExcelExportService)
- [ ] Print preview with CSS (@media print)
- [ ] Report generation job (GenerateReport)
- [ ] Report storage
- [ ] Report download
- [ ] Report tests

---

# Phase 6 — Report Templates

- [ ] ReportTemplate model
- [ ] Template management controller
- [ ] Template creation form
- [ ] Template editing
- [ ] Template listing
- [ ] Template deletion
- [ ] Template default setting
- [ ] Template usage (create report from template)
- [ ] Template tests

---

# Phase 7 — Print

- [ ] Print CSS stylesheet (@media print)
- [ ] Print layout component
- [ ] Print preview page
- [ ] Print header/footer configuration
- [ ] Page number support
- [ ] A4 page size configuration
- [ ] Landscape/portrait support
- [ ] Print tests

---

# Phase 8 — Security & Audit

- [ ] Authorization policy review
- [ ] CSRF protection audit
- [ ] File upload security audit
- [ ] SQL injection audit
- [ ] XSS protection audit
- [ ] Input sanitization
- [ ] Rate limiting configuration
- [ ] User data isolation verification
- [ ] Security tests

---

# Phase 9 — Performance

- [ ] Database index review
- [ ] N+1 query fix
- [ ] Query caching implementation
- [ ] Eager loading optimization
- [ ] Chunk processing for large datasets
- [ ] Queue worker configuration
- [ ] Page load speed optimization
- [ ] Frontend asset optimization
- [ ] Performance tests

---

# Phase 10 — UI Polish

- [ ] Responsive design review
- [ ] Mobile sidebar navigation
- [ ] Empty state components
- [ ] Loading state components
- [ ] Error state components
- [ ] Toast notifications
- [ ] Modal components
- [ ] Status badge components
- [ ] Print button components
- [ ] UI consistency review
- [ ] Design system alignment

---

# Phase 11 — Documentation & Testing

- [ ] Update README.md
- [ ] Update all documentation files
- [ ] Unit tests completion
- [ ] Feature tests completion
- [ ] Test coverage 80%+
- [ ] PHP linting (Pint)
- [ ] Code review
- [ ] Documentation review

---

# Phase 12 — Deployment

- [ ] Production `.env` configuration
- [ ] `php artisan optimize:clear`
- [ ] `composer install --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --force`
- [ ] `npm run build`
- [ ] Nginx configuration
- [ ] Supervisor for queue workers
- [ ] Cron job setup
- [ ] SSL/HTTPS setup
- [ ] Domain configuration
- [ ] Backup strategy
- [ ] Monitoring setup
- [ ] Deployment documentation

---

# Phase 13 — Post-Launch

- [ ] User acceptance testing (UAT)
- [ ] Bug fixes
- [ ] Performance monitoring
- [ ] Log review
- [ ] Error tracking
- [ ] User feedback collection
- [ ] Version 1.1 planning
- [ ] Advanced feature backlog

---

## Priority Matrix

### Critical Path (Must Have)

```text
Phase 0 → Phase 1 → Phase 3 → Phase 4 → Phase 5 → Phase 7 → Phase 8 → Phase 12
```

### Important (Should Have)

```text
Phase 2 → Phase 5 → Phase 6 → Phase 9 → Phase 10
```

### Nice to Have (Could Have)

```text
Phase 11 → Phase 13
```

---

## Acceptance Checklist

Aplikasi siap deploy apabila:

- [ ] Authentication berjalan dengan benar
- [ ] Excel upload dan import berjalan
- [ ] Data management berfungsi
- [ ] PDF report generation berfungsi
- [ ] Word report generation berfungsi
- [ ] Excel export berfungsi
- [ ] Print preview berfungsi
- [ ] Template management berfungsi
- [ ] Data user terisolasi
- [ ] Tidak ada critical bugs
- [ ] Test coverage >= 80%
- [ ] Code passes linting
- [ ] Production deployment ready
- [ ] Documentation complete
- [ ] Security audit passed
- [ ] Performance targets met

---

## Environment & Security

- Gunakan environment variables (`.env`) untuk data sensitif. Jangan pernah simpan kredensial/API keys langsung di dalam kode.
- Setiap request harus memastikan user terautentikasi dan memiliki izin mengakses data.
- Validasi semua input pengguna untuk mencegah SQL Injection dan XSS.

---

## Code Quality & Security

- Tulis kode yang modular, mudah dibaca, dan aman dari kerentanan umum (SQL Injection & XSS).
- Sebelum menyelesaikan tugas, pastikan kode telah divalidasi dan bebas dari kesalahan sintaks (`php -l` atau unit test jika ada).

---

## Git Workflow & Mandatory CI/CD Trigger (WAJIB)

1. **Granular Commit:** Lakukan `git commit` untuk setiap 1 tugas/fitur kecil yang selesai dikerjakan. Gunakan format konvensi pesan commit (contoh: `feat: ...` atau `fix: ...`).
2. **Auto Push:** Setelah komit berhasil dan dipastikan bebas error, kamu **WAJIB** menjalankan perintah:
   `git push origin main`

   > ⚠️ **Catatan Penting:** Perintah `git push` ini adalah pemicu (*trigger*) otomatis untuk pipeline CI/CD (GitHub Actions) agar perubahan ter-deploy langsung ke VPS.

---

## Restrictions (Yang Dilarang)

- ❌ Dilarang melakukan `git push` jika kodingan masih bermasalah/error.
- ❌ Dilarang menjalankan perintah terminal berskala destruktif (`rm -rf /`, `DROP DATABASE`, dll) tanpa persetujuan.
- ❌ Dilarang mengubah struktur folder utama aplikasi tanpa instruksi spesifik.

---

## Project Context

- **Project:** Laporan — Web Pengolah Laporan
- **Laravel Version:** 13
- **PHP Version:** 8.5
- **Database:** MySQL
- **Key Packages:** maatwebsite/excel, barryvdh/laravel-dompdf, phpoffice/phpword, livewire/livewire
