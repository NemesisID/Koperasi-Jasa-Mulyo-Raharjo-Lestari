import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Bell, ChevronDown, CircleHelp, FileBarChart,
    LayoutGrid, LogOut, Menu, Settings, UserRound, WalletCards, X,
} from 'lucide-react';
import { ConfirmPopup } from './Popups';

const cn = (...cls) => cls.filter(Boolean).join(' ');

export function MemberShell({ children, currentPage, setPage }) {
    const [mobileOpen, setMobileOpen] = useState(false);
    const [logoutOpen, setLogoutOpen] = useState(false);
    const [savingsOpen, setSavingsOpen] = useState(currentPage?.startsWith('simpanan'));

    const close = () => setMobileOpen(false);
    const nav = (page) => { setPage(page); close(); };
    const is = (key) => currentPage === key;
    const startsWith = (key) => currentPage?.startsWith(key);

    const navBtnCls = (active) => cn(
        'flex h-11 items-center gap-3 rounded-xl px-4 text-sm font-medium transition-all duration-150',
        active
            ? 'bg-primary text-white shadow-sm'
            : 'text-muted-foreground hover:bg-accent/60 hover:text-primary'
    );

    const subNavBtnCls = (active) => cn(
        'flex items-center gap-2 border-l-[3px] py-2 pl-4 pr-3 text-sm transition-all duration-150 text-left',
        active
            ? 'border-primary font-semibold text-primary'
            : 'border-transparent font-normal text-muted-foreground hover:border-slate-300 hover:text-primary'
    );

    const sidebar = (
        <aside className="custom-scrollbar flex h-full flex-col bg-[#f3f6fc] overflow-y-auto">
            {/* Logo */}
            <div className="flex items-center gap-3 px-6 py-5 border-b border-border/40">
                <img
                    src="/logo.png"
                    alt="Logo Koperasi"
                    className="size-10 shrink-0 object-contain"
                />
                <div className="min-w-0">
                    <p className="text-base font-bold text-primary leading-tight">Anggota</p>
                    <p className="truncate text-xs text-muted-foreground">Koperasi Jasa Mulyo</p>
                </div>
            </div>

            {/* Navigation */}
            <nav className="custom-scrollbar flex flex-1 flex-col gap-1 px-3 py-4 overflow-y-auto" aria-label="Navigasi utama">
                {/* Dashboard */}
                <button onClick={() => nav('dashboard')} className={navBtnCls(is('dashboard'))}>
                    <LayoutGrid size={18} />
                    <span>Dashboard</span>
                </button>

                {/* Simpanan */}
                <div>
                    <button
                        onClick={() => setSavingsOpen(v => !v)}
                        className={cn(
                            navBtnCls(startsWith('simpanan')),
                            'w-full justify-between'
                        )}
                    >
                        <span className="flex items-center gap-3">
                            <WalletCards size={18} />
                            <span>Simpanan</span>
                        </span>
                        <ChevronDown size={16} className={cn('transition-transform duration-200', savingsOpen && 'rotate-180')} />
                    </button>
                    {savingsOpen && (
                        <div className="mt-1 flex flex-col gap-0.5 pl-4 animate-in fade-in-50 duration-150">
                            {[
                                ['Pokok', 'simpanan-pokok'],
                                ['Wajib', 'simpanan-wajib'],
                                ['Sukarela', 'simpanan-sukarela'],
                            ].map(([label, key]) => (
                                <button
                                    key={key}
                                    onClick={() => nav(key)}
                                    className={subNavBtnCls(is(key))}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Laporan */}
                <button onClick={() => nav('laporan')} className={navBtnCls(startsWith('laporan'))}>
                    <FileBarChart size={18} />
                    <span>Laporan</span>
                </button>
            </nav>

            {/* Footer */}
            <div className="flex flex-col gap-1 border-t border-border/60 p-3 mt-auto bg-[#f3f6fc]">
                <button className={navBtnCls(false)}>
                    <CircleHelp size={18} />
                    <span>Bantuan</span>
                </button>
                <button className={navBtnCls(false)}>
                    <Settings size={18} />
                    <span>Pengaturan</span>
                </button>
                <button
                    onClick={() => setLogoutOpen(true)}
                    className="mt-1 flex h-11 items-center justify-center gap-3 rounded-xl bg-destructive text-sm font-semibold text-white shadow-sm transition-all hover:bg-destructive/90 hover:shadow"
                >
                    <LogOut size={18} />
                    <span>Logout</span>
                </button>
            </div>
        </aside>
    );

    return (
        <div className="min-h-screen bg-background font-sans">
            {/* Desktop sidebar */}
            <div className="fixed inset-y-0 left-0 hidden w-[280px] border-r border-border bg-secondary lg:block">
                {sidebar}
            </div>

            {/* Mobile sidebar */}
            {mobileOpen && (
                <div className="fixed inset-0 z-40 bg-foreground/20 backdrop-blur-sm lg:hidden modal-backdrop-anim" onClick={close}>
                    <div className="h-full w-[270px] shadow-2xl" onClick={e => e.stopPropagation()}>
                        {sidebar}
                    </div>
                </div>
            )}

            {/* Header (Solid) */}
            <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-card px-4 lg:ml-[280px] lg:px-8">
                <div className="flex items-center gap-3">
                    <button
                        aria-label="Buka menu"
                        onClick={() => setMobileOpen(!mobileOpen)}
                        className="rounded-lg border border-border p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground lg:hidden"
                    >
                        {mobileOpen ? <X size={18} /> : <Menu size={18} />}
                    </button>
                    <div>
                        <h1 className="text-base font-bold text-foreground md:text-lg">
                            Selamat Datang, <span className="text-primary font-semibold">(Nama)</span>
                        </h1>
                        <p className="text-xs text-muted-foreground">Koperasi Jasa Mulyo Raharjo Lestari</p>
                    </div>
                </div>
                <div className="flex items-center gap-4">
                    <button
                        aria-label="Notifikasi"
                        className="relative rounded-full p-2 text-muted-foreground hover:bg-accent hover:text-primary transition-colors"
                    >
                        <Bell size={20} />
                        <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-destructive ring-2 ring-card" />
                    </button>
                    <div className="flex items-center gap-2.5 rounded-full bg-[#e8efff] py-1.5 pl-4 pr-1.5 text-primary">
                        <div className="text-right leading-none">
                            <p className="text-xs font-semibold text-foreground">(Nama)</p>
                            <span className="inline-block mt-0.5 rounded-full bg-primary px-2 py-0.5 text-[9px] font-bold tracking-widest text-white">
                                ANGGOTA
                            </span>
                        </div>
                        <div className="flex size-8 items-center justify-center rounded-full border-2 border-primary bg-card text-primary shadow-sm">
                            <UserRound size={16} />
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Page Content */}
            <main key={currentPage} className="page-enter px-4 py-6 lg:ml-[280px] lg:px-8">
                {children}
            </main>

            <ConfirmPopup
                open={logoutOpen}
                title="Logout?"
                description="Apakah Anda ingin logout dari sistem?"
                actionLabel="Keluar"
                tone="destructive"
                icon="logout"
                onCancel={() => setLogoutOpen(false)}
                onConfirm={() => router.visit('/')}
            />
        </div>
    );
}
