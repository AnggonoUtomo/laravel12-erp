export type RetryUnit = 'seconds' | 'minutes' | 'hours' | 'days';

export type SystemSettingSection =
    | 'email'
    | 'branding'
    | 'localization'
    | 'pagination'
    | 'security'
    | 'password'
    | 'maintenance'
    | 'health'
    | 'environment';

export type EmailSettings = {
    enabled: boolean;
    mailer: 'smtp' | 'log' | 'array';
    host: string | null;
    port: number | null;
    username: string | null;
    password: string | null;
    password_configured: boolean;
    encryption: 'tls' | 'ssl' | null;
    from_address: string;
    from_name: string;
    send_credentials_on_create: boolean;
    send_credentials_on_password_update: boolean;
    credential_subject: string | null;
    credential_intro: string | null;
};

export type BrandingSettings = {
    app_name: string;
    logo_url: string | null;
    favicon_url: string | null;
};

export type LocalizationSettings = {
    timezone: string;
    date_format: string;
    time_format: string;
    datetime_format: string;
    preview_date: string;
    preview_time: string;
    preview_datetime: string;
};

export type PaginationSettings = {
    default_per_page: number;
    per_page_options: number[];
};

export type SecurityPolicy = {
    require_email_verification: boolean;
    audit_sensitive_actions: boolean;
    single_session_per_user: boolean;
    allow_account_deletion: boolean;
    session_lifetime_minutes: number;
    login_max_attempts: number;
    login_decay_minutes: number;
    password_confirmation_timeout_seconds: number;
};

export type PasswordPolicy = {
    min_length: number;
    require_uppercase: boolean;
    require_lowercase: boolean;
    require_numbers: boolean;
    require_symbols: boolean;
    uncompromised: boolean;
    expiry_days: number;
    history_count: number;
};

export type MaintenanceStyle = 'aurora' | 'operations' | 'minimal';

export type MaintenanceMode = {
    enabled: boolean;
    active: boolean;
    message: string | null;
    page_style: MaintenanceStyle;
    retry_seconds: number | null;
    refresh_seconds: number | null;
    secret: string | null;
    secret_configured: boolean;
    bypass_url: string | null;
};

export type HealthStatus = 'ok' | 'warning' | 'error';

export type SystemHealthCheck = {
    name: string;
    description: string;
    status: HealthStatus;
    value: string | number | boolean | null;
    meta: string | null;
    message: string;
};

export type SystemHealth = {
    summary: {
        status: HealthStatus;
        ok: number;
        warning: number;
        error: number;
        checked_at: string;
    };
    runtime: Record<string, string | number | boolean | null>;
    checks: SystemHealthCheck[];
};

export type EnvironmentInfoItem = {
    label: string;
    value: string | number | boolean | null;
    status?: HealthStatus;
};

export type EnvironmentInfoGroup = {
    title: string;
    description: string;
    items: EnvironmentInfoItem[];
};

export type EnvironmentInfo = {
    summary: {
        mode: string;
        generated_at: string;
        notice: string;
    };
    groups: EnvironmentInfoGroup[];
};

export type SystemSettingsProps = {
    emailSettings: EmailSettings;
    brandingSettings: BrandingSettings;
    localizationSettings: LocalizationSettings;
    paginationSettings: PaginationSettings;
    securityPolicy: SecurityPolicy;
    passwordPolicy: PasswordPolicy;
    maintenanceMode: MaintenanceMode;
    systemHealth: SystemHealth;
    environmentInfo: EnvironmentInfo;
    can: {
        update: boolean;
    };
};

export type EmailSettingForm = {
    enabled: boolean;
    mailer: 'smtp' | 'log' | 'array';
    host: string;
    port: string;
    username: string;
    password: string;
    encryption: 'tls' | 'ssl' | 'none';
    from_address: string;
    from_name: string;
    send_credentials_on_create: boolean;
    send_credentials_on_password_update: boolean;
    credential_subject: string;
    credential_intro: string;
};

export type BrandingForm = {
    app_name: string;
    logo: File | null;
    favicon: File | null;
    remove_logo: boolean;
    remove_favicon: boolean;
    _method: 'put';
};

export type LocalizationForm = {
    timezone: string;
    date_format: string;
    time_format: string;
};

export type PaginationForm = {
    default_per_page: string;
    per_page_options: number[];
};

export type SecurityPolicyForm = {
    require_email_verification: boolean;
    audit_sensitive_actions: boolean;
    single_session_per_user: boolean;
    allow_account_deletion: boolean;
    session_lifetime_minutes: string;
    login_max_attempts: string;
    login_decay_minutes: string;
    password_confirmation_timeout_seconds: string;
};

export type PasswordPolicyForm = {
    min_length: string;
    require_uppercase: boolean;
    require_lowercase: boolean;
    require_numbers: boolean;
    require_symbols: boolean;
    uncompromised: boolean;
    expiry_days: string;
    history_count: string;
};

export type MaintenanceModeForm = {
    enabled: boolean;
    message: string;
    page_style: MaintenanceStyle;
    retry_seconds: string;
    refresh_seconds: string;
    secret: string;
};

export type TestEmailForm = {
    recipient: string;
};
