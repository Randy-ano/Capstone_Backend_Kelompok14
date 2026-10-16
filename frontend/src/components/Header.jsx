export default function Header({ title }) {
  return (
    <header className="fixed top-0 left-72 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 px-space-lg flex items-center justify-between gap-space-md">
      <div className="flex items-center gap-space-md min-w-0">
        <div className="flex items-center gap-2 font-body-sm text-body-sm text-on-surface-variant">
          <span className="hover:text-primary transition-colors cursor-pointer">Platform</span>
          <span className="material-symbols-outlined text-[16px]">chevron_right</span>
          <span className="font-label-md text-label-md text-on-surface font-semibold truncate">{title}</span>
        </div>
        <div className="hidden xl:flex items-center gap-space-xs px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-fixed">
          <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
          <span className="font-label-sm text-label-sm font-medium">Sistem Aktif - Sinkronisasi Cloud</span>
        </div>
      </div>

      <div className="flex items-center gap-space-md flex-1 max-w-md mx-space-md">
        <div className="relative w-full">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
          <input
            className="w-full h-9 pl-9 pr-4 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container transition-all"
            placeholder="Cari talent, klien, no. kontrak, skill..."
            type="text"
          />
        </div>
      </div>

      <div className="flex items-center gap-space-sm">
        <button
          className="hidden lg:flex items-center gap-1.5 h-9 px-3 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md transition-all shadow-sm"
          type="button"
        >
          <span className="material-symbols-outlined text-[18px]">add</span>
          <span>Buat Requisisi / Tambah Talent</span>
        </button>
        <button
          className="relative p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
          title="Notifikasi"
          type="button"
        >
          <span className="material-symbols-outlined text-[22px]">notifications</span>
          <span className="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-error text-on-error font-label-sm text-[10px] flex items-center justify-center font-bold">
            4
          </span>
        </button>
        <button
          className="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
          title="Pusat Bantuan"
          type="button"
        >
          <span className="material-symbols-outlined text-[22px]">help</span>
        </button>
        <div className="h-6 w-px bg-surface-container-high mx-1 hidden sm:block"></div>
        <div className="flex items-center gap-space-sm pl-1 cursor-pointer group">
          <img alt="Profile" className="w-8 h-8 rounded-full object-cover ring-2 ring-surface-container-high" src="/avatar.jpg" />
          <div className="hidden md:flex flex-col text-left">
            <span className="font-label-md text-label-md text-on-surface group-hover:text-primary transition-colors leading-tight">
              Bambang Pratama, S.Kom
            </span>
            <span className="font-label-sm text-label-sm text-on-surface-variant">Talent Support Lead</span>
          </div>
          <span className="material-symbols-outlined text-outline text-[18px] group-hover:text-on-surface transition-colors">expand_more</span>
        </div>
      </div>
    </header>
  );
}
