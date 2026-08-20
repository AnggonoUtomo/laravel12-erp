import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, ArrowUpRight, CalendarDays, KeyRound, Route, Users } from 'lucide-react';
import { type ComponentType } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dasbor',
        href: '/dashboard',
    },
];

interface RecentAuditLog {
    id: number;
    module: string;
    event: string;
    description: string | null;
    created_at_human: string | null;
}

interface RecentLoginActivity {
    id: number;
    email: string;
    event: string;
    successful: boolean;
    occurred_at_human: string | null;
}

interface DashboardOverview {
    access: {
        console_admin: boolean;
        audit: boolean;
        login_activities: boolean;
    };
    console: {
        modules: number;
        console_modules: number;
        permissions: number;
        roles: number;
        users: number;
    };
    activity: {
        audit_logs: number;
        login_activities: number;
        recent_audit_logs: RecentAuditLog[];
        recent_login_activities: RecentLoginActivity[];
    };
}

type DashboardPageProps = SharedData & {
    dashboard: DashboardOverview;
};

type IconTone = 'amber' | 'blue' | 'emerald' | 'violet';

function IconBox({ icon: Icon, tone = 'blue' }: { icon: ComponentType<{ className?: string }>; tone?: IconTone }) {
    const tones: Record<IconTone, string> = {
        amber: 'icon-tone-amber',
        blue: 'icon-tone-indigo',
        emerald: 'icon-tone-emerald',
        violet: 'icon-tone-violet',
    };

    return (
        <div className={`dashboard-icon ${tones[tone]} flex size-11 shrink-0 items-center justify-center rounded-md`}>
            <Icon className="size-5" />
        </div>
    );
}

function formatNumber(value: number) {
    return new Intl.NumberFormat('id-ID').format(value);
}

export default function Dashboard() {
    const { props } = usePage<DashboardPageProps>();
    const { dashboard } = props;
    const userName = props.auth.user?.name ?? 'Admin';
    const unreadActivities = props.activity_center.unread_count;
    const today = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date());

    const summaryCards = [
        {
            title: 'Modul Console',
            value: formatNumber(dashboard.console.console_modules),
            change: `${formatNumber(dashboard.console.modules)} terdaftar`,
            detail: 'Modul aktif pada registry aplikasi',
            icon: Route,
            tone: 'blue' as const,
        },
        {
            title: 'User',
            value: formatNumber(dashboard.console.users),
            change: dashboard.access.console_admin ? `${formatNumber(dashboard.console.roles)} role` : 'akses pribadi',
            detail: dashboard.access.console_admin ? 'Akun yang tercatat di Console' : 'Metrik user dibatasi permission',
            icon: Users,
            tone: 'emerald' as const,
        },
        {
            title: 'Permission',
            value: formatNumber(dashboard.console.permissions),
            change: dashboard.access.console_admin ? 'registry' : 'efektif',
            detail: dashboard.access.console_admin ? 'Permission aktif pada registry' : 'Permission efektif akun ini',
            icon: KeyRound,
            tone: 'violet' as const,
        },
        {
            title: 'Aktivitas',
            value: formatNumber(unreadActivities),
            change: dashboard.access.audit ? `${formatNumber(dashboard.activity.audit_logs)} audit` : 'akses terbatas',
            detail: dashboard.access.login_activities
                ? `${formatNumber(dashboard.activity.login_activities)} login activity`
                : 'Detail observabilitas dibatasi permission',
            icon: Activity,
            tone: unreadActivities > 0 ? ('amber' as const) : ('violet' as const),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dasbor Console" />

            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-5 p-4 sm:p-6">
                <section className="bg-card overflow-hidden rounded-md border p-5 sm:p-6">
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-3xl">
                            <Badge variant="secondary" className="mb-3">
                                Console Overview
                            </Badge>
                            <h1 className="text-2xl font-semibold sm:text-3xl">Selamat datang kembali, {userName}</h1>
                            <p className="text-muted-foreground mt-2 text-sm leading-6">Ringkasan modul, akses, dan aktivitas operasional Console.</p>
                        </div>
                        <div className="text-muted-foreground flex items-center gap-2 text-sm">
                            <CalendarDays className="size-4" />
                            <span>{today}</span>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {summaryCards.map((item) => (
                        <Card key={item.title} data-dashboard-card className="overflow-hidden">
                            <CardContent className="flex items-center gap-4 p-5">
                                <IconBox icon={item.icon} tone={item.tone} />
                                <div className="min-w-0">
                                    <p className="text-muted-foreground text-sm">{item.title}</p>
                                    <div className="mt-1 flex items-center gap-2">
                                        <p className="text-2xl font-semibold">{item.value}</p>
                                        <Badge variant="secondary" className="text-[11px]">
                                            {item.change}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground mt-1 truncate text-xs">{item.detail}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-4 xl:grid-cols-2">
                    <Card data-dashboard-card>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle>Audit Terbaru</CardTitle>
                            {dashboard.access.audit ? (
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/audit-logs">
                                        Buka audit
                                        <ArrowUpRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : null}
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {dashboard.activity.recent_audit_logs.length === 0 ? (
                                <p className="text-muted-foreground bg-muted/20 rounded-md border p-4 text-sm">
                                    {dashboard.access.audit ? 'Belum ada audit log terbaru.' : 'Audit log disembunyikan untuk akun tanpa permission.'}
                                </p>
                            ) : (
                                dashboard.activity.recent_audit_logs.map((item) => (
                                    <div key={item.id} className="bg-muted/20 rounded-md border p-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">{item.description ?? item.event}</p>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {item.module} - {item.event}
                                                </p>
                                            </div>
                                            <span className="text-muted-foreground shrink-0 text-xs">{item.created_at_human ?? '-'}</span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card data-dashboard-card>
                        <CardHeader className="flex-row items-center justify-between space-y-0">
                            <CardTitle>Login Activity</CardTitle>
                            {dashboard.access.login_activities ? (
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/login-activities">
                                        Buka login
                                        <ArrowUpRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : null}
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {dashboard.activity.recent_login_activities.length === 0 ? (
                                <p className="text-muted-foreground bg-muted/20 rounded-md border p-4 text-sm">
                                    {dashboard.access.login_activities
                                        ? 'Belum ada aktivitas login terbaru.'
                                        : 'Login activity disembunyikan untuk akun tanpa permission.'}
                                </p>
                            ) : (
                                dashboard.activity.recent_login_activities.map((item) => (
                                    <div key={item.id} className="bg-muted/20 flex items-center justify-between gap-3 rounded-md border p-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{item.email}</p>
                                            <p className="text-muted-foreground mt-1 text-xs">{item.event}</p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-2">
                                            <Badge variant={item.successful ? 'secondary' : 'destructive'}>
                                                {item.successful ? 'Sukses' : 'Gagal'}
                                            </Badge>
                                            <span className="text-muted-foreground text-xs">{item.occurred_at_human ?? '-'}</span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </section>
            </div>
        </AppLayout>
    );
}
