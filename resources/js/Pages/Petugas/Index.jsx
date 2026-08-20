import { useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    CalendarDays, Check, ChevronRight, Clock3,
    Download, MapPin, Recycle, TrendingUp, Truck
} from 'lucide-react';
import { OfficerShell } from '@/Components/Koperasi/OfficerShell';
import { StatusPopup } from '@/Components/Koperasi/Popups';

const cn = (...cls) => cls.filter(Boolean).join(' ');

function Progress({ value, green = false }) {
    return (
        <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div
                className={cn('h-full rounded-full transition-all duration-300', green ? 'bg-emerald-600' : 'bg-primary')}
                style={{ width: `${value}%` }}
            />
        </div>
    );
}

function Stat({ label, value, children, featured = false }) {
    return (
        <article className={cn(
            'flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-5 shadow-xs transition-all hover:shadow-md',
            featured ? 'border-primary bg-primary text-white' : ''
        )}>
            <p className={cn('text-xs uppercase tracking-wider', featured ? 'text-blue-100' : 'text-muted-foreground')}>
                {label}
            </p>
            <strong className="mt-2 text-2xl font-bold md:text-3xl">{value}</strong>
            <div className="mt-3">{children}</div>
        </article>
    );
}

// ─── 1. Dashboard Sub-page ────────────────────────────────────
function DashboardPage() {
    const activities = [
        ['Jl. Merdeka No. 45', 'Organik: 5kg, Anorganik: 12kg', '10:30 AM', true],
        ['Komp. Griya Hijau Blok B2', 'Menunggu penjemputan', '11:15 AM', false],
        ['Jl. Sudirman 88', 'Menunggu penjemputan', '12:00 PM', false],
    ];

    return (
        <div className="mx-auto max-w-5xl flex flex-col gap-6">
            <div className="grid gap-5 md:grid-cols-3">
                <div className="md:col-span-2">
                    <Stat label="TOTAL SAMPAH TERKUMPUL" value="125.5 kg">
                        <p className="text-xs font-semibold text-emerald-700">↗ +12% dari kemarin</p>
                    </Stat>
                </div>
                <Stat label="JEMPUTAN SELESAI" value="18 / 24">
                    <Progress value={75} />
                </Stat>
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Aktivitas Terbaru</h2>
                </div>
                <div className="divide-y divide-border/60">
                    {activities.map(([place, note, time, done]) => (
                        <div key={place} className="flex items-center gap-4 px-6 py-4 hover:bg-secondary/30 transition-colors">
                            <span className={cn(
                                'flex size-10 shrink-0 items-center justify-center rounded-full',
                                done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-muted-foreground'
                            )}>
                                {done ? <Check size={18} /> : <Clock3 size={18} />}
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-foreground">{place}</p>
                                <p className="text-xs text-muted-foreground">{note}</p>
                            </div>
                            <time className={cn('text-xs font-semibold', done ? 'text-muted-foreground' : 'text-primary')}>{time}</time>
                        </div>
                    ))}
                </div>
                <div className="p-4 border-t border-border/60 bg-slate-50/50">
                    <button className="flex h-10 w-full items-center justify-center rounded-xl border border-primary text-xs font-bold text-primary hover:bg-primary hover:text-white transition-all">
                        Lihat Semua Jadwal
                    </button>
                </div>
            </section>
        </div>
    );
}

