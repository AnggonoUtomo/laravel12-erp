import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { usePermission } from '@/hooks/use-permission';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    ArchiveRestore,
    BadgeCheck,
    BriefcaseBusiness,
    Building2,
    CalendarClock,
    ChevronRight,
    ClipboardCheck,
    FileSignature,
    Layers,
    LayoutGrid,
    ListChecks,
    ListFilter,
    ListRestart,
    LockKeyhole,
    LogIn,
    LogOut,
    MailCheck,
    MapPin,
    Network,
    Palette,
    ScrollText,
    ShieldCheck,
    SlidersHorizontal,
    UserRound,
    UserRoundCog,
    Users,
    UsersRound,
} from 'lucide-react';
import { type ComponentType, useCallback, useEffect, useRef } from 'react';
import AppLogoIcon from './app-logo-icon';

type SidebarItem = NavItem & { badge?: string; permissions?: string[] };

type SidebarDropdownGroup = {
    title: string;
    icon: SidebarItem['icon'];
    items: SidebarItem[];
};

const SIDEBAR_SCROLL_KEY = 'laravel12-starterkit:sidebar-scroll-top';
const SIDEBAR_MENU_BUTTON_CLASS =
    'h-[42px] rounded-xl px-3 text-[15px] hover:!bg-accent hover:!text-accent-foreground data-[active=true]:!bg-accent data-[active=true]:!text-accent-foreground group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:[&>span]:hidden group-data-[collapsible=icon]:[&>svg:last-child]:hidden [&>svg]:!size-[18px]';
const SIDEBAR_SUB_MENU_BUTTON_CLASS =
    'h-[38px] rounded-lg px-3 text-[14px] hover:!bg-accent hover:!text-accent-foreground data-[active=true]:!bg-accent data-[active=true]:!text-accent-foreground [&>svg]:!size-[17px]';

const menuTitleTranslations: Record<string, string> = {
    'Audit Logs': 'Log Audit',
    'Backup & Restore': 'Backup & Pemulihan',
    'Login Activity': 'Aktivitas Login',
    'Notification Templates': 'Template Notifikasi',
    'Queue Monitor': 'Monitor Antrean',
    'Scheduler Monitor': 'Monitor Jadwal',
    'System Settings': 'Pengaturan Sistem',
};

const groupTitleTranslations: Record<string, string> = {
    Administrasi: 'Administrasi',
    Observability: 'Observabilitas',
    Operasional: 'Operasional',
    Sistem: 'Sistem',
};

const sidebarIconColors: Record<string, string> = {
    Dasbor: '!text-sky-500 dark:!text-sky-400',
    Administrasi: '!text-violet-500 dark:!text-violet-400',
    Observability: '!text-orange-500 dark:!text-orange-400',
    Operasional: '!text-amber-500 dark:!text-amber-400',
    Sistem: '!text-purple-500 dark:!text-purple-400',
    'Manajemen User': '!text-indigo-500 dark:!text-indigo-400',
    'Kontrol Akses': '!text-violet-500 dark:!text-violet-400',
    'System Settings': '!text-purple-500 dark:!text-purple-400',
    'Audit Logs': '!text-orange-500 dark:!text-orange-400',
    'Login Activity': '!text-sky-500 dark:!text-sky-400',
    'Queue Monitor': '!text-sky-500 dark:!text-sky-400',
    'Scheduler Monitor': '!text-orange-500 dark:!text-orange-400',
    'Notification Templates': '!text-emerald-500 dark:!text-emerald-400',
    'Backup & Restore': '!text-amber-500 dark:!text-amber-400',
    'Pengaturan Akun': '!text-purple-500 dark:!text-purple-400',
    Profil: '!text-indigo-500 dark:!text-indigo-400',
    'Kata Sandi': '!text-rose-500 dark:!text-rose-400',
    Tampilan: '!text-amber-500 dark:!text-amber-400',
};

