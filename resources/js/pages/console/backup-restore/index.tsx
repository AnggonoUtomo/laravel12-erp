import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { ArchiveRestore, DatabaseBackup, Download, FileJson, ShieldAlert, Upload } from 'lucide-react';
import { type FormEvent } from 'react';
import { BackupRestoreHeader } from './backup-restore-components/backup-restore-header';
import { BackupSummaryCards } from './backup-restore-components/backup-summary-cards';
import type { BackupOverview, BackupRestoreAbilities, FullRestoreForm, RestoreForm } from './types';
import { sectionLabel } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Backup & Restore',
        href: '/backup-restore',
    },
];

type Props = {
    overview: BackupOverview;
    can: BackupRestoreAbilities;
};

export default function BackupRestore({ overview, can }: Props) {
    const form = useForm<RestoreForm>({
        backup: null,
        restore_system_settings: true,
        restore_notification_templates: true,
    });
    const fullForm = useForm<FullRestoreForm>({
        backup: null,
        restore_database: true,
        restore_storage_public: true,
        dry_run: true,
        confirmation: '',
    });

    const submitRestore = (event: FormEvent) => {
        event.preventDefault();

        form.post(route('backup-restore.restore'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitFullRestore = (event: FormEvent) => {
        event.preventDefault();

        fullForm.post(route('backup-restore.full.restore'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Backup & Restore" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <BackupRestoreHeader />
                <BackupSummaryCards overview={overview} />

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6">
                        <Card data-dashboard-card className="overflow-hidden">
                            <CardHeader className="border-b">
                                <CardTitle className="flex items-center gap-2">
                                    <span className="dashboard-icon icon-tone-emerald flex size-10 items-center justify-center rounded-md">
                                        <DatabaseBackup className="size-5" />
                                    </span>
                                    Full Database / Server Backup
                                </CardTitle>
                                <CardDescription>Download ZIP berisi dump database dan storage public.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-5 p-5 sm:p-6">
                                <div className="grid gap-3 sm:grid-cols-3">
                                    <div className="rounded-lg border p-4">
                                        <p className="text-muted-foreground text-xs">Database</p>
                                        <p className="mt-2 text-sm font-semibold break-words">{overview.database_name}</p>
                                        <p className="text-muted-foreground mt-1 text-xs">{overview.database_connection}</p>
                                    </div>
                                    <div className="rounded-lg border p-4">
                                        <p className="text-muted-foreground text-xs">Storage Public</p>
                                        <p className="mt-2 text-sm font-semibold">{overview.storage_public_exists ? 'Available' : 'Missing'}</p>
                                    </div>
                                    <div className="rounded-lg border p-4">
                                        <p className="text-muted-foreground text-xs">Storage Size</p>
                                        <p className="mt-2 text-sm font-semibold">{overview.storage_public_size.toLocaleString()} bytes</p>
                                    </div>
                                </div>

                                <Button asChild disabled={!can.fullExport} className="h-11 w-full sm:w-auto">
                                    <a href={route('backup-restore.full.export')}>
                                        <Download className="size-4" />
                                        Download Full Backup ZIP
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card data-dashboard-card className="overflow-hidden">
                            <CardHeader className="border-b">
                                <CardTitle className="flex items-center gap-2">
                                    <span className="dashboard-icon icon-tone-rose flex size-10 items-center justify-center rounded-md">
                                        <ArchiveRestore className="size-5" />
                                    </span>
                                    Full Restore
                                </CardTitle>
                                <CardDescription>Restore database dan storage aplikasi dari signed ZIP full backup.</CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 sm:p-6">
                                <form onSubmit={submitFullRestore} className="space-y-5">
                                    <div className="space-y-2">
                                        <label htmlFor="full_backup" className="text-sm font-medium">
                                            Signed ZIP backup
                                        </label>
                                        <Input
                                            id="full_backup"
                                            type="file"
                                            accept=".zip,application/zip"
                                            disabled={!can.fullRestore || fullForm.processing}
                                            onChange={(event) => fullForm.setData('backup', event.target.files?.[0] ?? null)}
                                        />
                                        <InputError message={fullForm.errors.backup} />
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <label className="flex items-start gap-3 rounded-lg border p-4 text-sm">
                                            <Checkbox
                                                checked={fullForm.data.restore_database}
                                                disabled={!can.fullRestore || fullForm.processing}
                                                onCheckedChange={(checked) => fullForm.setData('restore_database', checked === true)}
                                            />
                                            <span>
                                                <span className="block font-medium">Restore Database</span>
                                                <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                    Menjalankan dump SQL dan menimpa tabel database saat ini.
                                                </span>
                                            </span>
                                        </label>

                                        <label className="flex items-start gap-3 rounded-lg border p-4 text-sm">
                                            <Checkbox
                                                checked={fullForm.data.restore_storage_public}
                                                disabled={!can.fullRestore || fullForm.processing}
                                                onCheckedChange={(checked) => fullForm.setData('restore_storage_public', checked === true)}
                                            />
                                            <span>
                                                <span className="block font-medium">Restore Storage Files</span>
                                                <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                    Memulihkan file dari storage public.
                                                </span>
                                            </span>
                                        </label>
                                    </div>

                                    <label className="flex items-start gap-3 rounded-lg border border-sky-300/70 bg-sky-50 p-4 text-sm text-sky-950 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-100">
                                        <Checkbox
                                            checked={fullForm.data.dry_run}
                                            disabled={!can.fullRestore || fullForm.processing}
                                            onCheckedChange={(checked) => fullForm.setData('dry_run', checked === true)}
                                        />
                                        <span>
                                            <span className="block font-medium">Dry-run validation saja</span>
                                            <span className="mt-1 block text-xs leading-relaxed opacity-80">
                                                Validasi signature, checksum, manifest, dan keamanan ZIP tanpa menulis database atau storage. Matikan
                                                opsi ini hanya saat benar-benar siap restore.
                                            </span>
                                        </span>
                                    </label>

                                    <div className="space-y-2">
                                        <label htmlFor="full_confirmation" className="text-sm font-medium">
                                            Ketik RESTORE FULL BACKUP
                                        </label>
                                        <Input
                                            id="full_confirmation"
                                            value={fullForm.data.confirmation}
                                            disabled={!can.fullRestore || fullForm.processing}
                                            onChange={(event) => fullForm.setData('confirmation', event.target.value)}
                                            placeholder="RESTORE FULL BACKUP"
                                        />
                                        <InputError message={fullForm.errors.confirmation} />
                                    </div>

                                    <div className="rounded-lg border border-red-300/70 bg-red-50 p-4 text-red-950 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100">
                                        <div className="flex items-start gap-3">
                                            <ShieldAlert className="mt-0.5 size-5 shrink-0" />
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">Full restore adalah operasi berisiko tinggi.</p>
                                                <p className="text-xs leading-relaxed opacity-80">
                                                    Backup database saat ini terlebih dahulu. Restore database dapat mengubah user, role, permission,
                                                    audit log, session login, dan seluruh tabel aplikasi. Jika database direstore, kamu akan diminta
                                                    login ulang.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={!can.fullRestore || fullForm.processing} className="h-11 min-w-44">
                                            <Upload className="size-4" />
                                            {fullForm.data.dry_run ? 'Validate Full Backup' : 'Restore Full Backup'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card data-dashboard-card className="overflow-hidden">
                            <CardHeader className="border-b">
                                <CardTitle className="flex items-center gap-2">
                                    <span className="dashboard-icon icon-tone-sky flex size-10 items-center justify-center rounded-md">
                                        <DatabaseBackup className="size-5" />
                                    </span>
                                    Export Setting
                                </CardTitle>
                                <CardDescription>Download backup konfigurasi yang bisa direstore ulang nanti.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-5 p-5 sm:p-6">
                                <div className="rounded-lg border border-dashed p-4">
                                    <p className="text-sm font-medium">Backup berisi</p>
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {overview.included_sections.map((section) => (
                                            <Badge key={section} variant="outline" className="capitalize">
                                                {sectionLabel(section)}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>

                                <Button asChild disabled={!can.export} className="h-11 w-full sm:w-auto">
                                    <a href={route('backup-restore.export')}>
                                        <Download className="size-4" />
                                        Download Backup JSON
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card data-dashboard-card className="overflow-hidden">
                            <CardHeader className="border-b">
                                <CardTitle className="flex items-center gap-2">
                                    <span className="dashboard-icon icon-tone-amber flex size-10 items-center justify-center rounded-md">
                                        <ArchiveRestore className="size-5" />
                                    </span>
                                    Restore Setting
                                </CardTitle>
                                <CardDescription>Upload file backup JSON dan pilih bagian yang ingin direstore.</CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 sm:p-6">
                                <form onSubmit={submitRestore} className="space-y-5">
                                    <div className="space-y-2">
                                        <label htmlFor="backup" className="text-sm font-medium">
                                            File Backup
                                        </label>
                                        <Input
                                            id="backup"
                                            type="file"
                                            accept="application/json,.json"
                                            disabled={!can.restore || form.processing}
                                            onChange={(event) => form.setData('backup', event.target.files?.[0] ?? null)}
                                        />
                                        <InputError message={form.errors.backup} />
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <label className="flex items-start gap-3 rounded-lg border p-4 text-sm">
                                            <Checkbox
                                                checked={form.data.restore_system_settings}
                                                disabled={!can.restore || form.processing}
                                                onCheckedChange={(checked) => form.setData('restore_system_settings', checked === true)}
                                            />
                                            <span>
                                                <span className="block font-medium">System Settings</span>
                                                <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                    SMTP, branding metadata, localization, pagination, security, password, dan maintenance setting.
                                                </span>
                                            </span>
                                        </label>

                                        <label className="flex items-start gap-3 rounded-lg border p-4 text-sm">
                                            <Checkbox
                                                checked={form.data.restore_notification_templates}
                                                disabled={!can.restore || form.processing}
                                                onCheckedChange={(checked) => form.setData('restore_notification_templates', checked === true)}
                                            />
                                            <span>
                                                <span className="block font-medium">Notification Templates</span>
                                                <span className="text-muted-foreground mt-1 block text-xs leading-relaxed">
                                                    Subject, body, variable, channel, dan status template notifikasi.
                                                </span>
                                            </span>
                                        </label>
                                    </div>

                                    <div className="rounded-lg border border-amber-300/70 bg-amber-50 p-4 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                        <div className="flex items-start gap-3">
                                            <ShieldAlert className="mt-0.5 size-5 shrink-0" />
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">Restore akan menimpa konfigurasi yang dipilih.</p>
                                                <p className="text-xs leading-relaxed opacity-80">
                                                    Pastikan file berasal dari aplikasi ini. Data user, role, permission, media, audit, dan login
                                                    activity tidak ikut direstore.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" disabled={!can.restore || form.processing} className="h-11 min-w-40">
                                            <Upload className="size-4" />
                                            Restore Backup
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <Card data-dashboard-card className="h-fit overflow-hidden">
                        <CardHeader className="border-b">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <span className="dashboard-icon icon-tone-indigo flex size-8 items-center justify-center rounded-md">
                                    <FileJson className="size-4" />
                                </span>
                                Scope Backup
                            </CardTitle>
                            <CardDescription>Ringkasan batasan backup setting.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5 p-5">
                            <div>
                                <p className="text-sm font-medium">Included</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {overview.included_sections.map((section) => (
                                        <Badge key={section} className="capitalize">
                                            {sectionLabel(section)}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <p className="text-sm font-medium">Excluded</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {overview.excluded_sections.map((section) => (
                                        <Badge key={section} variant="secondary" className="capitalize">
                                            {sectionLabel(section)}
                                        </Badge>
                                    ))}
                                </div>
                            </div>

                            <div className="bg-muted/50 rounded-lg border p-4 text-xs leading-relaxed">
                                <p className="font-medium">Ready At</p>
                                <p className="text-muted-foreground mt-1">{overview.last_ready_at}</p>
                            </div>

                            <div className="bg-muted/50 rounded-lg border p-4 text-xs leading-relaxed">
                                Backup ini untuk konfigurasi aplikasi, bukan disaster recovery full database. Untuk production, tetap gunakan backup
                                database dan storage dari server.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
