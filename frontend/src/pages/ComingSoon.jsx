import { Link, useLocation } from "react-router-dom";
import { NAV_ITEMS } from "../data/navigation";

// Halaman sementara untuk menu yang belum memiliki mockup di Stitch.
export default function ComingSoon() {
  const { pathname } = useLocation();
  const item = NAV_ITEMS.find((i) => i.path === pathname);

  return (
    <div className="px-space-lg py-space-xl">
      <div className="bg-surface-container-lowest rounded-xl shadow-sm p-space-xl flex flex-col items-center text-center gap-space-md max-w-xl mx-auto">
        <div className="w-14 h-14 rounded-xl bg-surface-container flex items-center justify-center text-primary">
          <span className="material-symbols-outlined text-[32px]">{item?.icon ?? "construction"}</span>
        </div>
        <div className="space-y-1">
          <h1 className="font-headline-sm text-headline-sm text-on-surface">{item?.label ?? "Halaman Tidak Ditemukan"}</h1>
          <p className="font-body-md text-body-md text-secondary">
            {item ? "Modul ini sedang dalam tahap perancangan dan akan segera tersedia." : "Alamat yang Anda tuju tidak tersedia."}
          </p>
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
