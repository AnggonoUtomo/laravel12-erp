import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEvent, useMemo, useState } from 'react';
import { BrandingSettingsPanel } from './components-system-settings/branding-settings-panel';
import { EmailSettingsPanel } from './components-system-settings/email-settings-panel';
import { EnvironmentInfoPanel } from './components-system-settings/environment-info-panel';
import { LocalizationSettingsPanel } from './components-system-settings/localization-settings-panel';
import { MaintenanceModePanel } from './components-system-settings/maintenance-mode-panel';
import { PaginationSettingsPanel } from './components-system-settings/pagination-settings-panel';
import { PasswordPolicyPanel } from './components-system-settings/password-policy-panel';
import { SecurityPolicyPanel } from './components-system-settings/security-policy-panel';
import { SystemHealthPanel } from './components-system-settings/system-health-panel';
import { SystemSettingMenu } from './components-system-settings/system-setting-menu';
import { retryMaximumSeconds, retryMinimumSeconds } from './options';
import type {
    BrandingForm,
    EmailSettingForm,
    LocalizationForm,
    MaintenanceModeForm,
    PaginationForm,
    PasswordPolicyForm,
    RetryUnit,
    SecurityPolicyForm,
    SystemSettingSection,
    SystemSettingsProps,
    TestEmailForm,
} from './types';
import { filePreview, formatSecondsBreakdown, retryPartsToSeconds, retrySecondsToParts } from './utils';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Settings',
        href: '/system-settings',
    },
];

const sectionLabels: Record<SystemSettingSection, string> = {
    email: 'Email',
    branding: 'Branding',
    localization: 'Timezone',
    pagination: 'Pagination',
    security: 'Security',
    password: 'Password',
    maintenance: 'Maintenance',
    health: 'Health',
    environment: 'Environment',
};

