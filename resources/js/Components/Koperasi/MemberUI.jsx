import { useState } from 'react';
import { ChevronLeft, ChevronRight, Download, Eye, Filter, TrendingUp } from 'lucide-react';

const cn = (...cls) => cls.filter(Boolean).join(' ');

// ─── Status Badge ───────────────────────────────────────────
export function Status({ children, kind = 'success' }) {
    const cls = {
        success: 'bg-emerald-100 text-emerald-800',
        waiting: 'bg-amber-100 text-amber-800',
        danger: 'bg-rose-100 text-rose-700',
        info: 'bg-blue-100 text-primary',
    }[kind] || 'bg-emerald-100 text-emerald-800';

    return (
        <span className={cn('inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-semibold', cls)}>
            <span className="size-1.5 rounded-full bg-current" />
            {children}
        </span>
    );
}

// ─── MetricCard ──────────────────────────────────────────────
export function MetricCard({ icon, label, value, note, tone = 'blue', featured = false }) {
    const toneStyles = {
        blue: { icon: 'bg-blue-50 text-primary', val: 'text-foreground' },
        green: { icon: 'bg-emerald-50 text-emerald-600', val: 'text-foreground' },
        red: { icon: 'bg-rose-50 text-rose-600', val: 'text-foreground' },
    };
    const s = toneStyles[tone] || toneStyles.blue;

    return (
        <article className={cn(
            'flex flex-col justify-between rounded-2xl border border-border/80 bg-card p-5 shadow-xs transition-all hover:shadow-md',
            featured ? 'border-primary bg-primary text-white' : ''
        )}>
            <div className="flex items-center gap-3">
                <div className={cn('flex size-10 items-center justify-center rounded-xl', featured ? 'bg-white/15 text-white' : s.icon)}>
                    {icon}
                </div>
                <p className={cn('text-xs uppercase tracking-wider', featured ? 'text-blue-100' : 'text-muted-foreground')}>
                    {label}
                </p>
            </div>
            <strong className={cn('mt-3 block text-2xl font-bold md:text-[26px]', featured ? 'text-white' : s.val)}>
                {value}
            </strong>
            {note && (
                <p className={cn('mt-2 text-xs font-medium', featured ? 'text-blue-100' : tone === 'green' ? 'text-emerald-700 font-semibold' : tone === 'red' ? 'text-rose-600 font-semibold' : 'text-primary')}>
                    {note}
                </p>
            )}
        </article>
    );
}

// ─── Pagination ──────────────────────────────────────────────
export function Pagination({ total = 3 }) {
    const [page, setPage] = useState(1);
    return (
        <div className="flex items-center gap-1.5">
            <button
                aria-label="Sebelumnya"
                onClick={() => setPage(Math.max(1, page - 1))}
                disabled={page === 1}
                className="flex size-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground hover:bg-secondary disabled:opacity-40"
            >
                <ChevronLeft size={16} />
            </button>
            {Array.from({ length: total }, (_, i) => i + 1).map(n => (
                <button
                    key={n}
                    onClick={() => setPage(n)}
                    className={cn(
                        'flex size-9 items-center justify-center rounded-lg text-xs font-bold transition-all',
                        page === n ? 'bg-primary text-white shadow-xs' : 'border border-border bg-card text-foreground hover:bg-secondary'
                    )}
                >
                    {n}
                </button>
            ))}
            <button
                aria-label="Berikutnya"
                onClick={() => setPage(Math.min(total, page + 1))}
                disabled={page === total}
                className="flex size-9 items-center justify-center rounded-lg border border-border bg-card text-muted-foreground hover:bg-secondary disabled:opacity-40"
            >
                <ChevronRight size={16} />
            </button>
        </div>
    );
}

// ─── DataPanel (table) ────────────────────────────────────────
export function DataPanel({ title, toolbar, headers, rows, footer = 'Menampilkan data' }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs">
            <div className="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center border-b border-border/60">
                <h3 className="text-lg font-bold text-foreground">{title}</h3>
                {toolbar}
            </div>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[700px] text-left text-sm">
                    <thead className="bg-[#eef3fc] text-xs font-bold uppercase tracking-wider text-slate-700">
                        <tr>
                            {headers.map(h => (
                                <th key={h} className="px-6 py-3.5">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border/60">
                        {rows.map((row, i) => (
                            <tr key={i} className="hover:bg-secondary/40 transition-colors">
                                {row.map((cell, j) => (
                                    <td key={j} className={cn('px-6 py-4', j === row.length - 1 ? 'text-right' : '')}>
                                        {cell}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <footer className="flex flex-col justify-between gap-3 bg-slate-50/50 p-4 border-t border-border/60 sm:flex-row sm:items-center text-xs text-muted-foreground">
                <p>{footer}</p>
                <Pagination />
            </footer>
        </section>
    );
}

// ─── PageTitle ────────────────────────────────────────────────
export function PageTitle({ title, description, action }) {
    return (
        <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 className="text-2xl font-bold text-foreground md:text-3xl">{title}</h1>
                {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
            </div>
            {action && <div className="flex flex-wrap items-center gap-3">{action}</div>}
        </div>
    );
}

// ─── Action Helpers ───────────────────────────────────────────
export const EyeAction = () => (
    <button aria-label="Lihat detail" className="text-muted-foreground hover:text-primary p-1">
        <Eye size={16} />
    </button>
);

export const FilterButton = () => (
    <button className="flex h-9 items-center gap-1.5 rounded-xl border border-border bg-card px-3.5 text-xs font-semibold text-foreground hover:bg-secondary">
        <Filter size={14} /> Filter
    </button>
);

export const Trend = ({ children }) => (
    <span className="inline-flex items-center gap-1 text-xs font-bold text-emerald-700">
        <TrendingUp size={14} />{children}
    </span>
);

export function DownloadButton({ label = 'Unduh Laporan' }) {
    const download = () => {
        const url = URL.createObjectURL(new Blob(['Laporan Koperasi Jasa Mulyo Raharjo Lestari'], { type: 'text/plain' }));
        const a = Object.assign(document.createElement('a'), { href: url, download: 'laporan-anggota.txt' });
        a.click();
        URL.revokeObjectURL(url);
    };
    return (
        <button
            onClick={download}
            className="flex h-9 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-xs font-semibold text-white shadow-xs hover:bg-emerald-800 transition-all"
        >
            <Download size={14} />
            <span>{label}</span>
        </button>
    );
}
