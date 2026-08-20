import { useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Banknote, CalendarDays, ChevronLeft, CirclePlus,
    Download, Eye, Landmark, Leaf, Plus, Search,
    TrendingDown, TrendingUp, UserRound, WalletCards
} from 'lucide-react';
import { MemberShell } from '@/Components/Koperasi/MemberShell';
import {
    DataPanel, DownloadButton, EyeAction, FilterButton,
    MetricCard, PageTitle, Status, Trend
} from '@/Components/Koperasi/MemberUI';
import { FormPopup, StatusPopup } from '@/Components/Koperasi/Popups';

const cn = (...cls) => cls.filter(Boolean).join(' ');

// ─── Summary Card Component ───────────────────────────────────
function SummaryCard({ title, amount }) {
    return (
        <section className="flex flex-col justify-between gap-4 rounded-2xl border border-blue-200 bg-[#eaf1fd] p-5 sm:flex-row sm:items-center">
            <div className="flex items-center gap-3.5">
                <div className="flex size-12 items-center justify-center rounded-xl bg-primary text-white shadow-sm">
                    <UserRound size={22} />
                </div>
                <div>
                    <h3 className="text-base font-bold text-foreground">{title}</h3>
                    <p className="text-xs text-muted-foreground">Tabungan simpanan Anda.</p>
                </div>
            </div>
            <div className="flex flex-col sm:items-end">
                <strong className="text-2xl font-extrabold text-primary">{amount}</strong>
                <span className="mt-1 inline-block rounded-full bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                    STATUS: TERVERIFIKASI
                </span>
            </div>
        </section>
    );
}

// ─── 1. Dashboard Sub-page ────────────────────────────────────
function DashboardPage() {
    const activity = [
        { date: '15 Mar 2024', desc: 'Simpanan Wajib Maret', cat: 'Wajib', amount: '+ 500.000', positive: true },
        { date: '02 Mar 2024', desc: 'Pembagian SHU Tahunan', cat: 'Dividen', amount: '+ 2.340.000', positive: true },
        { date: '18 Feb 2024', desc: 'Penarikan Simpanan Sukarela', cat: 'Penarikan', amount: '- 1.000.000', positive: false },
        { date: '12 Feb 2024', desc: 'Setoran Sampah Berkah', cat: 'Sukarela', amount: '+ 75.000', positive: true },
        { date: '15 Jan 2024', desc: 'Simpanan Wajib Januari', cat: 'Wajib', amount: '+ 500.000', positive: true },
        { date: '05 Jan 2024', desc: 'Simpanan Pokok (Registrasi)', cat: 'Pokok', amount: '+ 5.000.000', positive: true },
    ];

    return (
        <div className="flex flex-col gap-6">
            {/* Member Profile Card (Target Sampah Removed) */}
            <section className="flex flex-col justify-between gap-5 rounded-2xl border border-border/80 bg-card p-6 shadow-xs sm:flex-row sm:items-center">
                <div className="flex items-center gap-4">
                    <div className="flex size-20 shrink-0 items-center justify-center rounded-2xl bg-accent text-primary border border-blue-200">
                        <UserRound size={40} />
                    </div>
                    <div>
                        <h2 className="text-xl font-bold text-foreground md:text-2xl">Ibu Siti Rahayu</h2>
                        <p className="mt-1 text-xs text-muted-foreground">ID: <span className="font-mono font-semibold text-foreground">KOP-2023-0842</span></p>
                        <p className="text-xs text-muted-foreground">Dusun Makmur RT 04/RW 02</p>
                    </div>
                </div>
                <div className="self-start sm:self-center">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800">
                        <span className="size-2 rounded-full bg-emerald-600" /> Anggota Aktif
                    </span>
                </div>
            </section>

            {/* 3 Metric Cards */}
            <div className="grid gap-5 md:grid-cols-3">
                <MetricCard icon={<WalletCards size={20} />} label="Simpanan Masuk Hari Ini" value="Rp 12.450.000" note="↗ 12% dari kemarin" />
                <MetricCard icon={<TrendingUp size={20} />} label="Total Pendapatan (Bulan Ini)" value="Rp 84.320.000" note="✓ Sesuai Target" tone="green" />
                <MetricCard icon={<TrendingDown size={20} />} label="Total Pengeluaran" value="Rp 28.150.000" note="⚠ 5 Transaksi Pending" tone="red" />
            </div>

            {/* Riwayat Aktivitas Keuangan */}
            <DataPanel
                title="Riwayat Aktivitas Keuangan"
                toolbar={
                    <div className="flex items-center gap-2">
                        <select className="h-9 rounded-xl border border-border bg-slate-50/50 px-3 text-xs font-medium text-foreground focus:bg-white">
                            <option>Tahun 2024</option>
                            <option>Tahun 2023</option>
                        </select>
                        <FilterButton />
                    </div>
                }
                headers={['Tanggal', 'Deskripsi Transaksi', 'Kategori', 'Jumlah (IDR)']}
                rows={activity.map((r, i) => [
                    <span key="date" className="text-muted-foreground">{r.date}</span>,
                    <strong key="desc" className="font-semibold text-foreground">{r.desc}</strong>,
                    <span
                        key="cat"
                        className={cn(
                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            r.cat === 'Wajib' ? 'bg-amber-100 text-amber-800' :
                            r.cat === 'Dividen' ? 'bg-emerald-100 text-emerald-800' :
                            r.cat === 'Penarikan' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-primary'
                        )}
                    >
                        {r.cat}
                    </span>,
                    <strong key="amt" className={cn('font-bold', r.positive ? 'text-emerald-700' : 'text-rose-600')}>
                        {r.amount}
                    </strong>
                ])}
                footer="Menampilkan 6 dari 24 transaksi"
            />
        </div>
    );
}

