import { type NavItem, type NavigationGroup } from '@/types';

export type CommandPaletteResult = {
    id: string;
    title: string;
    group: string;
    url: string;
    keywords: string[];
    description?: string;
    badge?: string;
};

export function moveCommandPaletteSelection(currentIndex: number, itemCount: number, direction: 'next' | 'previous') {
    if (itemCount <= 0) {
        return 0;
    }

    if (direction === 'next') {
        return (currentIndex + 1) % itemCount;
    }

    return (currentIndex - 1 + itemCount) % itemCount;
}

type BuildNavigationSearchResultsOptions = {
    permissions: Record<string, boolean>;
    includeWhenNoPermissionMetadata?: boolean;
};

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

const forbiddenResultPattern = /\b(password|token|secret|api[_-]?key|apikey)\b/i;

function displayMenuTitle(title: string) {
    return menuTitleTranslations[title] ?? title;
}

function displayGroupTitle(title: string) {
    return groupTitleTranslations[title] ?? title;
}

function hasPermission(item: NavItem, permissions: Record<string, boolean>, includeWhenNoPermissionMetadata: boolean) {
    if (!item.permissions?.length) {
        return includeWhenNoPermissionMetadata;
    }

    return item.permissions.some((permission) => Boolean(permissions[permission]));
}

function isInternalUrl(url: string) {
    return url === url.trim() && url.startsWith('/') && !url.startsWith('//') && !url.includes('\\');
}

function containsForbiddenResultText(values: Array<string | null | undefined>) {
    return values.some((value) => forbiddenResultPattern.test(value ?? ''));
}

function isSafeCommandPaletteResult(result: CommandPaletteResult) {
    return !containsForbiddenResultText([result.id, result.title, result.group, result.url, result.description, result.badge, ...result.keywords]);
}

function uniqueKeywords(values: Array<string | null | undefined>) {
    const keywords = values
        .flatMap((value) => (value ?? '').split(/\s+/))
        .map((value) => value.trim())
        .filter(Boolean);

    return Array.from(new Set([...values.filter((value): value is string => Boolean(value?.trim())), ...keywords]));
}

function visitNavigationItem(
    item: NavItem,
    group: string,
    permissions: Record<string, boolean>,
    includeWhenNoPermissionMetadata: boolean,
    results: CommandPaletteResult[],
) {
    const title = displayMenuTitle(item.title);
    const groupTitle = displayGroupTitle(group);

    if (item.url && isInternalUrl(item.url) && hasPermission(item, permissions, includeWhenNoPermissionMetadata)) {
        const result = {
            id: `${group}:${item.url}`,
            title,
            group: groupTitle,
            url: item.url,
            badge: item.badge,
            keywords: uniqueKeywords([item.title, title, group, groupTitle, item.url]),
        };

        if (isSafeCommandPaletteResult(result)) {
            results.push(result);
        }
    }

    item.children?.forEach((child) => visitNavigationItem(child, group, permissions, includeWhenNoPermissionMetadata, results));
}

export function buildNavigationSearchResults(
    navigation: NavigationGroup[],
    { permissions, includeWhenNoPermissionMetadata = true }: BuildNavigationSearchResultsOptions,
): CommandPaletteResult[] {
    const results: CommandPaletteResult[] = [];

    navigation.forEach((group) => {
        group.items.forEach((item) => visitNavigationItem(item, group.group, permissions, includeWhenNoPermissionMetadata, results));
    });

    return results.sort((a, b) => a.group.localeCompare(b.group) || a.title.localeCompare(b.title));
}

export function filterNavigationSearchResults(results: CommandPaletteResult[], query: string, limit = 12): CommandPaletteResult[] {
    const normalizedQuery = query.trim().toLowerCase();

    if (!normalizedQuery) {
        return results.slice(0, limit);
    }

    return results
        .map((result) => {
            const fields = [result.title, result.group, result.url, ...result.keywords].map((value) => value.toLowerCase());
            const score = fields.reduce((bestScore, field) => {
                if (field === normalizedQuery) {
                    return Math.min(bestScore, 0);
                }

                if (field.startsWith(normalizedQuery)) {
                    return Math.min(bestScore, 1);
                }

                if (field.includes(normalizedQuery)) {
                    return Math.min(bestScore, 2);
                }

                return bestScore;
            }, Number.POSITIVE_INFINITY);

            return { result, score };
        })
        .filter(({ score }) => Number.isFinite(score))
        .sort((a, b) => a.score - b.score || a.result.title.localeCompare(b.result.title) || a.result.group.localeCompare(b.result.group))
        .map(({ result }) => result)
        .slice(0, limit);
}
