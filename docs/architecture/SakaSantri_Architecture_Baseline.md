# Arsitektur Baseline v0.1
## SakaSantri — Sistem Terpadu Pengelolaan Manajemen dan Operasional Pesantren

**Status:** Draft Baseline — Adaptasi Awal untuk SakaSantri
**Target Teknologi:** Laravel 12 + Inertia React Starter Kit
**Arsitektur:** DDD-lite Modular Monolith + Hexagonal Architecture
**Database Utama:** MySQL
**Model Produk:** Non-SaaS, single yayasan, multi-unit
**Bahasa Dokumentasi:** Bahasa Indonesia
**Bahasa Identifier Teknis:** Bahasa Inggris

---

# Riwayat Revisi

## v0.1-r1 — Namespace Area & ULID Primary Identifier

Perubahan utama:

```text
namespace                     -> dipertegas sebagai kategori/area module
Console                       -> AccessControl, SystemSetting, AuditTrail
StudentManagement             -> Student, Guardian, Dormitory
Academic                      -> AcademicPeriod, Academic
Finance                       -> StudentFinance
Communication                 -> Announcement
Platform                      -> Document, Notification, Reporting
table primary identifier      -> ULID
BIGINT id + public_id ULID    -> dihapus dari baseline
module/work path              -> menggunakan namespace area aktual
```

---

## v0.1 — Adaptasi Baseline SakaSantri

Perubahan utama:

```text
Domain laboratorium      -> diganti menjadi domain manajemen pesantren
Single laboratory        -> single yayasan dengan banyak unit
Testing workflow         -> operational workflow pesantren
Laboratory scope         -> struktur unit/layanan pendidikan
Personnel competence     -> SDM, guru, ustadz, jabatan, assignment
Sample/test execution    -> santri, akademik, asrama, keuangan
Evidence readiness       -> document/audit/operational traceability
SystemSetting            -> dipertahankan sebagai capability platform
Laravel Boost            -> dihapus / dilarang
Adapters/Integrations    -> optional/on-demand
```

---

# 1. Tujuan Dokumen

Dokumen ini menjadi baseline arsitektur teknis untuk pengembangan **SakaSantri**, yaitu sistem terpadu pengelolaan manajemen dan operasional pesantren yang:

1. digunakan oleh satu yayasan, bukan SaaS multi-tenant;
2. mendukung banyak unit di bawah yayasan yang sama;
3. mengelola pengguna, organisasi, santri, wali, SDM, akademik, asrama, keuangan, dokumen, notifikasi, laporan, dan audit;
4. menggunakan DDD-lite Modular Monolith;
5. menerapkan prinsip Hexagonal Architecture secara pragmatis;
6. memiliki Shared Kernel yang kecil dan terkontrol;
7. menggunakan CQRS hanya pada use case yang memberi manfaat;
8. menggunakan contract/service dan domain event untuk komunikasi antarmodul;
9. mempertahankan ownership data setiap bounded context;
10. dapat berkembang secara bertahap tanpa memecah sistem menjadi microservices terlalu dini.

Aplikasi ini bukan sekadar kumpulan CRUD administratif.

Aplikasi dirancang sebagai:

> **Pesantren Operations & Management Platform**

yang menghubungkan operasional yayasan, unit pendidikan, santri, wali, SDM, akademik, keuangan, asrama, komunikasi, dokumen, dan pelaporan dalam satu platform yang konsisten.

---

# 2. Prinsip Produk

## 2.1 Single Yayasan, Multi-Unit

SakaSantri bukan SaaS multi-tenant.

Model utama:

```text
Yayasan
├── Pesantren
├── Madrasah / Sekolah
├── Unit Tahfidz
├── Asrama
├── Unit Usaha
└── Unit lain
```

Seluruh unit berada dalam satu boundary organisasi yayasan.

Isolasi data berbasis tenant tidak menjadi concern utama Release 1.

Namun setiap data operasional harus tetap dapat direlasikan ke unit yang sesuai bila makna domain membutuhkannya.

## 2.2 Unit adalah Data, bukan Module Laravel

Penambahan unit pendidikan baru tidak otomatis membuat module baru.

Contoh:

```text
MI
MTs
MA
SMP
SMA
Tahfidz
Asrama Putra
Asrama Putri
```

harus dapat dimodelkan sebagai data organisasi dan konfigurasi operasional.

Module baru dibuat hanya bila terdapat bounded context atau capability bisnis yang benar-benar berbeda.

## 2.3 Incremental Release

Pengembangan dilakukan bertahap.

Release awal fokus pada fondasi dan capability utama, bukan mencoba menyelesaikan seluruh kebutuhan pesantren sekaligus.

Arsitektur tidak boleh menghambat penambahan capability berikutnya seperti:

```text
Payroll
Procurement
Inventory
Asset
POS / Koperasi
Laundry
Klinik
Perpustakaan
Donasi / Wakaf
Payment Gateway / VA / QRIS
Public API
BI / Analytics
AI Assistant
```

## 2.4 Namespace sebagai Area/Kategori Module

Pola module resmi:

```text
app/Modules/<Namespace>/<Module>/
```

Makna:

