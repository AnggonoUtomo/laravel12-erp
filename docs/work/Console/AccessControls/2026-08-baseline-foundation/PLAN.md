# PLAN — Fondasi Baseline v0.1-r1

**Status:** IN PROGRESS
**Work ID:** 2026-08-baseline-foundation

## Urutan

1. Rekonsiliasi schema aktif dan migration ledger tanpa perubahan data.
2. Tambahkan preflight serta test untuk mendeteksi ledger kosong dan mismatch schema.
3. Rekey ULID graph User/RBAC/Media/Audit/Session pada salinan database dan verifikasi data.
4. Migrasikan loader, generator, Shared Kernel, dan modul Console ke DDD-lite sambil mempertahankan Laravel 12 dan kontrak publik. Bridge legacy hanya dipertahankan bila audit consumer membuktikan masih diperlukan; consumer yang teridentifikasi diperbarui langsung.

## Validasi per Fase

- assertion row count, foreign key, dan sample relation sebelum/sesudah rekey;
- `php artisan module:validate`;
- focused test, full test suite, Pint, build/typecheck;
- `git diff --check`.
