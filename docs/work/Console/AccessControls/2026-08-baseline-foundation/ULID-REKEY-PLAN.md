# Rencana Teknis — ULID Rekey Graph Identity

## Scope ULID

| Graph | Tabel |
|---|---|
| Identity aplikasi | `users`, `system_settings`, `audit_logs`, `notification_templates`, `login_activities` |
| RBAC | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| Media dan audit morph | `media`, `audit_logs` |
| Referensi user framework | `sessions.user_id` |

`cache`, `jobs`, `failed_jobs`, `job_batches`, dan `migrations` tetap menggunakan identifier framework karena bukan identity aplikasi atau foreign reference ke aggregate aplikasi.

## Tahap Uji pada Clone

1. **Map ULID** — Tambahkan kolom transisional `*_ulid`, isi ULID unik untuk setiap primary key aplikasi, lalu catat count dan uniqueness.
2. **Pindahkan relasi** — Isi foreign/morph ULID dari map yang sama; verifikasi setiap reference tidak null atau orphan.
3. **Cutover** — Lepas FK/primary key lama, jadikan ULID sebagai key kanonik, pasang ulang FK dan composite key Spatie, lalu hapus kolom bigint lama.
4. **Model dan package adapter** — Terapkan `HasUlids`, custom Role/Permission Spatie, serta type/key model Media Library sebelum aplikasi dijalankan pada schema baru.
5. **Regresi** — Uji login, role/permission, impersonation, audit, session, dan route binding; baru siapkan runbook cutover sumber.

Cutover sumber dilakukan dalam maintenance window yang disetujui pemilik.

## Invariant Wajib

- jumlah row setiap tabel tidak berubah;
- semua permission/role assignment sebelum migrasi tetap dapat dibaca sesudah migrasi;
- `audit_logs.actor_id`, `login_activities.user_id`, dan `sessions.user_id` menunjuk User yang sama;
- tidak ada bigint identity atau foreign key aplikasi setelah cutover;
- rollback dilakukan dengan restore full backup, bukan rollback migration parsial.