export default function SystemSettings({
    emailSettings,
    brandingSettings,
    localizationSettings,
    paginationSettings,
    securityPolicy,
    passwordPolicy,
    maintenanceMode,
    systemHealth,
    environmentInfo,
    can,
}: SystemSettingsProps) {
    const [activeSection, setActiveSection] = useState<SystemSettingSection>('email');

    const form = useForm<EmailSettingForm>({
        enabled: emailSettings.enabled,
        mailer: emailSettings.mailer,
        host: emailSettings.host ?? '',
        port: emailSettings.port ? String(emailSettings.port) : '',
        username: emailSettings.username ?? '',
        password: '',
        encryption: emailSettings.encryption ?? 'none',
        from_address: emailSettings.from_address,
        from_name: emailSettings.from_name,
        send_credentials_on_create: emailSettings.send_credentials_on_create,
        send_credentials_on_password_update: emailSettings.send_credentials_on_password_update,
        credential_subject: emailSettings.credential_subject ?? '',
        credential_intro: emailSettings.credential_intro ?? '',
    });
    const brandingForm = useForm<BrandingForm>({
        app_name: brandingSettings.app_name,
        logo: null,
        favicon: null,
        remove_logo: false,
        remove_favicon: false,
        _method: 'put',
    });
    const localizationForm = useForm<LocalizationForm>({
        timezone: localizationSettings.timezone,
        date_format: localizationSettings.date_format,
        time_format: localizationSettings.time_format,
    });
    const paginationForm = useForm<PaginationForm>({
        default_per_page: String(paginationSettings.default_per_page),
        per_page_options: paginationSettings.per_page_options,
    });
    const securityForm = useForm<SecurityPolicyForm>({
        require_email_verification: securityPolicy.require_email_verification,
        audit_sensitive_actions: securityPolicy.audit_sensitive_actions,
        single_session_per_user: securityPolicy.single_session_per_user,
        allow_account_deletion: securityPolicy.allow_account_deletion,
        session_lifetime_minutes: String(securityPolicy.session_lifetime_minutes),
        login_max_attempts: String(securityPolicy.login_max_attempts),
        login_decay_minutes: String(securityPolicy.login_decay_minutes),
        password_confirmation_timeout_seconds: String(securityPolicy.password_confirmation_timeout_seconds),
    });
    const passwordPolicyForm = useForm<PasswordPolicyForm>({
        min_length: String(passwordPolicy.min_length),
        require_uppercase: passwordPolicy.require_uppercase,
        require_lowercase: passwordPolicy.require_lowercase,
        require_numbers: passwordPolicy.require_numbers,
        require_symbols: passwordPolicy.require_symbols,
        uncompromised: passwordPolicy.uncompromised,
        expiry_days: String(passwordPolicy.expiry_days),
        history_count: String(passwordPolicy.history_count),
    });
    const maintenanceForm = useForm<MaintenanceModeForm>({
        enabled: maintenanceMode.enabled,
        message: maintenanceMode.message ?? '',
        page_style: maintenanceMode.page_style ?? 'aurora',
        retry_seconds: maintenanceMode.retry_seconds ? String(maintenanceMode.retry_seconds) : '',
        refresh_seconds: maintenanceMode.refresh_seconds ? String(maintenanceMode.refresh_seconds) : '',
        secret: '',
    });
    const testForm = useForm<TestEmailForm>({
        recipient: '',
    });

    const initialRetry = retrySecondsToParts(maintenanceMode.retry_seconds);
    const [retryAmount, setRetryAmount] = useState(initialRetry.amount);
    const [retryUnit, setRetryUnit] = useState<RetryUnit>(initialRetry.unit);
    const retrySecondsPreview = Number(retryPartsToSeconds(retryAmount, retryUnit)) || null;
    const retryBreakdownPreview = formatSecondsBreakdown(retrySecondsPreview);
    const retryIsOutOfRange =
        retrySecondsPreview !== null && (retrySecondsPreview < retryMinimumSeconds || retrySecondsPreview > retryMaximumSeconds);

    const logoPreview = useMemo(
        () => filePreview(brandingForm.data.logo, brandingSettings.logo_url, brandingForm.data.remove_logo),
        [brandingForm.data.logo, brandingForm.data.remove_logo, brandingSettings.logo_url],
    );
    const faviconPreview = useMemo(
        () => filePreview(brandingForm.data.favicon, brandingSettings.favicon_url, brandingForm.data.remove_favicon),
        [brandingForm.data.favicon, brandingForm.data.remove_favicon, brandingSettings.favicon_url],
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.put(route('system-settings.email.update'), {
            preserveScroll: true,
        });
    };

    const submitBranding = (event: FormEvent) => {
        event.preventDefault();

        brandingForm.post(route('system-settings.branding.update'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitLocalization = (event: FormEvent) => {
        event.preventDefault();

        localizationForm.put(route('system-settings.localization.update'), {
            preserveScroll: true,
        });
    };

    const submitPagination = (event: FormEvent) => {
        event.preventDefault();

        paginationForm.put(route('system-settings.pagination.update'), {
            preserveScroll: true,
        });
    };

    const submitSecurityPolicy = (event: FormEvent) => {
        event.preventDefault();

        securityForm.put(route('system-settings.security-policy.update'), {
            preserveScroll: true,
        });
    };

    const submitPasswordPolicy = (event: FormEvent) => {
        event.preventDefault();

        passwordPolicyForm.put(route('system-settings.password-policy.update'), {
            preserveScroll: true,
        });
    };

    const submitMaintenanceMode = (event: FormEvent) => {
        event.preventDefault();

        maintenanceForm.transform((data) => ({
            ...data,
            retry_seconds: retryPartsToSeconds(retryAmount, retryUnit),
        }));
        maintenanceForm.put(route('system-settings.maintenance-mode.update'), {
            preserveScroll: true,
        });
    };

    const togglePerPageOption = (option: number, checked: boolean) => {
        const nextOptions = checked
            ? [...paginationForm.data.per_page_options, option]
            : paginationForm.data.per_page_options.filter((item) => item !== option);

        const sortedOptions = [...new Set(nextOptions)].sort((a, b) => a - b);
        paginationForm.setData('per_page_options', sortedOptions);

        if (!sortedOptions.includes(Number(paginationForm.data.default_per_page)) && sortedOptions.length > 0) {
            paginationForm.setData('default_per_page', String(sortedOptions[0]));
        }
    };

    const submitTestEmail = (event: FormEvent) => {
        event.preventDefault();

        testForm.post(route('system-settings.email.test'), {
            preserveScroll: true,
        });
    };

    const mailerUsesSmtp = form.data.mailer === 'smtp';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">System Settings</h1>
                        <p className="text-muted-foreground text-sm">Kelola identitas aplikasi, email, dan konfigurasi sistem inti.</p>
                    </div>
                    <Badge variant="outline" className="w-fit">
                        {sectionLabels[activeSection]}
                    </Badge>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
                    {activeSection === 'email' ? (
                        <EmailSettingsPanel
                            can={can}
                            emailSettings={emailSettings}
                            form={form}
                            testForm={testForm}
                            mailerUsesSmtp={mailerUsesSmtp}
                            submit={submit}
                            submitTestEmail={submitTestEmail}
                        />
                    ) : activeSection === 'branding' ? (
                        <BrandingSettingsPanel
                            can={can}
                            form={brandingForm}
                            logoPreview={logoPreview}
                            faviconPreview={faviconPreview}
                            submit={submitBranding}
                        />
                    ) : activeSection === 'localization' ? (
                        <LocalizationSettingsPanel
                            can={can}
                            localizationSettings={localizationSettings}
                            form={localizationForm}
                            submit={submitLocalization}
                        />
                    ) : activeSection === 'pagination' ? (
                        <PaginationSettingsPanel
                            can={can}
                            paginationSettings={paginationSettings}
                            form={paginationForm}
                            togglePerPageOption={togglePerPageOption}
                            submit={submitPagination}
                        />
                    ) : activeSection === 'security' ? (
                        <SecurityPolicyPanel can={can} securityPolicy={securityPolicy} form={securityForm} submit={submitSecurityPolicy} />
                    ) : activeSection === 'password' ? (
                        <PasswordPolicyPanel can={can} passwordPolicy={passwordPolicy} form={passwordPolicyForm} submit={submitPasswordPolicy} />
                    ) : activeSection === 'health' ? (
                        <SystemHealthPanel systemHealth={systemHealth} />
                    ) : activeSection === 'environment' ? (
                        <EnvironmentInfoPanel environmentInfo={environmentInfo} />
                    ) : (
                        <MaintenanceModePanel
                            can={can}
                            maintenanceMode={maintenanceMode}
                            form={maintenanceForm}
                            retryAmount={retryAmount}
                            retryUnit={retryUnit}
                            retrySecondsPreview={retrySecondsPreview}
                            retryBreakdownPreview={retryBreakdownPreview}
                            retryIsOutOfRange={retryIsOutOfRange}
                            setRetryAmount={setRetryAmount}
                            setRetryUnit={setRetryUnit}
                            submit={submitMaintenanceMode}
                        />
                    )}
                    <SystemSettingMenu activeSection={activeSection} onSectionChange={setActiveSection} />
                </div>
            </div>
        </AppLayout>
    );
}