```text
Namespace
= kategori / area bisnis / area sistem yang mengelompokkan module terkait

Module
= bounded capability konkret yang memiliki ownership, rule, data, dan lifecycle sendiri
```

Contoh:

```text
Console/AccessControl
Console/SystemSetting
Finance/StudentFinance
Academic/AcademicPeriod
StudentManagement/Student
```

`Namespace` bukan:

```text
nama aplikasi global
tenant
authorization mechanism
pengganti bounded context
alasan untuk membolehkan direct access lintas module
```

Module berbeda dalam namespace yang sama tetap mengikuti aturan contract/event dan ownership module.


---

# 3. Baseline Teknologi

## Backend

```text
PHP
Laravel 12
Laravel Fortify
MySQL
Laravel Queue
Laravel Events
Laravel Notifications
```

## Frontend

```text
React
TypeScript
Inertia
Tailwind CSS
shadcn/ui
Framer Motion
Ziggy
Vite
```

## Packages

```text
spatie/laravel-permission
spatie/laravel-medialibrary
tightenco/ziggy
```

## Explicit Architecture Decisions

```text
Laravel Wayfinder      : TIDAK digunakan
Frontend routing       : Ziggy conventional named routes
ziggy:generate         : bukan workflow utama
Auth                   : Laravel Starter Kit + Fortify
RBAC                   : Spatie Permission
Media / Attachment     : Spatie Media Library
Frontend Components    : shadcn/ui
Animation              : Framer Motion
Database               : MySQL
Architecture           : DDD-lite Modular Monolith
```

---

# 4. Kebijakan Laravel Boost

Laravel Boost bukan bagian dari stack proyek.

```text
Package : laravel/boost
Status  : REMOVE / FORBIDDEN
```

Jika package ditemukan:

```bash
composer remove laravel/boost
```

Codex tidak boleh:

```text
menginstal ulang laravel/boost
menjalankan boost:install
mengandalkan Laravel Boost MCP
menggunakan Boost-generated guidance sebagai source of truth proyek
```

Gunakan:

```text
native Laravel / Artisan
Composer
repository-defined scripts
tests
AGENTS.md
docs/architecture/
docs/work/
installed Codex skills yang relevan
```

---

# 5. High-Level Architecture

```text
┌──────────────────────────────────────────────────────────────┐
│                      INERTIA REACT UI                        │
│ React + TypeScript + shadcn/ui + Framer Motion + Ziggy      │
└──────────────────────────────┬───────────────────────────────┘
                               │
                       Presentation Layer
                               │
┌──────────────────────────────▼───────────────────────────────┐
│                        APPLICATION                           │
│ Actions │ Commands │ Queries │ DTO │ Services │ Handlers    │
└──────────────────────────────┬───────────────────────────────┘
                               │
                           Domain Ports
                               │
┌──────────────────────────────▼───────────────────────────────┐
│                           DOMAIN                             │
│ Entities │ Value Objects │ Contracts │ Events │ Services    │
│ Policies │ Rules                                               │
└──────────────────────────────┬───────────────────────────────┘
                               │
                            Adapters
                               │
┌──────────────────────────────▼───────────────────────────────┐
│                      INFRASTRUCTURE                          │
│ Eloquent │ Repositories │ Media │ Queue │ Event Listeners   │
└──────────────────────────────────────────────────────────────┘
```

Dependency rule:

```text
Presentation
    ↓
Application
    ↓
Domain

Infrastructure -> implements/adapts Domain/Application contracts
```

Domain tidak bergantung pada:

```text
Controller
Form Request
Inertia
React
Eloquent Model
Spatie internal implementation
HTTP Request
frontend concern
```

---

# 6. Modular Monolith

Struktur root:

```text
app/
├── Modules/
│   ├── Console/
│   │   ├── AccessControl/
│   │   ├── SystemSetting/
│   │   └── AuditTrail/
│   │
│   ├── Organization/
│   │   └── Organization/
│   │
│   ├── StudentManagement/
│   │   ├── Student/
│   │   ├── Guardian/
│   │   └── Dormitory/
│   │
│   ├── Academic/
│   │   ├── AcademicPeriod/
│   │   └── Academic/
│   │
│   ├── HumanResource/
│   │   └── HumanResource/
│   │
│   ├── Finance/
│   │   └── StudentFinance/
│   │
│   ├── Communication/
│   │   └── Announcement/
│   │
│   └── Platform/
│       ├── Document/
│       ├── Notification/
│       └── Reporting/
│
└── Shared/
    └── Kernel/
```

Makna struktur:

```text
Application
    ↓
Namespace / Area
    ↓
Module / Bounded Capability
```

Namespace baseline:

| Namespace | Fungsi |
|---|---|
| `Console` | administrasi sistem tingkat tinggi |
| `Organization` | struktur yayasan, unit, lokasi, dan organisasi |
| `StudentManagement` | area manajemen santri |
| `Academic` | area akademik |
| `HumanResource` | area SDM/personalia |
| `Finance` | area keuangan |
| `Communication` | komunikasi/publikasi |
| `Platform` | capability lintas domain |

Catatan:

- namespace mengategorikan module, bukan menggantikan module boundary;
- module dalam namespace yang sama tidak boleh melakukan direct model/repository mutation lintas module;
- daftar di atas adalah baseline Release awal;
- namespace dapat memperoleh module baru bila capability baru memang termasuk area tersebut;
- module tidak dibuat hanya karena ada menu/halaman baru;
- module tidak digabung hanya agar jumlah folder terlihat sedikit.

