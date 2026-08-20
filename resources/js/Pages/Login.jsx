import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Eye, EyeOff, Leaf, LockKeyhole, LogIn, ShieldCheck, UserRound } from 'lucide-react';

const roles = ['Pengurus', 'Anggota', 'Petugas Sampah'];

function FeatureCard({ icon: Icon, title, description, green = false }) {
    return (
        <article className="flex min-h-36 flex-1 flex-col items-start justify-center gap-2 rounded-xl border border-border bg-secondary px-5 py-4">
            <Icon className={green ? 'text-success' : 'text-primary'} size={24} strokeWidth={2.1} />
            <h2 className="text-sm font-semibold text-foreground">{title}</h2>
            <p className="text-xs leading-5 text-muted-foreground">{description}</p>
        </article>
    );
}

function LoginCard() {
    const [role, setRole] = useState('Anggota');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');

    function handleSubmit(e) {
        e.preventDefault();
        const data = new FormData(e.currentTarget);
        if (!data.get('identity') || !data.get('password')) { setError('Username dan password wajib diisi.'); return; }
        setError('');
        if (role === 'Anggota') router.visit('/anggota');
        else if (role === 'Petugas Sampah') router.visit('/petugas');
        else router.visit('/pengurus');
    }

    return (
        <section className="w-full max-w-[500px] rounded-3xl border border-border/60 bg-card px-6 py-7">
            <header className="flex items-center gap-3">
                <img
                    src="/logo.png"
                    alt="Logo Koperasi"
                    className="size-12 shrink-0 object-contain"
                />
                <div className="min-w-0">
                    <h2 className="truncate text-base font-semibold text-primary md:text-lg">Koperasi Jasa Mulyo Raharjo Lestari</h2>
                </div>
            </header>

            {/* Role tabs */}
            <div className="mt-6 flex rounded-2xl bg-accent p-1" role="tablist" aria-label="Pilih jenis akun">
                {roles.map(item => (
                    <button key={item} type="button" role="tab" aria-selected={role === item}
                        onClick={() => setRole(item)}
                        className={`min-h-10 flex-1 rounded-xl px-2 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-primary md:text-sm ${role === item ? 'bg-card font-semibold text-primary shadow-sm' : 'text-accent-foreground'}`}>
                        {item}
                    </button>
                ))}
            </div>

            <form className="mt-6 flex flex-col gap-4" onSubmit={handleSubmit}>
                {/* Identity */}
                <label className="flex flex-col gap-1.5 text-sm font-medium text-foreground" htmlFor="identity">
                    Username atau Email
                    <span className="flex h-12 items-center gap-3 rounded-xl border border-input/80 bg-background px-3 text-muted-foreground transition-all focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                        <UserRound size={17} />
                        <input id="identity" name="identity" autoComplete="username" placeholder="Masukkan username/Email"
                            className="h-full min-w-0 flex-1 border-none bg-transparent p-0 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0" />
                    </span>
                </label>

                {/* Password */}
                <label className="flex flex-col gap-1.5 text-sm font-medium text-foreground" htmlFor="password">
                    <span className="flex items-center justify-between gap-4">
                        Password
                        <a href="#" className="text-xs font-medium text-primary hover:underline">Lupa Password?</a>
                    </span>
                    <span className="flex h-12 items-center gap-3 rounded-xl border border-input/80 bg-background px-3 text-muted-foreground transition-all focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                        <LockKeyhole size={17} />
                        <input id="password" name="password" type={showPassword ? 'text' : 'password'}
                            autoComplete="current-password" placeholder="Masukkan password"
                            className="h-full min-w-0 flex-1 border-none bg-transparent p-0 text-sm text-foreground placeholder:text-muted-foreground placeholder:tracking-normal focus:outline-none focus:ring-0" />
                        <button type="button" onClick={() => setShowPassword(v => !v)} aria-label="Toggle password"
                            className="p-1 hover:text-primary">
                            {showPassword ? <EyeOff size={17} /> : <Eye size={17} />}
                        </button>
                    </span>
                </label>

                <label className="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" className="size-4 rounded border-2 border-input accent-primary" />
                    Ingat saya di perangkat ini
                </label>

                {error && <p role="alert" className="text-sm font-medium text-destructive">{error}</p>}

                <button type="submit"
                    className="flex h-12 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-base font-semibold text-white shadow-lg shadow-primary/20 transition-transform hover:-translate-y-0.5">
                    Masuk <LogIn size={18} />
                </button>
            </form>

        </section>
    );
}

export default function Login() {
    return (
        <main className="flex min-h-screen items-center justify-center bg-background px-4 py-8 md:px-8">
            <Head title="Masuk Akun" />
            <div className="mx-auto flex w-full max-w-[1100px] flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
                {/* Left panel */}
                <section className="flex w-full max-w-[500px] flex-col items-start">
                    

                    <h1 className="mt-8 max-w-[480px] text-balance text-3xl font-semibold leading-snug tracking-tight text-foreground md:text-[36px]">
                        Kelola Masa Depan<br />
                        <span className="text-primary">Bersama Koperasi Kita.</span>
                    </h1>
                    <p className="mt-4 max-w-[480px] text-pretty text-base leading-relaxed text-muted-foreground">
                        Selamat datang di portal administrasi Koperasi Bersih Sejahtera. Keamanan data dan transparansi adalah prioritas utama kami.
                    </p>

                    <div className="mt-6 flex w-full flex-col gap-3 sm:flex-row">
                        <FeatureCard icon={Building2} title="Keuangan Aman" description="Enkripsi tingkat tinggi untuk setiap transaksi keuangan." />
                        <FeatureCard icon={Leaf} title="Ecological Growth" description="Mendukung pertumbuhan ekonomi yang berkelanjutan." green />
                    </div>
                </section>

                <LoginCard />
            </div>
        </main>
    );
}