// ─── 2. Simpanan Pokok Sub-page ───────────────────────────────
function PokokPage() {
    const [q, setQ] = useState('');
    const rows = [
        ['BW', 'Bambang Wijaya', '12 Okt 2023'],
        ['SA', 'Siti Aminah', '11 Okt 2023'],
        ['AH', 'Ahmad Hidayat', '10 Okt 2023'],
        ['LS', 'Lilik Suradi', '09 Okt 2023'],
        ['SY', 'Suryono', '08 Okt 2023'],
    ].filter(r => r[1].toLowerCase().includes(q.toLowerCase()));

    return (
        <div className="flex flex-col gap-6">
            <PageTitle title="Simpanan Pokok" description="Kelola dana keanggotaan awal untuk kestabilan koperasi kita." />
            <SummaryCard title="Simpanan Pokok Pribadi" amount="Rp 50.000" />
            <DataPanel
                title="Daftar Setoran Anggota"
                toolbar={
                    <div className="relative w-full sm:w-64">
                        <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <input
                            value={q}
                            onChange={e => setQ(e.target.value)}
                            placeholder="Cari nama anggota..."
                            className="h-9 w-full rounded-xl border border-border bg-slate-50/50 pl-9 pr-3 text-xs focus:bg-white"
                        />
                    </div>
                }
                headers={['Nama Anggota', 'Tanggal Setoran', 'Jumlah', 'Status', 'Aksi']}
                rows={rows.map((r, i) => [
                    <div key="name" className="flex items-center gap-2.5">
                        <span className="flex size-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-primary">{r[0]}</span>
                        <span className="font-semibold text-foreground">{r[1]}</span>
                    </div>,
                    <span key="date" className="text-muted-foreground">{r[2]}</span>,
                    <strong key="amt" className="font-bold text-primary">Rp 50.000</strong>,
                    <Status key="status">Berhasil</Status>,
                    <EyeAction key="act" />
                ])}
                footer="Menampilkan 5 dari 491 data"
            />
        </div>
    );
}