Contoh namespace PHP:

```text
App\Modules\Console\AccessControl
App\Modules\StudentManagement\Student
App\Modules\Academic\AcademicPeriod
App\Modules\Finance\StudentFinance
App\Modules\Platform\Notification
```

---

# 7. Struktur Standar Module

Baseline:

```text
app/Modules/<Namespace>/<Module>/
├── module.json
├── module.php
├── permissions.php
├── ServiceProvider.php
├── README.md
│
├── Application/
│   ├── Actions/
│   ├── DTO/
│   └── Services/
│
├── Domain/
│   ├── Contracts/
│   ├── Entities/
│   ├── Events/
│   ├── Services/
│   └── ValueObjects/
│
├── Infrastructure/
│   ├── Models/
│   ├── Repositories/
│   ├── Observer/
│   └── Providers/
│
├── Presentation/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
├── Database/
│   ├── Migrations/
│   ├── Seeders/
│   ├── Factories/
│   └── Providers/
│
└── Routes/
    ├── web.php
    ├── api.php
    ├── console.php
    └── channels.php
```

Folder berikut bersifat optional/on-demand:

```text
Infrastructure/Adapters/
Infrastructure/Integrations/
Application/Commands/
Application/Queries/
Domain/Policies/
Domain/Specifications/
Domain/Exceptions/
```

Jangan membuat folder kosong hanya untuk memenuhi diagram.

---

# 8. Module Metadata

## module.json

Contoh:

```json
{
    "name": "Student",
    "namespace": "App\\Modules\\StudentManagement\\Student",
    "enabled": true,
    "version": "0.1.0",
    "description": "Student lifecycle and pesantren student administration",
    "dependencies": [
        "Organization",
        "Guardian"
    ]
}
```

## module.php

Concern runtime:

```text
route prefix
navigation
feature flag
configuration key
event registration
```

## permissions.php

Contoh:

```php
return [
    'student.view',
    'student.create',
    'student.update',
    'student.transfer',
    'student.graduate',
];
```

Permission dimiliki module yang menggunakan capability tersebut.

AccessControl mengorkestrasi role dan permission, tetapi ownership permission tetap pada module asal.

---

# 9. Bounded Context / Module Baseline

## 9.1 AccessControl

Tanggung jawab:

```text
authentication integration
users
account lifecycle
roles
permissions
access policies
2FA integration
profile access
```

Teknologi:

```text
Laravel Fortify
Spatie Permission
Laravel Gate / Policies
```

AccessControl tidak menyimpan data HR lengkap.

User account berbeda dari employee/student/guardian profile.

Relasi konseptual:

```text
User Account
    ↕
Person / Employee / Student / Guardian identity
```

## 9.2 Organization

Tanggung jawab:

```text
yayasan
pesantren
unit
lokasi
struktur organisasi
unit hierarchy
organization affiliation
```

Contoh:

```text
Yayasan
└── Pesantren
    ├── MTs
    ├── MA
    ├── Asrama Putra
    └── Asrama Putri
```

Organization adalah source of truth struktur unit.

## 9.3 SystemSetting

Tanggung jawab:

```text
global application settings
dynamic application options
setting definitions
setting groups
setting values
module-owned setting registration
application preferences
```

Prinsip:

```text
.env / config
= konfigurasi deployment/teknis

SystemSetting
= konfigurasi aplikasi/bisnis yang dapat diubah dinamis

Master Data
= data domain milik module terkait
```

Contoh yang cocok:

```text
application_name
timezone
date_format
student_number_prefix
invoice_number_prefix
payment_due_warning_days
default_academic_year
```

Contoh yang bukan SystemSetting:

```text
DB_HOST
APP_KEY
QUEUE_CONNECTION
Student
Guardian
Classroom
Subject
Employee
Unit
```

SystemSetting tidak boleh menjadi generic key/value dumping ground.

## 9.4 AcademicPeriod

Tanggung jawab:

```text
academic year
semester
term
academic calendar
active academic period
period opening/closing
```

Contoh:

```text
Academic Year 2026/2027
├── Semester 1
└── Semester 2
```

AcademicPeriod menyediakan historical context untuk Academic, Finance, dan reporting.

## 9.5 HumanResource

Tanggung jawab:

```text
employee
teacher
ustadz
staff
position
employment status
work assignment
attendance
basic personnel administration
```

Contoh tipe:

```text
Teacher
Ustadz
Musyrif
Finance Staff
Administration Staff
Unit Head
```

Payroll bukan bagian Release awal kecuali kemudian diputuskan.

## 9.6 Student

Tanggung jawab:

```text
student master
student identity
registration
status
unit affiliation
student number
transfer
leave
graduation
alumni transition
student lifecycle
```

Business identifier harus stabil.

Contoh:

```text
STD-2026-000001
```

Student tidak mengelola data autentikasi secara langsung.

## 9.7 Guardian

Tanggung jawab:

```text
guardian identity
relationship to student
contact information
primary guardian
billing contact
emergency contact
guardian-student relationship
```