function displayMenuTitle(title: string) {
    return menuTitleTranslations[title] ?? title;
}

function displayGroupTitle(title: string) {
    return groupTitleTranslations[title] ?? title;
}

type SidebarTooltipLabel = {
    depth: number;
    label: string;
};

function countTooltipLabels(items: SidebarItem[]): number {
    return items.reduce((count, item) => count + 1 + (item.children?.length ? countTooltipLabels(item.children as SidebarItem[]) : 0), 0);
}

function collectTooltipLabels(items: SidebarItem[], limit = 14): { labels: SidebarTooltipLabel[]; remaining: number } {
    const labels: SidebarTooltipLabel[] = [];

    const visit = (item: SidebarItem, depth: number) => {
        if (labels.length >= limit) {
            return;
        }

        labels.push({ depth, label: displayMenuTitle(item.title) });

        if (item.children?.length) {
            for (const child of item.children as SidebarItem[]) {
                visit(child, depth + 1);
            }
        }
    };

    items.forEach((item) => visit(item, 0));

    const total = countTooltipLabels(items);

    return {
        labels,
        remaining: Math.max(total - labels.length, 0),
    };
}

function SidebarTooltipContent({ title, items }: { title: string; items: SidebarItem[] }) {
    const { labels, remaining } = collectTooltipLabels(items);

    return (
        <div className="max-w-64 space-y-2">
            <div className="font-medium">{title}</div>
            {labels.length > 0 && (
                <ul className="text-muted-foreground space-y-1 text-xs">
                    {labels.map(({ depth, label }) => (
                        <li key={`${depth}-${label}`} className="flex items-center gap-2" style={{ paddingLeft: `${depth * 0.75}rem` }}>
                            <span className="bg-primary/60 size-1 rounded-full" />
                            <span>{label}</span>
                        </li>
                    ))}
                    {remaining > 0 && <li className="pl-3.5">+{remaining} menu lainnya</li>}
                </ul>
            )}
        </div>
    );
}

function sidebarIconColor(title: string) {
    return sidebarIconColors[title] ?? '!text-cyan-500 dark:!text-cyan-400';
}

function accountActionTone(title: string) {
    const tones: Record<string, string> = {
        Profil: 'bg-indigo-500/10 hover:!bg-indigo-500/15 data-[active=true]:!bg-indigo-500/15',
        'Kata Sandi': 'bg-rose-500/10 hover:!bg-rose-500/15 data-[active=true]:!bg-rose-500/15',
        Tampilan: 'bg-amber-500/10 hover:!bg-amber-500/15 data-[active=true]:!bg-amber-500/15',
    };

    return tones[title] ?? 'bg-primary/10 hover:!bg-primary/15 data-[active=true]:!bg-primary/15';
}

function isItemActive(item: SidebarItem, currentUrl: string) {
    if (item.exact) {
        return currentUrl === item.url;
    }

    if (item.url === '/dashboard') {
        return item.title === 'Dasbor' && (currentUrl === item.url || currentUrl === '/');
    }

    return currentUrl.startsWith(item.url);
}

const moduleIconMap: Record<string, ComponentType<{ className?: string }>> = {
    ArchiveRestore,
    BadgeCheck,
    BriefcaseBusiness,
    Building2,
    CalendarClock,
    ClipboardCheck,
    FileSignature,
    Layers,
    ListFilter,
    ListChecks,
    LogIn,
    LogOut,
    ListRestart,
    MailCheck,
    MapPin,
    Network,
    ScrollText,
    ShieldCheck,
    SlidersHorizontal,
    UserRoundCog,
    Users,
    UsersRound,
};

function resolveSidebarIcon(icon: SidebarItem['icon']) {
    if (!icon) {
        return null;
    }

    if (typeof icon === 'string') {
        return moduleIconMap[icon] ?? null;
    }

    return icon;
}

