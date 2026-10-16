import { useState } from "react";

export default function PenempatanMonitoring() {
  const [tab, setTab] = useState("tab-leave");

  return (
    <>
      <div className="flex flex-col w-full">
      <div className="p-space-lg space-y-space-xl max-w-[1560px] mx-auto w-full">
      {/* Header Section with Tactical Badges */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
      <div className="space-y-1">
      <div className="flex items-center gap-space-sm flex-wrap">
      <span className="px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold tracking-wider uppercase">Operasional Terpadu V2.4</span>
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                  Sync Live Klien Aktif
                </span>
      </div>
      <h1 className="font-headline-lg text-headline-lg text-on-surface tracking-tight">Penempatan Aktif, Monitoring SLA &amp; Pengelolaan Operasional Talent</h1>
      <p className="font-body-md text-body-md text-on-surface-variant max-w-4xl">Monitoring harian talent outsource aktif di klien: kehadiran, performa kerja, pengajuan cuti, jadwal shift, dan penanganan tiket keluhan.</p>
      </div>
      <div className="flex items-center gap-space-sm shrink-0">
      <button className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container-low transition-colors font-label-md text-label-md" type="button">
      <span className="material-symbols-outlined text-[18px] text-outline">download</span>
      <span>Unduh Rekap Bulanan</span>
      </button>
      <button className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary-container shadow-md transition-all font-label-md text-label-md" type="button">
      <span className="material-symbols-outlined text-[18px]">add_task</span>
      <span>Inisiasi Alokasi Baru</span>
      </button>
      </div>
      </div>
      {/* Top KPI Summary Cards with Visual Micro-Charts */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
      {/* KPI 1 */}
      <div className="p-space-lg rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
      <div className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-primary-fixed/30 pointer-events-none"></div>
      <div className="flex items-start justify-between">
      <div>
      <span className="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Talent Terdeploy</span>
      <div className="flex items-baseline gap-2 mt-1">
      <span className="font-headline-xl text-headline-xl text-on-surface font-bold">142</span>
      <span className="font-label-sm text-label-sm text-on-tertiary-container font-semibold">Aktif Lapangan</span>
      </div>
      </div>
      <div className="p-2.5 rounded-xl bg-surface-container text-primary">
      <span className="material-symbols-outlined text-[24px]">badge</span>
      </div>
      </div>
      <div className="mt-4 pt-3 flex items-center justify-between">
      <div className="flex items-center gap-2">
      <svg className="w-8 h-8 -rotate-90 text-surface-container-highest" viewBox="0 0 36 36">
      <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeWidth="3" />
      <path className="text-on-tertiary-container" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" strokeDasharray="98.6, 100" strokeLinecap="round" strokeWidth="3" />
      </svg>
      <span className="font-label-md text-label-md text-on-surface font-semibold">98.6%</span>
      </div>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Attendance Rate Bln Ini</span>
      </div>
      </div>
      {/* KPI 2 */}
      <div className="p-space-lg rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
      <div className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-secondary-container/40 pointer-events-none"></div>
      <div className="flex items-start justify-between">
      <div>
      <span className="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Pengajuan Cuti Pending</span>
      <div className="flex items-baseline gap-2 mt-1">
      <span className="font-headline-xl text-headline-xl text-on-surface font-bold">6</span>
      <span className="font-label-sm text-label-sm text-secondary font-semibold">Memerlukan Approval</span>
      </div>
      </div>
      <div className="p-2.5 rounded-xl bg-secondary-container text-on-secondary-fixed">
      <span className="material-symbols-outlined text-[24px]">pending_actions</span>
      </div>
      </div>
      <div className="mt-4 pt-3 flex items-center gap-1.5">
      <span className="w-2 h-2 rounded-full bg-secondary"></span>
      <span className="font-body-sm text-body-sm text-on-surface-variant truncate">Approval HR Klien &amp; Mgr Intellisys</span>
      </div>
      </div>
      {/* KPI 3 */}
      <div className="p-space-lg rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
      <div className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-primary-fixed-dim/20 pointer-events-none"></div>
      <div className="flex items-start justify-between">
      <div>
      <span className="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Talent Shift 24/7</span>
      <div className="flex items-baseline gap-2 mt-1">
      <span className="font-headline-xl text-headline-xl text-on-surface font-bold">23</span>
      <span className="font-label-sm text-label-sm text-primary font-semibold">Rotasi 3 Regu</span>
      </div>
      </div>
      <div className="p-2.5 rounded-xl bg-surface-container-high text-primary">
      <span className="material-symbols-outlined text-[24px]">timelapse</span>
      </div>
      </div>
      <div className="mt-4 pt-3 flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
      <span className="flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[16px] text-tertiary-container">dns</span>
                  DC &amp; NOC Support
                </span>
      <span className="font-label-sm text-label-sm font-semibold text-tertiary-container">100% Terisi</span>
      </div>
      </div>
      {/* KPI 4 */}
      <div className="p-space-lg rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
      <div className="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-tertiary-fixed/30 pointer-events-none"></div>
      <div className="flex items-start justify-between">
      <div>
      <span className="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider block">Pelanggaran SLA Kritis</span>
      <div className="flex items-baseline gap-2 mt-1">
      <span className="font-headline-xl text-headline-xl text-on-surface font-bold">0</span>
      <span className="font-label-sm text-label-sm text-on-tertiary-container font-semibold">Zero Incident</span>
      </div>
      </div>
      <div className="p-2.5 rounded-xl bg-tertiary-fixed text-on-tertiary-fixed">
      <span className="material-symbols-outlined text-[24px]">verified_user</span>
      </div>
      </div>
      <div className="mt-4 pt-3 flex items-center justify-between">
      <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-tertiary-container/10 text-tertiary-container font-label-sm text-label-sm font-bold">
      <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
                  Status Hijau (Prima)
                </span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Target MTTR &lt; 2 Jam</span>
      </div>
      </div>
      </div>
      {/* Alert / Incident Banner: Tiket Cepat */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md flex flex-col lg:flex-row items-start lg:items-center justify-between gap-space-md">
      <div className="flex items-start gap-3">
      <div className="p-2.5 rounded-xl bg-secondary-container text-on-secondary-fixed shrink-0">
      <span className="material-symbols-outlined text-[22px]">confirmation_number</span>
      </div>
      <div>
      <div className="flex items-center gap-2 flex-wrap">
      <span className="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-semibold">Tiket #TK-902</span>
      <span className="font-title-sm text-title-sm text-on-surface font-bold">Permintaan Penggantian Laptop Development Talent Mobile Developer</span>
      <span className="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm">Tokopedia</span>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant mt-1">Unit M1 Pro mengalami kendala layar berkedip. Status: <strong className="text-on-surface font-medium">Diproses AM (Account Manager)</strong> • SLA 24 jam tersisa <strong className="text-primary font-bold">18 Jam 14 Menit</strong> • Perangkat cadangan siap di Buffer Stock Kemang.</p>
      </div>
      </div>
      <div className="flex items-center gap-space-sm shrink-0 w-full lg:w-auto justify-end">
      <button className="px-3 py-1.5 rounded-lg bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-variant transition-colors" type="button">
                Lihat Riwayat Tiket
              </button>
      <button className="px-3.5 py-1.5 rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container shadow-sm transition-colors" type="button">
                Perbarui Status Tiket
              </button>
      </div>
      </div>
      {/* Main Tabs Navigation Bar */}
      <div className="flex items-center justify-between flex-wrap gap-space-md">
      <div className="inline-flex p-1 rounded-xl bg-surface-container-low shadow-sm">
      <button className={`px-4 py-2 rounded-lg font-label-md text-label-md transition-all ${tab === "tab-leave" ? "bg-surface-container-lowest text-primary shadow-sm font-semibold" : "text-on-surface-variant hover:text-on-surface"} flex items-center gap-2`} onClick={() => setTab("tab-leave")} type="button">
      <span className="material-symbols-outlined text-[18px]">event_note</span>
      <span>Pengajuan Cuti &amp; Tukar Shift</span>
      <span className="px-1.5 py-0.2 rounded-full bg-primary-container text-on-primary-container text-[11px] font-bold">6</span>
      </button>
      <button className={`px-4 py-2 rounded-lg font-label-md text-label-md transition-all ${tab === "tab-monitoring" ? "bg-surface-container-lowest text-primary shadow-sm font-semibold" : "text-on-surface-variant hover:text-on-surface"} flex items-center gap-2`} onClick={() => setTab("tab-monitoring")} type="button">
      <span className="material-symbols-outlined text-[18px]">health_and_safety</span>
      <span>Monitoring Kesehatan Kontrak &amp; SLA</span>
      <span className="px-1.5 py-0.2 rounded-full bg-surface-container text-on-surface-variant text-[11px]">3 Kritis</span>
      </button>
      <button className={`px-4 py-2 rounded-lg font-label-md text-label-md transition-all ${tab === "tab-tickets" ? "bg-surface-container-lowest text-primary shadow-sm font-semibold" : "text-on-surface-variant hover:text-on-surface"} flex items-center gap-2`} onClick={() => setTab("tab-tickets")} type="button">
      <span className="material-symbols-outlined text-[18px]">support_agent</span>
      <span>Tiket Komplain Klien</span>
      <span className="px-1.5 py-0.2 rounded-full bg-secondary-container text-on-secondary-fixed text-[11px]">1 Aktif</span>
      </button>
      </div>
      {/* Quick Search & Filter Controls */}
      <div className="flex items-center gap-2 flex-wrap">
      <div className="relative">
      <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline">filter_list</span>
      <select className="h-9 pl-9 pr-8 rounded-lg bg-surface-container-lowest text-on-surface font-body-sm text-body-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-container appearance-none cursor-pointer">
      <option>Semua Klien Penempatan</option>
      <option>PT Telkomsel Digital</option>
      <option>PT Bank Mandiri (Persero) Tbk</option>
      <option>PT Astra Graphia Information</option>
      <option>Tokopedia Tech Hub</option>
      </select>
      </div>
      <button className="h-9 px-3 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:text-on-surface shadow-sm font-label-md text-label-md flex items-center gap-1.5" type="button">
      <span className="material-symbols-outlined text-[18px]">calendar_today</span>
      <span>Maret 2025</span>
      </button>
      </div>
      </div>
      {/* TAB 1: PERSETUJUAN CUTI & SHIFT TALENT (MULTI-LEVEL APPROVAL) */}
      <div className={`space-y-space-md transition-all ${tab === "tab-leave" ? "" : "hidden"}`}>
      <div className="rounded-xl bg-surface-container-lowest shadow-sm overflow-hidden">
      <div className="p-space-md flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm bg-surface-container-low/50">
      <div>
      <h2 className="font-title-md text-title-md text-on-surface">Persetujuan Cuti &amp; Shift Talent (Multi-level Approval)</h2>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Memerlukan validasi silang antara Talent Manager internal Intellisys dan User/HR Klien untuk menghindari kekosongan SLA alokasi.</p>
      </div>
      <div className="flex items-center gap-2 self-start sm:self-center">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Menampilkan 6 Pengajuan Baru</span>
      </div>
      </div>
      <div className="overflow-x-auto">
      <table className="w-full text-left">
      <thead>
      <tr className="bg-surface-container-low font-label-md text-label-md text-on-surface-variant">
      <th className="py-3 px-4">Talent &amp; Peran</th>
      <th className="py-3 px-4">Klien Penempatan</th>
      <th className="py-3 px-4">Tipe Permohonan</th>
      <th className="py-3 px-4">Tanggal Efektif</th>
      <th className="py-3 px-4">Approval Intellisys</th>
      <th className="py-3 px-4">Approval HR Klien</th>
      <th className="py-3 px-4">Dampak SLA &amp; Backup</th>
      <th className="py-3 px-4 text-right">Tindakan</th>
      </tr>
      </thead>
      <tbody className="font-body-md text-body-md text-on-surface">
      {/* Row 1 */}
      <tr className="hover:bg-surface-container-low/40 transition-colors">
      <td className="py-3.5 px-4">
      <div className="flex items-center gap-3">
      <img className="w-9 h-9 rounded-full object-cover" data-alt="Portrait photo of a young professional Indonesian male QA software engineer smiling confident wearing navy blue polo shirt in clean enterprise office setting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAT7UUidS_DOqpqmF_9PRLdcvEyEjoSttpRl1NBtLaiuvLo1ajWv1DTe-uGppoajOTRTAWa4utVr0yGct4KqCpcLH0T8hefubKVEgVHHva2A9XyTIjVnQnxXK2EUT7CbDG3qLS40UV2sr5z1mA78-8PlcoOMV3Srg-8gwVx59UXxwHddKBzMlaosKGjIep0yvfJ87v1WlFu4dfgkjbWrF_34HZ0aJnslfwNpTzikJd05myo6KnUw0CJ" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold truncate">Farhan Rizky</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant">QA Automation Lead</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="font-medium text-on-surface">PT Telkomsel Digital</span>
      <span className="text-on-surface-variant text-[11px]">Core Billing Platform</span>
      </div>
      </td>
      <td className="py-3.5 px-4">
      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface">
      <span className="material-symbols-outlined text-[14px] text-primary">beach_access</span>
                          Cuti Tahunan (2 Hari)
                        </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm text-on-surface whitespace-nowrap">
                        01 Mar - 02 Mar 2025
                      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                          Disetujui: Bambang P.
                        </span>
      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                          Menunggu Approval Klien
                        </span>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="text-on-tertiary-container font-semibold">Tidak ada dampak SLA</span>
      <span className="text-on-surface-variant text-[11px]">Backup: Dita Sabrina (L3 QA)</span>
      </div>
      </td>
      <td className="py-3.5 px-4 text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="px-2.5 py-1 rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-primary-container transition-colors shadow-sm" title="Kirim Pengingat Klien" type="button">
                            Setujui / Push Klien
                          </button>
      <button className="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors" title="Lihat Detail Permohonan" type="button">
      <span className="material-symbols-outlined text-[18px]">visibility</span>
      </button>
      </div>
      </td>
      </tr>
      {/* Row 2 */}
      <tr className="hover:bg-surface-container-low/40 transition-colors">
      <td className="py-3.5 px-4">
      <div className="flex items-center gap-3">
      <img className="w-9 h-9 rounded-full object-cover" data-alt="Close up professional portrait of Indonesian male DevOps system administrator wearing smart casual black blazer looking focused against modern server room background" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC9WAiRx_RrZ_lo-g4I8PY1eLNphtJVn-ARng_ZEU86WwxWxrTpTpOQYqfOQ3WMeKUAw88ugD-IKCR0ZbdDy3JfVEhyr7AyVLHV-Xxn96WKnniE-G7CeVIuR63LQFX5EOopNtMur0flm-B4KAHZUKyUNN3s5eFajYfkCeJ2qBk-juwDMuCJEKi6UYJSPi5w77-Z3bSEdLDHoC6oMgCKIAwJC-flu6vrLpklDfoq9Ieyix4v2psRTwpA" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold truncate">Andi Saputra</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant">DevOps / SRE Shift Specialist</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="font-medium text-on-surface">PT Bank Mandiri (Persero)</span>
      <span className="text-on-surface-variant text-[11px]">NOC 24/7 Tier-2 Ops</span>
      </div>
      </td>
      <td className="py-3.5 px-4">
      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container font-label-sm text-label-sm text-on-secondary-fixed">
      <span className="material-symbols-outlined text-[14px] text-secondary">swap_horiz</span>
                          Tukar Shift Malam → Pagi
                        </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm text-on-surface whitespace-nowrap">
                        03 Mar 2025 (Shift 3 ke 1)
                      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                          Disetujui: Bambang P.
                        </span>
      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                          Disetujui: Irwan (VMO)
                        </span>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="text-on-tertiary-container font-semibold">Shift Tercover Sempurna</span>
      <span className="text-on-surface-variant text-[11px]">Tukar dengan: Rian Hidayat</span>
      </div>
      </td>
      <td className="py-3.5 px-4 text-right">
      <span className="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-surface-container font-label-sm text-label-sm text-on-surface-variant">
      <span className="material-symbols-outlined text-[14px] text-tertiary-container">done_all</span>
                          Selesai
                        </span>
      </td>
      </tr>
      {/* Row 3 */}
      <tr className="hover:bg-surface-container-low/40 transition-colors">
      <td className="py-3.5 px-4">
      <div className="flex items-center gap-3">
      <img className="w-9 h-9 rounded-full object-cover" data-alt="Portrait of an Indonesian female software developer wearing neutral hijab and light grey shirt smiling warmly in brightly lit corporate IT headquarters" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBn_as3CiIfi7ADdUb-6RjktvpCu8LyVCXUwB7BRTrv0dnhUBopa9o7wZ954L4ZZjbN3FS7we0D2VG2PSG571kJ7dvusd7IUA9K2L1JjUO5HWKN7kFxe0aEBmqf4crfNKRGRYSKsqV5Uj6j8D_D90lPgWNA5PkVLnBV7P6Q7ireqTf_d0okOwpjr1lqrXHxM9vWvblYlxEPyEk1DkLVzGQFnGPAAPnrCEbPJq82lb6ehZF7_wNZUYPZ" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold truncate">Nurul Hidayati</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant">Fullstack React / Node.js</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="font-medium text-on-surface">Tokopedia Tech Hub</span>
      <span className="text-on-surface-variant text-[11px]">Merchant Settlement Squad</span>
      </div>
      </td>
      <td className="py-3.5 px-4">
      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-error-container font-label-sm text-label-sm text-on-error-container">
      <span className="material-symbols-outlined text-[14px]">medical_services</span>
                          Izin Sakit (Surat Dokter)
                        </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm text-on-surface whitespace-nowrap">
                        28 Feb - 01 Mar 2025
                      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                          Verifikasi Dokumen
                        </span>
      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm font-semibold">
                          Menunggu Berkas
                        </span>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="text-error font-medium">Sprint Release 02 Mar</span>
      <span className="text-on-surface-variant text-[11px]">Diperlukan Reallokasi Story Point</span>
      </div>
      </td>
      <td className="py-3.5 px-4 text-right">
      <div className="flex items-center justify-end gap-1">
      <button className="px-2.5 py-1 rounded-lg bg-surface-container text-primary font-label-sm text-label-sm hover:bg-surface-variant transition-colors flex items-center gap-1" type="button">
      <span className="material-symbols-outlined text-[14px]">attachment</span>
                            Berkas
                          </button>
      <button className="px-2.5 py-1 rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-primary-container transition-colors shadow-sm" type="button">
                            Setujui
                          </button>
      </div>
      </td>
      </tr>
      {/* Row 4 */}
      <tr className="hover:bg-surface-container-low/40 transition-colors">
      <td className="py-3.5 px-4">
      <div className="flex items-center gap-3">
      <img className="w-9 h-9 rounded-full object-cover" data-alt="Portrait photo of an Indonesian senior backend software engineer with glasses and corporate polo shirt looking professional in modern office workspace" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBlFdbs4oi5qIpGLXrp7W6B9RFiI9U5DaJs0LZzLa_dIFasLFVxwXhx-R5OD7j5hQ9ex7zObKeF5vQlmkZgRAnyAagAG1fEU4wRspGq9jMX6Xj9J6y5tM6oZVVgigs5KchgnB7TsbuWjUwKmphwcda-rc4mXJS6ZyGIsF5eIG7eU-MhpsaIhfOdMX8we6g2a5v0_8ucYaEuNdDSDHatsakO0kDR__lVGnDB1qkOTcDZ6b904GQYhRRa" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold truncate">Yoga Pratama</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant">Senior Java Spring Engineer</span>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="font-medium text-on-surface">PT Astra Graphia IT</span>
      <span className="text-on-surface-variant text-[11px]">Enterprise ERP Migration</span>
      </div>
      </td>
      <td className="py-3.5 px-4">
      <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container font-label-sm text-label-sm text-on-surface">
      <span className="material-symbols-outlined text-[14px] text-primary">beach_access</span>
                          Cuti Tahunan (1 Hari)
                        </div>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm text-on-surface whitespace-nowrap">
                        07 Mar 2025
                      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                          Disetujui: Bambang P.
                        </span>
      </td>
      <td className="py-3.5 px-4">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                          Disetujui: Ibu Sandra
                        </span>
      </td>
      <td className="py-3.5 px-4 font-body-sm text-body-sm">
      <div className="flex flex-col">
      <span className="text-on-tertiary-container font-semibold">Tidak ada dampak SLA</span>
      <span className="text-on-surface-variant text-[11px]">Sprint Task selesai lebih awal</span>
      </div>
      </td>
      <td className="py-3.5 px-4 text-right">
      <span className="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-surface-container font-label-sm text-label-sm text-on-surface-variant">
      <span className="material-symbols-outlined text-[14px] text-tertiary-container">done_all</span>
                          Selesai
                        </span>
      </td>
      </tr>
      </tbody>
      </table>
      </div>
      <div className="p-space-md bg-surface-container-low/30 flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
      <span>Menampilkan 4 dari 6 permohonan terkini</span>
      <div className="flex items-center gap-2">
      <button className="px-3 py-1 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant transition-colors disabled:opacity-50" disabled type="button">Sebelumnya</button>
      <button className="px-3 py-1 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant transition-colors" type="button">Selanjutnya</button>
      </div>
      </div>
      </div>
      </div>
      {/* TAB 2: MONITORING KESEHATAN KONTRAK & SLA TALENT */}
      <div className={`space-y-space-md transition-all ${tab === "tab-monitoring" ? "" : "hidden"}`}>
      <div className="rounded-xl bg-surface-container-lowest shadow-sm overflow-hidden">
      <div className="p-space-md flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm bg-surface-container-low/50">
      <div>
      <h2 className="font-title-md text-title-md text-on-surface">Monitoring Kesehatan Kontrak &amp; SLA Talent Ditempatkan</h2>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Deteksi dini masa akhir kontrak PO klien, rating performa kuartalan, dan kepatuhan SLA kehadiran talent.</p>
      </div>
      <div className="flex items-center gap-2">
      <span className="px-2.5 py-1 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
                    3 Kontrak Expiring &lt; 30 Hari
                  </span>
      </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-md p-space-md">
      {/* Contract Card 1 */}
      <div className="rounded-xl bg-surface-container-low p-space-md flex flex-col justify-between space-y-4 hover:shadow-sm transition-all">
      <div className="space-y-3">
      <div className="flex items-start justify-between">
      <div className="flex items-center gap-3">
      <img className="w-11 h-11 rounded-full object-cover" data-alt="Portrait of an Indonesian female Cloud DevOps Engineer wearing modern dark navy glasses and polo shirt in corporate tech environment" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAE5zaF3ZAHVcIRfXeH-r1zLXsSL9_VTgpTVEFHQkxFBUpE8PPM6n8YZuQh-DeCe1OzICjlQxvVzoiHLJludk3T2HQhG7FtCcWOJxWOu5M0oxd-tPeB6xzTF67GWZyjntPnCBYQutqu8q7kqA3RFf1d4JN1VHQnHDJHwbTExeBMC-u-LjCZtpOMRwzczSfxxsrS4ss5pvLmTNpHOCQiqPp0wzQhXwd83u-1-SXND286hdZEr4liQYmT" />
      <div>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Rizkia Maharani</h3>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Cloud Solutions Architect</p>
      </div>
      </div>
      <div className="flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-lowest text-on-surface font-label-sm text-label-sm font-semibold shadow-xs">
      <span className="material-symbols-outlined text-[14px] text-on-tertiary-container" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span>4.9 / 5.0</span>
      </div>
      </div>
      <div className="p-3 rounded-lg bg-surface-container-lowest space-y-2">
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Klien Penempatan:</span>
      <span className="font-semibold text-on-surface">PT Telkomsel Digital</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">No. PO Kontrak:</span>
      <span className="font-mono text-primary text-[13px]">PO-TSEL-2024-089</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">SLA Kehadiran:</span>
      <span className="text-on-tertiary-container font-semibold">99.2% (158 / 160 Jam)</span>
      </div>
      </div>
      <div className="p-2.5 rounded-lg bg-error-container text-on-error-container flex items-center gap-2">
      <span className="material-symbols-outlined text-[20px] text-error shrink-0">timer</span>
      <div className="text-[12px] leading-tight">
      <strong className="font-bold">Akan Berakhir dalam 28 Hari</strong> • Perlu inisiasi perpanjangan addendum PO Klien sebelum 15 Mar 2025.
                      </div>
      </div>
      </div>
      <div className="flex items-center gap-2 pt-2">
      <button className="w-full py-2 px-3 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-medium transition-colors shadow-sm text-center" type="button">
                      Inisiasi Renewal Kontrak
                    </button>
      <button className="p-2 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:text-on-surface shadow-xs" title="Evaluasi Talent" type="button">
      <span className="material-symbols-outlined text-[18px]">assessment</span>
      </button>
      </div>
      </div>
      {/* Contract Card 2 */}
      <div className="rounded-xl bg-surface-container-low p-space-md flex flex-col justify-between space-y-4 hover:shadow-sm transition-all">
      <div className="space-y-3">
      <div className="flex items-start justify-between">
      <div className="flex items-center gap-3">
      <img className="w-11 h-11 rounded-full object-cover" data-alt="Portrait of an experienced Indonesian Cyber Security Specialist with crisp white shirt in futuristic financial operations room" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBHLiRwofD82YqnXH3_OEWs4NfS_2n4dI8-NLEsyj4vzzEZVttn_fhoZOprELGW-faY6-gLNZwnoU-pwM1Y8xfbYMA6T4WBa4_vPylmlxbrzG2LF8kAyZor5TSb42jHN_JDsH_IyD-byqi_ErsvJ84hkMmdaTF49hp0jMRzkTNh0BrWtPbZyg4Gh9JsbLb1cFrPmwYfhCLUoE26DD3sduW34iwAyJ952Cc3h7mB1x1AdA7ILicH6Od1" />
      <div>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Bagas Wicaksono</h3>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Cybersecurity Analyst (SOC)</p>
      </div>
      </div>
      <div className="flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-lowest text-on-surface font-label-sm text-label-sm font-semibold shadow-xs">
      <span className="material-symbols-outlined text-[14px] text-on-tertiary-container" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span>4.8 / 5.0</span>
      </div>
      </div>
      <div className="p-3 rounded-lg bg-surface-container-lowest space-y-2">
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Klien Penempatan:</span>
      <span className="font-semibold text-on-surface">PT Bank Mandiri (Persero)</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">No. PO Kontrak:</span>
      <span className="font-mono text-primary text-[13px]">PO-MDR-2024-412</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">SLA Kehadiran:</span>
      <span className="text-on-tertiary-container font-semibold">100% (Tanpa Absen)</span>
      </div>
      </div>
      <div className="p-2.5 rounded-lg bg-secondary-container text-on-secondary-fixed flex items-center gap-2">
      <span className="material-symbols-outlined text-[20px] text-secondary shrink-0">hourglass_top</span>
      <div className="text-[12px] leading-tight">
      <strong className="font-bold">Akan Berakhir dalam 45 Hari</strong> • Draft quotation renewal 12 bulan telah di-generate oleh AM.
                      </div>
      </div>
      </div>
      <div className="flex items-center gap-2 pt-2">
      <button className="w-full py-2 px-3 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-medium transition-colors shadow-sm text-center" type="button">
                      Review Draft Renewal
                    </button>
      <button className="p-2 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:text-on-surface shadow-xs" title="Evaluasi Talent" type="button">
      <span className="material-symbols-outlined text-[18px]">assessment</span>
      </button>
      </div>
      </div>
      {/* Contract Card 3 */}
      <div className="rounded-xl bg-surface-container-low p-space-md flex flex-col justify-between space-y-4 hover:shadow-sm transition-all">
      <div className="space-y-3">
      <div className="flex items-start justify-between">
      <div className="flex items-center gap-3">
      <img className="w-11 h-11 rounded-full object-cover" data-alt="Portrait of Indonesian female Data Engineer wearing formal dark grey blazer with confident expression in tech lab" src="https://lh3.googleusercontent.com/aida-public/AB6AXuClo5sYWSDRObHYgAZmAijjdfU8ymvQaUjfaD-bQ2zDbXZxRlDQ_iF7X9jQ9tQWbDhh5wl3SDNTw2d0EauNfRKET9cDKe27yR_iAW53P40ZJHNnvIjgPAxnOtOtT5scLl23HMoyMAupTpWW6Mkjsp9FMYTKc_uS9jLNHEwi-OV3XiN6Ftc4zFDvUI3BBEUZJFhrK8moIT387SsmBSsI_yBrKbVB1RJvXtutmCwRekhvcM-7yVCVt-dv" />
      <div>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Dewi Anggraini</h3>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Data Platform Engineer</p>
      </div>
      </div>
      <div className="flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container-lowest text-on-surface font-label-sm text-label-sm font-semibold shadow-xs">
      <span className="material-symbols-outlined text-[14px] text-on-tertiary-container" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span>4.7 / 5.0</span>
      </div>
      </div>
      <div className="p-3 rounded-lg bg-surface-container-lowest space-y-2">
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Klien Penempatan:</span>
      <span className="font-semibold text-on-surface">Tokopedia Tech Hub</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">No. PO Kontrak:</span>
      <span className="font-mono text-primary text-[13px]">PO-TKPD-2024-118</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">SLA Kehadiran:</span>
      <span className="text-on-tertiary-container font-semibold">97.8% (Sesuai Batas Aman)</span>
      </div>
      </div>
      <div className="p-2.5 rounded-lg bg-surface-container text-on-surface flex items-center gap-2">
      <span className="material-symbols-outlined text-[20px] text-primary shrink-0">event_available</span>
      <div className="text-[12px] leading-tight">
      <strong className="font-bold">Masa Aktif 110 Hari Lagi</strong> • Kontrak aman hingga Akhir Juni 2025. Performa stabil.
                      </div>
      </div>
      </div>
      <div className="flex items-center gap-2 pt-2">
      <button className="w-full py-2 px-3 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant font-label-md text-label-md font-medium transition-colors text-center" type="button">
                      Lihat Detail Kontrak
                    </button>
      <button className="p-2 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:text-on-surface shadow-xs" title="Evaluasi Talent" type="button">
      <span className="material-symbols-outlined text-[18px]">assessment</span>
      </button>
      </div>
      </div>
      </div>
      </div>
      </div>
      {/* TAB 3: TIKET KOMPLAIN KLIEN */}
      <div className={`space-y-space-md transition-all ${tab === "tab-tickets" ? "" : "hidden"}`}>
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-md">
      <div>
      <h2 className="font-title-md text-title-md text-on-surface">Tiket Komplain Klien &amp; SLA Resolusi</h2>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Penanganan kendala hardware, performa, atau pergantian talent dalam komitmen Service Level Agreement Intellisys.</p>
      </div>
      <button className="px-3.5 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container shadow-sm flex items-center gap-1.5 self-start" type="button">
      <span className="material-symbols-outlined text-[18px]">add</span>
      <span>Input Tiket Manual</span>
      </button>
      </div>
      <div className="space-y-space-md">
      {/* Detailed Ticket 1 */}
      <div className="p-space-md rounded-xl bg-surface-container-low flex flex-col md:flex-row items-start md:items-center justify-between gap-space-md">
      <div className="flex items-start gap-4">
      <div className="p-3 rounded-xl bg-secondary-container text-on-secondary-fixed shrink-0">
      <span className="material-symbols-outlined text-[24px]">laptop_mac</span>
      </div>
      <div className="space-y-1">
      <div className="flex items-center gap-2 flex-wrap">
      <span className="font-bold text-on-surface font-title-sm text-title-sm">Tiket #TK-902</span>
      <span className="px-2 py-0.5 rounded-full bg-surface-container-highest text-primary font-label-sm text-label-sm">Hardware &amp; Dev Machine</span>
      <span className="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">Tokopedia Tech Hub</span>
      </div>
      <h4 className="font-title-sm text-title-sm text-on-surface">Permintaan penggantian unit laptop development M1 Pro untuk talent Mobile Dev</h4>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Talent: <strong>Fikri Ramadhan (iOS Lead)</strong> • Masalah: Kernel panic dan screen flicker setelah update Xcode. SLA pergantian hardware maks 24 Jam.</p>
      <div className="flex items-center gap-3 pt-1 text-[12px] text-on-surface-variant">
      <span>Dibuat: Hari ini, 08:30 WIB</span>
      <span>•</span>
      <span className="text-primary font-bold">Target SLA: 18 Jam Tersisa</span>
      <span>•</span>
      <span className="text-tertiary-container font-semibold">Penanggung Jawab: Denny (IT Ops Lead)</span>
      </div>
      </div>
      </div>
      <div className="flex items-center gap-2 w-full md:w-auto justify-end">
      <button className="px-3 py-1.5 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant font-label-md text-label-md" type="button">
                      Eskalasi Level 2
                    </button>
      <button className="px-3 py-1.5 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md shadow-sm" type="button">
                      Selesaikan Tiket
                    </button>
      </div>
      </div>
      {/* Ticket 2 (Resolved) */}
      <div className="p-space-md rounded-xl bg-surface-container-lowest shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-space-md">
      <div className="flex items-start gap-4">
      <div className="p-3 rounded-xl bg-tertiary-fixed text-on-tertiary-fixed shrink-0">
      <span className="material-symbols-outlined text-[24px]">task_alt</span>
      </div>
      <div className="space-y-1">
      <div className="flex items-center gap-2 flex-wrap">
      <span className="font-bold text-on-surface font-title-sm text-title-sm">Tiket #TK-889</span>
      <span className="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm">Akses VPN &amp; Token Security</span>
      <span className="px-2 py-0.5 rounded-full bg-tertiary-container/10 text-tertiary-container font-label-sm text-label-sm font-semibold">PT Bank Mandiri (Persero)</span>
      </div>
      <h4 className="font-title-sm text-title-sm text-on-surface">Konfigurasi RSA Hard Token untuk Talent SOC Analyst Baru</h4>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Talent: <strong>Bagas Wicaksono</strong> • Token RSA telah dikirim dan disinkronkan dengan IAM Bank Mandiri. MTTR: 1 Jam 15 Menit.</p>
      <div className="flex items-center gap-3 pt-1 text-[12px] text-on-surface-variant">
      <span>Diselesaikan: 27 Feb 2025, 14:15 WIB</span>
      <span>•</span>
      <span className="text-tertiary-container font-semibold">SLA Terpenuhi (100%)</span>
      </div>
      </div>
      </div>
      <div className="flex items-center gap-2 w-full md:w-auto justify-end">
      <span className="px-3 py-1.5 rounded-lg bg-surface-container font-label-md text-label-md text-on-surface-variant">
                      Tiket Ditutup
                    </span>
      </div>
      </div>
      </div>
      </div>
      </div>
      {/* Shift Schedule 24/7 Monitoring Matrix (Overview Section) */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md space-y-space-md">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm">
      <div>
      <div className="flex items-center gap-2">
      <span className="w-2.5 h-2.5 rounded-full bg-tertiary animate-pulse"></span>
      <h2 className="font-title-md text-title-md text-on-surface">Jadwal Shift 24/7 Hari Ini (Data Center &amp; NOC Support)</h2>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Monitoring keterisian kuota regu shift per jam secara real-time untuk menjamin uptime perbankan &amp; telco.</p>
      </div>
      <div className="flex items-center gap-2">
      <span className="font-label-sm text-label-sm px-2.5 py-1 rounded-full bg-surface-container text-on-surface">Zona Waktu: WIB (GMT+7)</span>
      </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-space-md">
      {/* Shift 1 */}
      <div className="p-space-md rounded-xl bg-surface-container-low space-y-3">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-on-surface font-bold">Shift Pagi (07:00 - 15:30)</span>
      <span className="px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">Berjalan Normal</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Talent On-Duty:</span>
      <span className="font-bold text-on-surface">8 Talent Terdaftar</span>
      </div>
      <div className="w-full bg-surface-container-high rounded-full h-2">
      <div className="bg-tertiary-container h-2 rounded-full" style={{ width: "100%" }}></div>
      </div>
      <div className="text-[12px] text-on-surface-variant flex items-center justify-between">
      <span>Lead Shift: <strong>Rian Hidayat</strong></span>
      <span>Check-in: 100% On-Time</span>
      </div>
      </div>
      {/* Shift 2 */}
      <div className="p-space-md rounded-xl bg-surface-container-low space-y-3">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-on-surface font-bold">Shift Siang (15:00 - 23:30)</span>
      <span className="px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm text-label-sm font-semibold">Handover Siap</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Talent Terjadwal:</span>
      <span className="font-bold text-on-surface">8 Talent Siap</span>
      </div>
      <div className="w-full bg-surface-container-high rounded-full h-2">
      <div className="bg-primary-container h-2 rounded-full" style={{ width: "100%" }}></div>
      </div>
      <div className="text-[12px] text-on-surface-variant flex items-center justify-between">
      <span>Lead Shift: <strong>Danang Kusuma</strong></span>
      <span>Briefing: 14:45 WIB</span>
      </div>
      </div>
      {/* Shift 3 */}
      <div className="p-space-md rounded-xl bg-surface-container-low space-y-3">
      <div className="flex items-center justify-between">
      <span className="font-label-md text-label-md text-on-surface font-bold">Shift Malam (23:00 - 07:30)</span>
      <span className="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">Penyesuaian Tukar</span>
      </div>
      <div className="flex items-center justify-between font-body-sm text-body-sm">
      <span className="text-on-surface-variant">Talent Terjadwal:</span>
      <span className="font-bold text-on-surface">7 Talent (1 Backup)</span>
      </div>
      <div className="w-full bg-surface-container-high rounded-full h-2">
      <div className="bg-secondary h-2 rounded-full" style={{ width: "100%" }}></div>
      </div>
      <div className="text-[12px] text-on-surface-variant flex items-center justify-between">
      <span>Lead Shift: <strong>Andi Saputra</strong></span>
      <span>Status: Kuota Terpenuhi</span>
      </div>
      </div>
      </div>
      </div>
      </div>
      </div>
    </>
  );
}
