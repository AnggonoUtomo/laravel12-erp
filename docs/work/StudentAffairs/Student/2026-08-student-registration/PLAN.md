# PLAN — Registrasi Santri

**Status:** DRAFT
**Work ID:** 2026-08-student-registration
**Namespace:** StudentAffairs
**Module:** Student
**Tanggal:** 2026-08-19

## Tujuan

Menyediakan alur registrasi santri yang menjaga ownership module Student, menghubungkan wali melalui contract yang jelas, dan mempertahankan audit trail.

## Ruang Lingkup

### Termasuk

- registrasi data santri
- public identifier
- student number
- relasi awal unit organisasi
- link guardian
- audit event
- tests

### Tidak Termasuk

- penempatan asrama
- enrollment kelas
- billing otomatis

## Sumber yang Relevan

- `/AGENTS.md`
- `/docs/architecture/SakaSantri_Architecture_Baseline.md`
- `app/Modules/StudentAffairs/Student/README.md`

## Area Arsitektur yang Tersentuh

```text
[x] Domain
[x] Application
[x] Infrastructure
[ ] Adapter
[ ] Integration
[x] Presentation
[x] Database
[x] Routes
[x] Frontend
```

## Langkah Implementasi

1. Definisikan entity/value object dan lifecycle awal Student.
2. Tambahkan repository dan persistence.
3. Implementasikan RegisterStudentAction.
4. Hubungkan Guardian melalui contract/query tanpa direct model access.
5. Tambahkan endpoint Inertia dan form.
6. Dispatch `StudentRegistered`.
7. Tambahkan focused tests dan audit assertion.

## Validasi

- Student unit tests
- Student registration feature test
- authorization test
- cross-module Guardian contract test

## Constraint Tooling

```text
Laravel Boost tidak digunakan.
Wayfinder tidak digunakan.
```
