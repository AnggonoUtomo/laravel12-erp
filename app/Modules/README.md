# Module Contract

Struktur module memakai group/konteks di bawah `app/Modules`.

```txt
app/Modules/
  Console/
    AccessControls/
    SystemSettings/
    UserManagements/
  {Project}/
    {Module}/
```

Module baru dari generator memakai DDD-lite. Kontrak runtime mendukung struktur target berikut:

- `module.php` untuk metadata formal module.
- `module.json` untuk metadata yang mudah dibaca tooling.
- `README.md` untuk ownership, kontrak publik, dan dependency module.
- `Presentation/Routes/web.php` untuk route milik module.
- `ServiceProvider.php` untuk binding dan bootstrapping module.
- `permissions.php` untuk daftar permission dan default permission per role.
- `navigation.php` untuk item sidebar/menu module.

Route loader memprioritaskan `Presentation/Routes/web.php`. `routes.php` tetap didukung sebagai fallback untuk modul legacy dan tidak akan dimuat bersamaan dengan route target.

Generator tidak membuat `Infrastructure/Adapters` atau `Infrastructure/Integrations` secara kosong. Tambahkan keduanya hanya bila terdapat adapter atau integrasi nyata.

File akan di-discover otomatis oleh `App\Support\Modules\ModuleServiceProvider` dan `App\Support\Modules\ModuleRegistry`, termasuk module yang berada di dalam `Console` atau project lain yang dibuat secara eksplisit.

Panduan lebih detail tersedia di:

- `docs/architecture/starterkit-blueprint.md`
- `docs/README.md`
- `docs/guides/project-module-guide.md`
- `docs/architecture/shared-kernel.md`
- `docs/architecture/integration-layer.md`
- `docs/projects/accounting/roadmap.md`
- `docs/projects/crm/roadmap.md`
- `docs/projects/attendance/roadmap.md`
- `docs/projects/payroll/roadmap.md`

## Generator

Gunakan Artisan command untuk membuat module baru:

```bash
php artisan make:module Reports
```

Perintah di atas membuat module di project default dari `config/modules.php`, yaitu `Console`.

Untuk membuat module di project/group lain:

```bash
php artisan make:module WorkOrders --project=Operations
```

Atau gunakan namespace dan module sebagai dua argumen:

```bash
php artisan make:module StudentManagement Student
```

Output backend akan dibuat di `app/Modules/Operations/WorkOrders`, sedangkan halaman Inertia awal dibuat di `resources/js/pages/operations/work-orders`.

Aturan route generator:

- Project `Console` memakai route tanpa prefix project, misalnya `php artisan make:module Reports` menghasilkan `/reports` dan route name `reports.*`.
- Project non-Console memakai prefix project, misalnya `php artisan make:module WorkOrders --project=Operations` menghasilkan `/operations/work-orders` dan route name `operations.work.orders.*`.
- Project acronym uppercase seperti `ERP` akan dibuat sebagai slug `erp`, bukan `e-r-p`.

Catatan penting: generator tidak menebak project dari posisi terminal. Gunakan `--project` untuk konteks project yang eksplisit, atau ubah `MODULE_DEFAULT_PROJECT` di `.env` jika project aktif harian bukan `Console`.

## Aturan Update Dokumentasi

Setiap perubahan pada generator, module contract, struktur folder, route convention, permission convention, atau arsitektur lintas module wajib langsung diikuti update dokumen terkait. Untuk perubahan generator, minimal update:

- `docs/guides/project-module-guide.md`
- `app/Modules/README.md`

Contoh `module.php`:

```php
use App\Modules\Operations\WorkOrders\ServiceProvider;

return [
    'name' => 'WorkOrders',
    'project' => 'Operations',
    'title' => 'Work Orders',
    'slug' => 'work-orders',
    'description' => 'Pengelolaan work order operasional.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [
        ServiceProvider::class,
    ],
    'dependencies' => [],
    'exports' => [
        'routes' => true,
        'permissions' => true,
        'navigation' => true,
    ],
    'events' => [],
    'listeners' => [],
];
```

Contoh `permissions.php`:

```php
return [
    'permissions' => [
        'module.view',
        'module.create',
    ],
    'roles' => [
        'admin' => ['module.view', 'module.create'],
        'staff' => ['module.view'],
    ],
];
```

Contoh `navigation.php`:

```php
return [
    'group' => 'Produktivitas',
    'sort' => 40,
    'items' => [
        [
            'title' => 'Nama Menu',
            'url' => '/module-url',
            'icon' => 'Users',
            'permissions' => ['module.view'],
        ],
    ],
];
```