Satu guardian dapat berelasi ke banyak student.

Satu student dapat memiliki lebih dari satu guardian.

## 9.8 Dormitory

Tanggung jawab:

```text
dormitory building
room
capacity
musyrif assignment
student placement
placement history
room transfer
occupancy
```

Contoh:

```text
Asrama Putra A
├── Kamar A-01
├── Kamar A-02
└── Kamar A-03
```

Dormitory tidak mengambil alih student lifecycle.

## 9.9 Academic

Tanggung jawab:

```text
class
subject
curriculum reference
teaching assignment
schedule
student enrollment
attendance
assessment
grade
report card
academic progression
```

Model konseptual:

```text
Academic Period
    ↓
Class
    ↓
Subject Offering
    ↓
Teaching Assignment
    ↓
Schedule
    ↓
Attendance / Assessment
    ↓
Grade / Report Card
```

Historical academic record harus mempertahankan konteks periode, kelas, subject, dan teacher assignment yang berlaku saat transaksi terjadi.

## 9.10 Student Finance

Tanggung jawab:

```text
fee definition
student billing
invoice
payment
payment allocation
cash transaction
receivable status
financial reporting
```

Baseline:

```text
Billing
    ↓
Invoice
    ↓
Payment
    ↓
Allocation
    ↓
Balance
```

Payment gateway/VA/QRIS dapat ditambahkan melalui Infrastructure/Integrations saat dibutuhkan.

Finance tidak boleh mengubah Student model langsung.

## 9.11 Document

Tanggung jawab:

```text
document metadata
document classification
document ownership/reference
media attachment
document lifecycle
controlled download
student document
employee document
organization document
```

Spatie Media Library digunakan sebagai adapter file.

Domain tidak bergantung langsung pada implementasi Spatie untuk rule kritis.

## 9.12 Announcement

Tanggung jawab:

```text
announcement
audience
publish schedule
visibility
publication state
attachment reference
```

Audience dapat berupa:

```text
all users
unit
role
student group
guardian group
employee group
```

## 9.13 Notification

Tanggung jawab:

```text
notification orchestration
database notification
email notification
future WhatsApp integration
delivery state
notification preference where applicable
```

Notification merupakan side effect, bukan source of truth domain utama.

## 9.14 Reporting

Tanggung jawab:

```text
dashboard
operational statistics
management reports
exports
read models
aggregated views
```

Reporting diperbolehkan menggunakan optimized read model/query selama tidak melakukan business mutation lintas module.

## 9.15 AuditTrail

Tanggung jawab:

```text
actor
action
subject_type
subject_id
old_values
new_values
reason
occurred_at
request_id
ip/device metadata when appropriate
```

Audit Trail berbeda dari application log:

```text
Application Log
= debugging / technical operation

Audit Trail
= business traceability / accountability
```

---

# 10. User Management vs Human Resource

Pemisahan kritis:

```text
AccessControl
= account, login, role, permission, account status

HumanResource
= employee/teacher/ustadz/staff identity and employment data

Student
= student identity and lifecycle

Guardian
= guardian identity and relationship
```

Seseorang dapat memiliki profile domain tanpa harus memiliki akun.

Contoh:

```text
Guardian
    -> belum memiliki User Account
    -> tetap valid sebagai guardian record
```

Jika kemudian diberi akses portal:

```text
Guardian
    -> linked User Account
```

Relasi ini tidak menggabungkan bounded context.

---

# 11. Workflow Student Lifecycle

Contoh:

```text
Candidate / Registration
        ↓
Student Registered
        ↓
Active
        ↓
Placed in Unit / Class / Dormitory
        ↓
Academic & Finance Activities
        ↓
Transfer / Leave / Graduation
        ↓
Alumni
```

Status harus eksplisit.

Hindari boolean explosion seperti:

```text
is_active
is_graduated
is_alumni
is_transferred
is_suspended
```

Gunakan lifecycle state bila kompleks.

---

# 12. Academic Workflow

```text
Academic Period Open
        ↓
Class Created
        ↓
Subject Offering
        ↓
Teacher Assignment
        ↓
Student Enrollment
        ↓
Schedule
        ↓
Attendance
        ↓
Assessment
        ↓
Grade Finalization
        ↓
Report Card
```

Final grade tidak boleh sekadar ditimpa setelah finalisasi.

Perubahan setelah finalisasi harus memiliki:

```text
revision
reason
actor
timestamp
audit record
```

---

# 13. Finance Workflow

```text
Fee Definition
      ↓
Billing Generation
      ↓
Invoice
      ↓
Payment
      ↓
Payment Allocation
      ↓
Balance / Settlement
```

Historical invoice harus mempertahankan nominal, due date, dan definition context yang berlaku saat invoice diterbitkan.

Perubahan master fee tidak boleh mengubah invoice historis.

---

# 14. Dormitory Workflow

```text
Dormitory
    ↓
Room
    ↓
Capacity
    ↓
Student Placement
    ↓
Placement History
```

Rule contoh:

```text
room active
capacity available
student eligible for dormitory
gender/unit rule fulfilled
placement period valid
```

Transfer kamar harus meninggalkan history.

---

# 15. Shared Kernel

Lokasi:

