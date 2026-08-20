import { useState, useMemo, createContext, useContext } from 'react';
import { Head } from '@inertiajs/react';
import {
    ArrowDownToLine, BarChart3, Check, CheckCircle2, ChevronLeft,
    ChevronRight, CircleDollarSign, Clock, Download, Eye,
    FileBarChart, Filter, Landmark, Package,
    Plus, PlusCircle, RefreshCcw, Search,
    TrendingDown, TrendingUp, Truck, UserPlus, UserRound,
    WalletCards, X
} from 'lucide-react';
import { ManagerShell } from '@/Components/Koperasi/ManagerShell';
import { FormPopup, StatusPopup } from '@/Components/Koperasi/Popups';

// ─── Popup context ────────────────────────────────────────────
const PopupCtx = createContext({ openForm: () => {}, showStatus: () => {} });
const usePopup = () => useContext(PopupCtx);

const cn = (...cls) => cls.filter(Boolean).join(' ');

// ─── Reusable Small Components ────────────────────────────────
function DownloadButton({ label = 'Unduh Laporan' }) {
    const { showStatus } = usePopup();
    const go = () => {
        const url = URL.createObjectURL(new Blob(['Laporan Koperasi Jasa Mulyo Raharjo Lestari\nTanggal: ' + new Date().toLocaleDateString('id-ID')], { type: 'text/plain' }));
        const a = Object.assign(document.createElement('a'), { href: url, download: 'laporan-koperasi.txt' });
        a.click();
        URL.revokeObjectURL(url);
        showStatus('PDF Berhasil Didownload', 'Dokumen laporan telah tersimpan di unduhan Anda.');
    };
    return (
        <button
            onClick={go}
            className="flex h-10 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-emerald-800 hover:shadow"
        >
            <ArrowDownToLine size={16} />
            <span>{label}</span>
        </button>
    );
}

function PageHeader({ title, desc, action }) {
    return (
        <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 className="text-2xl font-bold text-foreground md:text-3xl">{title}</h1>
                {desc && <p className="mt-1 text-sm text-muted-foreground">{desc}</p>}
            </div>
            {action && <div className="flex flex-wrap items-center gap-3">{action}</div>}
        </div>
    );
}

function StatCard({ icon: Icon = WalletCards, label, value, note, tone = 'blue', indicator = 'top' }) {
    const toneStyles = {
        blue: {
            iconBg: 'bg-blue-50 text-primary',
            border: indicator === 'top' ? 'border-t-4 border-t-primary' : 'border-l-4 border-l-primary',
            note: 'text-primary',
        },
        green: {
            iconBg: 'bg-emerald-50 text-emerald-600',
            border: indicator === 'top' ? 'border-t-4 border-t-emerald-600' : 'border-l-4 border-l-emerald-600',
            note: 'text-emerald-700 font-medium',
        },
        red: {
            iconBg: 'bg-rose-50 text-rose-600',
            border: indicator === 'top' ? 'border-t-4 border-t-rose-600' : 'border-l-4 border-l-rose-600',
            note: 'text-rose-600 font-medium',
        },
        gold: {
            iconBg: 'bg-amber-50 text-amber-700',
            border: indicator === 'top' ? 'border-t-4 border-t-amber-600' : 'border-l-4 border-l-amber-600',
            note: 'text-amber-700 font-medium',
        },
    };

    const s = toneStyles[tone] || toneStyles.blue;

    return (
        <article className={cn('flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-5 shadow-xs transition-all hover:shadow-md', s.border)}>
            <div className="flex items-center justify-between">
                <span className={cn('flex size-10 items-center justify-center rounded-xl', s.iconBg)}>
                    <Icon size={20} />
                </span>
                {note && <span className={cn('text-xs font-semibold', s.note)}>{note}</span>}
            </div>
            <div className="mt-4">
                <p className="text-xs uppercase tracking-wider text-muted-foreground">{label}</p>
                <strong className="mt-1 block text-2xl font-bold text-foreground md:text-[26px]">{value}</strong>
            </div>
        </article>
    );
}

function Pager({ total = 3 }) {
    const [page, setPage] = useState(1);
    return (
        <div className="flex items-center justify-end gap-1.5 p-4 border-t border-border/60">
            <button
                onClick={() => setPage(Math.max(1, page - 1))}
                disabled={page === 1}
                className="flex size-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground hover:bg-secondary disabled:opacity-40"
            >
                <ChevronLeft size={16} />
            </button>
            {Array.from({ length: total }, (_, i) => i + 1).map(x => (
                <button
                    key={x}
                    onClick={() => setPage(x)}
                    className={cn(
                        'flex size-9 items-center justify-center rounded-lg text-xs font-bold transition-all',
                        page === x
                            ? 'bg-primary text-white shadow-xs'
                            : 'border border-border bg-card text-foreground hover:bg-secondary'
                    )}
                >
                    {x}
                </button>
            ))}
            <button
                onClick={() => setPage(Math.min(total, page + 1))}
                disabled={page === total}
                className="flex size-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground hover:bg-secondary disabled:opacity-40"
            >
                <ChevronRight size={16} />
            </button>
        </div>
    );
}

