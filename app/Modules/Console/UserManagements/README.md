# Manajemen User

**Module:** `Console.UserManagements`
**Architecture:** DDD-lite

## Ownership

Lifecycle akun pengguna aplikasi: pembuatan akun, penugasan role dan permission, avatar, pengarsipan/pemulihan, serta impersonation terotorisasi.

## Public Contract

Route `users.*`, halaman Inertia `console/users/index`, permission `users.*`, dan service aplikasi untuk lifecycle akun pengguna adalah kontrak yang dipertahankan.
