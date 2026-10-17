import { Link } from "react-router-dom";

// Ditampilkan saat role user tidak berhak membuka sebuah menu (padanan 403 di backend).
export default function AksesDitolak() {
  return (
    <div className="px-space-lg py-space-xl">
      <div className="bg-surface-container-lowest rounded-xl shadow-sm p-space-xl flex flex-col items-center text-center gap-space-md max-w-xl mx-auto">
        <div className="w-14 h-14 rounded-xl bg-error-container flex items-center justify-center text-error">
          <span className="material-symbols-outlined text-[32px]">lock</span>
        </div>
        <div className="space-y-1">
          <h1 className="font-headline-sm text-headline-sm text-on-surface">Akses Ditolak</h1>
          <p className="font-body-md text-body-md text-secondary">Peran Anda tidak memiliki akses ke halaman ini.</p>
        </div>
        <Link
          to="/dashboard-rekapitulasi"
          className="flex items-center gap-2 h-10 px-4 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-lg text-label-lg transition-all shadow-sm"
        >
          <span className="material-symbols-outlined text-[18px]">grid_view</span>
          Kembali ke Dashboard
        </Link>
      </div>
    </div>
  );
}