function SidebarItemIcon({ item }: { item: SidebarItem }) {
    const Icon = resolveSidebarIcon(item.icon);

    return Icon ? <Icon className={sidebarIconColor(item.title)} /> : null;
}

const groupIconMap: Record<string, SidebarItem['icon']> = {
    Administrasi: ShieldCheck,
    Observability: ScrollText,
    Operasional: ListRestart,
    Sistem: SlidersHorizontal,
};

const settingsNavItems: SidebarItem[] = [
    {
        title: 'Profil',
        url: '/settings/profile',
        icon: UserRound,
    },
    {
        title: 'Kata Sandi',
        url: '/settings/password',
        icon: LockKeyhole,
    },
    {
        title: 'Tampilan',
        url: '/settings/appearance',
        icon: Palette,
    },
];

function hasActiveDescendant(items: SidebarItem[], currentUrl: string): boolean {
    return items.some(
        (item) => isItemActive(item, currentUrl) || (item.children?.length ? hasActiveDescendant(item.children as SidebarItem[], currentUrl) : false),
    );
}

function SidebarNavLink({ item, scope }: { item: SidebarItem; scope: string }) {
    const page = usePage();
    const label = displayMenuTitle(item.title);
    const isActive = isItemActive(item, page.url);

    return (
        <SidebarMenuItem key={`${scope}-${item.title}`}>
            <SidebarMenuButton asChild isActive={isActive} tooltip={label} className={`${SIDEBAR_MENU_BUTTON_CLASS} ${item.badge ? 'pr-9' : ''}`}>
                <Link href={item.url} prefetch>
                    <SidebarItemIcon item={item} />
                    <span>{label}</span>
                </Link>
            </SidebarMenuButton>
            {item.badge && <SidebarMenuBadge className="bg-primary/10 text-primary rounded-full">{item.badge}</SidebarMenuBadge>}
        </SidebarMenuItem>
    );
}

function SidebarNavGroup({ title, items }: { title: string; items: SidebarItem[] }) {
    const { canAny } = usePermission();
    const visibleItems = items.filter((item) => !item.permissions || canAny(item.permissions));

    return (
        <SidebarMenu className="gap-1">
            {visibleItems.map((item) =>
                item.children?.length ? (
                    <SidebarDropdownGroup key={`${title}-${item.title}`} title={item.title} icon={item.icon} items={item.children as SidebarItem[]} />
                ) : (
                    <SidebarNavLink key={`${title}-${item.title}`} item={item} scope={title} />
                ),
            )}
        </SidebarMenu>
    );
}

function SidebarDropdownGroup({ title, icon, items }: SidebarDropdownGroup) {
    const page = usePage();
    const { canAny } = usePermission();
    const Icon = resolveSidebarIcon(icon);
    const label = displayMenuTitle(title);

    const visibleChildren = items.filter((item) => !item.permissions || canAny(item.permissions));
    const hasActiveChild = hasActiveDescendant(visibleChildren, page.url);

    if (!visibleChildren.length) {
        return null;
    }

    return (
        <Collapsible asChild defaultOpen={hasActiveChild} className="group/collapsible">
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        isActive={hasActiveChild}
                        tooltip={{
                            children: <SidebarTooltipContent title={label} items={visibleChildren} />,
                            className: 'bg-popover text-popover-foreground border shadow-lg',
                        }}
                        className={SIDEBAR_MENU_BUTTON_CLASS}
                    >
                        {Icon && <Icon className={sidebarIconColor(title)} />}
                        <span>{label}</span>
                        <ChevronRight className="text-muted-foreground ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {visibleChildren.map((item) =>
                            item.children?.length ? (
                                <SidebarDropdownGroup key={item.title} title={item.title} icon={item.icon} items={item.children as SidebarItem[]} />
                            ) : (
                                <SidebarMenuSubItem key={item.title}>
                                    <SidebarMenuSubButton asChild isActive={isItemActive(item, page.url)} className={SIDEBAR_SUB_MENU_BUTTON_CLASS}>
                                        <Link href={item.url} prefetch>
                                            <SidebarItemIcon item={item} />
                                            <span>{displayMenuTitle(item.title)}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            ),
                        )}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}

