# ADR — Fondasi Baseline v0.1-r1

**Status:** ACCEPTED
**Work ID:** 2026-08-baseline-foundation
**Namespace:** Console
**Module:** AccessControls
**Tanggal:** 2026-08-20

## Keputusan

Baseline v0.1-r1 diterapkan bertahap tanpa mengubah kontrak publik. `AccessControls` dan `UserManagements` tetap dua module terpisah. `AuditLogs` tetap nama module kanonik.

Fase pertama merekonsiliasi migration ledger dan mengubah graph identity aktif ke ULID secara data-safe sebelum upgrade Laravel atau migrasi struktur DDD-lite.

## Konsekuensi

- Route, nama route, permission key, halaman Inertia, dan perilaku generator dipertahankan.
- Bridge legacy tidak dihapus tanpa audit konsumen dan persetujuan eksplisit.
- Migration tidak dijalankan terhadap database aktif sebelum preflight menyatakan schema dan data aman.