```text
app/Shared/Kernel/
├── Application/
├── Domain/
│   ├── Contracts/
│   ├── Events/
│   ├── Exceptions/
│   ├── ValueObjects/
│   └── Support/
└── Infrastructure/
```

Shared Kernel harus kecil.

Boleh berisi konsep universal seperti:

```text
AggregateRoot
DomainEvent
DomainException
EntityId / ULID abstraction
Money
DateRange
Pagination contract
Clock contract
CurrentUser contract
Transactional contract
```

Tidak boleh menjadi:

```text
Helpers/
Utils/
Common/
Misc/
EverythingReusable/
```

Rule:

> Jika konsep memiliki makna kuat hanya pada satu module, tetap simpan di module tersebut.

---

# 16. CQRS Strategy

CQRS diterapkan secara pragmatis.

Tidak menggunakan database read/write terpisah pada Release awal.

```text
Write Model -> MySQL
Read Model  -> MySQL
```

Command mengubah state.

Query read-only.

Contoh command:

```text
RegisterStudent
AssignGuardian
PlaceStudentInDormitory
CreateClass
AssignTeacher
RecordAttendance
GenerateInvoice
RecordPayment
PublishAnnouncement
```

Contoh query:

```text
GetStudentDetail
GetGuardianStudents
GetRoomOccupancy
GetClassRoster
GetAttendanceSummary
GetOutstandingInvoices
GetDashboardMetrics
```

Query dapat menggunakan optimized Eloquent/read queries.

---

# 17. Application Actions

`Actions/` digunakan untuk orchestration use case yang melibatkan lebih dari satu operasi/domain service atau membutuhkan transaction boundary.

Contoh:

```text
RegisterNewStudentAction
```

dapat melakukan:

```text
register student
link guardian
assign organization unit
create initial document requirements
write audit trail
dispatch StudentRegistered
```

Jangan memindahkan orchestration kompleks ke Controller.

---

# 18. Domain Events

Domain event menyatakan fakta bisnis yang telah terjadi.

Contoh:

```text
UserCreated
StudentRegistered
StudentTransferred
StudentGraduated
GuardianLinked
EmployeeRegistered
StudentPlacedInDormitory
ClassCreated
TeacherAssigned
AttendanceRecorded
InvoiceIssued
PaymentRecorded
AnnouncementPublished
DocumentAttached
```

Naming rule:

```text
Past tense
```

---

# 19. Event Listeners

Listener digunakan untuk side effect dan decoupling.

Contoh:

```text
StudentRegistered
    ├── initialize reporting projection
    ├── create optional onboarding notification
    └── write audit trail
```

Hindari:

```text
StudentService
    -> new FinanceService
    -> new DormitoryService
    -> new NotificationService
    -> new ReportingService
```

yang menciptakan tight coupling.

Untuk invariant lintas module yang harus sinkron, gunakan published contract/application service yang eksplisit.

---

# 20. Event Sinkron vs Asinkron

Default:

```text
Domain invariant       : synchronous
Critical state update  : synchronous
Audit record           : synchronous
Email notification     : queue
WhatsApp notification  : queue
Large export           : queue
Document generation    : queue
Analytics projection   : queue
```

Jangan menjadikan semua listener asynchronous.

---

# 21. Kontrak Antar-Module

Module tidak mengambil Eloquent Model module lain lalu melakukan business mutation secara langsung.

Hindari:

```php
use App\Modules\StudentManagement\Student\Infrastructure\Models\StudentModel;
```

di Finance hanya untuk mengubah student.

Gunakan:

```text
Application Contract
Domain Contract
Application Service
Command
Query
Domain Event
Integration Event
```

Komunikasi sinkron antar-module:

```text
published contract / application service
```

Komunikasi yang dapat dipisahkan:

```text
domain event / integration event
```

Pseudo-microservice HTTP internal di dalam monolith tidak digunakan.

---

# 22. Dependency Direction Antar-Module

Contoh arah dependensi:

```text
AccessControl

Organization

SystemSetting

AcademicPeriod
    -> Organization

HumanResource
    -> Organization

Student
    -> Organization
    -> Guardian contract where required

Guardian

Dormitory
    -> Organization
    -> Student contract
    -> HumanResource contract

Academic
    -> Organization
    -> AcademicPeriod
    -> Student contract
    -> HumanResource contract

StudentFinance
    -> Organization
    -> Student contract
    -> Guardian query/contract where required

Document
    -> generic subject references

Announcement
    -> Organization
    -> audience contracts as required

Notification
    -> listens to events / notification contracts

Reporting
    -> read/query contracts or projections

AuditTrail
    -> listens to events / audit contract
```

Circular dependency dilarang.

Jika A membutuhkan B dan B membutuhkan A:

1. evaluasi ulang boundary;
2. gunakan event;
3. ekstrak contract kecil;
4. gunakan neutral identifier/reference;
5. pindahkan ke Shared Kernel hanya bila benar-benar universal.

---

# 23. Database Strategy

Release awal:

```text
MySQL
single database
module-owned tables
module-owned migrations
module-owned seeders
module-owned factories
foreign keys where appropriate
transactional consistency
```

Tidak menggunakan database per module.

Migration berada di:

```text
app/Modules/<Namespace>/<Module>/Database/Migrations/
```

Naming contoh:

