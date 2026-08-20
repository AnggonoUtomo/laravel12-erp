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

Setiap module dapat menyediakan file berikut di root folder module:

- `module.php` untuk metadata formal module.
- `routes.php` untuk route milik module.
- `permissions.php` untuk daftar permission dan default permission per role.
- `navigation.php` untuk item sidebar/menu module.
- `Providers/*ServiceProvider.php` untuk policy, gate, binding, event, dan bootstrapping module.

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
use App\Modules\Operations\WorkOrders\Providers\WorkOrdersServiceProvider;

return [
    'name' => 'WorkOrders',
    'project' => 'Operations',
    'title' => 'Work Orders',
    'slug' => 'work-orders',
    'description' => 'Pengelolaan work order operasional.',
    'version' => '1.0.0',
    'enabled' => true,
    'providers' => [
        WorkOrdersServiceProvider::class,
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