// ─── 1. Dashboard Sub-page ────────────────────────────────────
function DashboardPage({ setPage }) {
    const recentTx = [
        { name: 'Bambang Susanto', initial: 'BS', type: 'Simpanan Wajib', method: 'Tabungan Sampah', methodIcon: RefreshCcw, amount: 'Rp 150.000', status: 'Berhasil' },
        { name: 'Siti Halimah', initial: 'SH', type: 'Simpanan Sukarela', method: 'Tunai', methodIcon: WalletCards, amount: 'Rp 500.000', status: 'Berhasil' },
        { name: 'Agus Raharjo', initial: 'AR', type: 'Simpanan Pokok', method: 'Transfer Bank', methodIcon: Landmark, amount: 'Rp 1.000.000', status: 'Verifikasi' },
    ];

    return (
        <div className="flex flex-col gap-6">
            {/* Hero Banner: Ringkasan Koperasi (Full width, Target Sampah removed) */}
            <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073d8c] via-[#0a56c2] to-[#1466dc] p-7 md:p-9 text-white shadow-lg">
                <div className="absolute right-0 top-0 translate-x-12 -translate-y-12 size-80 rounded-full bg-white/5 blur-2xl pointer-events-none" />
                <div className="relative z-10">
                    <h2 className="text-2xl font-bold tracking-tight md:text-3xl">Ringkasan Koperasi</h2>
                    <p className="mt-1 text-sm text-blue-100/80">Pantauan menyeluruh aktivitas keanggotaan dan keuangan koperasi.</p>

                    <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-white/15">
                        <div className="pt-4 sm:pt-0 sm:pr-4">
                            <p className="text-xs font-medium uppercase tracking-wider text-blue-200/90">Total Anggota</p>
                            <div className="mt-1 flex items-baseline gap-1.5">
                                <strong className="text-3xl font-extrabold tracking-tight md:text-4xl">1,284</strong>
                                <span className="text-sm font-medium text-blue-200">Orang</span>
                            </div>
                        </div>

                        <div className="pt-4 sm:pt-0 sm:px-6">
                            <p className="text-xs font-medium uppercase tracking-wider text-blue-200/90">Setoran Sampah</p>
                            <div className="mt-1 flex items-baseline gap-1.5">
                                <strong className="text-3xl font-extrabold tracking-tight md:text-4xl">4,850</strong>
                                <span className="text-sm font-medium text-blue-200">kg</span>
                            </div>
                        </div>

                        <div className="pt-4 sm:pt-0 sm:pl-6">
                            <p className="text-xs font-medium uppercase tracking-wider text-blue-200/90">Total Saldo Aktif</p>
                            <div className="mt-1">
                                <strong className="text-3xl font-extrabold tracking-tight md:text-4xl">Rp 245,8M</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 3 Stat Cards */}
            <div className="grid gap-5 md:grid-cols-3">
                <StatCard
                    icon={WalletCards}
                    label="Simpanan Masuk Hari Ini"
                    value="Rp 12.450.000"
                    note="↗ 12% dari kemarin"
                    tone="blue"
                />
                <StatCard
                    icon={TrendingUp}
                    label="Total Pendapatan (Bulan Ini)"
                    value="Rp 84.320.000"
                    note="✓ Sesuai Target"
                    tone="green"
                />
                <StatCard
                    icon={TrendingDown}
                    label="Total Pengeluaran"
                    value="Rp 28.150.000"
                    note="⚠ 5 Transaksi Pending"
                    tone="red"
                />
            </div>

            {/* Recent Transactions Table */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center border-b border-border/60">
                    <div className="flex items-center gap-2.5">
                        <Clock size={20} className="text-primary" />
                        <h2 className="text-lg font-bold text-foreground">Ringkasan Transaksi Terbaru</h2>
                    </div>
                    <button
                        onClick={() => setPage('laporan')}
                        className="rounded-xl border border-primary/40 bg-card px-4 py-2 text-xs font-semibold text-primary transition-all hover:bg-primary hover:text-white"
                    >
                        Lihat Semua Laporan
                    </button>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Nama Anggota</th>
                                <th className="px-6 py-3.5">Jenis Simpanan</th>
                                <th className="px-6 py-3.5">Metode</th>
                                <th className="px-6 py-3.5">Jumlah</th>
                                <th className="px-6 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {recentTx.map((tx, idx) => {
                                const MethodIcon = tx.methodIcon;
                                return (
                                    <tr key={idx} className="hover:bg-secondary/40 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <div className={cn(
                                                    'flex size-9 items-center justify-center rounded-full text-xs font-bold',
                                                    idx === 0 ? 'bg-blue-100 text-primary' : idx === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'
                                                )}>
                                                    {tx.initial}
                                                </div>
                                                <span className="font-semibold text-foreground">{tx.name}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={cn(
                                                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                                tx.type === 'Simpanan Wajib' ? 'bg-primary text-white' : tx.type === 'Simpanan Sukarela' ? 'bg-amber-100 text-amber-800' : 'bg-blue-600 text-white'
                                            )}>
                                                {tx.type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-2 text-muted-foreground">
                                                <MethodIcon size={16} />
                                                <span>{tx.method}</span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 font-bold text-primary">{tx.amount}</td>
                                        <td className="px-6 py-4">
                                            {tx.status === 'Berhasil' ? (
                                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
                                                    <CheckCircle2 size={15} /> Berhasil
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600">
                                                    <Clock size={15} /> Verifikasi
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <div className="p-4 text-center text-xs text-muted-foreground border-t border-border/60 bg-slate-50/50">
                    Menampilkan 3 dari 124 transaksi terbaru hari ini.
                </div>
            </section>
        </div>
    );
}

// ─── 2. Simpanan Pokok Sub-page ───────────────────────────────
function SavingsPokokPage() {
    const { openForm } = usePopup();
    const [search, setSearch] = useState('');

    const members = [
        { initial: 'BW', name: 'Bambang Wijaya', date: '12 Okt 2023', amount: 'Rp 50.000', status: 'Berhasil' },
        { initial: 'SA', name: 'Siti Aminah', date: '11 Okt 2023', amount: 'Rp 50.000', status: 'Berhasil' },
        { initial: 'AH', name: 'Ahmad Hidayat', date: '10 Okt 2023', amount: 'Rp 50.000', status: 'Berhasil' },
        { initial: 'LS', name: 'Lilik Suradi', date: '09 Okt 2023', amount: 'Rp 50.000', status: 'Berhasil' },
        { initial: 'SY', name: 'Suryono', date: '08 Okt 2023', amount: 'Rp 50.000', status: 'Berhasil' },
    ];

    const filtered = members.filter(m => m.name.toLowerCase().includes(search.toLowerCase()));

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Manajemen Simpanan Pokok"
                desc="Kelola dana keanggotaan awal untuk kestabilan koperasi kita."
            />

            {/* Top Summary Banner: Simpanan Pokok Pribadi */}
            <div className="flex flex-col justify-between gap-4 rounded-2xl border border-blue-200 bg-[#eaf1fd] p-5 sm:flex-row sm:items-center">
                <div className="flex items-center gap-3.5">
                    <div className="flex size-12 items-center justify-center rounded-xl bg-primary text-white shadow-sm">
                        <UserRound size={22} />
                    </div>
                    <div>
                        <h2 className="text-base font-bold text-foreground">Simpanan Pokok Pribadi</h2>
                        <p className="text-xs text-muted-foreground">Tabungan simpanan Anda.</p>
                    </div>
                </div>
                <div className="flex flex-col sm:items-end">
                    <strong className="text-2xl font-extrabold text-primary">Rp 50.000</strong>
                    <span className="mt-1 inline-block rounded-full bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                        STATUS: TERVERIFIKASI
                    </span>
                </div>
            </div>

            {/* 3 Cards (Stats + CTA Card) */}
            <div className="grid gap-5 md:grid-cols-3">
                <StatCard
                    icon={WalletCards}
                    label="Total Terkumpul"
                    value="Rp 24.550.000"
                    note="↗ +12% Bulan ini"
                    tone="blue"
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Anggota Sudah Bayar"
                    value="491 Orang"
                    note="👥 82% dari total 600"
                    tone="green"
                />

                {/* Blue CTA Card matching Berkas */}
                <div className="flex flex-col justify-between rounded-2xl bg-primary p-6 text-white shadow-md">
                    <div>
                        <h2 className="text-lg font-bold">Daftarkan Anggota Baru</h2>
                        <p className="mt-2 text-xs leading-relaxed text-blue-100">
                            Daftarkan Anggota Baru dengan memasukkan data dan nominal Simpanan Pokok.
                        </p>
                    </div>
                    <button
                        onClick={() => openForm('member')}
                        className="mt-5 flex h-10 items-center justify-center gap-2 rounded-xl bg-white text-xs font-bold text-primary shadow-sm transition-all hover:bg-blue-50"
                    >
                        <UserPlus size={16} />
                        <span>Daftarkan</span>
                    </button>
                </div>
            </div>

            {/* Table */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Daftar Setoran Anggota</h2>
                    <div className="flex items-center gap-2">
                        <div className="relative flex-1 sm:w-64">
                            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                placeholder="Cari nama anggota..."
                                className="h-9 w-full rounded-xl border border-border bg-slate-50/50 pl-9 pr-3 text-xs focus:bg-white"
                            />
                        </div>
                        <button className="flex size-9 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground hover:bg-secondary">
                            <Filter size={15} />
                        </button>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Nama Anggota</th>
                                <th className="px-6 py-3.5">Tanggal Setoran</th>
                                <th className="px-6 py-3.5">Jumlah</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {filtered.map((m, idx) => (
                                <tr key={idx} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-primary">
                                                {m.initial}
                                            </div>
                                            <span className="font-semibold text-foreground">{m.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-muted-foreground">{m.date}</td>
                                    <td className="px-6 py-4 font-bold text-primary">{m.amount}</td>
                                    <td className="px-6 py-4">
                                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                            <CheckCircle2 size={14} /> {m.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-xs font-bold text-primary hover:underline">
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex flex-col sm:flex-row items-center justify-between p-4 border-t border-border/60 bg-slate-50/50 text-xs text-muted-foreground">
                    <span>Menampilkan 5 dari 491 data</span>
                    <Pager total={3} />
                </div>
            </section>
        </div>
    );
}

// ─── 3. Simpanan Wajib Sub-page ───────────────────────────────
function SavingsWajibPage() {
    const { openForm } = usePopup();
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Manajemen Simpanan Wajib"
                desc="Setoran sampah tiap bulan yang diakumulasi untuk modal bersama koperasi."
                action={
                    <button
                        onClick={() => openForm('waste')}
                        className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                    >
                        <Plus size={16} />
                        <span>Input Setoran Sampah</span>
                    </button>
                }
            />

            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={RefreshCcw} label="Total Berat Sampah" value="1,240.5 kg" note="↗ +12% Bulan Ini" tone="blue" />
                <StatCard icon={CircleDollarSign} label="Total Konversi Rupiah" value="Rp 15.420.000" note="✓ Stabil" tone="green" />
                <StatCard icon={UserRound} label="Anggota Paling Aktif" value="Bapak Slamet" note="85 kg / bulan" tone="gold" />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Data Setoran Sampah Anggota</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Berat (KG)</th>
                                <th className="px-6 py-3.5">Nilai Konversi (RP)</th>
                                <th className="px-6 py-3.5">Tanggal Setor</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { weight: '24.5 kg', val: 'Rp 122.500', date: '12 Okt 2023', status: 'Terverifikasi' },
                                { weight: '18.0 kg', val: 'Rp 90.000', date: '11 Okt 2023', status: 'Terverifikasi' },
                                { weight: '32.1 kg', val: 'Rp 160.500', date: '10 Okt 2023', status: 'Terverifikasi' },
                                { weight: '15.4 kg', val: 'Rp 77.000', date: '08 Okt 2023', status: 'Terverifikasi' },
                            ].map((row, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 font-bold text-emerald-700">{row.weight}</td>
                                    <td className="px-6 py-4 font-semibold text-foreground">{row.val}</td>
                                    <td className="px-6 py-4 text-muted-foreground">{row.date}</td>
                                    <td className="px-6 py-4">
                                        <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                            {row.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-xs font-bold text-primary hover:underline">Detail</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 4. Simpanan Sukarela Sub-page ────────────────────────────
function SavingsSukarelaPage() {
    const { openForm } = usePopup();
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Manajemen Simpanan Sukarela"
                desc="Kelola dana simpanan sukarela anggota dengan transparansi penuh."
                action={
                    <button
                        onClick={() => openForm('voluntary')}
                        className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                    >
                        <Plus size={16} />
                        <span>Input Simpanan Sukarela</span>
                    </button>
                }
            />

            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={WalletCards} label="Total Dana Sukarela" value="Rp 154.250.000" note="↗ +12% Bulan Ini" tone="blue" />
                <StatCard icon={CheckCircle2} label="Transaksi Hari Ini" value="42 Transaksi" note="✓ Terverifikasi" tone="green" />
                <StatCard icon={TrendingUp} label="Rata-rata Setoran" value="Rp 3.670.000" note="30 hari terakhir" tone="gold" />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Riwayat Simpanan Sukarela</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Waktu Transaksi</th>
                                <th className="px-6 py-3.5">Nominal</th>
                                <th className="px-6 py-3.5">Catatan</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { time: 'Hari Ini, 10:45 WIB', amount: 'Rp 5.500.000', note: 'Setoran rutin bulanan' },
                                { time: 'Hari Ini, 09:12 WIB', amount: 'Rp 12.000.000', note: 'Bonus penjualan limbah daur ulang' },
                                { time: 'Kemarin, 16:30 WIB', amount: 'Rp 2.250.000', note: 'Simpanan tambahan sukarela' },
                                { time: 'Kemarin, 14:15 WIB', amount: 'Rp 750.000', note: 'Setoran mandiri via transfer' },
                            ].map((row, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 text-muted-foreground">{row.time}</td>
                                    <td className="px-6 py-4 font-bold text-emerald-700">{row.amount}</td>
                                    <td className="px-6 py-4 text-foreground">{row.note}</td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-xs font-bold text-primary hover:underline">Detail</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 5. SHU Page ──────────────────────────────────────────────
function ShuPage() {
    const { openForm } = usePopup();
    const rows = [
        { initial: 'HS', name: 'Hadi Suwarno', principal: 'Rp 12.500.000', participation: 'Rp 4.200.000', totalShu: 'Rp 1.450.000', status: 'Menunggu' },
        { initial: 'SM', name: 'Siti Maryam', principal: 'Rp 8.200.000', participation: 'Rp 2.100.000', totalShu: 'Rp 890.000', status: 'Menunggu' },
        { initial: 'AN', name: 'Agus Nurhadi', principal: 'Rp 15.000.000', participation: 'Rp 6.800.000', totalShu: 'Rp 1.920.000', status: 'Sudah Dibagikan' },
        { initial: 'RP', name: 'Ratna Permata', principal: 'Rp 5.400.000', participation: 'Rp 1.500.000', totalShu: 'Rp 560.000', status: 'Menunggu' },
    ];

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Sisa Hasil Usaha"
                desc="Kelola pembagian SHU anggota secara transparan."
                action={
                    <>
                        <DownloadButton />
                        <button
                            onClick={() => openForm('distribution')}
                            className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                        >
                            <Plus size={16} />
                            <span>Distribusi Baru</span>
                        </button>
                    </>
                }
            />

            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={Landmark} label="Total SHU Tahun Berjalan" value="Rp 145.200.000" note="↗ +12%" tone="blue" />
                <StatCard icon={WalletCards} label="Dana Cadangan" value="Rp 36.300.000" note="25% Alokasi" tone="gold" />
                <StatCard icon={UserRound} label="SHU yang Dibagikan" value="Rp 108.900.000" note="75% Alokasi" tone="green" />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Daftar Pembagian SHU Anggota</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Nama Anggota</th>
                                <th className="px-6 py-3.5">Simpanan (P+W)</th>
                                <th className="px-6 py-3.5">Partisipasi Usaha</th>
                                <th className="px-6 py-3.5">Total SHU</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {rows.map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-primary">
                                                {r.initial}
                                            </div>
                                            <span className="font-semibold text-foreground">{r.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">{r.principal}</td>
                                    <td className="px-6 py-4">{r.participation}</td>
                                    <td className="px-6 py-4 font-bold text-primary">{r.totalShu}</td>
                                    <td className="px-6 py-4">
                                        <span className={cn(
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            r.status === 'Sudah Dibagikan' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'
                                        )}>
                                            ● {r.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary p-1">
                                            <Eye size={16} />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 6. Pendapatan Page ───────────────────────────────────────
function IncomePage() {
    const { openForm } = usePopup();
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Manajemen Pendapatan"
                desc="Kelola dan pantau seluruh arus pendapatan koperasi dari berbagai unit usaha, hasil olahan daur ulang, hingga iuran anggota secara real-time untuk transparansi keuangan."
                action={
                    <>
                        <DownloadButton />
                        <button
                            onClick={() => openForm('income')}
                            className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                        >
                            <Plus size={16} />
                            <span>Input Pendapatan Manual</span>
                        </button>
                    </>
                }
            />

            {/* 3 Stat Cards with top indicator */}
            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={Landmark} label="Total Pendapatan Bulan Ini" value="Rp 45.280.000" note="↗ +12.5%" tone="blue" />
                <StatCard icon={RefreshCcw} label="Unit Pengolahan Sampah" value="Rp 28.150.000" note="↗ +8.2%" tone="green" />
                <StatCard icon={Package} label="Unit Usaha Lain" value="Rp 17.130.000" note="↘ -2.1%" tone="gold" />
            </div>

            {/* Charts Grid */}
            <div className="grid gap-5 lg:grid-cols-3">
                {/* Bar chart */}
                <section className="rounded-2xl border border-border/80 bg-card p-6 shadow-xs lg:col-span-2">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-bold text-foreground">Tren Pendapatan 6 Bulan</h2>
                        <div className="flex items-center gap-2">
                            <span className="rounded-lg bg-secondary px-2.5 py-1 text-xs font-semibold text-primary">Jan - Jun</span>
                            <span className="rounded-lg bg-secondary px-2.5 py-1 text-xs font-semibold text-muted-foreground">2026</span>
                        </div>
                    </div>

                    <div className="mt-8 flex h-48 items-end gap-5 px-4">
                        {[
                            { month: 'Jan', h: 65, active: false },
                            { month: 'Feb', h: 45, active: false },
                            { month: 'Mar', h: 60, active: false },
                            { month: 'Apr', h: 90, active: true },
                            { month: 'Mei', h: 72, active: false },
                            { month: 'Jun', h: 80, active: false },
                        ].map((b, i) => (
                            <div key={i} className="flex flex-1 flex-col items-center gap-2">
                                <div
                                    className={cn('w-full rounded-t-lg transition-all', b.active ? 'bg-primary' : 'bg-slate-200 hover:bg-slate-300')}
                                    style={{ height: `${b.h}%` }}
                                />
                                <span className={cn('text-xs', b.active ? 'font-bold text-primary' : 'text-muted-foreground')}>
                                    {b.month}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Donut chart */}
                <section className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs">
                    <h2 className="text-base font-bold text-foreground">Sumber Pendapatan</h2>
                    <div className="my-auto flex flex-col items-center py-4">
                        <div className="relative flex size-36 items-center justify-center rounded-full border-[18px] border-emerald-600 border-t-primary border-r-primary text-center shadow-inner">
                            <div>
                                <strong className="text-xl font-bold">100%</strong>
                                <span className="block text-[10px] text-muted-foreground uppercase tracking-widest">TOTAL</span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-col gap-2 text-xs">
                        <div className="flex items-center justify-between"><span className="flex items-center gap-2"><span className="size-2.5 rounded-full bg-primary" /> Pengolahan Sampah</span><strong className="font-bold">62%</strong></div>
                        <div className="flex items-center justify-between"><span className="flex items-center gap-2"><span className="size-2.5 rounded-full bg-emerald-600" /> Unit Usaha</span><strong className="font-bold">25%</strong></div>
                        <div className="flex items-center justify-between"><span className="flex items-center gap-2"><span className="size-2.5 rounded-full bg-slate-300" /> Lainnya</span><strong className="font-bold">13%</strong></div>
                    </div>
                </section>
            </div>

            {/* Table */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Daftar Transaksi Pendapatan</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Tanggal</th>
                                <th className="px-6 py-3.5">Sumber</th>
                                <th className="px-6 py-3.5">Keterangan</th>
                                <th className="px-6 py-3.5">Nominal</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { date: '12 Apr 2024', source: 'PENGOLAHAN', desc: 'Penjualan Kompos & Pupuk Organik (200kg)', amount: 'Rp 3.400.000', status: 'Berhasil' },
                                { date: '11 Apr 2024', source: 'UNIT USAHA', desc: 'Sewa Hand-Tractor (3 Hari)', amount: 'Rp 750.000', status: 'Berhasil' },
                                { date: '10 Apr 2024', source: 'IURAN ANGGOTA', desc: 'Simpanan Wajib Kolektif (15 Anggota)', amount: 'Rp 1.500.000', status: 'Diproses' },
                                { date: '09 Apr 2024', source: 'PENGOLAHAN', desc: 'Penjualan Pupuk Cair Organik (50 Ltr)', amount: 'Rp 1.250.000', status: 'Berhasil' },
                                { date: '08 Apr 2024', source: 'HIBAH/LAINNYA', desc: 'Donasi Program Eco-Village', amount: 'Rp 5.000.000', status: 'Berhasil' },
                            ].map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 text-muted-foreground">{r.date}</td>
                                    <td className="px-6 py-4">
                                        <span className="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                            {r.source}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 font-medium text-foreground">{r.desc}</td>
                                    <td className="px-6 py-4 font-bold text-foreground">{r.amount}</td>
                                    <td className="px-6 py-4">
                                        <span className={cn(
                                            'inline-flex items-center gap-1 text-xs font-semibold',
                                            r.status === 'Berhasil' ? 'text-emerald-600' : 'text-primary'
                                        )}>
                                            <CheckCircle2 size={14} /> {r.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary"><Eye size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 7. Pengeluaran Page ──────────────────────────────────────
function ExpensePage() {
    const { openForm } = usePopup();
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Manajemen Pengeluaran"
                desc="Pantau biaya operasional dan pengeluaran unit usaha secara real-time untuk menjaga transparansi keuangan koperasi."
                action={
                    <>
                        <DownloadButton />
                        <button
                            onClick={() => openForm('expense')}
                            className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                        >
                            <Plus size={16} />
                            <span>Input Pengeluaran Baru</span>
                        </button>
                    </>
                }
            />

            {/* 3 Stat Cards with left border indicator matching Berkas */}
            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={WalletCards} label="Total Pengeluaran Bulan Ini" value="Rp 12.450.000" note="↗ 12%" tone="blue" indicator="left" />
                <StatCard icon={UserRound} label="Biaya Operasional" value="Rp 8.200.000" note="Operasional" tone="green" indicator="left" />
                <StatCard icon={Package} label="Pengeluaran Unit Usaha" value="Rp 4.250.000" note="Bisnis" tone="gold" indicator="left" />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Daftar Transaksi Pengeluaran</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Tanggal</th>
                                <th className="px-6 py-3.5">Kategori</th>
                                <th className="px-6 py-3.5">Keterangan</th>
                                <th className="px-6 py-3.5">Nominal</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { date: '12 Okt 2023', cat: 'Logistik', desc: 'Pembelian BBM Truk Sampah AB-1234-XX', amount: 'Rp 450.000', status: 'Lunas' },
                                { date: '11 Okt 2023', cat: 'Gaji', desc: 'Gaji 5 Orang Petugas Kebersihan Lapangan', amount: 'Rp 7.500.000', status: 'Lunas' },
                                { date: '10 Okt 2023', cat: 'Maintenance', desc: 'Servis Rutin Mesin Pencacah Plastik', amount: 'Rp 1.200.000', status: 'Lunas' },
                                { date: '08 Okt 2023', cat: 'Lainnya', desc: 'Konsumsi Rapat Pengurus Koperasi', amount: 'Rp 300.000', status: 'Lunas' },
                            ].map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 text-muted-foreground">{r.date}</td>
                                    <td className="px-6 py-4">
                                        <span className="rounded-md bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            {r.cat}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 font-medium text-foreground">{r.desc}</td>
                                    <td className="px-6 py-4 font-bold text-foreground">{r.amount}</td>
                                    <td className="px-6 py-4">
                                        <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
                                            ● {r.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary"><Eye size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 8. Laporan Hub Sub-page ──────────────────────────────────
function ReportHubPage({ setPage }) {
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Pusat Laporan Koperasi"
                desc="Akses semua data finansial dan operasional koperasi dalam satu tempat. Gunakan filter untuk melihat detail per periode."
            />

            {/* Top 2 Featured Report Cards */}
            <div className="grid gap-5 md:grid-cols-2">
                {/* Laporan Saldo Card */}
                <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs">
                    <div>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex size-11 items-center justify-center rounded-xl bg-primary text-white">
                                    <WalletCards size={22} />
                                </div>
                                <div>
                                    <h2 className="text-base font-bold text-foreground">Laporan Saldo Koperasi</h2>
                                    <p className="text-xs text-muted-foreground">Ringkasan total dana Saldo Koperasi</p>
                                </div>
                            </div>
                            <span className="rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-bold text-emerald-800">
                                Terupdate Hari Ini
                            </span>
                        </div>

                        <div className="mt-6 grid grid-cols-3 gap-3">
                            <div className="rounded-xl border border-border/60 bg-slate-50/70 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground">POKOK</p>
                                <strong className="mt-1 block text-sm font-extrabold text-primary">Rp 125,4M</strong>
                            </div>
                            <div className="rounded-xl border border-border/60 bg-slate-50/70 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground">WAJIB</p>
                                <strong className="mt-1 block text-sm font-extrabold text-emerald-700">Rp 450,8M</strong>
                            </div>
                            <div className="rounded-xl border border-border/60 bg-slate-50/70 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase text-muted-foreground">SUKARELA</p>
                                <strong className="mt-1 block text-sm font-extrabold text-amber-700">Rp 89,2M</strong>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-2 gap-3">
                        <button
                            onClick={() => setPage('laporan-saldo')}
                            className="flex h-10 items-center justify-center gap-1.5 rounded-xl border border-primary text-xs font-semibold text-primary hover:bg-primary/5"
                        >
                            <Eye size={15} /> Lihat Laporan
                        </button>
                        <button
                            onClick={() => window.print()}
                            className="flex h-10 items-center justify-center gap-1.5 rounded-xl border border-border text-xs font-semibold text-foreground hover:bg-secondary"
                        >
                            <Download size={15} /> Cetak PDF
                        </button>
                    </div>
                </div>

                {/* Laba Rugi Card (Dark Blue theme) */}
                <div className="flex flex-col justify-between rounded-2xl bg-[#0b489a] p-6 text-white shadow-md">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="flex size-11 items-center justify-center rounded-xl bg-white/15 text-white">
                                <FileBarChart size={22} />
                            </div>
                            <div>
                                <h2 className="text-base font-bold">Laba Rugi (P&L)</h2>
                                <p className="text-xs text-blue-200">Ikhtisar pendapatan vs pengeluaran bulan ini</p>
                            </div>
                        </div>

                        <div className="mt-6 flex flex-col gap-2.5 text-sm border-t border-white/15 pt-4">
                            <div className="flex justify-between">
                                <span className="text-blue-100">Total Pendapatan</span>
                                <strong className="font-bold">Rp 842,5Jt</strong>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-blue-100">Total Pengeluaran</span>
                                <strong className="font-bold text-rose-300">(Rp 215,3Jt)</strong>
                            </div>
                            <div className="flex justify-between border-t border-white/15 pt-2 text-base">
                                <span className="font-bold text-emerald-300">Laba Bersih</span>
                                <strong className="font-extrabold text-emerald-300">Rp 627,2Jt</strong>
                            </div>
                        </div>
                    </div>

                    <button
                        onClick={() => setPage('laporan-laba-rugi')}
                        className="mt-6 flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-white text-xs font-bold text-primary shadow-sm hover:bg-blue-50"
                    >
                        <BarChart3 size={15} /> Detail Laba Rugi
                    </button>
                </div>
            </div>

            {/* 3 Secondary Report Cards */}
            <div className="grid gap-5 md:grid-cols-3">
                <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs">
                    <div>
                        <div className="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-primary">
                            <UserRound size={20} />
                        </div>
                        <h2 className="mt-4 text-base font-bold">Laporan Keuangan Anggota</h2>
                        <p className="mt-1 text-xs text-muted-foreground leading-relaxed">Cari dan lihat riwayat simpanan, pinjaman, dan SHU untuk anggota.</p>
                    </div>
                    <button onClick={() => setPage('laporan-simpanan-anggota')} className="mt-5 h-10 w-full rounded-xl bg-primary text-xs font-bold text-white">
                        Buka Pencarian
                    </button>
                </div>

                <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs">
                    <div>
                        <div className="flex size-10 items-center justify-center rounded-xl bg-purple-50 text-purple-700">
                            <WalletCards size={20} />
                        </div>
                        <h2 className="mt-4 text-base font-bold">Laporan Simpanan Anggota</h2>
                        <p className="mt-1 text-xs text-muted-foreground leading-relaxed">Total dana simpanan seluruh anggota, buka lebih detail untuk informasi ini.</p>
                    </div>
                    <button onClick={() => setPage('laporan-simpanan-anggota')} className="mt-5 h-10 w-full rounded-xl bg-purple-800 text-xs font-bold text-white">
                        Lihat Pemasukan
                    </button>
                </div>

                <div className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs">
                    <div>
                        <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <TrendingUp size={20} />
                        </div>
                        <h2 className="mt-4 text-base font-bold">Laporan Pemasukan</h2>
                        <p className="mt-1 text-xs text-muted-foreground leading-relaxed">Detil sumber pendapatan koperasi: Bunga pinjaman, iuran anggota, dan unit usaha lainnya.</p>
                    </div>
                    <button onClick={() => setPage('laporan-pemasukan')} className="mt-5 h-10 w-full rounded-xl bg-emerald-800 text-xs font-bold text-white">
                        Lihat Pemasukan
                    </button>
                </div>
            </div>

            {/* Table: Aktivitas Laporan Terbaru */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Aktivitas Laporan Terbaru</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Nama Laporan</th>
                                <th className="px-6 py-3.5">Periode</th>
                                <th className="px-6 py-3.5">Dibuat Oleh</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { name: 'P&L Bulan Januari 2024', period: '01 Jan - 31 Jan 2024', author: 'Admin Siti', status: 'Finalized' },
                                { name: 'Rekap Simpanan Pokok S4', period: 'Oct - Dec 2023', author: 'System Auto', status: 'Finalized' },
                                { name: 'Audit Pengeluaran CSR', period: '15 Feb 2024', author: 'Admin Bambang', status: 'Review' },
                            ].map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 font-semibold text-foreground">{r.name}</td>
                                    <td className="px-6 py-4 text-muted-foreground">{r.period}</td>
                                    <td className="px-6 py-4 text-foreground">{r.author}</td>
                                    <td className="px-6 py-4">
                                        <span className={cn(
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            r.status === 'Finalized' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'
                                        )}>
                                            {r.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-xs font-bold text-primary hover:underline">
                                            {r.status === 'Finalized' ? 'Download' : 'View'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    );
}

// ─── 8.1 Detail: Laporan Saldo Koperasi ────────────────────────
function ReportSaldoDetail({ setPage }) {
    return (
        <div className="flex flex-col gap-6">
            <div>
                <button
                    onClick={() => setPage('laporan')}
                    className="group mb-3 inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
                >
                    <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                    <span>Kembali ke Pusat Laporan</span>
                </button>
                <PageHeader
                    title="Laporan Saldo Koperasi"
                    desc="Rincian akumulasi saldo simpanan pokok, wajib, dan sukarela dari seluruh anggota koperasi."
                    action={
                        <div className="flex gap-2">
                            <DownloadButton label="Unduh PDF" />
                            <button
                                onClick={() => window.print()}
                                className="flex h-10 items-center gap-1.5 rounded-xl border border-border bg-card px-4 text-xs font-semibold text-foreground hover:bg-secondary"
                            >
                                Cetak
                            </button>
                        </div>
                    }
                />
            </div>

            {/* Total Balance Card */}
            <div className="rounded-2xl border border-blue-200 bg-gradient-to-br from-[#eaf1fd] to-[#f4f8ff] p-6 shadow-xs">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <p className="text-xs uppercase font-bold tracking-wider text-muted-foreground">TOTAL SALDO AKUMULASI KOPERASI</p>
                        <strong className="mt-1 block text-3xl font-black text-primary md:text-4xl">Rp 665.400.000</strong>
                    </div>
                    <span className="self-start sm:self-center rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800">
                        Status Finansial Sehat
                    </span>
                </div>

                <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border border-border/80 bg-card p-4 text-center shadow-xs">
                        <p className="text-xs font-bold uppercase text-muted-foreground">SIMPANAN POKOK</p>
                        <strong className="mt-1 block text-xl font-black text-primary">Rp 125.400.000</strong>
                        <span className="mt-1 block text-[11px] text-muted-foreground">1,284 Anggota</span>
                    </div>
                    <div className="rounded-xl border border-border/80 bg-card p-4 text-center shadow-xs">
                        <p className="text-xs font-bold uppercase text-muted-foreground">SIMPANAN WAJIB</p>
                        <strong className="mt-1 block text-xl font-black text-emerald-700">Rp 450.800.000</strong>
                        <span className="mt-1 block text-[11px] text-muted-foreground">Dari Konversi Sampah</span>
                    </div>
                    <div className="rounded-xl border border-border/80 bg-card p-4 text-center shadow-xs">
                        <p className="text-xs font-bold uppercase text-muted-foreground">SIMPANAN SUKARELA</p>
                        <strong className="mt-1 block text-xl font-black text-amber-700">Rp 89.200.000</strong>
                        <span className="mt-1 block text-[11px] text-muted-foreground">Tabungan Mandiri Anggota</span>
                    </div>
                </div>
            </div>

            {/* Table */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Rincian Saldo Simpanan Per Kategori</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Kategori Simpanan</th>
                                <th className="px-6 py-3.5">Jumlah Penyetor</th>
                                <th className="px-6 py-3.5">Akumulasi Saldo</th>
                                <th className="px-6 py-3.5">Pertumbuhan (MoM)</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { cat: 'Simpanan Pokok Anggota', count: '1,284 Anggota', amount: 'Rp 125.400.000', grow: '+4.2%' },
                                { cat: 'Simpanan Wajib (Bank Sampah)', count: '1,120 Anggota Aktif', amount: 'Rp 450.800.000', grow: '+12.8%' },
                                { cat: 'Simpanan Sukarela & Investasi', count: '450 Anggota', amount: 'Rp 89.200.000', grow: '+8.5%' },
                            ].map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 font-semibold text-foreground">{r.cat}</td>
                                    <td className="px-6 py-4 text-muted-foreground">{r.count}</td>
                                    <td className="px-6 py-4 font-bold text-primary">{r.amount}</td>
                                    <td className="px-6 py-4 font-semibold text-emerald-700">{r.grow}</td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary"><Eye size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 8.2 Detail: Laporan Laba Rugi (P&L) ───────────────────────
function ReportProfitLossDetail({ setPage }) {
    const income = [
        { label: 'Penjualan Kompos & Pupuk Organik', note: 'Hasil olahan sampah organik', val: 'Rp 78.500.000' },
        { label: 'Penjualan Plastik & Logam Daur Ulang', note: 'Penjualan ke pabrik daur ulang', val: 'Rp 42.320.000' },
        { label: 'Jasa Angkut Sampah Khusus', note: 'Sewa armada & pemilahan skala RT/RW', val: 'Rp 25.000.000' },
    ];
    const costs = [
        { label: 'Gaji & Honorarium Petugas', note: 'Gaji 5 staf & 4 petugas lapangan', val: '(Rp 45.000.000)' },
        { label: 'Pemeliharaan Mesin & Truk', note: 'Perawatan mesin pencacah & armada', val: '(Rp 12.450.000)' },
        { label: 'Operasional & BBM', note: 'BBM armada penjemputan sampah', val: '(Rp 18.900.000)' },
    ];

    return (
        <div className="flex flex-col gap-6">
            <div>
                <button
                    onClick={() => setPage('laporan')}
                    className="group mb-3 inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
                >
                    <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                    <span>Kembali ke Pusat Laporan</span>
                </button>
                <PageHeader
                    title="Laporan Laba Rugi (P&L)"
                    desc="Ikhtisar komprehensif pendapatan operasional vs beban pengeluaran berjalan."
                    action={
                        <div className="flex gap-2">
                            <DownloadButton label="Unduh PDF" />
                            <button
                                onClick={() => window.print()}
                                className="flex h-10 items-center gap-1.5 rounded-xl border border-border bg-card px-4 text-xs font-semibold text-foreground hover:bg-secondary"
                            >
                                Cetak
                            </button>
                        </div>
                    }
                />
            </div>

            {/* P&L 3 Summary Stat Cards */}
            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={TrendingUp} label="Total Pendapatan" value="Rp 842.500.000" note="↑ +12% dari periode lalu" tone="green" />
                <StatCard icon={TrendingDown} label="Total Beban Operasional" value="Rp 215.300.000" note="↓ Efisiensi 5.2%" tone="red" />
                <div className="flex flex-col justify-between rounded-2xl bg-[#0b489a] p-5 text-white shadow-md">
                    <p className="text-xs uppercase font-bold tracking-wider text-blue-200">LABA BERSIH (SHU)</p>
                    <strong className="mt-2 block text-3xl font-black text-emerald-300 md:text-4xl">Rp 627.200.000</strong>
                    <p className="mt-3 text-xs font-semibold text-emerald-300">Margin Bersih 74.4% - Sangat Baik</p>
                </div>
            </div>

            {/* Table */}
            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Detail Rincian Laporan Laba Rugi</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Kategori Akun</th>
                                <th className="px-6 py-3.5">Keterangan</th>
                                <th className="px-6 py-3.5 text-right">Nilai (IDR)</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            <tr className="bg-emerald-50/70">
                                <td colSpan={3} className="px-6 py-3 text-xs font-black uppercase tracking-wider text-emerald-900">
                                    A. Pendapatan Operasional
                                </td>
                            </tr>
                            {income.map((item, i) => (
                                <tr key={`inc-${i}`} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 font-semibold text-foreground">{item.label}</td>
                                    <td className="px-6 py-4 text-muted-foreground">{item.note}</td>
                                    <td className="px-6 py-4 text-right font-bold text-emerald-700">{item.val}</td>
                                </tr>
                            ))}

                            <tr className="bg-rose-50/70">
                                <td colSpan={3} className="px-6 py-3 text-xs font-black uppercase tracking-wider text-rose-900">
                                    B. Beban & Biaya Operasional
                                </td>
                            </tr>
                            {costs.map((item, i) => (
                                <tr key={`cost-${i}`} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 font-semibold text-foreground">{item.label}</td>
                                    <td className="px-6 py-4 text-muted-foreground">{item.note}</td>
                                    <td className="px-6 py-4 text-right font-bold text-rose-600">{item.val}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="border-t-2 border-primary bg-[#eef3fc] font-bold">
                            <tr>
                                <td colSpan={2} className="px-6 py-4 text-base font-black text-foreground">
                                    LABA BERSIH PERIODE BERJALAN
                                </td>
                                <td className="px-6 py-4 text-right text-xl font-black text-emerald-700">
                                    Rp 627.200.000
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    );
}

// ─── 8.3 Detail: Laporan Simpanan Anggota ──────────────────────
function ReportMemberSavingsDetail({ setPage }) {
    const [q, setQ] = useState('');
    const members = [
        { id: 'ANG-001', name: 'Bambang Susanto', pokok: 'Rp 50.000', wajib: 'Rp 1.250.000', sukarela: 'Rp 5.500.000', total: 'Rp 6.800.000', status: 'Aktif' },
        { id: 'ANG-002', name: 'Siti Aminah', pokok: 'Rp 50.000', wajib: 'Rp 950.000', sukarela: 'Rp 2.200.000', total: 'Rp 3.200.000', status: 'Aktif' },
        { id: 'ANG-003', name: 'Ahmad Hidayat', pokok: 'Rp 50.000', wajib: 'Rp 1.800.000', sukarela: 'Rp 8.000.000', total: 'Rp 9.850.000', status: 'Aktif' },
        { id: 'ANG-004', name: 'Ratna Permata', pokok: 'Rp 50.000', wajib: 'Rp 640.000', sukarela: 'Rp 500.000', total: 'Rp 1.190.000', status: 'Aktif' },
        { id: 'ANG-005', name: 'Suryono', pokok: 'Rp 50.000', wajib: 'Rp 820.000', sukarela: 'Rp 1.200.000', total: 'Rp 2.070.000', status: 'Aktif' },
    ].filter(m => m.name.toLowerCase().includes(q.toLowerCase()) || m.id.toLowerCase().includes(q.toLowerCase()));

    return (
        <div className="flex flex-col gap-6">
            <div>
                <button
                    onClick={() => setPage('laporan')}
                    className="group mb-3 inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
                >
                    <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                    <span>Kembali ke Pusat Laporan</span>
                </button>
                <PageHeader
                    title="Laporan Simpanan Seluruh Anggota"
                    desc="Pencarian dan rekap data simpanan per masing-masing anggota koperasi."
                    action={<DownloadButton label="Unduh Rekap Anggota" />}
                />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Daftar Rekap Simpanan Anggota</h2>
                    <div className="relative w-full sm:w-72">
                        <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <input
                            value={q}
                            onChange={e => setQ(e.target.value)}
                            placeholder="Cari ID atau nama anggota..."
                            className="h-9 w-full rounded-xl border border-border bg-slate-50/50 pl-9 pr-3 text-xs focus:bg-white"
                        />
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[750px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">ID & Nama</th>
                                <th className="px-6 py-3.5">Simp. Pokok</th>
                                <th className="px-6 py-3.5">Simp. Wajib</th>
                                <th className="px-6 py-3.5">Simp. Sukarela</th>
                                <th className="px-6 py-3.5">Total Simpanan</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {members.map((m, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4">
                                        <span className="block font-mono text-[11px] font-bold text-muted-foreground">{m.id}</span>
                                        <span className="font-semibold text-foreground">{m.name}</span>
                                    </td>
                                    <td className="px-6 py-4 text-muted-foreground">{m.pokok}</td>
                                    <td className="px-6 py-4 text-emerald-700 font-medium">{m.wajib}</td>
                                    <td className="px-6 py-4 text-amber-700 font-medium">{m.sukarela}</td>
                                    <td className="px-6 py-4 font-bold text-primary">{m.total}</td>
                                    <td className="px-6 py-4">
                                        <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            {m.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary"><Eye size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── 8.4 Detail: Laporan Pemasukan ────────────────────────────
function ReportIncomeDetail({ setPage }) {
    return (
        <div className="flex flex-col gap-6">
            <div>
                <button
                    onClick={() => setPage('laporan')}
                    className="group mb-3 inline-flex items-center gap-2 rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
                >
                    <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                    <span>Kembali ke Pusat Laporan</span>
                </button>
                <PageHeader
                    title="Laporan Pemasukan Koperasi"
                    desc="Detail arus kas masuk dari penjualan produk olahan, jasa penjemputan sampah, dan hibah."
                    action={<DownloadButton label="Unduh Laporan Pemasukan" />}
                />
            </div>

            <div className="grid gap-5 md:grid-cols-3">
                <StatCard icon={Landmark} label="Total Pemasukan Berjalan" value="Rp 842.500.000" note="↗ +15% YoY" tone="green" />
                <StatCard icon={TrendingUp} label="Rata-rata Pemasukan / Bulan" value="Rp 70.200.000" note="✓ Konsisten" tone="blue" />
                <StatCard icon={Package} label="Sumber Terbesar" value="Kompos Organik" note="48% Total Pendapatan" tone="gold" />
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Histori Arus Kas Masuk</h2>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[700px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Tanggal</th>
                                <th className="px-6 py-3.5">Kategori Sumber</th>
                                <th className="px-6 py-3.5">Deskripsi</th>
                                <th className="px-6 py-3.5">Nominal Masuk</th>
                                <th className="px-6 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {[
                                { date: '15 Okt 2023', cat: 'Pengolahan Kompos', desc: 'Penjualan Kompos 1.2 Ton ke Mitra Tani', amount: 'Rp 6.000.000' },
                                { date: '14 Okt 2023', cat: 'Penjualan Plastik', desc: 'Pengepakan 800kg Botol PET Daur Ulang', amount: 'Rp 4.800.000' },
                                { date: '12 Okt 2023', cat: 'Iuran Kolektif', desc: 'Iuran Pokok & Wajib 20 Anggota Baru', amount: 'Rp 2.000.000' },
                                { date: '10 Okt 2023', cat: 'Program CSR', desc: 'Dana Hibah Bina Lingkungan Hijau', amount: 'Rp 15.000.000' },
                            ].map((r, i) => (
                                <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4 text-muted-foreground">{r.date}</td>
                                    <td className="px-6 py-4">
                                        <span className="rounded-md bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            {r.cat}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 font-medium text-foreground">{r.desc}</td>
                                    <td className="px-6 py-4 font-bold text-emerald-700">{r.amount}</td>
                                    <td className="px-6 py-4 text-right">
                                        <button className="text-muted-foreground hover:text-primary"><Eye size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <Pager total={3} />
            </section>
        </div>
    );
}

// ─── Router Component ─────────────────────────────────────────
function ManagerPages({ page, setPage }) {
    switch (page) {
        case 'dashboard':
            return <DashboardPage setPage={setPage} />;
        case 'simpanan-pokok':
            return <SavingsPokokPage />;
        case 'simpanan-wajib':
            return <SavingsWajibPage />;
        case 'simpanan-sukarela':
            return <SavingsSukarelaPage />;
        case 'shu':
            return <ShuPage />;
        case 'pendapatan':
            return <IncomePage />;
        case 'pengeluaran':
            return <ExpensePage />;
        case 'laporan':
            return <ReportHubPage setPage={setPage} />;
        case 'laporan-saldo':
            return <ReportSaldoDetail setPage={setPage} />;
        case 'laporan-laba-rugi':
            return <ReportProfitLossDetail setPage={setPage} />;
        case 'laporan-simpanan-anggota':
            return <ReportMemberSavingsDetail setPage={setPage} />;
        case 'laporan-pemasukan':
            return <ReportIncomeDetail setPage={setPage} />;
        default:
            return <DashboardPage setPage={setPage} />;
    }
}

const pageTitles = {
    'dashboard': 'Dashboard Pengurus',
    'simpanan-pokok': 'Simpanan Pokok',
    'simpanan-wajib': 'Simpanan Wajib',
    'simpanan-sukarela': 'Simpanan Sukarela',
    'shu': 'Sisa Hasil Usaha (SHU)',
    'pendapatan': 'Manajemen Pendapatan',
    'pengeluaran': 'Manajemen Pengeluaran',
    'laporan': 'Pusat Laporan',
    'laporan-saldo': 'Laporan Saldo Koperasi',
    'laporan-laba-rugi': 'Laporan Laba Rugi (P&L)',
    'laporan-simpanan-anggota': 'Laporan Simpanan Anggota',
    'laporan-pemasukan': 'Laporan Pemasukan',
};

// ─── Page Entry ───────────────────────────────────────────────
export default function PengurusIndex() {
    const [page, setPage] = useState('dashboard');
    const [form, setForm] = useState(null);
    const [status, setStatus] = useState('');

    return (
        <PopupCtx.Provider value={{ openForm: setForm, showStatus: setStatus }}>
            <Head title={pageTitles[page] || 'Dashboard Pengurus'} />
            <ManagerShell currentPage={page} setPage={setPage}>
                <ManagerPages page={page} setPage={setPage} />
            </ManagerShell>
            <FormPopup kind={form} onClose={() => setForm(null)} onSuccess={setStatus} />
            <StatusPopup open={Boolean(status)} title={status} onClose={() => setStatus('')} />
        </PopupCtx.Provider>
    );
}