```text
users
organizations
organization_units
academic_years
employees
students
guardians
student_guardians
dormitories
dormitory_rooms
student_room_placements
classes
subjects
teaching_assignments
attendances
fee_definitions
invoices
payments
documents
announcements
audit_entries
```

Nama final table ditentukan saat module design dan harus konsisten dengan ownership.

---

# 24. Identifier Strategy — ULID

Baseline resmi SakaSantri:

> **Primary identifier (`id`) pada table aplikasi menggunakan ULID.**

Tidak menggunakan pola default:

```text
BIGINT auto increment sebagai id utama
+
public_id ULID terpisah
```

Baseline:

```text
Primary Key:
ULID

Business Identifier:
field terpisah sesuai domain
```

Contoh:

```text
id           = 01K2Y7K9T6B4D4C6R9F2Z8M3QH
student_no   = STD-2026-000001
employee_no  = EMP-2026-0012
invoice_no   = INV-2026-000812
```

`student_no`, `employee_no`, `invoice_no`, dan business identifier lain:

```text
bukan primary key
bukan pengganti ULID
dapat mengikuti format bisnis masing-masing module
```

Contoh migration Laravel:

```php
Schema::create('students', function (Blueprint $table) {
    $table->ulid('id')->primary();
    // ...
});
```

Foreign key ke entity ULID menggunakan tipe yang kompatibel:

```php
$table->foreignUlid('student_id');
```

Eloquent model yang menggunakan ULID harus dikonfigurasi konsisten, misalnya dengan `HasUlids` atau mekanisme project-level yang setara.

Aturan:

1. semua table milik aplikasi yang memiliki surrogate primary identifier menggunakan ULID;
2. foreign key yang menunjuk ULID harus menggunakan tipe ULID/string yang kompatibel;
3. pure pivot table yang secara domain cukup menggunakan composite key tidak wajib diberi surrogate `id`;
4. jangan menambahkan `public_id` hanya untuk menduplikasi `id` ULID;
5. vendor/package table harus diselaraskan dengan strategi ULID bila table tersebut menjadi bagian dari identity/reference model aplikasi dan package mendukung konfigurasi tersebut;
6. business number tetap dipisahkan dari technical identifier.

Alasan baseline:

```text
identifier dapat dibuat tanpa koordinasi auto-increment
aman digunakan pada URL/API tanpa mengekspos urutan record
lebih sesuai untuk modular architecture dan future integration
sortable secara waktu dibanding UUID acak tradisional
```

---

# 25. Soft Delete

Tidak semua table otomatis menggunakan soft delete.

Gunakan berdasarkan makna domain.

Contoh:

```text
Student         -> lifecycle/status lebih penting daripada delete
Employee        -> employment status/history
Invoice         -> immutable financial record
Payment         -> immutable financial record
Audit Entry     -> never delete through normal UI
Draft           -> delete mungkin diperbolehkan
```

---

# 26. Historical Traceability

Mutable master data tidak boleh mengubah makna history.

Contoh yang harus dipertahankan:

```text
Student status at transaction time
Academic Year / Semester
Class / Subject Offering
Teacher Assignment
Fee Definition snapshot
Invoice amount and due date
Dormitory placement period
Approval / actor
Document version
```

Jika data master berubah, record historis tetap dapat dijelaskan sesuai konteks saat transaksi terjadi.

---

# 27. Authorization Architecture

Ada dua lapisan utama:

## System Authorization

Dikelola:

```text
AccessControl
Spatie Permission
Laravel Gate / Policy
```

Contoh:

```text
student.update
academic.grade.finalize
finance.payment.record
report.export
```

## Domain Eligibility / Business Rule

Dikelola oleh module pemilik domain.

Contoh:

```text
user memiliki permission finance.payment.record
```

belum berarti semua pembayaran dapat diubah tanpa memeriksa:

```text
invoice state
payment state
accounting period
business rule
```

RBAC tidak menggantikan domain rule.

---

# 28. Media & File Architecture

Spatie Media Library digunakan sebagai file adapter.

Use case:

```text
Student Photo
Identity Document
Family Card
Employee Document
Certificate
Academic Attachment
Announcement Attachment
Payment Proof
Generated Report
```

Domain menggunakan konsep:

```text
DocumentReference
MediaReference
AttachmentReference
```

Infrastructure mengadaptasikan ke Media Library.

File yang sensitif tidak diasumsikan public URL.

---

# 29. Frontend Architecture

Baseline:

```text
resources/js/
├── app.tsx
├── components/
│   ├── ui/
│   └── shared/
├── layouts/
├── hooks/
├── lib/
├── types/
└── modules/
    ├── access-control/
    ├── organization/
    ├── system-setting/
    ├── academic-period/
    ├── human-resource/
    ├── student/
    ├── guardian/
    ├── dormitory/
    ├── academic/
    ├── finance/
    ├── document/
    ├── announcement/
    ├── notification/
    ├── reporting/
    └── audit-trail/
```

Per module:

```text
student/
├── pages/
├── components/
├── hooks/
├── types/
└── schemas/
```

shadcn/ui berada di:

```text
components/ui
```

Komponen business-specific tetap berada di module terkait atau `components/shared` jika benar-benar lintas domain.

---