function SidebarCategoryDropdown({ title, items }: { title: string; items: SidebarItem[] }) {
    const page = usePage();
    const { canAny } = usePermission();
    const visibleItems = items.filter((item) => !item.permissions || canAny(item.permissions));
    const hasActiveChild = hasActiveDescendant(visibleItems, page.url);
    const Icon = resolveSidebarIcon(groupIconMap[title]);
    const label = displayGroupTitle(title);

    if (!visibleItems.length) {
        return null;
    }

    return (
        <SidebarGroup className="py-0.5">
            <SidebarGroupContent>
                <SidebarMenu className="gap-1">
                    <Collapsible asChild defaultOpen={hasActiveChild} className="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger asChild>
                                <SidebarMenuButton
                                    isActive={hasActiveChild}
                                    tooltip={{
                                        children: <SidebarTooltipContent title={label} items={visibleItems} />,
                                        className: 'bg-popover text-popover-foreground border shadow-lg',
                                    }}
                                    className={`${SIDEBAR_MENU_BUTTON_CLASS} font-medium`}
                                >
                                    {Icon && <Icon className={sidebarIconColor(title)} />}
                                    <span>{label}</span>
                                    <ChevronRight className="text-muted-foreground ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    {visibleItems.map((item) =>
                                        item.children?.length ? (
                                            <SidebarDropdownGroup
                                                key={`${title}-${item.title}`}
                                                title={item.title}
                                                icon={item.icon}
                                                items={item.children as SidebarItem[]}
                                            />
                                        ) : (
                                            <SidebarMenuSubItem key={`${title}-${item.title}`}>
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={isItemActive(item, page.url)}
                                                    className={SIDEBAR_SUB_MENU_BUTTON_CLASS}
                                                >
                                                    <Link href={item.url} prefetch>
                                                        <SidebarItemIcon item={item} />
                                                        <span>{displayMenuTitle(item.title)}</span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ),
                                    )}
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

function SidebarAccountAction({ item }: { item: SidebarItem }) {
    const page = usePage();
    const label = displayMenuTitle(item.title);
    const isActive = isItemActive(item, page.url);

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <SidebarMenuButton
                    asChild
                    isActive={isActive}
                    className={`border-sidebar-border/70 data-[active=true]:!text-accent-foreground size-9 justify-center rounded-lg border p-0 [&>svg]:!size-[17px] ${accountActionTone(item.title)}`}
                >
                    <Link href={item.url} prefetch>
                        <SidebarItemIcon item={item} />
                        <span className="sr-only">{label}</span>
                    </Link>
                </SidebarMenuButton>
            </TooltipTrigger>
            <TooltipContent side="right" align="center">
                {label}
            </TooltipContent>
        </Tooltip>
    );
}