// ─── 3. Simpanan Wajib Sub-page ───────────────────────────────
function WajibPage({ openForm }) {
    const rows = [
        { weight: '24.5 kg', val: 'Rp 122.500', date: '12 Okt 2023', status: 'Terverifikasi' },
        { weight: '18.0 kg', val: 'Rp 90.000', date: '11 Okt 2023', status: 'Terverifikasi' },
        { weight: '32.1 kg', val: 'Rp 160.500', date: '10 Okt 2023', status: 'Terverifikasi' },
        { weight: '15.4 kg', val: 'Rp 77.000', date: '08 Okt 2023', status: 'Terverifikasi' },
    ];

    return (
        <div className="flex flex-col gap-6">
            <PageTitle
                title="Simpanan Wajib"
                description="Setoran sampah tiap bulan yang diakumulasi untuk modal bersama koperasi."
                action={
                    <button
                        onClick={() => openForm('waste')}
                        className="flex h-10 items-center gap-2 rounded-xl bg-primary px-4 text-xs font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                    >
                        <CirclePlus size={16} />
                        <span>Setor Sampah</span>
                    </button>
                }
            />

            <div className="grid gap-5 md:grid-cols-3">
                <MetricCard icon={<Leaf size={20} />} label="Total Berat Sampah Disetor" value="1240,5 kg" note="↗ +12% Bulan Ini" />
                <MetricCard icon={<Banknote size={20} />} label="Total Konversi Rupiah" value="Rp 15.420.000" note="✓ Stabil" tone="green" />
                <article className="flex flex-col justify-between rounded-2xl bg-gradient-to-br from-[#ffeed4] to-[#fedfa9] p-5 text-[#765000] border border-amber-200">
                    <span className="self-end rounded-full bg-amber-600 px-2 py-0.5 text-[10px] font-bold text-white uppercase">MVP</span>
                    <div className="mt-4">
                        <p className="text-xs uppercase tracking-wider text-amber-900/70">Status Anggota:</p>
                        <strong className="text-2xl font-black text-amber-950">MASTER</strong>
                        <p className="mt-0.5 text-xs font-semibold text-amber-900">85 kg / bulan</p>
                    </div>
                </article>
            </div>

            <DataPanel
                title="Data Setoran Sampah"
                toolbar={<button className="rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-semibold text-primary hover:bg-secondary">Filter Tanggal</button>}
                headers={['Berat (KG)', 'Nilai Konversi (RP)', 'Tanggal Setor', 'Status', 'Aksi']}
                rows={rows.map((r, i) => [
                    <strong key="w" className="text-base font-bold text-emerald-700">{r.weight}</strong>,
                    <span key="v" className="font-semibold">{r.val}</span>,
                    <span key="d" className="text-muted-foreground">{r.date}</span>,
                    <Status key="s">{r.status}</Status>,
                    <EyeAction key="a" />
                ])}
                footer="Menampilkan 1-4 dari 150 data"
            />
        </div>
    );
}

// ─── 4. Simpanan Sukarela Sub-page ────────────────────────────
function SukarelaPage({ openForm }) {
    const rows = [
        ['Hari Ini, 10:45 WIB', 'Rp 5.500.000', 'Setoran rutin bulanan'],
        ['Hari Ini, 09:12 WIB', 'Rp 12.000.000', 'Bonus penjualan limbah'],
        ['Kemarin, 16:30 WIB', 'Rp 2.250.000', '-'],
        ['Kemarin, 14:15 WIB', 'Rp 750.000', 'Simpanan tambahan'],
    ];

    return (
        <div className="flex flex-col gap-6">
            <PageTitle
                title="Simpanan Sukarela"
                description="Kelola dana simpanan sukarela anggota dengan transparansi penuh."
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

            <SummaryCard title="Simpanan Sukarela Pribadi" amount="Rp 1.550.000" />

            <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border/80 bg-card p-4">
                <div className="flex items-center gap-2">
                    <CalendarDays size={16} className="text-muted-foreground" />
                    <input type="date" className="h-9 rounded-xl border border-border bg-slate-50/50 px-3 text-xs font-medium text-foreground" />
                </div>
                <div className="flex items-center gap-2">
                    <FilterButton />
                    <DownloadButton />
                </div>
            </div>

            <DataPanel
                title="Riwayat Simpanan"
                headers={['Waktu Transaksi', 'Nominal', 'Catatan', 'Aksi']}
                rows={rows.map((r, i) => [
                    <span key="t" className="text-muted-foreground">{r[0]}</span>,
                    <strong key="n" className="font-bold text-emerald-700">{r[1]}</strong>,
                    <span key="c" className="text-foreground">{r[2]}</span>,
                    <EyeAction key="a" />
                ])}
                footer="Menampilkan 1-4 dari 280 transaksi"
            />
        </div>
    );
}