# 30. Ziggy Routing

Frontend menggunakan Laravel named routes melalui Ziggy.

Contoh:

```ts
router.visit(route('students.show', student.id))
```

Convention:

```text
students.index
students.show
students.create
students.store
students.edit
students.update

finance.invoices.index
finance.invoices.show
finance.payments.store

academic.classes.index
academic.attendance.store
```

Hindari route name berdasarkan nama controller implementation.

---

# 31. Routing Module

Setiap module memiliki route sendiri:

```text
Routes/
├── web.php
├── api.php
├── console.php
└── channels.php
```

`ServiceProvider` module memuat file route yang relevan.

Root `routes/web.php` tidak menjadi tempat seluruh route domain dikumpulkan.

---

# 32. Service Provider

Setiap module memiliki:

```text
ServiceProvider.php
```

Tanggung jawab:

```text
bind contracts
register repositories
register event listeners
load module routes
load module migrations
merge module configuration
register policies
load permission definitions
register infrastructure providers
```

`ServiceProvider` tidak mengandung business logic.

---

# 33. Adapter & Integration

Folder berikut optional:

```text
Infrastructure/Adapters/
Infrastructure/Integrations/
```

Gunakan `Adapters/` untuk implementasi port terhadap teknologi.

Contoh:

```text
Domain/Contracts/DocumentStorage.php
    ↓
Infrastructure/Adapters/SpatieDocumentStorage.php
```

Gunakan `Integrations/` untuk sistem eksternal.

Contoh masa depan:

```text
Infrastructure/Integrations/WhatsApp/
Infrastructure/Integrations/PaymentGateway/
Infrastructure/Integrations/GovernmentReporting/
Infrastructure/Integrations/ExternalEmail/
```

Jangan membuat integration ceremony tanpa kebutuhan nyata.

---

# 34. Notification Channel

Channel awal:

```text
database
email
```

Integrasi berikut dapat ditambahkan bertahap:

```text
WhatsApp
SMS
push notification
```

WhatsApp dan email merupakan integration concern, bukan alasan membuat microservice baru pada Release awal.

---

# 35. Queue

Queue digunakan untuk:

```text
email
WhatsApp delivery
large exports
document generation
media conversion
read projections
analytics
non-critical notifications
```

Tidak digunakan untuk rule yang menentukan validitas transaksi utama secara immediate.

---

# 36. Baseline Keamanan

Minimal:

```text
Fortify authentication
2FA where enabled
role + permission
CSRF
request validation
rate limiting
secure file access
audit trail
principle of least privilege
authorization per module
sensitive data access control
```

Data wali, santri, pegawai, dan keuangan diperlakukan sebagai data yang aksesnya harus dibatasi berdasarkan kebutuhan kerja.

---

# 37. Testing Strategy

## Unit Tests

```text
domain rules
value objects
lifecycle state
billing rules
occupancy rules
academic rules
```

## Feature Tests

```text
commands
actions
authorization
controllers
Inertia responses
database effects
events
```

## Integration Tests

```text
Spatie Permission
Media Library
Queue
Mail
Storage
cross-module listeners
external integrations when added
```

Folder:

```text
tests/
└── Modules/
    ├── Student/
    ├── Academic/
    ├── StudentFinance/
    └── Dormitory/
```

---

# 38. Module Generator

Module Generator menjadi bagian Phase 0.

Command utama harus menerima namespace dan module, misalnya:

```bash
php artisan make:module StudentManagement Student
php artisan make:module Finance StudentFinance
```

Jika implementasi command memakai format separator, format alternatif diperbolehkan selama hasil path dan namespace sama, misalnya:

```bash
php artisan make:module StudentManagement/Student
```

Output minimum harus mengikuti struktur standar module.

Generator tidak membuat secara default:

```text
Infrastructure/Adapters/
Infrastructure/Integrations/
Application/Commands/
Application/Queries/
```

Child generator dapat digunakan ketika diperlukan.

Contoh:

```bash
php artisan module:make-action StudentManagement Student RegisterStudent
php artisan module:make-dto StudentManagement Student StudentData
php artisan module:make-contract StudentManagement Student StudentRepository
php artisan module:make-event StudentManagement Student StudentRegistered
php artisan module:make-model StudentManagement Student Student
php artisan module:make-repository StudentManagement Student EloquentStudentRepository
php artisan module:make-adapter Platform Document SpatieDocumentStorage
php artisan module:make-integration Finance StudentFinance PaymentGateway
```

Generator harus menjaga pola namespace:

```text
App\Modules\<Namespace>\<Module>
```

dan path:

```text
app/Modules/<Namespace>/<Module>/
```

Generator juga harus menghasilkan migration/model convention yang konsisten dengan **ULID primary identifier**.

---

# 39. Release Awal

Prioritas capability:

```text
01 Access Control
02 Organization
03 System Setting
04 Academic Period
05 Human Resource
06 Student
07 Guardian
08 Dormitory
09 Academic
10 Student Finance
11 Document
12 Announcement
13 Notification
14 Reporting
15 Audit Trail
```

Scope ini dapat diimplementasikan secara incremental.

Capability besar berikut tidak harus langsung dibuat pada Release awal:

