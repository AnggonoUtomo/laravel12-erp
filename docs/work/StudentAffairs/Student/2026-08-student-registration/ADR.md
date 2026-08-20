# ADR — Ownership Registrasi Santri

**Status:** DRAFT
**Work ID:** 2026-08-student-registration
**Namespace:** StudentAffairs
**Module:** Student
**Tanggal:** 2026-08-19

## Konteks

Registrasi santri membutuhkan data Student, Organization, dan Guardian. Perlu ditentukan boundary agar Student tidak melakukan mutation langsung terhadap model module lain.

## Keputusan

`Student` menjadi owning module untuk lifecycle registrasi santri. Relasi wali menggunakan identifier/contract yang dipublikasikan oleh `Guardian`. Unit organisasi divalidasi melalui contract/query yang dimiliki `Organization`.

## Alternatif yang Dipertimbangkan

- Menempatkan seluruh registrasi pada module global Enrollment.
- Mengakses langsung Eloquent model Guardian dan Organization.

## Konsekuensi

### Positif

- ownership Student jelas
- dependency eksplisit
- tidak ada direct model mutation lintas module

### Tradeoff / Risiko

- membutuhkan contract kecil untuk validasi relasi

## Kesesuaian Arsitektur

- [x] Module ownership tetap terjaga
- [x] Arah dependency tetap benar
- [x] Tidak ada circular dependency
- [x] Dampak Shared Kernel ditinjau
- [x] Historical traceability ditinjau
- [x] Security/privacy ditinjau
- [x] Single-yayasan constraint tetap terjaga
