import { useState } from 'react';
import {
    ArrowDownToLine, Check, Eye, EyeOff, Leaf, LogOut,
    Plus, Save, TrendingDown,
    TrendingUp, UserPlus, WalletCards, X
} from 'lucide-react';

const cn = (...cls) => cls.filter(Boolean).join(' ');

// ─── Native modal overlay with animation ──────────────────────
function Modal({ open, onClose, children }) {
    if (!open) return null;
    return (
        <div
            className="modal-backdrop-anim fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 backdrop-blur-xs p-4"
            onClick={onClose}
        >
            <div
                className="modal-content-anim w-full max-w-lg overflow-hidden rounded-2xl border border-border bg-card shadow-2xl"
                onClick={e => e.stopPropagation()}
            >
                {children}
            </div>
        </div>
    );
}

// ─── Reusable form fields with clean styling ──────────────────
function Field({ name, label, placeholder, type = 'text', defaultValue, required = true }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={name} className="text-xs font-semibold text-foreground/80">{label}</label>
            <input
                required={required}
                id={name}
                name={name}
                type={type}
                defaultValue={defaultValue}
                placeholder={placeholder}
                className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-foreground transition-all placeholder:text-muted-foreground focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"
            />
        </div>
    );
}

function MoneyField({ name, label, defaultValue, required = true }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={name} className="text-xs font-semibold text-foreground/80">{label}</label>
            <div className="relative">
                <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-primary">Rp</span>
                <input
                    required={required}
                    id={name}
                    name={name}
                    type="text"
                    inputMode="numeric"
                    defaultValue={defaultValue}
                    placeholder="0"
                    className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-3.5 text-sm text-foreground transition-all placeholder:text-muted-foreground focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"
                />
            </div>
        </div>
    );
}

function SelectField({ name, label, options, required = true }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={name} className="text-xs font-semibold text-foreground/80">{label}</label>
            <select
                required={required}
                id={name}
                name={name}
                className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 text-sm text-foreground transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"
            >
                {options.map(o => <option key={o} value={o}>{o}</option>)}
            </select>
        </div>
    );
}

function TextAreaField({ name, label, placeholder, rows = 3, required = true }) {
    return (
        <div className="flex flex-col gap-1.5">
            <label htmlFor={name} className="text-xs font-semibold text-foreground/80">{label}</label>
            <textarea
                required={required}
                id={name}
                name={name}
                placeholder={placeholder}
                rows={rows}
                className="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-3 text-sm text-foreground transition-all placeholder:text-muted-foreground focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15 resize-none"
            />
        </div>
    );
}

// ─── Popup configurations ─────────────────────────────────────
const configs = {
    income: {
        title: 'Input Pendapatan',
        icon: TrendingUp,
        tone: 'text-emerald-700',
        submit: 'Simpan Data',
        submitCls: 'bg-emerald-700 hover:bg-emerald-800 text-white',
        bg: 'bg-emerald-50 border-b border-emerald-100',
    },
    expense: {
        title: 'Input Pengeluaran',
        icon: TrendingDown,
        tone: 'text-rose-700',
        submit: 'Simpan Pengeluaran',
        submitCls: 'bg-primary hover:bg-primary/90 text-white',
        bg: 'bg-rose-50 border-b border-rose-100',
    },
    member: {
        title: 'Daftar Anggota Baru',
        icon: UserPlus,
        tone: 'text-primary',
        submit: 'Daftar Anggota',
        submitCls: 'bg-primary hover:bg-primary/90 text-white',
        bg: 'bg-accent/60 border-b border-border/60',
    },
    waste: {
        title: 'Setor Sampah',
        icon: Leaf,
        tone: 'text-emerald-800',
        submit: 'Simpan Setoran',
        submitCls: 'bg-emerald-700 hover:bg-emerald-800 text-white',
        bg: 'bg-[#6bf1c2] text-emerald-900 border-b border-emerald-300',
    },
    voluntary: {
        title: 'Input Simpanan Sukarela',
        icon: WalletCards,
        tone: 'text-primary',
        submit: 'Simpan Data',
        submitCls: 'bg-primary hover:bg-primary/90 text-white',
        bg: 'bg-accent/60 border-b border-border/60',
    },
    distribution: {
        title: 'Distribusi SHU Baru',
        icon: WalletCards,
        tone: 'text-primary',
        submit: 'Proses Distribusi',
        submitCls: 'bg-primary hover:bg-primary/90 text-white',
        bg: 'bg-accent/60 border-b border-border/60',
    },
};

