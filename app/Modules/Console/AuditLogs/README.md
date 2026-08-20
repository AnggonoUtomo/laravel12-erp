# Audit Logs

**Module:** `Console.AuditLogs`
**Architecture:** DDD-lite

## Ownership

Jejak audit aksi penting aplikasi, termasuk actor, subject, konteks request, perubahan lama/baru yang telah disanitasi, dan metadata pelacakan.

## Public Contract

Route `audit-logs.index`, halaman Inertia `console/audit-logs/index`, permission `audit-logs.view`, serta service pencatatan audit dipertahankan sebagai kontrak internal aplikasi.