function SidebarAccountFooter({ user }: { user: SharedData['auth']['user'] }) {
    const getInitials = useInitials();

    if (!user) {
        return null;
    }

    return (
        <SidebarFooter className="border-sidebar-border/70 items-center border-t p-3 group-data-[collapsible=icon]:px-1 group-data-[collapsible=icon]:py-2">
            <div className="flex justify-center rounded-xl px-2 py-1 group-data-[collapsible=icon]:p-0">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link href="/settings/profile" prefetch className="shrink-0 rounded-2xl">
                            <Avatar className="border-sidebar-border size-20 rounded-2xl border shadow-sm group-data-[collapsible=icon]:size-9 group-data-[collapsible=icon]:rounded-xl">
                                <AvatarImage src={user.avatar} alt={user.name} />
                                <AvatarFallback className="rounded-2xl text-lg font-semibold group-data-[collapsible=icon]:rounded-xl group-data-[collapsible=icon]:text-xs">
                                    {getInitials(user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <span className="sr-only">Profil akun</span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side="right" align="center">
                        Profil akun
                    </TooltipContent>
                </Tooltip>
            </div>

            <SidebarMenu className="grid grid-cols-3 gap-1 group-data-[collapsible=icon]:flex group-data-[collapsible=icon]:flex-col group-data-[collapsible=icon]:items-center">
                {settingsNavItems.map((item) => (
                    <SidebarMenuItem key={item.title} className="flex justify-center">
                        <SidebarAccountAction item={item} />
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarFooter>
    );
}

export function AppSidebar() {
    const page = usePage<SharedData>();
    const sidebarContentRef = useRef<HTMLDivElement | null>(null);
    const appName = page.props.branding?.app_name ?? page.props.name;
    const overviewNavItems: SidebarItem[] = [
        {
            title: 'Dasbor',
            url: '/dashboard',
            icon: LayoutGrid,
        },
    ];
    const moduleNavGroups = Object.values(
        page.props.navigation.reduce<Record<string, { title: string; items: SidebarItem[] }>>((groups, group) => {
            const title = group.group;
            const items = Array.isArray(group.items) ? (group.items as SidebarItem[]) : [];

            groups[title] ??= { title, items: [] };
            groups[title].items.push(...items);

            return groups;
        }, {}),
    ).filter((group) => group.items.length > 0);

    const rememberSidebarScroll = useCallback(() => {
        if (typeof window === 'undefined' || !sidebarContentRef.current) {
            return;
        }

        window.sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebarContentRef.current.scrollTop));
    }, []);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const savedScrollTop = Number(window.sessionStorage.getItem(SIDEBAR_SCROLL_KEY) ?? '0');
        if (!Number.isFinite(savedScrollTop) || savedScrollTop <= 0) {
            return;
        }

        const restoreScroll = () => {
            if (sidebarContentRef.current) {
                sidebarContentRef.current.scrollTop = savedScrollTop;
            }
        };

        restoreScroll();
        const animationFrame = window.requestAnimationFrame(restoreScroll);
        const timeout = window.setTimeout(restoreScroll, 80);

        return () => {
            window.cancelAnimationFrame(animationFrame);
            window.clearTimeout(timeout);
        };
    }, [page.url]);

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader className="p-3 group-data-[collapsible=icon]:p-2">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="hover:text-sidebar-foreground h-12 rounded-none px-1 text-[16px] shadow-none group-data-[collapsible=icon]:!size-8 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:!p-0 hover:bg-transparent [&>svg]:!size-[20px]"
                        >
                            <Link href="/dashboard" prefetch onClick={rememberSidebarScroll}>
                                <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 shrink-0 items-center justify-center rounded-md">
                                    {page.props.branding?.logo_url ? (
                                        <img src={page.props.branding.logo_url} alt={appName} className="size-6 object-contain" />
                                    ) : (
                                        <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                                    )}
                                </div>
                                <div className="ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
                                    <span className="mb-0.5 truncate leading-none font-semibold">{appName}</span>
                                </div>
                                <span className="bg-primary/10 text-primary ml-auto rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide group-data-[collapsible=icon]:hidden">
                                    ERP
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent ref={sidebarContentRef} className="sidebar-scrollbar-hidden gap-0.5" onScroll={rememberSidebarScroll}>
                <div className="bg-sidebar sticky top-0 z-10 pt-2 pb-0.5">
                    <SidebarGroup className="py-0.5">
                        <SidebarGroupContent>
                            <SidebarNavGroup title="Ringkasan" items={overviewNavItems} />
                        </SidebarGroupContent>
                    </SidebarGroup>
                </div>
                {moduleNavGroups.map((group) => (
                    <SidebarCategoryDropdown key={group.title} title={group.title} items={group.items} />
                ))}
            </SidebarContent>
            <SidebarAccountFooter user={page.props.auth.user} />
        </Sidebar>
    );
}