export function FormPopup({ kind, onClose, onSuccess }) {
    const [error, setError] = useState('');
    const [showPw, setShowPw] = useState(false);
    if (!kind || !configs[kind]) return null;
    const { title, icon: Icon, tone, submit, submitCls, bg } = configs[kind];

    const handleSubmit = (e) => {
        e.preventDefault();
        const required = [...e.currentTarget.querySelectorAll('[required]')];
        if (required.some(f => !f.value.trim())) {
            setError('Lengkapi semua data yang wajib diisi.');
            return;
        }
        setError('');
        onClose();
        onSuccess('Data Berhasil Disimpan');
    };

    return (
        <Modal open onClose={onClose}>
            <div className={cn('flex items-center justify-between px-6 py-4', bg)}>
                <h2 className={cn('flex items-center gap-2.5 text-base font-bold', tone)}>
                    <Icon size={20} />
                    <span>{title}</span>
                </h2>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-lg p-1 text-muted-foreground hover:bg-black/5 hover:text-foreground transition-colors"
                >
                    <X size={18} />
                </button>
            </div>

            <form onSubmit={handleSubmit} className="flex flex-col gap-4 p-6 bg-card">
                {kind === 'income' && (
                    <>
                        <Field name="source" label="Sumber Dana" placeholder="Contoh: Penjualan Pupuk" />
                        <MoneyField name="amount" label="Jumlah Nominal" />
                        <TextAreaField name="note" label="Keterangan" placeholder="Catatan tambahan..." />
                    </>
                )}

                {kind === 'expense' && (
                    <>
                        <SelectField name="category" label="Kategori Pengeluaran" options={['Logistik', 'Operasional', 'Gaji', 'Maintenance', 'Lainnya']} />
                        <MoneyField name="amount" label="Jumlah Nominal" />
                        <TextAreaField name="description" label="Keterangan Pengeluaran" placeholder="Tujuan / rincian pengeluaran..." />
                    </>
                )}

                {kind === 'member' && (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Field name="memberName" label="Nama Anggota" placeholder="Nama Lengkap" />
                            <Field name="username" label="Username" placeholder="username_koperasi" />
                        </div>
                        <TextAreaField name="address" label="Alamat" placeholder="Alamat lengkap domisili" rows={2} />
                        <div className="grid gap-3 sm:grid-cols-2">
                            <MoneyField name="principal" label="Simpanan Pokok (Rp)" defaultValue="50.000" />
                            <div className="flex flex-col gap-1.5">
                                <label htmlFor="password" className="text-xs font-semibold text-foreground/80">Password</label>
                                <div className="relative">
                                    <input
                                        required
                                        id="password"
                                        name="password"
                                        type={showPw ? 'text' : 'password'}
                                        placeholder="••••••••"
                                        className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 pr-10 text-sm text-foreground transition-all focus:border-primary focus:bg-white focus:ring-2 focus:ring-primary/15"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPw(v => !v)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    >
                                        {showPw ? <EyeOff size={16} /> : <Eye size={16} />}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </>
                )}

                {kind === 'waste' && (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Field name="member" label="Nama Anggota / ID" placeholder="Masukkan Nama atau No. ID" />
                            <SelectField name="wasteType" label="Jenis Sampah" options={['Organik', 'Anorganik', 'Plastik', 'Kardus/Kertas', 'Minyak Jelantah']} />
                        </div>
                        <Field name="weight" label="Berat Sampah (kg)" placeholder="0.0" type="number" />
                        <div className="flex flex-col gap-1.5">
                            <label className="text-xs font-semibold text-foreground/80">Hasil Konversi Uang</label>
                            <input
                                readOnly
                                value="Rp 50.000"
                                className="h-10 w-full rounded-xl border border-slate-200 bg-secondary/80 px-3.5 text-sm font-semibold text-primary"
                            />
                        </div>
                    </>
                )}

                {kind === 'voluntary' && (
                    <>
                        <Field name="member" label="Nama Anggota / ID" placeholder="Nama atau No. ID" />
                        <MoneyField name="amount" label="Jumlah Setoran Sukarela" />
                        <Field name="date" label="Tanggal Setoran" type="date" />
                        <TextAreaField name="note" label="Catatan" placeholder="Catatan transaksi..." rows={2} required={false} />
                    </>
                )}

                {kind === 'distribution' && (
                    <>
                        <Field name="year" label="Tahun Buku" defaultValue="2023" />
                        <MoneyField name="totalShu" label="Total SHU Dibagikan" defaultValue="108.900.000" />
                        <Field name="recipientCount" label="Jumlah Penerima" defaultValue="450 Anggota" />
                    </>
                )}

                {error && <p className="text-xs font-medium text-destructive">{error}</p>}

                <div className="flex gap-3 pt-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="h-11 flex-1 rounded-xl border border-border bg-card text-sm font-semibold text-foreground/80 hover:bg-secondary transition-all"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        className={cn('h-11 flex-[1.5] rounded-xl text-sm font-semibold shadow-sm transition-all hover:shadow', submitCls)}
                    >
                        {submit}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

export function StatusPopup({ open, title, description = 'Perbarui laman untuk melihat data terbaru', onClose }) {
    return (
        <Modal open={open} onClose={onClose}>
            <div className="flex flex-col items-center gap-3.5 px-6 py-8 text-center bg-card">
                <div className="flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-8 ring-emerald-50">
                    <Save size={26} />
                </div>
                <div>
                    <h2 className="text-lg font-bold text-foreground">{title}</h2>
                    <p className="mt-1 text-xs text-muted-foreground">{description}</p>
                </div>
                <button
                    onClick={onClose}
                    className="mt-3 h-11 w-full rounded-xl bg-primary text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary/90"
                >
                    Tutup
                </button>
            </div>
        </Modal>
    );
}

export function ConfirmPopup({ open, title, description, actionLabel, tone = 'primary', icon = 'save', onCancel, onConfirm }) {
    return (
        <Modal open={open} onClose={onCancel}>
            <div className="flex flex-col items-center gap-3.5 px-6 py-8 text-center bg-card">
                <div className={cn(
                    'flex size-14 items-center justify-center rounded-full',
                    tone === 'destructive'
                        ? 'bg-rose-100 text-rose-600 ring-8 ring-rose-50'
                        : 'bg-blue-100 text-primary ring-8 ring-blue-50'
                )}>
                    {icon === 'logout' ? <LogOut size={24} /> : <Save size={24} />}
                </div>
                <div>
                    <h2 className="text-lg font-bold text-foreground">{title}</h2>
                    <p className="mt-1 text-xs text-muted-foreground">{description}</p>
                </div>
                <div className="mt-3 grid w-full grid-cols-2 gap-3">
                    <button
                        onClick={onCancel}
                        className="h-11 rounded-xl border border-border bg-card text-sm font-semibold text-foreground/80 hover:bg-secondary transition-all"
                    >
                        Batal
                    </button>
                    <button
                        onClick={onConfirm}
                        className={cn(
                            'h-11 rounded-xl text-sm font-semibold text-white shadow-sm transition-all',
                            tone === 'destructive'
                                ? 'bg-destructive hover:bg-destructive/90'
                                : 'bg-primary hover:bg-primary/90'
                        )}
                    >
                        {actionLabel}
                    </button>
                </div>
            </div>
        </Modal>
    );
}

