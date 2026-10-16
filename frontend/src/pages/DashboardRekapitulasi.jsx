export default function DashboardRekapitulasi() {
  return (
    <>
      <div className="flex flex-col w-full">
      <div className="px-space-lg py-space-md space-y-space-lg">
      {/* Top Bar: Context Role Switcher & Realtime Sync Indicator */}
      <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md bg-surface-container-lowest p-space-sm rounded-xl shadow-sm">
      <div className="flex items-center gap-space-sm flex-wrap">
      <span className="font-label-sm text-label-sm text-outline uppercase tracking-wider px-2">Beralih Tampilan:</span>
      <div className="flex items-center bg-surface-container-low p-1 rounded-full gap-1">
      <button className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-lowest text-primary shadow-sm font-label-md text-label-md" type="button">
      <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
      <span>Manager &amp; Talent Support</span>
      <span className="font-label-sm text-label-sm bg-primary text-on-primary px-1.5 py-0.2 rounded-full">Aktif</span>
      </button>
      <button className="px-3 py-1.5 rounded-full text-secondary hover:text-on-surface hover:bg-surface-container font-label-md text-label-md transition-all" type="button">Account Manager</button>
      <button className="px-3 py-1.5 rounded-full text-secondary hover:text-on-surface hover:bg-surface-container font-label-md text-label-md transition-all" type="button">HR Client Portal</button>
      <button className="px-3 py-1.5 rounded-full text-secondary hover:text-on-surface hover:bg-surface-container font-label-md text-label-md transition-all" type="button">Talent Portal</button>
      </div>
      </div>
      <div className="flex items-center gap-space-md self-end lg:self-center">
      <div className="flex items-center gap-2 px-3 py-1.5 bg-surface-container-low rounded-lg font-label-sm text-label-sm text-on-surface-variant">
      <span className="material-symbols-outlined text-[16px] text-tertiary-container">verified</span>
      <span>SLA Compliance Index: <strong className="text-on-surface font-semibold">98.4% Optimal</strong></span>
      </div>
      <button className="flex items-center gap-1 h-8 px-2.5 rounded-lg bg-surface-container text-primary hover:bg-surface-variant font-label-md text-label-md transition-all" type="button">
      <span className="material-symbols-outlined text-[16px]">tune</span>
      <span>Filter Tampilan</span>
      </button>
      </div>
      </div>
      {/* Welcome Hero Executive Card */}
      <div className="relative overflow-hidden rounded-xl bg-gradient-to-r from-primary to-primary-container text-on-primary p-space-lg shadow-sm">
      <div className="absolute -right-12 -top-12 w-64 h-64 bg-primary-fixed-dim/10 rounded-full blur-2xl pointer-events-none"></div>
      <div className="absolute right-32 -bottom-16 w-80 h-80 bg-tertiary-fixed/10 rounded-full blur-3xl pointer-events-none"></div>
      <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-space-md">
      <div className="space-y-1">
      <div className="flex items-center gap-2">
      <span className="font-label-sm text-label-sm uppercase tracking-wider text-on-primary-container px-2 py-0.5 rounded bg-primary-container/80">Ringkasan Eksekutif Terpadu</span>
      <span className="text-on-primary/60 text-body-sm font-body-sm">•</span>
      <span className="font-body-sm text-body-sm text-on-primary/80">Kamis, 24 Oktober 2024</span>
      </div>
      <h1 className="font-headline-lg text-headline-lg font-bold tracking-tight text-on-primary">
                  Selamat Datang, Bambang Pratama
                </h1>
      <p className="font-body-md text-body-md text-on-primary/80 max-w-2xl">
                  Pusat Kendali Operasional Managed Talent &amp; IT Outsourcing PT Intellisys. 14 proyek korporat berjalan dengan indikator performa stabil tanpa eskalasi kritis.
                </p>
      </div>
      <div className="flex items-center gap-space-sm shrink-0">
      <button className="flex items-center gap-2 h-10 px-4 rounded-lg bg-surface-container-lowest text-primary hover:bg-surface-bright font-label-lg text-label-lg transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[18px]">file_download</span>
      <span>Unduh Laporan Mingguan</span>
      </button>
      <button className="flex items-center gap-2 h-10 px-4 rounded-lg bg-tertiary-container text-on-tertiary font-label-lg text-label-lg hover:bg-tertiary transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[18px]">bolt</span>
      <span>Respon Cepat SLA</span>
      </button>
      </div>
      </div>
      </div>
      {/* 4 Key Metric Cards (KPI Cards) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
      {/* Card 1 */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-sm hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-secondary">Total Talent Aktif</span>
      <div className="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
      <span className="material-symbols-outlined text-[20px]">badge</span>
      </div>
      </div>
      <div>
      <div className="font-headline-lg text-headline-lg font-bold text-on-surface">142 <span className="font-title-sm text-title-sm text-secondary font-normal">Talent</span></div>
      <div className="flex items-center gap-1.5 mt-1 font-body-sm text-body-sm">
      <span className="flex items-center text-on-tertiary-container font-semibold">
      <span className="material-symbols-outlined text-[14px]">trending_up</span> +8%
                  </span>
      <span className="text-secondary">bln ini</span>
      <span className="text-outline-variant">•</span>
      <span className="text-on-surface-variant font-medium">96% Retensi</span>
      </div>
      </div>
      <div className="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
      <div className="bg-primary h-full rounded-full" style={{ width: "88%" }}></div>
      </div>
      </div>
      {/* Card 2 */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-sm hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-secondary">Permintaan Kebutuhan</span>
      <div className="w-8 h-8 rounded-lg bg-secondary-container flex items-center justify-center text-on-secondary-fixed">
      <span className="material-symbols-outlined text-[20px]">receipt_long</span>
      </div>
      </div>
      <div>
      <div className="font-headline-lg text-headline-lg font-bold text-on-surface">18 <span className="font-title-sm text-title-sm text-secondary font-normal">Posisi Buka</span></div>
      <div className="flex items-center gap-1.5 mt-1 font-body-sm text-body-sm">
      <span className="px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-semibold flex items-center gap-1">
      <span className="material-symbols-outlined text-[12px]">alarm</span> 8 Urgent SLA &lt; 3 Hari
                  </span>
      </div>
      </div>
      <div className="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
      <div className="bg-error h-full rounded-full" style={{ width: "44%" }}></div>
      </div>
      </div>
      {/* Card 3 */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-sm hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-secondary">AI Match &amp; Screening</span>
      <div className="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary-container">
      <span className="material-symbols-outlined text-[20px]">auto_awesome</span>
      </div>
      </div>
      <div>
      <div className="font-headline-lg text-headline-lg font-bold text-on-surface">34 <span className="font-title-sm text-title-sm text-secondary font-normal">Kandidat</span></div>
      <div className="flex items-center gap-1.5 mt-1 font-body-sm text-body-sm">
      <span className="text-on-tertiary-container font-semibold flex items-center gap-1">
      <span className="material-symbols-outlined text-[14px]">speed</span> 89.2% Skor Rata-rata
                  </span>
      <span className="text-secondary">akurasi AI</span>
      </div>
      </div>
      <div className="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
      <div className="bg-tertiary-container h-full rounded-full" style={{ width: "76%" }}></div>
      </div>
      </div>
      {/* Card 4 */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-sm hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-secondary">Tiket Komplain &amp; SLA</span>
      <div className="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-on-surface-variant">
      <span className="material-symbols-outlined text-[20px]">support_agent</span>
      </div>
      </div>
      <div>
      <div className="font-headline-lg text-headline-lg font-bold text-on-surface">2 <span className="font-title-sm text-title-sm text-secondary font-normal">Tiket Terbuka</span></div>
      <div className="flex items-center gap-1.5 mt-1 font-body-sm text-body-sm">
      <span className="px-2 py-0.5 rounded-full bg-surface-container text-primary font-semibold flex items-center gap-1">
      <span className="material-symbols-outlined text-[12px]">check_circle</span> 100% On-Track
                  </span>
      <span className="text-secondary">Resolusi &lt; 24 jam</span>
      </div>
      </div>
      <div className="w-full bg-surface-container-high h-1.5 rounded-full overflow-hidden">
      <div className="bg-on-tertiary-container h-full rounded-full" style={{ width: "95%" }}></div>
      </div>
      </div>
      </div>
      {/* Pipeline Funnel Progress: Horizontal Step Flow */}
      <div className="bg-surface-container-lowest p-space-lg rounded-xl shadow-sm space-y-space-md">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
      <h2 className="font-title-md text-title-md text-on-surface font-bold">End-to-End Recruitment &amp; Placement Pipeline</h2>
      <p className="font-body-sm text-body-sm text-secondary">Alur pemrosesan pemenuhan talenta dari permintaan awal hingga onboarding</p>
      </div>
      <div className="flex items-center gap-2">
      <span className="font-label-sm text-label-sm text-secondary">Total Dalam Siklus: <strong className="text-on-surface font-semibold">148 Alur Aktif</strong></span>
      <a className="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-1" href="#">
      <span>Buka Kanban</span>
      <span className="material-symbols-outlined text-[14px]">chevron_right</span>
      </a>
      </div>
      </div>
      {/* Funnel Steps Visualizer */}
      <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-space-xs relative">
      {/* Step 1 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>01. Permintaan</span>
      <span className="material-symbols-outlined text-[14px] text-outline">description</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface">18</span>
      <span className="font-label-sm text-label-sm text-secondary block">Requisisi Klien</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-primary h-full" style={{ width: "100%" }}></div>
      </div>
      </div>
      {/* Step 2 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>02. AI Match</span>
      <span className="material-symbols-outlined text-[14px] text-primary">auto_awesome</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-primary">45</span>
      <span className="font-label-sm text-label-sm text-secondary block">Rekomendasi AI</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-primary h-full" style={{ width: "85%" }}></div>
      </div>
      </div>
      {/* Step 3 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>03. Screening</span>
      <span className="material-symbols-outlined text-[14px] text-outline">checklist</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface">34</span>
      <span className="font-label-sm text-label-sm text-secondary block">Uji Teknis &amp; HR</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-primary-container h-full" style={{ width: "70%" }}></div>
      </div>
      </div>
      {/* Step 4 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>04. Pengajuan</span>
      <span className="material-symbols-outlined text-[14px] text-outline">send</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface">22</span>
      <span className="font-label-sm text-label-sm text-secondary block">Ke Manajer Klien</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-primary-container h-full" style={{ width: "55%" }}></div>
      </div>
      </div>
      {/* Step 5 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>05. Wawancara</span>
      <span className="material-symbols-outlined text-[14px] text-tertiary-container">event</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface">14</span>
      <span className="font-label-sm text-label-sm text-secondary block">Jadwal Interview</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-tertiary-container h-full" style={{ width: "40%" }}></div>
      </div>
      </div>
      {/* Step 6 */}
      <div className="bg-surface-container-low p-space-sm rounded-lg flex flex-col justify-between hover:bg-surface-container transition-colors relative group">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>06. Penawaran</span>
      <span className="material-symbols-outlined text-[14px] text-outline">handshake</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface">6</span>
      <span className="font-label-sm text-label-sm text-secondary block">Offering &amp; PO</span>
      </div>
      <div className="w-full bg-surface-container-high h-1 rounded-full overflow-hidden">
      <div className="bg-tertiary-container h-full" style={{ width: "25%" }}></div>
      </div>
      </div>
      {/* Step 7 */}
      <div className="bg-tertiary-container text-on-tertiary p-space-sm rounded-lg flex flex-col justify-between col-span-2 sm:col-span-1 shadow-sm">
      <div className="flex items-center justify-between font-label-sm text-label-sm text-on-tertiary/80">
      <span>07. Onboarding</span>
      <span className="material-symbols-outlined text-[14px]">how_to_reg</span>
      </div>
      <div className="my-space-xs">
      <span className="font-headline-sm text-headline-sm font-bold text-on-tertiary">9</span>
      <span className="font-label-sm text-label-sm text-on-tertiary/90 block">Talent Aktif Baru</span>
      </div>
      <div className="w-full bg-tertiary h-1 rounded-full overflow-hidden">
      <div className="bg-tertiary-fixed h-full" style={{ width: "100%" }}></div>
      </div>
      </div>
      </div>
      </div>
      {/* Main Section: 2 Columns Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
      {/* Left Column (8 cols): Data Table Kebutuhan Klien Prioritas */}
      <div className="lg:col-span-8 space-y-space-md">
      <div className="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
      {/* Table Header Controls */}
      <div className="p-space-md flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
      <div>
      <div className="flex items-center gap-2">
      <h3 className="font-title-md text-title-md text-on-surface font-bold">Kebutuhan Klien Prioritas &amp; SLA</h3>
      <span className="font-label-sm text-label-sm bg-secondary-container text-on-secondary-fixed px-2 py-0.5 rounded-full font-semibold">5 Berjalan</span>
      </div>
      <p className="font-body-sm text-body-sm text-secondary">Pantau pemenuhan rekuisisi dengan komitmen waktu tercepat</p>
      </div>
      <div className="flex items-center gap-2">
      <div className="relative">
      <span className="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-outline text-[16px]">filter_list</span>
      <select className="h-8 pl-8 pr-6 rounded-lg bg-surface-container-low font-label-sm text-label-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary appearance-none cursor-pointer">
      <option>Semua Status SLA</option>
      <option>Mendesak (&lt; 3 Hari)</option>
      <option>Tahap Screening</option>
      <option>Wawancara Klien</option>
      </select>
      </div>
      <button className="h-8 px-3 rounded-lg bg-surface-container hover:bg-surface-variant text-primary font-label-sm text-label-sm font-semibold transition-all flex items-center gap-1" type="button">
      <span className="material-symbols-outlined text-[16px]">refresh</span>
      <span>Muat Ulang</span>
      </button>
      </div>
      </div>
      {/* Table Container */}
      <div className="overflow-x-auto">
      <table className="w-full text-left border-collapse">
      <thead>
      <tr className="bg-surface-container-low font-label-sm text-label-sm text-secondary tracking-wider uppercase">
      <th className="py-3 px-space-md font-semibold">No. Requisisi &amp; Klien</th>
      <th className="py-3 px-space-sm font-semibold">Posisi &amp; Alokasi</th>
      <th className="py-3 px-space-sm font-semibold">SLA Target</th>
      <th className="py-3 px-space-sm font-semibold">Match AI</th>
      <th className="py-3 px-space-sm font-semibold">Status Tahapan</th>
      <th className="py-3 px-space-md text-right font-semibold">Aksi</th>
      </tr>
      </thead>
      <tbody className="divide-y divide-surface-container-low font-body-sm text-body-sm text-on-surface">
      {/* Row 1 */}
      <tr className="hover:bg-surface-bright transition-colors group">
      <td className="py-3.5 px-space-md">
      <div className="font-label-md text-label-md font-semibold text-primary">REQ-2024-0891</div>
      <div className="font-body-sm text-body-sm text-on-surface font-medium">Bank Mandiri (Mandiri Sekuritas)</div>
      <span className="font-label-sm text-[11px] text-secondary">VMO Finansial • Jakarta Pusat</span>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="font-label-md text-label-md font-semibold text-on-surface">Sr. Fullstack Java</div>
      <div className="text-secondary font-label-sm text-label-sm">3 dari 4 Talent Terpenuhi</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold">
      <span className="material-symbols-outlined text-[13px]">timer</span>
      <span>Sisa 24 Jam</span>
      </div>
      <div className="text-[11px] text-secondary mt-0.5">Batas: 25 Okt, 17:00</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="flex items-center gap-1.5">
      <div className="w-7 h-7 rounded-full bg-tertiary-container/10 text-tertiary-container font-bold font-label-sm text-label-sm flex items-center justify-center">
                              94%
                            </div>
      <div className="flex flex-col">
      <span className="font-label-sm text-label-sm font-medium">Raka Raditya</span>
      <span className="text-[10px] text-secondary">Spring Boot, Vue.js</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-space-sm">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            Interview Klien
                          </span>
      </td>
      <td className="py-3.5 px-space-md text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="h-7 px-2.5 rounded bg-primary text-on-primary hover:bg-primary-container font-label-sm text-label-sm transition-all" title="Kirim rekomendasi" type="button">
                              Kirim Kandidat
                            </button>
      <button className="p-1 text-outline hover:text-on-surface rounded hover:bg-surface-container" type="button">
      <span className="material-symbols-outlined text-[18px]">more_vert</span>
      </button>
      </div>
      </td>
      </tr>
      {/* Row 2 */}
      <tr className="hover:bg-surface-bright transition-colors group">
      <td className="py-3.5 px-space-md">
      <div className="font-label-md text-label-md font-semibold text-primary">REQ-2024-0895</div>
      <div className="font-body-sm text-body-sm text-on-surface font-medium">PT Telkom Digital Solusi</div>
      <span className="font-label-sm text-[11px] text-secondary">Telco Core • Bandung Remote</span>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="font-label-md text-label-md font-semibold text-on-surface">DevOps AWS Engineer</div>
      <div className="text-secondary font-label-sm text-label-sm">1 dari 2 Talent Terpenuhi</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm font-semibold">
      <span className="material-symbols-outlined text-[13px]">schedule</span>
      <span>Sisa 2 Hari</span>
      </div>
      <div className="text-[11px] text-secondary mt-0.5">Batas: 26 Okt, 15:00</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="flex items-center gap-1.5">
      <div className="w-7 h-7 rounded-full bg-tertiary-container/10 text-tertiary-container font-bold font-label-sm text-label-sm flex items-center justify-center">
                              91%
                            </div>
      <div className="flex flex-col">
      <span className="font-label-sm text-label-sm font-medium">Dimas Wardhana</span>
      <span className="text-[10px] text-secondary">Terraform, K8s</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-space-sm">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-primary-container font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
                            Screening Berkas
                          </span>
      </td>
      <td className="py-3.5 px-space-md text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="h-7 px-2.5 rounded bg-surface-container hover:bg-surface-variant text-primary font-label-sm text-label-sm transition-all" type="button">
                              Lihat Match AI
                            </button>
      <button className="p-1 text-outline hover:text-on-surface rounded hover:bg-surface-container" type="button">
      <span className="material-symbols-outlined text-[18px]">more_vert</span>
      </button>
      </div>
      </td>
      </tr>
      {/* Row 3 */}
      <tr className="hover:bg-surface-bright transition-colors group">
      <td className="py-3.5 px-space-md">
      <div className="font-label-md text-label-md font-semibold text-primary">REQ-2024-0902</div>
      <div className="font-body-sm text-body-sm text-on-surface font-medium">BCA Finance</div>
      <span className="font-label-sm text-[11px] text-secondary">Multifinance • Jakarta Barat</span>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="font-label-md text-label-md font-semibold text-on-surface">Mobile Flutter Lead</div>
      <div className="text-secondary font-label-sm text-label-sm">2 dari 2 Talent Terpenuhi</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed/30 text-tertiary-container font-label-sm text-label-sm font-bold">
      <span className="material-symbols-outlined text-[13px]">check_circle</span>
      <span>SLA Terpenuhi</span>
      </div>
      <div className="text-[11px] text-secondary mt-0.5">PO Dalam Proses</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="flex items-center gap-1.5">
      <div className="w-7 h-7 rounded-full bg-tertiary-container/10 text-tertiary-container font-bold font-label-sm text-label-sm flex items-center justify-center">
                              96%
                            </div>
      <div className="flex flex-col">
      <span className="font-label-sm text-label-sm font-medium">Fikri Alamsyah</span>
      <span className="text-[10px] text-secondary">Flutter, CI/CD iOS</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-space-sm">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-tertiary-fixed/40 text-tertiary-container font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary-container"></span>
                            Menunggu PO
                          </span>
      </td>
      <td className="py-3.5 px-space-md text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="h-7 px-2.5 rounded bg-surface-container hover:bg-surface-variant text-primary font-label-sm text-label-sm transition-all" type="button">
                              Cek Dokumen
                            </button>
      <button className="p-1 text-outline hover:text-on-surface rounded hover:bg-surface-container" type="button">
      <span className="material-symbols-outlined text-[18px]">more_vert</span>
      </button>
      </div>
      </td>
      </tr>
      {/* Row 4 */}
      <tr className="hover:bg-surface-bright transition-colors group">
      <td className="py-3.5 px-space-md">
      <div className="font-label-md text-label-md font-semibold text-primary">REQ-2024-0914</div>
      <div className="font-body-sm text-body-sm text-on-surface font-medium">Bukalapak (Mitra Tech)</div>
      <span className="font-label-sm text-[11px] text-secondary">E-Commerce • Hybrid</span>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="font-label-md text-label-md font-semibold text-on-surface">Data Engineer (PySpark)</div>
      <div className="text-secondary font-label-sm text-label-sm">0 dari 2 Talent Terpenuhi</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold">
      <span className="material-symbols-outlined text-[13px]">bolt</span>
      <span>Sisa 18 Jam</span>
      </div>
      <div className="text-[11px] text-secondary mt-0.5">Kebutuhan Mendesak</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="flex items-center gap-1.5">
      <div className="w-7 h-7 rounded-full bg-tertiary-container/10 text-tertiary-container font-bold font-label-sm text-label-sm flex items-center justify-center">
                              88%
                            </div>
      <div className="flex flex-col">
      <span className="font-label-sm text-label-sm font-medium">Siti Nurhaliza</span>
      <span className="text-[10px] text-secondary">Airflow, Snowflake</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-space-sm">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                            Shortlisted
                          </span>
      </td>
      <td className="py-3.5 px-space-md text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="h-7 px-2.5 rounded bg-primary text-on-primary hover:bg-primary-container font-label-sm text-label-sm transition-all" type="button">
                              Kirim Kandidat
                            </button>
      <button className="p-1 text-outline hover:text-on-surface rounded hover:bg-surface-container" type="button">
      <span className="material-symbols-outlined text-[18px]">more_vert</span>
      </button>
      </div>
      </td>
      </tr>
      {/* Row 5 */}
      <tr className="hover:bg-surface-bright transition-colors group">
      <td className="py-3.5 px-space-md">
      <div className="font-label-md text-label-md font-semibold text-primary">REQ-2024-0920</div>
      <div className="font-body-sm text-body-sm text-on-surface font-medium">PT Astra Graphia Information</div>
      <span className="font-label-sm text-[11px] text-secondary">Enterprise IT • Jakarta Selatan</span>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="font-label-md text-label-md font-semibold text-on-surface">QA Automation Lead</div>
      <div className="text-secondary font-label-sm text-label-sm">1 dari 1 Talent Terpenuhi</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm font-semibold">
      <span className="material-symbols-outlined text-[13px]">schedule</span>
      <span>Sisa 4 Hari</span>
      </div>
      <div className="text-[11px] text-secondary mt-0.5">SLA Reguler</div>
      </td>
      <td className="py-3.5 px-space-sm">
      <div className="flex items-center gap-1.5">
      <div className="w-7 h-7 rounded-full bg-tertiary-container/10 text-tertiary-container font-bold font-label-sm text-label-sm flex items-center justify-center">
                              95%
                            </div>
      <div className="flex flex-col">
      <span className="font-label-sm text-label-sm font-medium">Bayu Wicaksono</span>
      <span className="text-[10px] text-secondary">Cypress, Appium</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-space-sm">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            Interview Klien
                          </span>
      </td>
      <td className="py-3.5 px-space-md text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="h-7 px-2.5 rounded bg-surface-container hover:bg-surface-variant text-primary font-label-sm text-label-sm transition-all" type="button">
                              Lihat Match AI
                            </button>
      <button className="p-1 text-outline hover:text-on-surface rounded hover:bg-surface-container" type="button">
      <span className="material-symbols-outlined text-[18px]">more_vert</span>
      </button>
      </div>
      </td>
      </tr>
      </tbody>
      </table>
      </div>
      {/* Table Footer Pagination / Quick View */}
      <div className="p-space-sm bg-surface-container-low flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>Menampilkan 5 dari 18 lowongan aktif</span>
      <div className="flex items-center gap-1">
      <button className="px-2 py-1 rounded bg-surface-container-lowest text-on-surface font-semibold shadow-sm" type="button">1</button>
      <button className="px-2 py-1 rounded hover:bg-surface-container text-secondary" type="button">2</button>
      <button className="px-2 py-1 rounded hover:bg-surface-container text-secondary" type="button">3</button>
      <button className="px-2 py-1 rounded hover:bg-surface-container text-secondary flex items-center" type="button">
      <span className="material-symbols-outlined text-[14px]">chevron_right</span>
      </button>
      </div>
      </div>
      </div>
      {/* Secondary Inline Section: Talent Performance Highlight */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-space-md">
      <div className="flex items-center gap-space-md">
      <div className="w-12 h-12 rounded-xl bg-tertiary-container/10 text-tertiary-container flex items-center justify-center shrink-0">
      <span className="material-symbols-outlined text-[28px]">workspace_premium</span>
      </div>
      <div>
      <h4 className="font-title-sm text-title-sm text-on-surface font-bold">Evaluasi Triwulan Kinerja Talent (Q3 2024)</h4>
      <p className="font-body-sm text-body-sm text-secondary">94.8% Talent Intellisys mendapatkan rating penilaian kinerja &gt;= 4.5/5.0 dari manajer klien.</p>
      </div>
      </div>
      <button className="shrink-0 h-9 px-3 rounded-lg bg-surface-container hover:bg-surface-variant text-primary font-label-md text-label-md font-semibold transition-all" type="button">
                  Lihat Analitik SLA
                </button>
      </div>
      </div>
      {/* Right Column (4 cols): Distribusi Industri & Peringatan SLA Cepat */}
      <div className="lg:col-span-4 space-y-space-md">
      {/* Widget 1: Peringatan SLA & Tindakan Cepat (Critical Attention) */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-md">
      <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="material-symbols-outlined text-[20px] text-error">notification_important</span>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Peringatan Tindakan Cepat</h3>
      </div>
      <span className="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-bold">3 Perlu Respon</span>
      </div>
      <div className="space-y-space-sm">
      {/* Action Item 1 */}
      <div className="p- space-sm p-3 bg-surface-container-low rounded-lg space-y-1 hover:bg-surface-container transition-colors">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-semibold text-error flex items-center gap-1">
      <span className="w-1.5 h-1.5 rounded-full bg-error"></span>
                        Interview 2 Jam Lagi
                      </span>
      <span className="font-label-sm text-[11px] text-secondary">14:00 WIB</span>
      </div>
      <p className="font-label-md text-label-md text-on-surface font-semibold">User Interview: Fikri Alamsyah</p>
      <p className="font-body-sm text-body-sm text-secondary">BCA Finance (Head of Mobile Development) via Google Meet</p>
      <div className="pt-1 flex items-center gap-2">
      <button className="h-6 px-2 rounded bg-primary text-on-primary font-label-sm text-label-sm" type="button">Masuk Ruang Meet</button>
      <button className="h-6 px-2 rounded bg-surface-container-high text-on-surface font-label-sm text-label-sm" type="button">Ingatkan Talent</button>
      </div>
      </div>
      {/* Action Item 2 */}
      <div className="p-3 bg-surface-container-low rounded-lg space-y-1 hover:bg-surface-container transition-colors">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-semibold text-tertiary-container flex items-center gap-1">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary-container"></span>
                        Persetujuan Cuti Talent
                      </span>
      <span className="font-label-sm text-[11px] text-secondary">Hari Ini</span>
      </div>
      <p className="font-label-md text-label-md text-on-surface font-semibold">3 Talent Menunggu Konfirmasi Klien</p>
      <p className="font-body-sm text-body-sm text-secondary">Penempatan Bank Mandiri (2 Talent) &amp; Telkom (1 Talent)</p>
      <div className="pt-1 flex items-center gap-2">
      <button className="h-6 px-2 rounded bg-surface-container-high text-on-surface hover:bg-surface-container font-label-sm text-label-sm" type="button">Proseskan Pengajuan</button>
      </div>
      </div>
      {/* Action Item 3 */}
      <div className="p-3 bg-surface-container-low rounded-lg space-y-1 hover:bg-surface-container transition-colors">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-semibold text-primary flex items-center gap-1">
      <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        PO &amp; Kontrak Kadaluarsa
                      </span>
      <span className="font-label-sm text-[11px] text-secondary">Sisa 7 Hari</span>
      </div>
      <p className="font-label-md text-label-md text-on-surface font-semibold">Perpanjangan PO PT Astra Graphia</p>
      <p className="font-body-sm text-body-sm text-secondary">Kontrak 4 QA Engineer selesai 31 Oktober 2024</p>
      <div className="pt-1 flex items-center gap-2">
      <button className="h-6 px-2 rounded bg-primary-container text-on-primary-container font-label-sm text-label-sm" type="button">Kirim Dokumen Renewal</button>
      </div>
      </div>
      </div>
      </div>
      {/* Widget 2: Distribusi Penempatan Talent per Industri (SVG Donut Chart) */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm space-y-space-md">
      <div className="flex items-center justify-between">
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Distribusi Talent per Industri</h3>
      <span className="material-symbols-outlined text-outline text-[18px]">pie_chart</span>
      </div>
      {/* Inline Donut Chart Visualization (Under 2KB SVG) */}
      <div className="flex items-center gap-space-md">
      <div className="relative w-28 h-28 shrink-0 flex items-center justify-center">
      <svg className="w-full h-full -rotate-90" viewBox="0 0 36 36">
      {/* Background circle */}
      <circle cx="18" cy="18" fill="transparent" r="15.915" stroke="#eff4ff" strokeWidth="4" />
      {/* Segment 1: Fintech & Banking 48% (#00236f) */}
      <circle cx="18" cy="18" fill="transparent" r="15.915" stroke="#00236f" strokeDasharray="48 52" strokeDashoffset="0" strokeWidth="4" />
      {/* Segment 2: E-commerce 24% (#1e3a8a) */}
      <circle cx="18" cy="18" fill="transparent" r="15.915" stroke="#1e3a8a" strokeDasharray="24 76" strokeDashoffset="-48" strokeWidth="4" />
      {/* Segment 3: Telco 18% (#004a32) */}
      <circle cx="18" cy="18" fill="transparent" r="15.915" stroke="#004a32" strokeDasharray="18 82" strokeDashoffset="-72" strokeWidth="4" />
      {/* Segment 4: Logistics & Others 10% (#565e74) */}
      <circle cx="18" cy="18" fill="transparent" r="15.915" stroke="#565e74" strokeDasharray="10 90" strokeDashoffset="-90" strokeWidth="4" />
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
      <span className="font-headline-sm text-headline-sm font-bold text-on-surface leading-none">142</span>
      <span className="font-label-sm text-[10px] text-secondary">Talent</span>
      </div>
      </div>
      {/* Legend List */}
      <div className="space-y-1.5 flex-1 min-w-0">
      <div className="flex items-center justify-between text-body-sm font-body-sm">
      <div className="flex items-center gap-1.5 min-w-0">
      <span className="w-2.5 h-2.5 rounded-full bg-primary shrink-0"></span>
      <span className="truncate text-on-surface text-label-sm">Fintech &amp; Perbankan</span>
      </div>
      <span className="font-semibold text-label-sm text-on-surface">48%</span>
      </div>
      <div className="flex items-center justify-between text-body-sm font-body-sm">
      <div className="flex items-center gap-1.5 min-w-0">
      <span className="w-2.5 h-2.5 rounded-full bg-primary-container shrink-0"></span>
      <span className="truncate text-on-surface text-label-sm">E-Commerce &amp; Tech</span>
      </div>
      <span className="font-semibold text-label-sm text-on-surface">24%</span>
      </div>
      <div className="flex items-center justify-between text-body-sm font-body-sm">
      <div className="flex items-center gap-1.5 min-w-0">
      <span className="w-2.5 h-2.5 rounded-full bg-tertiary-container shrink-0"></span>
      <span className="truncate text-on-surface text-label-sm">Telekomunikasi</span>
      </div>
      <span className="font-semibold text-label-sm text-on-surface">18%</span>
      </div>
      <div className="flex items-center justify-between text-body-sm font-body-sm">
      <div className="flex items-center gap-1.5 min-w-0">
      <span className="w-2.5 h-2.5 rounded-full bg-secondary shrink-0"></span>
      <span className="truncate text-on-surface text-label-sm">Logistik &amp; Korporasi</span>
      </div>
      <span className="font-semibold text-label-sm text-on-surface">10%</span>
      </div>
      </div>
      </div>
      </div>
      {/* Widget 3: Live SLA Incident Monitor Status */}
      <div className="bg-surface-container-low p-space-md rounded-xl space-y-space-sm">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm uppercase tracking-wider text-secondary">Kepatuhan Kontrak SLA</span>
      <span className="font-label-sm text-label-sm text-tertiary-container font-semibold">Real-Time</span>
      </div>
      <div className="flex items-baseline justify-between">
      <span className="font-headline-md text-headline-md font-bold text-on-surface">99.2%</span>
      <span className="font-body-sm text-body-sm text-secondary">Target Kontrak: &gt; 95.0%</span>
      </div>
      <div className="w-full bg-surface-container-high h-2 rounded-full overflow-hidden">
      <div className="bg-tertiary-container h-full rounded-full" style={{ width: "99.2%" }}></div>
      </div>
      <div className="pt-1 flex items-center justify-between font-label-sm text-label-sm text-secondary">
      <span>Rata-rata Waktu Respon: <strong className="text-on-surface">24 Menit</strong></span>
      <span>Zero Breaches (30 Hari)</span>
      </div>
      </div>
      </div>
      </div>
      </div>
      </div>
    </>
  );
}