```text
Payroll
Procurement
Inventory
Asset
POS/Koperasi
Laundry
Klinik
Perpustakaan
Workflow Engine generik
AI Assistant
Donasi/Wakaf
Payment Gateway/VA/QRIS
Public API penuh
BI kompleks
```

---

# 40. Urutan Implementasi yang Direkomendasikan

## Phase 0 — Foundation

```text
Laravel 12 starter kit
Fortify
Inertia React
TypeScript
Tailwind
shadcn/ui
Framer Motion
Ziggy
Spatie Permission
Spatie Media Library
remove Laravel Boost
module loader
Module Generator
custom module stubs
Shared Kernel
base event architecture
CQRS conventions
testing conventions
documentation structure
```

## Phase 1 — Console, Organization & Period Foundation

```text
Console/AccessControl
Console/SystemSetting
Organization/Organization
Academic/AcademicPeriod
```

## Phase 2 — People Core

```text
HumanResource/HumanResource
StudentManagement/Student
StudentManagement/Guardian
```

## Phase 3 — Residential & Academic Core

```text
StudentManagement/Dormitory
Academic/Academic
```

## Phase 4 — Finance & Documents

```text
Finance/StudentFinance
Platform/Document
```

## Phase 5 — Communication & Governance

```text
Communication/Announcement
Platform/Notification
Console/AuditTrail
```

## Phase 6 — Reporting

```text
Platform/Reporting
dashboard
exports
management views
```

## Phase 7 — Expansion

```text
Payroll
Procurement
Inventory
Asset
POS/Koperasi
Laundry
Klinik
Perpustakaan
Donasi/Wakaf
Payment Gateway
Government Reporting
Public API
```

## Phase 8 — Intelligence

```text
operational analytics
financial intelligence
student risk indicators
attendance trend
capacity planning
AI assistant
```

Intelligence layer dibangun setelah volume dan kualitas data memadai.

---

# 41. Prinsip Penambahan Module

Sebelum membuat module baru, jawab:

1. Apakah capability memiliki bahasa/domain rule yang berbeda?
2. Apakah lifecycle datanya berbeda?
3. Apakah ownership datanya jelas?
4. Apakah modul yang ada akan terlalu banyak tanggung jawab jika capability dimasukkan?
5. Apakah boundary ini membantu dependency dan perubahan kode?
6. Apakah module baru benar-benar memberi nilai atau hanya memecah CRUD?

Jangan membuat module berdasarkan menu UI.

---

# 42. Prinsip Integrasi Antarmodule

Prioritas:

```text
synchronous contract/service
domain event
integration event
query/read model
shared identifier
```

Hindari:

```text
direct Infrastructure Model mutation
direct repository access lintas module
direct table mutation lintas module
service locator global
pseudo-microservice HTTP internal
```

---

# 43. Work Package dan ADR

Task non-trivial menggunakan:

```text
docs/work/<namespace>/<Module>/<work-id>/
├── ADR.md
├── PLAN.md
└── TASKS.md
```

ADR hanya dibuat/diperbarui bila ada keputusan bermakna.

Jangan menggunakan ADR sebagai catatan harian.

---

# 44. Source of Truth Dokumentasi

```text
AGENTS.md
= aturan repository-wide

docs/architecture/
= arsitektur jangka panjang

app/Modules/<Namespace>/<Module>/README.md
= kebenaran level module

docs/work/
= keputusan dan eksekusi task
```

Dokumen canonical baseline:

```text
docs/architecture/SakaSantri_Architecture_Baseline.md
```

Historical revisions jika diperlukan:

```text
docs/architecture/history/
```

---

# 45. Guardrail untuk Codex

Codex tidak boleh:

```text
mengubah bounded context tanpa keputusan eksplisit
mengganti stack utama
mengaktifkan SaaS/multi-tenancy tanpa keputusan
menggunakan Laravel Boost
menambahkan Wayfinder
memindahkan migration module ke database/migrations global
mengakses langsung model/repository module lain untuk business mutation
membuat generic repository tanpa manfaat domain
membuat CQRS ceremony pada CRUD sederhana
membuat adapter/integration folder kosong
membuat microservice tanpa keputusan arsitektur
```

Codex harus:

```text
mengikuti AGENTS.md
membaca architecture doc yang relevan
menggunakan module generator
membuat work package untuk task non-trivial
menjaga module ownership
menggunakan ULID untuk primary id table aplikasi
menggunakan foreign ULID yang kompatibel
menjalankan test yang relevan
memperbarui dokumentasi yang memang terdampak
```

---

# 46. Prinsip Akhir

Arsitektur SakaSantri harus:

1. cukup terstruktur untuk menjaga domain boundary;
2. cukup pragmatis agar tetap produktif dikerjakan satu developer;
3. tidak membuat abstraction tanpa business value;
4. tidak memecah sistem terlalu dini;
5. mempertahankan historical traceability;
6. memudahkan ekspansi module pada tahap berikutnya;
7. tetap konsisten dengan Laravel ecosystem;
8. memudahkan Codex/AI agent bekerja secara terarah melalui dokumentasi canonical.

> **SakaSantri dibangun sebagai modular monolith yang berkembang bersama kebutuhan pesantren, bukan sebagai kumpulan CRUD dan bukan sebagai microservices prematur.**
