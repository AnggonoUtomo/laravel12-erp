import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Clock3, HeartPulse, KeyRound, ListFilter, Mail, Palette, Power, Server, ShieldAlert } from 'lucide-react';
import type { ComponentType } from 'react';
import type { SystemSettingSection } from '../types';

type MenuItem = {
    key: SystemSettingSection;
    title: string;
    description: string;
    icon: ComponentType<{ className?: string }>;
    color: string;
};

const menuItems: MenuItem[] = [
    {
        key: 'email',
        title: 'Email & SMTP',
        description: 'Konfigurasi pengiriman email, aktivasi user, dan tautan atur password.',
        icon: Mail,
        color: 'text-sky-500 dark:text-sky-400',
    },
    {
        key: 'branding',
        title: 'App Name & Logo',
        description: 'Ubah nama aplikasi, logo utama, dan favicon.',
        icon: Palette,
        color: 'text-rose-500 dark:text-rose-400',
    },
    {
        key: 'localization',
        title: 'Timezone & Date',
        description: 'Atur zona waktu, format tanggal, dan format jam aplikasi.',
        icon: Clock3,
        color: 'text-indigo-500 dark:text-indigo-400',
    },
    {
        key: 'pagination',
        title: 'Default Pagination',
        description: 'Atur default row dan opsi jumlah row pada tabel.',
        icon: ListFilter,
        color: 'text-emerald-500 dark:text-emerald-400',
    },
    {
        key: 'security',
        title: 'Security Policy',
        description: 'Atur session, login throttle, dan kontrol keamanan global.',
        icon: ShieldAlert,
        color: 'text-rose-500 dark:text-rose-400',
    },
    {
        key: 'password',
        title: 'Password Policy',
        description: 'Atur kekuatan password dan siklus keamanan akun.',
        icon: KeyRound,
        color: 'text-indigo-500 dark:text-indigo-400',
    },
    {
        key: 'maintenance',
        title: 'Maintenance Mode',
        description: 'Aktifkan mode perawatan dan secret bypass.',
        icon: Power,
        color: 'text-amber-500 dark:text-amber-400',
    },
    {
        key: 'health',
        title: 'System Health',
        description: 'Cek database, cache, queue, storage, mail, dan runtime aplikasi.',
        icon: HeartPulse,
        color: 'text-emerald-500 dark:text-emerald-400',
    },
    {
        key: 'environment',
        title: 'Environment Info',
        description: 'Lihat environment, driver, path, dan extension PHP secara read-only.',
        icon: Server,
        color: 'text-sky-500 dark:text-sky-400',
    },
];

type Props = {
    activeSection: SystemSettingSection;
    onSectionChange: (section: SystemSettingSection) => void;
};

export function SystemSettingMenu({ activeSection, onSectionChange }: Props) {
    return (
        <Card data-dashboard-card className="h-fit overflow-hidden">
            <CardHeader className="border-b">
                <CardTitle className="text-base">Menu System Setting</CardTitle>
                <CardDescription>Pilih konfigurasi sistem yang ingin dikelola.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2 p-3">
                {menuItems.map((item) => {
                    const Icon = item.icon;
                    const active = activeSection === item.key;

                    return (
                        <button
                            key={item.key}
                            type="button"
                            onClick={() => onSectionChange(item.key)}
                            className={
                                active
                                    ? 'border-primary/40 bg-primary/10 text-primary flex w-full items-start gap-3 rounded-lg border p-3 text-left transition'
                                    : 'hover:bg-muted/60 flex w-full items-start gap-3 rounded-lg border border-transparent p-3 text-left transition'
                            }
                        >
                            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center">
                                <Icon className={`size-5 ${item.color}`} />
                            </span>
                            <span className="min-w-0">
                                <span className="block text-sm font-medium">{item.title}</span>
                                <span className="text-muted-foreground mt-0.5 block text-xs leading-relaxed">{item.description}</span>
                            </span>
                        </button>
                    );
                })}
            </CardContent>
        </Card>
    );
}