// ─── 5. Laporan Hub & Sub-pages ───────────────────────────────
function ReportsPage({ setPage }) {
    const reportCards = [
        { title: 'Laporan SHU', desc: 'Cari dan lihat riwayat simpanan, pinjaman, dan SHU untuk anggota.', icon: UserRound, key: 'laporan-shu', bg: 'bg-blue-50 text-primary' },
        { title: 'Laporan Saldo', desc: 'Total dana simpanan seluruh anggota koperasi.', icon: WalletCards, key: 'laporan-saldo', bg: 'bg-purple-50 text-purple-700' },
        { title: 'Laporan Laba Rugi', desc: 'Bunga pinjaman, iuran anggota, dan unit usaha lainnya.', icon: TrendingUp, key: 'laporan-laba-rugi', bg: 'bg-emerald-50 text-emerald-700' },
    ];

    return (
        <div className="flex flex-col gap-6">
            <PageTitle title="Pusat Laporan" description="Akses semua data finansial dan operasional pada akun Anda." />
            <div className="grid gap-6 md:grid-cols-3">
                {reportCards.map((c, i) => {
                    const Icon = c.icon;
                    return (
                        <article key={i} className="flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-6 shadow-xs transition-all hover:shadow-md">
                            <div>
                                <div className={cn('flex size-12 items-center justify-center rounded-xl', c.bg)}>
                                    <Icon size={22} />
                                </div>
                                <h3 className="mt-4 text-lg font-bold text-foreground">{c.title}</h3>
                                <p className="mt-1.5 text-xs text-muted-foreground leading-relaxed">{c.desc}</p>
                            </div>
                            <button
                                onClick={() => setPage(c.key)}
                                className="mt-5 flex h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-primary text-xs font-semibold text-white shadow-xs hover:bg-primary/90"
                            >
                                Buka Laporan →
                            </button>
                        </article>
                    );
                })}
            </div>
        </div>
    );
}

function ShuReportPage({ setPage }) {
    const withdrawal = [
        ['11/05/2026', 'Rp 12.500.000', 'Rp 4.200.000', 'Rp 1.450.000', 'Menunggu'],
        ['11/05/2026', 'Rp 8.200.000', 'Rp 2.100.000', 'Rp 890.000', 'Menunggu'],
        ['11/05/2026', 'Rp 15.000.000', 'Rp 6.800.000', 'Rp 1.920.000', 'Sudah Dibagikan'],
    ];

    return (
        <div className="flex flex-col gap-6">
            <button
                onClick={() => setPage('laporan')}
                className="group inline-flex items-center gap-2 self-start rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
            >
                <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                <span>Kembali ke Pusat Laporan</span>
            </button>
            <section className="flex flex-col justify-between gap-4 rounded-2xl border border-border/80 bg-card p-6 shadow-xs sm:flex-row sm:items-center">
                <div>
                    <Landmark className="text-primary" size={22} />
                    <h2 className="mt-2 text-xl font-bold">Total SHU Anda</h2>
                    <p className="text-xs text-muted-foreground">Perhitungan per 31 Desember 2023</p>
                </div>
                <div className="sm:text-right">
                    <Trend>+12%</Trend>
                    <strong className="mt-1 block text-3xl font-extrabold text-primary">Rp 145.200.000</strong>
                </div>
            </section>
            <DataPanel
                title="Penarikan SHU Anggota"
                toolbar={<DownloadButton />}
                headers={['Tanggal', 'Simpanan (P+W)', 'Partisipasi Usaha', 'Total SHU', 'Status', 'Aksi']}
                rows={withdrawal.map((r, i) => [
                    <strong key="d">#{i+1} {r[0]}</strong>,
                    r[1],
                    r[2],
                    <strong key="shu" className="font-bold text-primary">{r[3]}</strong>,
                    <Status key="s" kind={r[4].startsWith('Menunggu') ? 'waiting' : 'success'}>{r[4]}</Status>,
                    <EyeAction key="a" />
                ])}
                footer="Menampilkan 3 dari 450 anggota"
            />
        </div>
    );
}

