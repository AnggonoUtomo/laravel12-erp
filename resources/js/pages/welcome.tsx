import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Boxes, Database, LayoutDashboard, ShieldCheck } from 'lucide-react';

type LoginDestination = {
    name: string;
    description: string;
    loginRoute: string;
    dashboardRoute: string;
    icon: typeof LayoutDashboard;
    cardClassName: string;
    iconClassName: string;
    buttonClassName: string;
};

const destinations: LoginDestination[] = [
    {
        name: 'Console',
        description: 'Administrasi sistem dan pengaturan aplikasi.',
        loginRoute: route('login'),
        dashboardRoute: route('dashboard'),
        icon: LayoutDashboard,
        cardClassName: 'border-sky-800/70 bg-sky-950 text-white',
        iconClassName: 'border-white/15 bg-white/10 text-sky-100',
        buttonClassName: 'bg-sky-500 text-white hover:bg-sky-400',
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = Boolean(auth.user);

    return (
        <>
            <Head title="Masuk" />

            <main className="bg-muted/30 text-foreground relative flex min-h-screen flex-col overflow-x-hidden px-5 lg:h-screen lg:overflow-hidden">
                <div className="bg-primary/5 absolute top-0 left-1/2 h-80 w-80 -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl" />

                <section className="relative mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center py-6" aria-labelledby="welcome-title">
                    <div className="mb-5 text-center">
                        <span className="bg-primary text-primary-foreground mx-auto flex size-10 items-center justify-center rounded-lg shadow-sm">
                            <AppLogoIcon className="size-6 fill-current" />
                        </span>
                        <p className="text-muted-foreground mt-3 text-[10px] font-semibold tracking-[0.22em] uppercase">
                            Enterprise Resource Planning
                        </p>
                        <h1 id="welcome-title" className="mt-2 text-2xl font-semibold tracking-tight">
                            Selamat datang
                        </h1>
                        <p className="text-muted-foreground mt-1 text-xs leading-5">
                            {isAuthenticated ? 'Pilih workspace yang ingin Anda buka.' : 'Pilih workspace untuk melanjutkan ke halaman login.'}
                        </p>
                    </div>

                    <div className="mx-auto grid w-full max-w-xs gap-4">
                        {destinations.map((destination) => {
                            const Icon = destination.icon;
                            const href = isAuthenticated ? destination.dashboardRoute : destination.loginRoute;

                            return (
                                <div
                                    key={destination.name}
                                    className={`flex aspect-square flex-col items-start justify-between rounded-xl border p-5 shadow-lg shadow-black/10 transition-transform duration-200 hover:-translate-y-1 ${destination.cardClassName}`}
                                >
                                    <div>
                                        <span className={`flex size-10 items-center justify-center rounded-lg border ${destination.iconClassName}`}>
                                            <Icon className="size-5" aria-hidden="true" />
                                        </span>
                                        <h2 className="mt-4 text-lg font-semibold">{destination.name}</h2>
                                        <p className="mt-1 text-xs leading-5 text-white/70">{destination.description}</p>
                                    </div>
                                    <Button asChild className={`w-full justify-between ${destination.buttonClassName}`}>
                                        <Link href={href} aria-label={`${isAuthenticated ? 'Buka' : 'Login ke'} ${destination.name}`}>
                                            {isAuthenticated ? 'Buka' : 'Login'}
                                            <ArrowRight className="size-4" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                </div>
                            );
                        })}
                    </div>
                </section>

                <footer className="bg-muted/30 relative -mx-5 border-t pt-3">
                    <div className="mx-auto w-full max-w-5xl px-5">
                        <div className="flex flex-wrap justify-center gap-2">
                            <FeatureCard
                                icon={ShieldCheck}
                                title="Akses terkontrol"
                                description="Policy server-side"
                                tone="bg-sky-500/12 text-sky-600"
                            />
                            <FeatureCard
                                icon={Boxes}
                                title="Arsitektur modular"
                                description="Boundary terpisah"
                                tone="bg-emerald-500/12 text-emerald-600"
                            />
                            <FeatureCard
                                icon={Database}
                                title="Data terlindungi"
                                description="Validasi dan audit"
                                tone="bg-amber-500/12 text-amber-600"
                            />
                        </div>
                    </div>

                    <div className="mt-3 w-full border-t border-slate-800 bg-slate-950 px-5 py-3 text-white">
                        <div className="mx-auto flex w-full max-w-5xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex flex-wrap items-center gap-2" aria-label="Informasi teknis">
                                <Badge className="border border-sky-400/20 bg-sky-400/10 text-sky-200 hover:bg-sky-400/10">
                                    <Boxes className="size-3" /> Laravel 12
                                </Badge>
                                <Badge className="border border-emerald-400/20 bg-emerald-400/10 text-emerald-200 hover:bg-emerald-400/10">
                                    <LayoutDashboard className="size-3" /> React + Inertia
                                </Badge>
                                <Badge className="border border-amber-400/20 bg-amber-400/10 text-amber-200 hover:bg-amber-400/10">
                                    <ShieldCheck className="size-3" /> TypeScript
                                </Badge>
                            </div>
                            <p className="text-[11px] text-slate-300">Akun dari administrator · © {new Date().getFullYear()} ERP</p>
                        </div>
                    </div>
                </footer>
            </main>
        </>
    );
}

function FeatureCard({ icon: Icon, title, description, tone }: { icon: typeof ShieldCheck; title: string; description: string; tone: string }) {
    return (
        <article className="bg-background/80 flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 sm:w-56">
            <span className={`flex size-8 shrink-0 items-center justify-center rounded-md ${tone}`}>
                <Icon className="size-4" aria-hidden="true" />
            </span>
            <div>
                <h2 className="text-xs font-semibold">{title}</h2>
                <p className="text-muted-foreground text-[11px] leading-4">{description}</p>
            </div>
        </article>
    );
}
