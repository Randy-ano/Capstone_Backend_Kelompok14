import { useState } from "react";

export default function PermintaanKebutuhan() {
  const [modalOpen, setModalOpen] = useState(false);

  return (
    <>
      <div className="flex flex-col w-full pb-space-xl">
      {/* Top Banner / Visual Context Scrim */}
      <div className="relative w-full bg-surface-container-low px-space-xl py-space-lg flex flex-col md:flex-row md:items-center justify-between gap-space-md shadow-sm">
      <div className="flex flex-col gap-1 max-w-3xl">
      <div className="flex items-center gap-space-xs text-primary font-label-sm text-label-sm tracking-wider uppercase">
      <span className="material-symbols-outlined text-[16px]">work_outline</span>
      <span>Modul Klien &amp; Rekuisisi Penempatan</span>
      <span className="inline-block w-1 h-1 rounded-full bg-outline"></span>
      <span className="text-on-surface-variant font-medium">B2B Portal V2</span>
      </div>
      <h1 className="font-headline-lg text-headline-lg text-on-surface tracking-tight">
              Manajemen Permintaan Kebutuhan (Job Requirements) &amp; Evaluasi Kandidat
            </h1>
      <p className="font-body-md text-body-md text-on-surface-variant">
              Kolaborasi terpadu antara HR Client, Account Manager Intellisys, dan Talent Operations untuk review kualifikasi, persetujuan interview, serta validasi komersial talent.
            </p>
      </div>
      <div className="flex items-center gap-space-sm self-start md:self-auto shrink-0">
      <button className="flex items-center gap-2 h-10 px-4 rounded-xl bg-surface-container text-primary hover:bg-surface-variant font-label-md text-label-md transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[18px]">download</span>
      <span>Ekspor Rekap CSV</span>
      </button>
      <button className="flex items-center gap-2 h-10 px-5 rounded-xl bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md transition-all shadow-md" onClick={() => setModalOpen(true)} type="button">
      <span className="material-symbols-outlined text-[18px]">add_circle</span>
      <span>+ Buat Permintaan Kebutuhan Baru</span>
      </button>
      </div>
      </div>
      <div className="px-space-xl pt-space-lg flex flex-col gap-space-lg">
      {/* Quick Analytics / Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-space-md">
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
      <div className="flex items-center justify-between text-on-surface-variant">
      <span className="font-label-md text-label-md">Total Requisisi Aktif</span>
      <span className="material-symbols-outlined text-primary text-[20px]">assignment</span>
      </div>
      <div className="mt-space-sm flex items-baseline gap-2">
      <span className="font-headline-lg text-headline-lg text-on-surface">18</span>
      <span className="font-label-sm text-label-sm text-on-tertiary-container font-semibold">↑ 4 kuartal ini</span>
      </div>
      <div className="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
      <div className="bg-primary h-full rounded-full" style={{ width: "72%" }}></div>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
      <div className="flex items-center justify-between text-on-surface-variant">
      <span className="font-label-md text-label-md">Menunggu Review Klien</span>
      <span className="material-symbols-outlined text-amber-600 text-[20px]">pending_actions</span>
      </div>
      <div className="mt-space-sm flex items-baseline gap-2">
      <span className="font-headline-lg text-headline-lg text-on-surface">12</span>
      <span className="font-label-sm text-label-sm text-error font-semibold">3 mendekati SLA</span>
      </div>
      <div className="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
      <div className="bg-amber-500 h-full rounded-full" style={{ width: "48%" }}></div>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
      <div className="flex items-center justify-between text-on-surface-variant">
      <span className="font-label-md text-label-md">Jadwal Interview Terjadwal</span>
      <span className="material-symbols-outlined text-primary-container text-[20px]">video_call</span>
      </div>
      <div className="mt-space-sm flex items-baseline gap-2">
      <span className="font-headline-lg text-headline-lg text-on-surface">8</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant">Minggu ini</span>
      </div>
      <div className="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
      <div className="bg-primary-container h-full rounded-full" style={{ width: "85%" }}></div>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
      <div className="flex items-center justify-between text-on-surface-variant">
      <span className="font-label-md text-label-md">Rata-rata AI Match Rate</span>
      <span className="material-symbols-outlined text-tertiary-container text-[20px]">auto_awesome</span>
      </div>
      <div className="mt-space-sm flex items-baseline gap-2">
      <span className="font-headline-lg text-headline-lg text-on-surface">91.4%</span>
      <span className="font-label-sm text-label-sm text-on-tertiary-container font-semibold">Tepat Sasaran</span>
      </div>
      <div className="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
      <div className="bg-tertiary-container h-full rounded-full" style={{ width: "91%" }}></div>
      </div>
      </div>
      </div>
      {/* Filter and Control Bar */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
      <div className="flex flex-wrap items-center gap-space-sm">
      <div className="relative min-w-[240px]">
      <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
      <input className="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest shadow-sm transition-all" placeholder="Cari kandidat, no. tiket, skill..." type="text" />
      </div>
      {/* Filter Status Kebutuhan */}
      <div className="flex items-center gap-1.5 bg-surface-container-low px-2 py-1.5 rounded-lg shadow-sm">
      <span className="material-symbols-outlined text-outline text-[18px] ml-1">filter_alt</span>
      <select className="bg-transparent font-label-md text-label-md text-on-surface focus:outline-none pr-3 cursor-pointer" defaultValue="waiting">
      <option value="all">Semua Status Evaluasi</option>
      <option value="waiting">Menunggu Review (12)</option>
      <option value="screening">Screening Lolos</option>
      <option value="interview">Interview User</option>
      <option value="offering">Offering &amp; PO</option>
      <option value="done">Selesai / Terisi</option>
      </select>
      </div>
      {/* Filter Klien Korporat */}
      <div className="flex items-center gap-1.5 bg-surface-container-low px-2 py-1.5 rounded-lg shadow-sm">
      <span className="material-symbols-outlined text-outline text-[18px] ml-1">business</span>
      <select className="bg-transparent font-label-md text-label-md text-on-surface focus:outline-none pr-3 cursor-pointer" defaultValue="bca">
      <option value="all">Semua Enterprise Klien</option>
      <option value="bca">PT Bank Central Asia Tbk</option>
      <option value="telkomsel">PT Telkomsel Indonesia</option>
      <option value="mandiri">PT Bank Mandiri (Persero) Tbk</option>
      <option value="goto">PT GoTo Gojek Tokopedia Tbk</option>
      </select>
      </div>
      {/* AM In-Charge */}
      <div className="flex items-center gap-1.5 bg-surface-container-low px-2 py-1.5 rounded-lg shadow-sm">
      <span className="material-symbols-outlined text-outline text-[18px] ml-1">badge</span>
      <select className="bg-transparent font-label-md text-label-md text-on-surface focus:outline-none pr-3 cursor-pointer">
      <option value="all">Semua Account Manager</option>
      <option value="rian">Rian Hidayat</option>
      <option value="nurfadilah">Nurfadilah Pratama</option>
      <option value="doni">Doni Satrio</option>
      </select>
      </div>
      </div>
      <div className="flex items-center gap-2 text-on-surface-variant font-label-sm text-label-sm">
      <span>Menampilkan <strong>4</strong> dari <strong>12</strong> usulan kandidat aktif</span>
      <button className="p-1.5 rounded-lg hover:bg-surface-container text-on-surface transition-colors" title="Muat Ulang" type="button">
      <span className="material-symbols-outlined text-[18px]">sync</span>
      </button>
      </div>
      </div>
      {/* Multi-View Segmented Tabs */}
      <div className="flex items-center justify-between">
      <div className="inline-flex p-1 bg-surface-container rounded-full shadow-sm">
      <button className="px-5 py-2 rounded-full font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-all flex items-center gap-2" type="button">
      <span className="material-symbols-outlined text-[18px]">list_alt</span>
      <span>Daftar Requisisi Aktif</span>
      <span className="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface font-semibold text-[11px]">18</span>
      </button>
      <button className="px-5 py-2 rounded-full font-label-md text-label-md bg-surface-container-lowest text-primary font-semibold shadow-sm flex items-center gap-2 transition-all" type="button">
      <span className="material-symbols-outlined text-[18px]">how_to_reg</span>
      <span>Evaluasi Kandidat Diajukan</span>
      <span className="px-2 py-0.5 rounded-full bg-primary-container text-on-primary-container font-semibold text-[11px]">12</span>
      </button>
      <button className="px-5 py-2 rounded-full font-label-md text-label-md text-on-surface-variant hover:text-on-surface transition-all flex items-center gap-2" type="button">
      <span className="material-symbols-outlined text-[18px]">calendar_month</span>
      <span>Jadwal Interview &amp; Feedback</span>
      <span className="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface font-semibold text-[11px]">8</span>
      </button>
      </div>
      <div className="hidden md:flex items-center gap-space-sm text-on-surface-variant font-body-sm text-body-sm">
      <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Siap Interview</span>
      <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Butuh Evaluasi</span>
      <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-primary"></span> Diterima</span>
      </div>
      </div>
      {/* Main Working Grid: Data Table (8 cols) + Side Evaluation Drawer (4 cols) */}
      <div className="grid grid-cols-1 xl:grid-cols-12 gap-space-lg items-start">
      {/* Primary Data Table: Usulan Kandidat Menunggu Review */}
      <div className="xl:col-span-8 flex flex-col bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
      <div className="p-space-md bg-surface-container-low flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="material-symbols-outlined text-primary text-[20px]">assignment_turned_in</span>
      <span className="font-title-sm text-title-sm text-on-surface">Daftar Kandidat Diajukan Menunggu Review Klien</span>
      </div>
      <span className="font-label-sm text-label-sm px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant">SLA Respon Klien: 48 Jam Kerja</span>
      </div>
      <div className="overflow-x-auto w-full">
      <table className="w-full text-left border-collapse">
      <thead>
      <tr className="bg-surface-container-low/60 text-on-surface-variant font-label-md text-label-md">
      <th className="py-3 px-4">No. Tiket &amp; Tanggal</th>
      <th className="py-3 px-4">Profil Kandidat</th>
      <th className="py-3 px-4">Klien &amp; AM</th>
      <th className="py-3 px-4 text-center">AI Match</th>
      <th className="py-3 px-4">Komersial (Rate)</th>
      <th className="py-3 px-4">Status Approval</th>
      <th className="py-3 px-4 text-right">Aksi Evaluasi</th>
      </tr>
      </thead>
      <tbody className="font-body-md text-body-md divide-y divide-surface-container-low">
      {/* Row 1 (Selected Row) */}
      <tr className="bg-surface-container-low/40 hover:bg-surface-container transition-colors">
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-primary">SUB-2025-112</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">24 Feb 2025</span>
      <span className="font-label-sm text-[10px] text-error font-medium mt-1">SLA: 12 jam tersisa</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex items-start gap-3">
      <img className="w-10 h-10 rounded-full object-cover shrink-0 shadow-sm mt-0.5" data-alt="Portrait photo of Andika Wijaya, male Indonesian senior software engineer wearing smart casual navy shirt with clean background, professional corporate headshot" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCfwBQtnmYo2QUEQixO82Q8y-o3QamTc-BJSpgrq7i0O-wla60P48fG_eFm-40QMjrm0cClX77jvN8AePKK2h7pFcdDbvkGACsrD3aBlheUSBypsPAxal8-a-yDKM3bYGUQcg4W74sRuSdqCyjRV3ZM7v3sFv2s5QySIyHFwUFi71LtdyEKodTl3YeKkPp_H9YOf4WhZ4JqiAl-QEnUh-i__IW-Sup96yLtWUmShTmSaqIl5Lfiae6a" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold leading-snug">Andika Wijaya</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant truncate">Cloud DevOps AWS Lead</span>
      <div className="flex items-center gap-1 mt-1 flex-wrap">
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">AWS EKS</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Terraform</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">CI/CD</span>
      </div>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-medium text-on-surface">PT Bank Central Asia Tbk</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Divisi IT Infrastructure</span>
      <span className="font-label-sm text-label-sm text-primary mt-1 flex items-center gap-1">
      <span className="material-symbols-outlined text-[13px]">person</span>
      <span>Rian Hidayat (AM)</span>
      </span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top text-center">
      <div className="inline-flex flex-col items-center">
      <span className="font-title-sm text-title-sm font-bold text-tertiary-container bg-surface-container px-2.5 py-1 rounded-full shadow-sm">
                            94% Match
                          </span>
      <span className="font-label-sm text-[10px] text-on-surface-variant mt-0.5">8/8 Keahlian Kunci</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">Rp 25.000.000</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Per Bulan / All-in</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant mt-0.5">Kontrak 12 Bulan</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-900 font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
      <span>Menunggu Review HR Client</span>
      </span>
      </td>
      <td className="py-3.5 px-4 align-top text-right">
      <div className="flex flex-col gap-1 items-end">
      <button className="w-full flex items-center justify-center gap-1 h-8 px-3 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-sm text-label-sm font-medium transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[16px]">rate_review</span>
      <span>Beri Feedback</span>
      </button>
      <div className="flex items-center gap-1">
      <button className="h-7 px-2 rounded-lg bg-surface-container hover:bg-surface-variant text-on-surface font-label-sm text-label-sm transition-colors" title="Undang Langsung" type="button">
                              Undang
                            </button>
      <button className="h-7 px-2 rounded-lg bg-error-container text-on-error-container hover:bg-red-200 font-label-sm text-label-sm transition-colors" title="Minta Pengganti" type="button">
                              Tolak
                            </button>
      </div>
      </div>
      </td>
      </tr>
      {/* Row 2 */}
      <tr className="hover:bg-surface-container transition-colors">
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-primary">SUB-2025-108</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">23 Feb 2025</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex items-start gap-3">
      <img className="w-10 h-10 rounded-full object-cover shrink-0 shadow-sm mt-0.5" data-alt="Portrait photo of Putri Maharani, female Indonesian Senior React Native and Mobile Lead with glasses smiling in professional corporate setting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB-rVlvf9wvQL4HkBAb2hSSvsqp3jHilyH5Go-1PvXgrjPWoh4GybeX8IGTIV4oKBvZjpfKW-vqkPz_IWWs99kEzVSt3IpouQ5vVcD6ahTe1FrXpWuQfZVOfcZA1v4cyb9oCpybgjGqOocqimJTjvR7YikChmuMDKVMpAmV5aXXougzX5RpfzUm7Xt92EuifnJ_ezeRqY1L2eT7NOIYEjbx5uHiTIeokjqx53myypYoBTNUjMh2V5zi" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold leading-snug">Putri Maharani</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant truncate">Mobile Lead React Native</span>
      <div className="flex items-center gap-1 mt-1 flex-wrap">
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">React Native</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Redux Saga</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">TypeScript</span>
      </div>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-medium text-on-surface">PT Bank Mandiri (Persero)</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Digital Banking Tribe</span>
      <span className="font-label-sm text-label-sm text-primary mt-1 flex items-center gap-1">
      <span className="material-symbols-outlined text-[13px]">person</span>
      <span>Doni Satrio (AM)</span>
      </span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top text-center">
      <div className="inline-flex flex-col items-center">
      <span className="font-title-sm text-title-sm font-bold text-tertiary-container bg-surface-container px-2.5 py-1 rounded-full shadow-sm">
                            89% Match
                          </span>
      <span className="font-label-sm text-[10px] text-on-surface-variant mt-0.5">7/8 Keahlian Kunci</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">Rp 23.500.000</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Per Bulan / All-in</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant mt-0.5">Kontrak 6 Bulan</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-900 font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
      <span>Lolos Review - Siap Interview</span>
      </span>
      </td>
      <td className="py-3.5 px-4 align-top text-right">
      <div className="flex flex-col gap-1 items-end">
      <button className="w-full flex items-center justify-center gap-1 h-8 px-3 rounded-lg bg-surface-container text-primary hover:bg-surface-variant font-label-sm text-label-sm font-medium transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[16px]">calendar_add_on</span>
      <span>Undang Interview</span>
      </button>
      <button className="h-7 px-2 rounded-lg bg-transparent text-on-surface-variant hover:bg-surface-container font-label-sm text-label-sm transition-colors" type="button">
                            Lihat Feedback
                          </button>
      </div>
      </td>
      </tr>
      {/* Row 3 */}
      <tr className="hover:bg-surface-container transition-colors">
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-primary">SUB-2025-095</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">22 Feb 2025</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex items-start gap-3">
      <img className="w-10 h-10 rounded-full object-cover shrink-0 shadow-sm mt-0.5" data-alt="Professional studio portrait of Fajar Nugraha, Indonesian Senior Java Spring Boot Architect in charcoal suit with tech office ambiance" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC7Z9Z_E1ISQYVQTWYo5nei8kdOJL6Mdb7iHu2y-hJ0kJ9oHpDbe7xAImh1izX88GiIL6TW-1AWDqhyRDw5oNjLd2uurKgHyAi0Sn0bhZyq1kxie2NrW17rvBfxieFS9d_0SRsC609YaF-cp9MbawlpEc2JqcqKj6Ql4baKf7n7c2Sgka594FTtQeBLyedQXKWdqq12Ez7PelHglqXiIvdf4KiXpjPhSJDPm96rdqyGL77MYEjmd4ar" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold leading-snug">Fajar Nugraha</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant truncate">Enterprise Java Backend Lead</span>
      <div className="flex items-center gap-1 mt-1 flex-wrap">
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Spring Boot</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Apache Kafka</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">PostgreSQL</span>
      </div>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-medium text-on-surface">PT Telkomsel Indonesia</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Core Payment Services</span>
      <span className="font-label-sm text-label-sm text-primary mt-1 flex items-center gap-1">
      <span className="material-symbols-outlined text-[13px]">person</span>
      <span>Nurfadilah Pratama (AM)</span>
      </span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top text-center">
      <div className="inline-flex flex-col items-center">
      <span className="font-title-sm text-title-sm font-bold text-tertiary-container bg-surface-container px-2.5 py-1 rounded-full shadow-sm">
                            96% Match
                          </span>
      <span className="font-label-sm text-[10px] text-on-surface-variant mt-0.5">Semua Kriteria Sesuai</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">Rp 28.000.000</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Per Bulan / All-in</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant mt-0.5">Kontrak 12 Bulan</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-900 font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-primary-container"></span>
      <span>Dijadwalkan Interview User</span>
      </span>
      </td>
      <td className="py-3.5 px-4 align-top text-right">
      <div className="flex flex-col gap-1 items-end">
      <span className="font-label-sm text-label-sm text-primary font-semibold">27 Feb 2025, 14:00</span>
      <span className="font-body-sm text-[11px] text-on-surface-variant">via MS Teams Meeting</span>
      <button className="mt-1 h-7 px-2 rounded-lg bg-surface-container hover:bg-surface-variant text-on-surface font-label-sm text-label-sm transition-colors" type="button">
                            Edit Jadwal
                          </button>
      </div>
      </td>
      </tr>
      {/* Row 4 */}
      <tr className="hover:bg-surface-container transition-colors">
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-primary">SUB-2025-089</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">19 Feb 2025</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex items-start gap-3">
      <img className="w-10 h-10 rounded-full object-cover shrink-0 shadow-sm mt-0.5" data-alt="Portrait photo of Devina Prasetia, female Indonesian Senior QA Automation Lead in clean office environment with tablet" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAuB5LA7wT6XEy4t8i6Tz9kCF6Om6RecR9bvFWs4QoKn17J7olfS383NWetLe7utcWhebsutuJurK7JltWLODSy69GR0pKnMtTBxpuv9lcnAn1WqJm4ANidixO4fi3LSdDtL4FvHO3AI6OxT4R_IIueTBT9esazEz6qlScZWmUU99HLDxnnxdpzNPLVmqCyUeB13PbwekVD3ggD7yDjtatcAm4OmqgEnjEaREVLpH5zZ2Unnz7X9Y4H" />
      <div className="flex flex-col min-w-0">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold leading-snug">Devina Prasetia</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant truncate">Lead QA Automation &amp; SDET</span>
      <div className="flex items-center gap-1 mt-1 flex-wrap">
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Cypress</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Appium</span>
      <span className="font-label-sm text-[10px] bg-surface-container px-1.5 py-0.5 rounded text-on-surface-variant">Katalon</span>
      </div>
      </div>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-medium text-on-surface">PT Bank Central Asia Tbk</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">QA Center of Excellence</span>
      <span className="font-label-sm text-label-sm text-primary mt-1 flex items-center gap-1">
      <span className="material-symbols-outlined text-[13px]">person</span>
      <span>Rian Hidayat (AM)</span>
      </span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top text-center">
      <div className="inline-flex flex-col items-center">
      <span className="font-title-sm text-title-sm font-bold text-tertiary-container bg-surface-container px-2.5 py-1 rounded-full shadow-sm">
                            92% Match
                          </span>
      <span className="font-label-sm text-[10px] text-on-surface-variant mt-0.5">Lolos Uji Teknis</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <div className="flex flex-col">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">Rp 21.000.000</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Per Bulan / All-in</span>
      <span className="font-label-sm text-label-sm text-on-surface-variant mt-0.5">Kontrak 12 Bulan</span>
      </div>
      </td>
      <td className="py-3.5 px-4 align-top">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-950 font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-emerald-700"></span>
      <span>Diterima - Butuh PO</span>
      </span>
      </td>
      <td className="py-3.5 px-4 align-top text-right">
      <div className="flex flex-col gap-1 items-end">
      <button className="w-full flex items-center justify-center gap-1 h-8 px-3 rounded-lg bg-tertiary-container text-on-tertiary font-label-sm text-label-sm font-medium transition-all shadow-sm" type="button">
      <span className="material-symbols-outlined text-[16px]">upload_file</span>
      <span>Unggah PO Klien</span>
      </button>
      <span className="font-label-sm text-[10px] text-on-surface-variant">Quotation Approved</span>
      </div>
      </td>
      </tr>
      </tbody>
      </table>
      </div>
      {/* Table Pagination Footer */}
      <div className="p-space-md bg-surface-container-low flex flex-col sm:flex-row items-center justify-between gap-space-sm">
      <span className="font-body-sm text-body-sm text-on-surface-variant">
                  Menampilkan 1-4 dari total 12 kandidat diajukan untuk ditinjau
                </span>
      <div className="flex items-center gap-1">
      <button className="p-2 rounded-lg bg-surface-container text-outline opacity-50 cursor-not-allowed" disabled type="button">
      <span className="material-symbols-outlined text-[18px]">chevron_left</span>
      </button>
      <button className="w-8 h-8 rounded-lg bg-primary text-on-primary font-label-sm text-label-sm font-semibold" type="button">1</button>
      <button className="w-8 h-8 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant font-label-sm text-label-sm transition-colors" type="button">2</button>
      <button className="w-8 h-8 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant font-label-sm text-label-sm transition-colors" type="button">3</button>
      <button className="p-2 rounded-lg bg-surface-container text-on-surface hover:bg-surface-variant transition-colors" type="button">
      <span className="material-symbols-outlined text-[18px]">chevron_right</span>
      </button>
      </div>
      </div>
      </div>
      {/* Side Evaluation Panel / HR Client Quick Review Drawer (4 cols) */}
      <div className="xl:col-span-4 flex flex-col gap-space-md">
      {/* Active Review Header Card */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col gap-space-sm">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm uppercase tracking-wider text-primary font-bold">Panel Evaluasi Aktif</span>
      <span className="font-label-sm text-label-sm bg-amber-50 text-amber-800 px-2 py-0.5 rounded-full font-semibold">Kandidat Fokus</span>
      </div>
      <div className="flex items-center gap-space-sm p-space-sm bg-surface-container-low rounded-xl">
      <img className="w-12 h-12 rounded-full object-cover shadow-sm" data-alt="Close-up portrait of Andika Wijaya, Lead AWS Cloud DevOps candidate" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBTAzwB5PINIUWqN2t2wrvFCbtFPkxkG0ce5mFMN4xUCHYc9QB8DJbck_u-HDk0JCIWYP-t6aLe7MGUjzm_mTtCReBAu5AEdLQ1fvZyoGb3TI0ga8PXP4Ueu4Yk5-7t59RSD0TDc3aWyz-tXnB1BF35LAug4X_UGR0bDFeRF78-Ou9YO8pYjvGSb8i5_wYvRGXVmLFYJPzLkXGQFG6ulsA3wMv8ywrpIPlOyUndGgFdGxM9X2T0PXvn" />
      <div className="flex flex-col min-w-0">
      <h3 className="font-title-sm text-title-sm text-on-surface font-semibold truncate">Andika Wijaya</h3>
      <p className="font-body-sm text-body-sm text-on-surface-variant">AWS DevOps Lead (Pengalaman 7 Tahun)</p>
      <div className="flex items-center gap-2 mt-1">
      <a className="font-label-sm text-label-sm text-primary underline flex items-center gap-0.5" href="#">
      <span className="material-symbols-outlined text-[14px]">description</span>
      <span>Lihat CV Lengkap (PDF)</span>
      </a>
      </div>
      </div>
      </div>
      {/* Form Quick Feedback HR Klien */}
      <form className="flex flex-col gap-space-sm mt-2" onSubmit={(e) => { e.preventDefault(); alert("Feedback berhasil disimpan dan diteruskan ke Account Manager!"); }}>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium flex items-center justify-between">
      <span>Rating Kesesuaian Profil Teknis</span>
      <span className="text-amber-500 font-semibold">4.5 / 5.0</span>
      </label>
      <div className="flex items-center gap-1 text-amber-400 cursor-pointer py-1">
      <span className="material-symbols-outlined text-[24px]" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span className="material-symbols-outlined text-[24px]" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span className="material-symbols-outlined text-[24px]" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span className="material-symbols-outlined text-[24px]" style={{ fontVariationSettings: "'FILL' 1" }}>star</span>
      <span className="material-symbols-outlined text-[24px]" style={{ fontVariationSettings: "'FILL' 1" }}>star_half</span>
      </div>
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">
                      Catatan &amp; Komentar Evaluasi Teknis Klien
                    </label>
      <textarea className="w-full p-2.5 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest shadow-sm resize-none" placeholder="Tuliskan catatan teknis untuk AM Intellisys (misal: 'Sertifikasi AWS Solutions Architect terverifikasi, mohon jadwalkan interview teknis dengan Lead DevOps BCA')..." rows="3" />
      </div>
      <div className="flex flex-col gap-2 pt-1">
      <span className="font-label-md text-label-md text-on-surface font-medium">Tindakan Selanjutnya (Checklist)</span>
      <label className="flex items-center gap-2.5 cursor-pointer text-on-surface font-body-sm text-body-sm">
      <input defaultChecked className="w-4 h-4 rounded text-primary accent-primary cursor-pointer" type="checkbox" />
      <span>Lanjut ke sesi Interview User Klien</span>
      </label>
      <label className="flex items-center gap-2.5 cursor-pointer text-on-surface font-body-sm text-body-sm">
      <input defaultChecked className="w-4 h-4 rounded text-primary accent-primary cursor-pointer" type="checkbox" />
      <span>Kompensasi &amp; Rate Sesuai Budget Unit Kerja</span>
      </label>
      <label className="flex items-center gap-2.5 cursor-pointer text-on-surface font-body-sm text-body-sm">
      <input className="w-4 h-4 rounded text-primary accent-primary cursor-pointer" type="checkbox" />
      <span>Minta Revisi Portofolio / Bukti Sertifikasi Khusus</span>
      </label>
      </div>
      <div className="flex items-center gap-2 pt-space-xs">
      <button className="flex-1 flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-semibold transition-all shadow-sm" type="submit">
      <span className="material-symbols-outlined text-[18px]">check_circle</span>
      <span>Kirim Feedback Klien</span>
      </button>
      <button className="p-2.5 rounded-xl bg-surface-container text-on-surface hover:bg-surface-variant transition-colors" title="Simpan sebagai Draf" type="button">
      <span className="material-symbols-outlined text-[20px]">bookmark</span>
      </button>
      </div>
      </form>
      </div>
      {/* Dokumen & Legalitas Terkait Requisisi Ini */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col gap-space-sm">
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm uppercase tracking-wider text-outline font-bold">Status Dokumen &amp; Komersial</span>
      <span className="material-symbols-outlined text-outline text-[18px]">lock</span>
      </div>
      <div className="space-y-2">
      {/* Quotation Item */}
      <div className="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low">
      <div className="flex items-center gap-2.5">
      <span className="material-symbols-outlined text-emerald-600 text-[20px]">verified</span>
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-on-surface">Quotation #QT-2025-043</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Nilai Kontrak: Rp 300.000.000 / thn</span>
      </div>
      </div>
      <span className="font-label-sm text-label-sm bg-emerald-100 text-emerald-900 font-semibold px-2 py-0.5 rounded-full">
                      Disetujui
                    </span>
      </div>
      {/* Draft PO Item */}
      <div className="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low">
      <div className="flex items-center gap-2.5">
      <span className="material-symbols-outlined text-amber-600 text-[20px]">history_edu</span>
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-on-surface">Draft PO #PO-8821</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Procurement PT BCA Tbk</span>
      </div>
      </div>
      <span className="font-label-sm text-label-sm bg-amber-100 text-amber-900 font-semibold px-2 py-0.5 rounded-full">
                      Menunggu TTD
                    </span>
      </div>
      {/* NDA Item */}
      <div className="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-low">
      <div className="flex items-center gap-2.5">
      <span className="material-symbols-outlined text-primary text-[20px]">gavel</span>
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-on-surface">Template NDA Talent</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Perlindungan Kerahasiaan Data</span>
      </div>
      </div>
      <span className="font-label-sm text-label-sm bg-surface-container text-primary font-semibold px-2 py-0.5 rounded-full">
                      Tersedia
                    </span>
      </div>
      </div>
      <div className="pt-2 flex items-center justify-between text-on-surface-variant font-label-sm text-label-sm">
      <span>Kontrak Induk (MSA): No. 091/DIR-BCA/2023</span>
      <a className="text-primary hover:underline font-semibold" href="#">Detail MSA</a>
      </div>
      </div>
      {/* AM Contact Support Card */}
      <div className="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex items-center justify-between">
      <div className="flex items-center gap-3">
      <img className="w-10 h-10 rounded-full object-cover shadow-sm" data-alt="Portrait photo of Rian Hidayat, male Indonesian Account Manager in corporate suit with smiling friendly expression" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAImPAuOVikVuUfM4aWJeZiaYHaPcgjlxEQROTfPYTO-Vv1loHQEUUzDz2gWnse8lPKrxTeHCWV6ld8_ceFFNjagwoYywvcJhquifc_VqT09mDPr3VpRxnKqmn0BMdr87719P955KTzMkFQKsKcvtwmcBdqpPxsn_Q1QtIe5JMOE86_J1A1nGCucJcSHSCTL-qA0sfN5Ygqos9qdJiHjNkaty7XuphmiEKpUtudJdXIZmbRucAeGx9x" />
      <div className="flex flex-col">
      <span className="font-label-md text-label-md font-semibold text-on-surface">Rian Hidayat</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Account Manager Intellisys</span>
      <span className="font-label-sm text-[11px] text-tertiary-container font-medium flex items-center gap-1">
      <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Respon cepat via WhatsApp
                    </span>
      </div>
      </div>
      <button className="h-9 px-3 rounded-lg bg-surface-container hover:bg-surface-variant text-primary font-label-sm text-label-sm font-semibold flex items-center gap-1 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">chat</span>
      <span>Kontak AM</span>
      </button>
      </div>
      </div>
      </div>
      </div>
      {/* Modal: + Buat Permintaan Kebutuhan Baru (Job Request) */}
      <div className={`${modalOpen ? "flex" : "hidden"} fixed inset-0 z-50 items-center justify-center p-4 bg-on-background/60 backdrop-blur-sm`} onClick={(e) => e.target === e.currentTarget && setModalOpen(false)}>
      <div className="bg-surface-container-lowest w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[921px]">
      {/* Modal Header */}
      <div className="p-space-lg bg-surface-container-low flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="material-symbols-outlined text-primary text-[24px]">post_add</span>
      <div>
      <h2 className="font-title-md text-title-md text-on-surface font-semibold">Buat Permintaan Kebutuhan Baru (Job Request)</h2>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Kirimkan spesifikasi peran IT untuk diproses oleh Tim Rekrutmen &amp; Talent Intellisys</p>
      </div>
      </div>
      <button className="p-2 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors" onClick={() => setModalOpen(false)} type="button">
      <span className="material-symbols-outlined text-[20px]">close</span>
      </button>
      </div>
      {/* Modal Body Form */}
      <form className="p-space-lg overflow-y-auto space-y-space-md flex-1" onSubmit={(e) => { e.preventDefault(); alert("Permintaan Kebutuhan berhasil diterbitkan! Account Manager akan segera meninjau."); setModalOpen(false); }}>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-space-md">
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Judul Posisi / Role IT</label>
      <input className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest shadow-sm" placeholder="Contoh: Senior Flutter Developer" required type="text" />
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Klien &amp; Unit Kerja</label>
      <select className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest shadow-sm">
      <option value="bca">PT Bank Central Asia Tbk - IT Infrastructure</option>
      <option value="mandiri">PT Bank Mandiri (Persero) - Digital Banking</option>
      <option value="telkomsel">PT Telkomsel Indonesia - Core Telco</option>
      </select>
      </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-space-md">
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Jumlah Headcount</label>
      <input className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest shadow-sm" min="1" type="number" defaultValue="1" />
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Durasi Kontrak</label>
      <select className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest shadow-sm" defaultValue="12 Bulan (1 Tahun)">
      <option>6 Bulan (Perpanjangan)</option>
      <option>12 Bulan (1 Tahun)</option>
      <option>24 Bulan (2 Tahun)</option>
      </select>
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Lokasi Kerja</label>
      <select className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest shadow-sm">
      <option>Hybrid (Jakarta Pusat)</option>
      <option>On-site (Wisma BCA BSD)</option>
      <option>100% Remote</option>
      </select>
      </div>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-space-md">
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Ekspektasi Budget Bulanan</label>
      <input className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest shadow-sm" placeholder="Rp 22.000.000 - Rp 27.000.000" type="text" />
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Target Mulai Bekerja (On-Boarding)</label>
      <input className="w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest shadow-sm" type="date" defaultValue="2025-04-01" />
      </div>
      </div>
      <div className="flex flex-col gap-1">
      <label className="font-label-md text-label-md text-on-surface font-medium">Kualifikasi Utama &amp; Skill Mandatori</label>
      <textarea className="w-full p-2.5 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest shadow-sm resize-none" placeholder="Contoh: Menguasai Golang Microservices, pengalaman implementasi Redis Caching, Kafka, dan minimal 4 tahun di industri fintech/perbankan..." rows="3" />
      </div>
      <div className="p-3 bg-surface-container-low rounded-xl flex items-center justify-between text-body-sm text-on-surface-variant">
      <div className="flex items-center gap-2">
      <span className="material-symbols-outlined text-primary text-[20px]">info</span>
      <span>Permintaan baru akan memicu proses AI Matching dan penyaringan kandidat dalam 1x24 jam.</span>
      </div>
      </div>
      {/* Modal Actions */}
      <div className="flex items-center justify-end gap-space-sm pt-2">
      <button className="h-10 px-4 rounded-xl bg-surface-container text-on-surface hover:bg-surface-variant font-label-md text-label-md transition-colors" onClick={() => setModalOpen(false)} type="button">
                  Batalkan
                </button>
      <button className="h-10 px-6 rounded-xl bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md font-semibold transition-all shadow-md flex items-center gap-2" type="submit">
      <span className="material-symbols-outlined text-[18px]">send</span>
      <span>Terbitkan Requisisi</span>
      </button>
      </div>
      </form>
      </div>
      </div>
      </div>
    </>
  );
}