function SaldoReportPage({ setPage }) {
    return (
        <div className="flex flex-col gap-6">
            <button
                onClick={() => setPage('laporan')}
                className="group inline-flex items-center gap-2 self-start rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
            >
                <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                <span>Kembali ke Pusat Laporan</span>
            </button>
            <div className="grid gap-5 md:grid-cols-2">
                <MetricCard icon={<Leaf size={20} />} label="Total sampah yang Anda kumpulkan" value="154 Kg" note="Per 31 Desember 2023" tone="green" />
                <MetricCard icon={<Landmark size={20} />} label="Total saldo dari kumpulan sampah Anda" value="Rp 145.200.000" note="Per 31 Desember 2023" />
            </div>
            <DataPanel
                title="Penarikan Saldo Anggota"
                toolbar={<DownloadButton />}
                headers={['Tanggal', 'Simpanan (P+W)', 'Partisipasi Usaha', 'Total SHU', 'Status', 'Aksi']}
                rows={[
                    ['11/05/2026', 'Rp 12.500.000', 'Rp 4.200.000', 'Rp 1.450.000', 'Sudah Dibagikan'],
                    ['11/05/2026', 'Rp 8.200.000', 'Rp 2.100.000', 'Rp 890.000', 'Sudah Dibagikan'],
                    ['11/05/2026', 'Rp 15.000.000', 'Rp 6.800.000', 'Rp 1.920.000', 'Sudah Dibagikan'],
                ].map((r, i) => [
                    <strong key="d">#{i+1} {r[0]}</strong>,
                    r[1],
                    r[2],
                    <strong key="shu" className="font-bold text-primary">{r[3]}</strong>,
                    <Status key="s" kind="success">{r[4]}</Status>,
                    <EyeAction key="a" />
                ])}
                footer="Menampilkan 3 data"
            />
        </div>
    );
}

