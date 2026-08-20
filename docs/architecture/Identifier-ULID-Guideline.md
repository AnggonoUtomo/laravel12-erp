# Guideline Identifier ULID — SakaSantri

## Keputusan

Semua table aplikasi yang memiliki surrogate primary identifier menggunakan **ULID** pada kolom `id`.

```php
$table->ulid('id')->primary();
```

Foreign key:

```php
$table->foreignUlid('student_id');
```

## Business Identifier

Business number tetap field berbeda:

```text
students.id          -> ULID
students.student_no  -> business identifier

invoices.id          -> ULID
invoices.invoice_no  -> business identifier
```

Tidak membuat `public_id` hanya untuk menduplikasi ULID `id`.

## Pivot

Pure pivot table yang cukup diidentifikasi dengan composite key tidak wajib memiliki surrogate `id`.

## Eloquent

Model harus menggunakan konfigurasi ULID yang konsisten, misalnya `HasUlids` atau mekanisme project-level yang setara.

## Package / Vendor Tables

Jika package memiliki model/table yang menjadi bagian identity/reference aplikasi, konfigurasi identifier harus diselaraskan ke ULID sejauh didukung oleh package dan kebutuhan proyek.
