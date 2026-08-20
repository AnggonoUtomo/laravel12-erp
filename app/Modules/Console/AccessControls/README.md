# Kontrol Akses

**Module:** `Console.AccessControls`
**Architecture:** DDD-lite

## Ownership

Mengelola role dan permission console. Kontrak publik dipertahankan pada route `access-control.*`, permission `access-control.*`, dan halaman Inertia `console/access-control/index`.

## Dependencies

- `Console.AuditLogs` untuk pencatatan perubahan role dan permission.
- Spatie Permission melalui model aplikasi `App\Models\Role` dan `App\Models\Permission`.
