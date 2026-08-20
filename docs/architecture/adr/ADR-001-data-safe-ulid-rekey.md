# ADR-001 — Rekey ULID Data-Safe untuk Fondasi Aktif

**Status:** Accepted
**Tanggal:** 2026-08-20

## Konteks

Baseline v0.1-r1 menetapkan ULID sebagai primary identifier seluruh tabel aplikasi. Database aktif masih memiliki primary key bigint pada User, RBAC Spatie, SystemSetting, AuditLog, NotificationTemplate, LoginActivity, dan Media Library. Ledger awal memiliki 20 record HR/DM stale yang tidak lagi memiliki file maupun tabel pemilik; record tersebut telah dihapus setelah full backup diverifikasi.

Migrasi tidak boleh menghapus data aktif atau mengubah kontrak publik tanpa bridge yang diaudit. Tabel legacy `hr_*` dan `dm_*` telah dihapus atas instruksi pemilik karena modul pemiliknya tidak lagi berada di repository.

## Keputusan

1. Schema aktif direkonsiliasi terlebih dahulu ke migration ledger yang eksplisit dan dapat direproduksi.
2. Rekey ULID dilakukan secara transisional: buat key ULID, backfill deterministik, pindahkan foreign key dan morph key, lalu jadikan ULID sebagai primary key.
3. User, role, permission, pivot Spatie, media, session, audit, dan model aplikasi terkait dimigrasikan sebagai satu graph relasi.
4. Tabel framework yang tidak menyimpan identity aplikasi tidak dipaksa berubah hanya karena memiliki surrogate key.
5. Setiap cutover wajib memiliki preflight, backup/rollback yang teruji, assertion jumlah data, dan validasi foreign key.
6. Cutover database sumber dilakukan dalam maintenance window agar tidak ada write di antara perubahan primary key dan deployment adapter model ULID. Cutover sumber selesai pada 2026-08-20 setelah validasi backup final.
7. Clone `laravel12_erp_ulid_trial` telah menyelesaikan cutover: key aktif `id` dan FK aplikasi memakai `CHAR(26)` ULID. Nilai bigint terdahulu disimpan sebagai `legacy_*` nullable untuk rekonsiliasi; data baru tidak menulisnya.

## Alternatif yang Ditolak

- Reset database: menyalahi requirement pelestarian data aktif.
- Menambah `public_id` sambil mempertahankan bigint sebagai primary key: bertentangan dengan baseline ULID.
- Menandai migration lama sebagai sudah berjalan tanpa verifikasi schema: menghasilkan migration ledger yang tidak dapat dipercaya.

## Konsekuensi

- Fase ini mendahului refactor DDD-lite; Laravel 12 dipertahankan sesuai keputusan pemilik.
- Konfigurasi dan model Spatie perlu extension yang kompatibel ULID.
- Setiap perubahan schema diuji pada salinan database sebelum diterapkan ke database aktif.
- Adapter `HasUlids` dipakai oleh User dan model aplikasi; Role, Permission, dan Media memakai subclass aplikasi yang dikonfigurasi pada paket Spatie.
- Backup final sumber tersimpan pada `storage/app/backups/full-backup-20260820-132846.zip` dan telah lulus validasi signature serta checksum sebelum migrasi dijalankan.
