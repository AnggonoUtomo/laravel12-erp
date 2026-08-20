# AGENTS.md
# Kontrak Kerja Codex — SakaSantri

## 1. Tujuan

File ini berisi aturan repository-wide yang harus stabil dan konsisten.

Detail arsitektur berada di:

```text
docs/architecture/
```

Keputusan dan catatan kerja task berada di:

```text
docs/work/
```

Tujuan:

> menjaga konsistensi arsitektur SakaSantri dengan overhead proses yang rendah.

---

## 2. Stack Wajib

```text
Laravel 12
Laravel Starter Kit / Fortify
Inertia
React
TypeScript
Tailwind CSS
shadcn/ui
Framer Motion
Spatie Permission
Spatie Media Library
Ziggy conventional named routes
MySQL
```

Arsitektur:

```text
DDD-lite
Modular Monolith
Hexagonal Architecture
Shared Kernel
Pragmatic CQRS
Domain Events
Event Listeners
Selective Queue
```

Jangan menambahkan Wayfinder.

Jangan menambahkan Laravel Boost.

Jangan mengganti package inti tanpa instruksi eksplisit.

---

## 3. Model Produk

```text
Non-SaaS
Single Yayasan
Multi-Unit
```

Jangan memperkenalkan multi-tenancy SaaS tanpa keputusan arsitektur eksplisit.

Unit pendidikan/pesantren adalah data organisasi, bukan otomatis module Laravel.

---

## 4. Root Module

```text
app/Modules/<Namespace>/<Module>/
```

Namespace:

```text
App\Modules\<Namespace>\<Module>
```

---


## 4A. Namespace = Area/Kategori Module

Pola:

```text
app/Modules/<Namespace>/<Module>/
```

`Namespace` adalah logical/domain-area grouping.

Contoh baseline:

```text
Console/AccessControl
Console/SystemSetting
StudentManagement/Student
Academic/AcademicPeriod
Finance/StudentFinance
Platform/Notification
```

Namespace:

```text
bukan nama aplikasi global
bukan tenant
bukan authorization mechanism
bukan izin direct access lintas module
```

Module tetap menjadi boundary ownership utama.


## 5. Ownership Module

Setiap module memiliki:

```text
Application/
Domain/
Infrastructure/
Presentation/
Database/
Routes/
module.json
module.php
permissions.php
ServiceProvider.php
README.md
```

Migration dan route tetap dimiliki module.

---

## 6. Adapter dan Integration Optional

```text
Infrastructure/Adapters/
Infrastructure/Integrations/
```

dibuat hanya saat diperlukan.

Jangan membuat folder kosong demi pola.

Repository tetap di:

```text
Infrastructure/Repositories/
```

Untuk kolaborasi internal gunakan:

```text
Contract
Application Service
Command
Query
Domain Event
Integration Event
```

Jangan membuat pseudo-microservice HTTP di dalam monolith.

---

## 7. Arah Dependensi

```text
Presentation
    ↓
Application
    ↓
Domain
```

Infrastructure mengimplementasikan/adapt contract.

Domain tidak bergantung pada Eloquent, Controller, Form Request, Inertia, React, atau internal package.

Mutation lintas module tidak boleh dilakukan dengan mengubah Infrastructure Model module lain.

---

## 8. DDD-lite

Gunakan rich domain modeling bila business rule nyata.

Hindari:

```text
generic repository
aggregate kosong
service kosong
CQRS folder kosong
wrapper tanpa makna domain
```

CRUD sederhana boleh tetap sederhana.

Business rule kompleks tidak boleh ditaruh di Controller.

---

## 9. SystemSetting

```text
.env / config
= deployment/technical configuration

SystemSetting
= application/business configuration yang dinamis

Master Data
= tetap dimiliki module domain
```

Jangan jadikan SystemSetting generic key/value dumping ground.

---

## 10. Pemisahan Domain Kritis

```text
User Account       != Employee Profile
User Account       != Student Profile
User Account       != Guardian Profile
Organization Unit  != Laravel Module
Document           != generic public file
Application Log    != Audit Trail
RBAC               != Domain Business Rule
Current Master     != Historical Context
```

---

## 11. Shared Kernel

Lokasi:

```text
app/Shared/Kernel/
```

Harus kecil.

Jangan membuat:

```text
Helpers
Utils
Common
Misc
EverythingReusable
```

---

## 12. CQRS dan Event

Command mengubah state.

Query read-only.

Tidak ada database read/write terpisah pada baseline.

Gunakan event untuk decoupling dan side effect yang bermakna.

Invariant yang harus sinkron tidak boleh disembunyikan dalam async event.

---

## 13. Module Generator

Utamakan:

```bash
php artisan make:module <Module>
```

Gunakan child generator module jika tersedia.

Default generator tidak membuat:

```text
Infrastructure/Adapters/
Infrastructure/Integrations/
```

Gunakan child command ketika benar-benar dibutuhkan.

---

## 14. Laravel Boost Dilarang

```text
laravel/boost
```

harus dihapus jika ditemukan dan tidak boleh ditambahkan kembali.

---

## 15. Ziggy

Gunakan named Laravel routes melalui Ziggy.

```ts
route('students.show', student.id)
```

Jangan gunakan Wayfinder.

---

## 16. Work Package

Task non-trivial:

```text
docs/work/<namespace>/<Module>/<work-id>/
├── ADR.md
├── PLAN.md
└── TASKS.md
```

Jangan membuat work package untuk typo atau perubahan satu baris yang trivial.

---

## 17. ADR

ADR mencatat keputusan, bukan aktivitas harian.

Gunakan bila ada:

```text
new domain boundary
aggregate ownership
integration strategy
schema strategy berdampak besar
security decision
cross-module dependency decision
```

---

## 17A. Identifier — ULID

Semua table aplikasi yang memiliki surrogate primary identifier menggunakan:

```text
id = ULID
```

Laravel migration:

```php
$table->ulid('id')->primary();
```

Foreign reference:

```php
$table->foreignUlid('student_id');
```

Jangan menggunakan pola:

```text
BIGINT id + public_id ULID
```

Business identifier seperti `student_no` atau `invoice_no` tetap field terpisah dan bukan primary key.

Pure pivot table dengan composite key tidak wajib memiliki surrogate `id`.


## 18. Historis dan Traceability

Jangan mengubah makna histori dengan mutable master data.

Pertahankan konteks transaksi seperti:

```text
academic period
class/subject offering
teacher assignment
invoice amount
fee definition snapshot
dormitory placement
actor/approval
document version
```

---

## 19. Source of Truth

```text
AGENTS.md
= repository rules

docs/architecture/
= long-term architecture

Module README
= module-level truth

docs/work/
= task decision and execution
```

---

## 20. Guardrail

Codex tidak boleh secara sepihak:

```text
menambah module
mengubah bounded context
mengganti stack
mengubah single-yayasan menjadi SaaS
mengubah security model
mengubah primary data ownership
mengubah pola integrasi antarmodule
```

Keputusan semacam itu harus mengikuti dokumen baseline atau instruksi eksplisit user.
