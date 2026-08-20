# TASKS — Fondasi Baseline v0.1-r1

**Status:** IN PROGRESS

- [x] Audit dokumentasi, kode, dan database aktif.
- [x] Hapus tabel legacy `hr_*` dan `dm_*` yang tidak memiliki module pemilik.
- [x] Tetapkan compatibility boundary dan ownership Console.
- [x] Rekonsiliasi schema/migration ledger.
- [x] Rekey ULID graph identity pada clone dan source; source dicutover dalam maintenance window setelah backup final terverifikasi.
- [x] Migrasikan `Console.AccessControls` ke DDD-lite dengan route target-first dan kontrak publik tetap.
- [x] Migrasikan `Console.SystemSettings` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.UserManagements` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.AuditLogs` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.LoginActivities` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.NotificationTemplates` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.ActivityCenters` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.QueueMonitors` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.SchedulerMonitors` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [x] Migrasikan `Console.GlobalSearches` ke DDD-lite dan perbarui semua consumer internal tanpa alias legacy.
- [ ] Migrasikan module system ke DDD-lite.
- [ ] Validasi regresi dan dokumentasi module.