function ProfitPage({ setPage }) {
    const income = [['Penjualan Unit Usaha', 'Hasil penjualan sembako & kerajinan', '78.500.000'], ['Toko Fisik', 'Penjualan retail gerai koperasi', '42.320.000'], ['Iuran Anggota', 'Akumulasi iuran bulanan 450 anggota', '25.000.000']];
    const costs = [['Gaji & Honorarium', 'Gaji 5 staf & 2 kurir operasional', '(45.000.000)'], ['Pemeliharaan & Alat', 'Perawatan gedung & servis kendaraan', '(12.450.000)'], ['Logistik & Distribusi', 'Bensin, packing, dan biaya kirim', '(18.900.000)']];

    return (
        <div className="flex flex-col gap-6">
            <button
                onClick={() => setPage('laporan')}
                className="group inline-flex items-center gap-2 self-start rounded-xl border border-border bg-card px-3.5 py-2 text-xs font-bold text-foreground/80 shadow-xs transition-all hover:border-primary hover:bg-secondary hover:text-primary"
            >
                <ChevronLeft size={16} className="transition-transform group-hover:-translate-x-0.5" />
                <span>Kembali ke Pusat Laporan</span>
            </button>
            <PageTitle title="Laporan Laba Rugi Koperasi" description="Rekapitulasi performa keuangan koperasi periode berjalan." />
            <div className="grid gap-5 md:grid-cols-3">
                <MetricCard icon={<TrendingUp size={20} />} label="Total Pendapatan" value="Rp 145.820.000" note="↑ 12% dari bulan lalu" tone="green" />
                <MetricCard icon={<TrendingDown size={20} />} label="Total Beban" value="Rp 82.350.000" note="↑ 5% kenaikan logistik" tone="red" />
                <MetricCard icon={<Banknote size={20} />} label="Laba Bersih (SHU)" value="Rp 63.470.000" note="Margin 43.5% - Sehat" featured />
            </div>
            <DataPanel
                title="Detail Rincian Laba Rugi"
                toolbar={
                    <div className="flex gap-2">
                        <DownloadButton />
                        <button onClick={() => window.print()} className="h-9 rounded-xl border border-primary px-3 text-xs font-semibold text-primary">
                            Cetak
                        </button>
                    </div>
                }
                headers={['Kategori Keuangan', 'Keterangan', 'Nilai (IDR)']}
                rows={[
                    ...[['PENDAPATAN OPERASIONAL', '', ''], ...income].map(r => [<strong key={r[0]} className="text-emerald-700">{r[0]}</strong>, r[1], <strong key={r[2]} className="text-emerald-700">{r[2]}</strong>]),
                    ...[['BEBAN OPERASIONAL', '', ''], ...costs].map(r => [<strong key={r[0]} className="text-rose-700">{r[0]}</strong>, r[1], <strong key={r[2]} className="text-rose-700">{r[2]}</strong>]),
                ]}
                footer="Laba bersih periode ini: Rp 63.470.000"
            />
        </div>
    );
}

// ─── Router Component ─────────────────────────────────────────
function MemberPages({ page, setPage, openForm }) {
    switch (page) {
        case 'dashboard':
            return <DashboardPage />;
        case 'simpanan-pokok':
            return <PokokPage />;
        case 'simpanan-wajib':
            return <WajibPage openForm={openForm} />;
        case 'simpanan-sukarela':
            return <SukarelaPage openForm={openForm} />;
        case 'laporan':
            return <ReportsPage setPage={setPage} />;
        case 'laporan-shu':
            return <ShuReportPage setPage={setPage} />;
        case 'laporan-saldo':
            return <SaldoReportPage setPage={setPage} />;
        case 'laporan-laba-rugi':
            return <ProfitPage setPage={setPage} />;
        default:
            return <DashboardPage />;
    }
}

const memberPageTitles = {
    'dashboard': 'Dashboard Anggota',
    'simpanan-pokok': 'Simpanan Pokok Anggota',
    'simpanan-wajib': 'Simpanan Wajib Anggota',
    'simpanan-sukarela': 'Simpanan Sukarela Anggota',
    'laporan': 'Pusat Laporan Anggota',
    'laporan-shu': 'Laporan SHU Anggota',
    'laporan-saldo': 'Laporan Saldo Sampah',
    'laporan-laba-rugi': 'Laporan Laba Rugi Koperasi',
};

export default function AnggotaIndex() {
    const [page, setPage] = useState('dashboard');
    const [form, setForm] = useState(null);
    const [status, setStatus] = useState('');

    return (
        <>
            <Head title={memberPageTitles[page] || 'Dashboard Anggota'} />
            <MemberShell currentPage={page} setPage={setPage}>
                <MemberPages page={page} setPage={setPage} openForm={setForm} />
            </MemberShell>
            <FormPopup kind={form} onClose={() => setForm(null)} onSuccess={setStatus} />
            <StatusPopup open={Boolean(status)} title={status} onClose={() => setStatus('')} />
        </>
    );
}