// ─── 2. Pickup Sub-page ───────────────────────────────────────
function PickupPage({ showStatus }) {
    const [states, setStates] = useState([true, false, true]);
    const pickups = [
        ['Bpk. Budi Santoso', 'Jl. Mawar Merah No. 12, RT 03/RW 01, Kel. Melati', ['Organik', 'Anorganik']],
        ['Ibu Siti Aminah', 'Jl. Kenanga Asri Blok B No. 4, Kel. Melati', ['Anorganik']],
        ['Bpk. Tono Haryanto', 'Gang Kelinci No. 8, RT 01/RW 02, Kel. Melati', ['Organik']],
    ];
    const toggle = (i) => setStates(s => s.map((v, j) => j === i ? !v : v));

    return (
        <div className="mx-auto max-w-5xl flex flex-col gap-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-bold text-foreground md:text-3xl">Daftar Jemputan Hari Ini</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Tugas penjemputan sampah dari anggota koperasi.</p>
                </div>
                <div className="flex items-center gap-2.5 rounded-xl border border-border bg-card px-4 py-2.5 text-primary shadow-xs">
                    <CalendarDays size={18} />
                    <div>
                        <p className="text-[10px] uppercase font-bold text-muted-foreground">Tanggal Jemput</p>
                        <strong className="text-xs font-bold text-foreground">24 October 2023</strong>
                    </div>
                </div>
            </div>

            <div className="flex flex-col gap-4">
                {pickups.map(([name, address, tags], i) => (
                    <article key={name} className="flex flex-col justify-between gap-5 rounded-2xl border border-border/80 bg-card p-6 shadow-xs sm:flex-row sm:items-center">
                        <div className="flex gap-4">
                            <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-xs">
                                <MapPin size={22} />
                            </span>
                            <div>
                                <h3 className="text-base font-bold text-foreground">{name}</h3>
                                <p className="mt-0.5 text-xs text-muted-foreground">{address}</p>
                                <div className="mt-2.5 flex gap-2">
                                    {tags.map(tag => (
                                        <span
                                            key={tag}
                                            className={cn(
                                                'rounded-full px-3 py-0.5 text-xs font-semibold',
                                                tag === 'Organik' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-primary'
                                            )}
                                        >
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-xl border border-border bg-slate-50/70 p-4">
                            <p className="mb-2 text-xs font-semibold text-muted-foreground">Status Pemilahan Sampah</p>
                            <button
                                type="button"
                                role="switch"
                                aria-checked={states[i]}
                                onClick={() => toggle(i)}
                                className="flex items-center gap-3"
                            >
                                <span className={cn(
                                    'flex h-7 w-12 items-center rounded-full p-1 transition-all duration-200',
                                    states[i] ? 'justify-end bg-primary' : 'justify-start bg-slate-300'
                                )}>
                                    <span className="size-5 rounded-full bg-white shadow-sm" />
                                </span>
                                <strong className={cn('text-xs font-bold', states[i] ? 'text-primary' : 'text-muted-foreground')}>
                                    {states[i] ? 'Sudah Dipilah' : 'Belum Dipilah'}
                                </strong>
                            </button>
                        </div>
                    </article>
                ))}
            </div>

            <button
                onClick={() => { setStates([true, true, true]); showStatus('Data Berhasil Disimpan', 'Semua status jemputan hari ini telah diperbarui.'); }}
                className="mt-2 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary text-sm font-semibold text-white shadow-md transition-all hover:bg-primary/90"
            >
                <Check size={18} />
                <span>Selesaikan Jemputan Hari Ini</span>
            </button>
        </div>
    );
}

// ─── 3. Report Sub-page ───────────────────────────────────────
function ReportPage() {
    const [downloaded, setDownloaded] = useState(false);
    const categories = [
        ['Organik', '520', '42', true],
        ['Plastik', '315', '25', false],
        ['Kertas/Kardus', '280', '22', false],
        ['Logam & Kaca', '130', '11', false],
    ];

    const download = () => {
        const url = URL.createObjectURL(new Blob(['Laporan Harian Petugas Sampah - 24 Oktober 2023\nTotal: 1,245 kg'], { type: 'text/plain' }));
        const a = Object.assign(document.createElement('a'), { href: url, download: 'laporan-harian.txt' });
        a.click();
        URL.revokeObjectURL(url);
        setDownloaded(true);
    };

    return (
        <div className="mx-auto max-w-5xl flex flex-col gap-6">
            <section className="flex flex-col justify-between gap-4 rounded-2xl border border-border/80 bg-card p-6 shadow-xs sm:flex-row sm:items-center">
                <div>
                    <h1 className="text-2xl font-bold text-foreground md:text-3xl">Laporan Harian</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Rekapitulasi pengumpulan sampah hari ini.</p>
                </div>
                <div className="flex items-center gap-2 rounded-xl bg-[#eef3fc] px-4 py-2.5 text-xs font-bold text-primary">
                    <CalendarDays size={16} /> 24 Oktober 2023
                </div>
            </section>

            <div className="grid gap-5 md:grid-cols-3">
                <Stat featured label="Total Hari Ini" value="1,245 kg">
                    <p className="text-xs text-blue-200">↗ +12% dari kemarin</p>
                </Stat>
                <Stat label="Titik Jemput" value="32">
                    <p className="text-xs font-semibold text-muted-foreground">Tersisa 4 titik</p>
                </Stat>
                <Stat label="Target Harian" value="83%">
                    <Progress value={83} />
                </Stat>
            </div>

            <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
                <div className="flex items-center justify-between p-5 border-b border-border/60">
                    <h2 className="text-lg font-bold text-foreground">Rincian Per Kategori</h2>
                    <button
                        onClick={download}
                        className="flex h-9 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800 transition-all"
                    >
                        <Download size={14} /> Unduh PDF
                    </button>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                            <tr>
                                <th className="px-6 py-3.5">Kategori</th>
                                <th className="px-6 py-3.5">Berat (KG)</th>
                                <th className="px-6 py-3.5">Kontribusi</th>
                                <th className="px-6 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border/60">
                            {categories.map(([name, weight, percent, green]) => (
                                <tr key={name} className="hover:bg-secondary/40 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className={cn(
                                                'flex size-9 items-center justify-center rounded-xl',
                                                green ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-primary'
                                            )}>
                                                <Recycle size={18} />
                                            </span>
                                            <span className="font-semibold text-foreground">{name}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 font-bold text-foreground">{weight} kg</td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="w-32">
                                                <Progress value={Number(percent)} green={green} />
                                            </div>
                                            <span className="text-xs font-bold text-muted-foreground">{percent}%</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <button
                                            aria-label={`Detail ${name}`}
                                            onClick={() => alert(`${name}: ${weight} kg`)}
                                            className="text-muted-foreground hover:text-primary p-1"
                                        >
                                            <ChevronRight size={18} />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between border-t-2 border-primary bg-[#eef3fc] px-6 py-4">
                    <strong className="text-sm font-bold text-foreground">Total Keseluruhan</strong>
                    <strong className="text-xl font-extrabold text-primary">1,245 kg</strong>
                </div>
            </section>

            <StatusPopup
                open={downloaded}
                title="PDF Berhasil Didownload"
                description="Dokumen rekapitulasi harian telah tersimpan di unduhan."
                onClose={() => setDownloaded(false)}
            />
        </div>
    );
}

// ─── Router Component ─────────────────────────────────────────
function OfficerPages({ page, showStatus }) {
    if (page === 'jemput-sampah') return <PickupPage showStatus={showStatus} />;
    if (page === 'laporan') return <ReportPage />;
    return <DashboardPage />;
}

const officerPageTitles = {
    'dashboard': 'Dashboard Petugas',
    'jemput-sampah': 'Jemput Sampah',
    'laporan': 'Laporan Harian Petugas',
};

export default function PetugasIndex() {
    const [page, setPage] = useState('dashboard');
    const [status, setStatus] = useState('');

    return (
        <>
            <Head title={officerPageTitles[page] || 'Dashboard Petugas'} />
            <OfficerShell currentPage={page} setPage={setPage}>
                <OfficerPages page={page} showStatus={setStatus} />
            </OfficerShell>
            <StatusPopup open={Boolean(status)} title={status} onClose={() => setStatus('')} />
        </>
    );
}
