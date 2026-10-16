import { useState } from "react";

export default function AiMatchRecommendation() {
  const [minMatch, setMinMatch] = useState(85);

  return (
    <>
      <div className="flex flex-col w-full">
      <div className="px-space-lg py-space-lg flex flex-col gap-space-lg max-w-[1600px] w-full mx-auto">
      {/* Header Page Context */}
      <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-space-md">
      <div className="flex flex-col gap-space-xs">
      <div className="flex items-center gap-2">
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container text-primary font-label-sm text-label-sm uppercase tracking-wider font-semibold">
      <span className="material-symbols-outlined text-[14px]">psychology</span>
                  Intellisys Match Engine v4.2
                </span>
      <span className="text-outline text-label-sm font-label-sm">|</span>
      <span className="text-on-surface-variant font-label-sm text-label-sm">Sinkronisasi Terakhir: Hari ini, 09:41 WIB</span>
      </div>
      <h1 className="font-headline-lg text-headline-lg text-on-surface tracking-tight">Rekomendasi Kandidat AI &amp; Screening Talent Pool</h1>
      <p className="font-body-md text-body-md text-on-surface-variant max-w-3xl">Pencocokan cerdas profil CV, keahlian teknis, ekspektasi rate/gaji, dan riwayat proyek terhadap kebutuhan klien.</p>
      </div>
      <div className="flex items-center gap-space-sm self-start lg:self-auto">
      <button className="h-10 px-4 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-2 transition-all" type="button">
      <span className="material-symbols-outlined text-[18px]">history</span>
      <span>Riwayat Pencocokan</span>
      </button>
      <button className="h-10 px-4 rounded-xl bg-primary text-on-primary hover:bg-primary-container font-label-md text-label-md flex items-center gap-2 shadow-sm transition-all" type="button">
      <span className="material-symbols-outlined text-[18px]">tune</span>
      <span>Kalibrasi AI Engine</span>
      </button>
      </div>
      </div>
      {/* Client Requisition Context Card */}
      <div className="relative overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm p-space-lg flex flex-col gap-space-md">
      <div className="absolute -right-16 -top-16 w-64 h-64 bg-primary/5 rounded-full pointer-events-none blur-2xl"></div>
      <div className="flex flex-col xl:flex-row xl:items-center justify-between gap-space-md relative z-10">
      <div className="flex items-start gap-space-md min-w-0">
      <div className="w-12 h-12 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-sm">
      <span className="material-symbols-outlined text-[26px]">account_balance</span>
      </div>
      <div className="flex flex-col min-w-0">
      <div className="flex flex-wrap items-center gap-2 mb-1">
      <span className="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-semibold">REQ-2025-084</span>
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
      <span className="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                      Urgent SLA (Tersisa 2 Hari)
                    </span>
      <span className="text-outline text-label-sm font-label-sm">• SLA Pengajuan: 08 Nov 2025</span>
      </div>
      <h2 className="font-headline-sm text-headline-sm text-on-surface truncate">Senior Fullstack Golang &amp; React Developer</h2>
      <p className="font-body-md text-body-md text-on-surface-variant flex items-center gap-1.5 mt-0.5">
      <span>PT Bank Mandiri (Persero) Tbk</span>
      <span className="text-outline">•</span>
      <span>Digital Banking Division</span>
      <span className="text-outline">•</span>
      <span>Penempatan: Plaza Mandiri, Jakarta Selatan (Hybrid)</span>
      </p>
      </div>
      </div>
      <div className="flex flex-wrap items-center gap-3 xl:self-center">
      <div className="px-4 py-2.5 rounded-lg bg-surface-container-low flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Kebutuhan</span>
      <span className="font-title-sm text-title-sm text-on-surface font-bold">2 Orang Talent</span>
      </div>
      <div className="px-4 py-2.5 rounded-lg bg-surface-container-low flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Rentang Alokasi Gaji</span>
      <span className="font-title-sm text-title-sm text-primary font-bold">Rp 22.000.000 - Rp 28.000.000<span className="font-body-sm text-body-sm text-on-surface-variant font-normal">/bln</span></span>
      </div>
      <div className="px-4 py-2.5 rounded-lg bg-surface-container-low flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Durasi Kontrak</span>
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">12 Bulan (Opsi Perpanjangan)</span>
      </div>
      <button className="h-10 px-3.5 rounded-lg bg-secondary-container hover:bg-surface-container text-on-secondary-fixed font-label-md text-label-md flex items-center gap-1.5 transition-colors self-end xl:self-center" title="Ubah Requisisi" type="button">
      <span className="material-symbols-outlined text-[18px]">swap_horiz</span>
      <span>Ganti Requisisi</span>
      </button>
      </div>
      </div>
      {/* Quick Requirements Tags */}
      <div className="pt-3 flex flex-wrap items-center gap-2 bg-surface-container-low/50 px-3.5 py-2.5 rounded-lg">
      <span className="font-label-sm text-label-sm text-on-surface-variant font-semibold">Stack Wajib Klien:</span>
      <span className="px-2 py-0.5 rounded-md bg-surface-container-lowest text-on-surface font-label-sm text-label-sm shadow-xs font-medium">Golang (Microservices)</span>
      <span className="px-2 py-0.5 rounded-md bg-surface-container-lowest text-on-surface font-label-sm text-label-sm shadow-xs font-medium">React.js / Next.js</span>
      <span className="px-2 py-0.5 rounded-md bg-surface-container-lowest text-on-surface font-label-sm text-label-sm shadow-xs font-medium">PostgreSQL High-Availability</span>
      <span className="px-2 py-0.5 rounded-md bg-surface-container-lowest text-on-surface font-label-sm text-label-sm shadow-xs font-medium">Docker &amp; K8s</span>
      <span className="px-2 py-0.5 rounded-md bg-surface-container-lowest text-on-surface font-label-sm text-label-sm shadow-xs font-medium">Apache Kafka</span>
      <div className="ml-auto flex items-center gap-1.5 text-on-tertiary-container font-label-sm text-label-sm font-semibold">
      <span className="material-symbols-outlined text-[16px]">verified</span>
      <span>9 Kandidat Terverifikasi Memenuhi Syarat Minimal</span>
      </div>
      </div>
      </div>
      {/* Filter & Multifaceted Search Component */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md flex flex-col gap-space-md">
      <div className="grid grid-cols-1 md:grid-cols-12 gap-space-sm items-center">
      {/* Search bar */}
      <div className="md:col-span-5 relative">
      <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">person_search</span>
      <input className="w-full h-10 pl-10 pr-4 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container transition-all" placeholder="Cari nama talent, sertifikasi, atau universitas..." type="text" defaultValue="Senior Golang" />
      </div>
      {/* Availability Filter */}
      <div className="md:col-span-3">
      <div className="relative">
      <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">event_repeat</span>
      <select className="w-full h-10 pl-9 pr-8 rounded-lg bg-surface-container-low font-body-sm text-body-sm text-on-surface focus:outline-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary-container appearance-none cursor-pointer" defaultValue="ready">
      <option value="all">Semua Ketersediaan</option>
      <option value="ready">Langsung Siap (Bench/Tersedia Segera)</option>
      <option value="14days">Notice ≤ 14 Hari</option>
      <option value="30days">Notice 1 Bulan</option>
      </select>
      <span className="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
      </div>
      </div>
      {/* Minimum AI Match Slider Simulator */}
      <div className="md:col-span-4 flex items-center gap-space-sm bg-surface-container-low px-3 py-1.5 rounded-lg">
      <span className="material-symbols-outlined text-primary text-[20px]">auto_awesome</span>
      <div className="flex-1 flex flex-col">
      <div className="flex items-center justify-between text-label-sm font-label-sm">
      <span className="text-on-surface-variant">Min. AI Match Score:</span>
      <span className="font-bold text-primary" >{minMatch}%+</span>
      </div>
      <input className="w-full accent-primary h-1.5 cursor-pointer bg-surface-container" max="95" min="70" step="5" type="range" value={minMatch} onChange={(e) => setMinMatch(Number(e.target.value))} />
      </div>
      <span className="px-2 py-0.5 rounded bg-surface-container-lowest font-label-sm text-label-sm text-primary font-bold shadow-xs">High</span>
      </div>
      </div>
      {/* Quick Skill Badges Filter (Toggles) */}
      <div className="flex flex-wrap items-center gap-2 pt-1">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Penyaring Keahlian:</span>
      <button className="px-3 py-1 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center gap-1.5 shadow-xs" type="button">
      <span>Golang</span>
      <span className="material-symbols-outlined text-[14px]">check</span>
      </button>
      <button className="px-3 py-1 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center gap-1.5 shadow-xs" type="button">
      <span>React.js</span>
      <span className="material-symbols-outlined text-[14px]">check</span>
      </button>
      <button className="px-3 py-1 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center gap-1.5 shadow-xs" type="button">
      <span>PostgreSQL</span>
      <span className="material-symbols-outlined text-[14px]">check</span>
      </button>
      <button className="px-3 py-1 rounded-full bg-surface-container text-on-surface-variant hover:bg-surface-container-high font-label-sm text-label-sm flex items-center gap-1 transition-colors" type="button">
      <span>Docker</span>
      <span className="material-symbols-outlined text-[14px]">add</span>
      </button>
      <button className="px-3 py-1 rounded-full bg-surface-container text-on-surface-variant hover:bg-surface-container-high font-label-sm text-label-sm flex items-center gap-1 transition-colors" type="button">
      <span>Kubernetes</span>
      <span className="material-symbols-outlined text-[14px]">add</span>
      </button>
      <button className="px-3 py-1 rounded-full bg-surface-container text-on-surface-variant hover:bg-surface-container-high font-label-sm text-label-sm flex items-center gap-1 transition-colors" type="button">
      <span>Kafka</span>
      <span className="material-symbols-outlined text-[14px]">add</span>
      </button>
      <button className="text-primary hover:underline font-label-sm text-label-sm ml-auto flex items-center gap-1" type="button">
      <span className="material-symbols-outlined text-[14px]">refresh</span>
      <span>Reset Filter</span>
      </button>
      </div>
      </div>
      {/* Main Workspace: Candidate Cards Grid (8 cols) & Sidebar Parameter Widget (4 cols) */}
      <div className="grid grid-cols-1 xl:grid-cols-12 gap-space-lg items-start">
      {/* CANDIDATES LIST COLUMN */}
      <div className="xl:col-span-8 flex flex-col gap-space-lg">
      {/* Section Bar Info */}
      <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="font-title-sm text-title-sm text-on-surface font-semibold">Kandidat Terpilih Algoritma AI</span>
      <span className="px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-sm text-label-sm font-bold">3 Kandidat Unggulan</span>
      </div>
      <div className="flex items-center gap-2 text-on-surface-variant font-label-sm text-label-sm">
      <span>Urutkan:</span>
      <button className="font-semibold text-primary flex items-center gap-0.5">Skor Kecocokan AI Teratas <span className="material-symbols-outlined text-[16px]">arrow_downward</span></button>
      </div>
      </div>
      {/* CARD 1: TOP PICK - 96% MATCH */}
      <div className="relative bg-surface-container-lowest rounded-xl shadow-md p-space-lg flex flex-col gap-space-md transition-all hover:shadow-lg group">
      {/* Top Badge Ribbon */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm">
      <div className="flex items-center gap-2">
      <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-tertiary-container text-tertiary-fixed font-label-sm text-label-sm font-bold shadow-xs">
      <span className="material-symbols-outlined text-[16px]" style={{ fontVariationSettings: "'FILL' 1" }}>stars</span>
                      Rekomendasi Utama (Top Pick)
                    </span>
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-low text-on-tertiary-container font-label-sm text-label-sm font-semibold">
      <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
                      Siap Ditempatkan (Bench Intellisys)
                    </span>
      </div>
      <div className="flex items-center gap-2">
      <span className="text-on-surface-variant font-label-sm text-label-sm">Talent ID: #INT-9921</span>
      {/* Score Badge */}
      <div className="px-3 py-1 rounded-full bg-surface-container-low flex items-center gap-1.5 text-on-tertiary-container font-bold font-title-sm text-title-sm">
      <span className="material-symbols-outlined text-[18px]">auto_awesome</span>
      <span>96% Sangat Sesuai</span>
      </div>
      </div>
      </div>
      {/* Profile Main Data */}
      <div className="flex flex-col sm:flex-row items-start gap-space-md">
      <img className="w-16 h-16 rounded-xl object-cover shadow-sm ring-2 ring-surface-container-high shrink-0" data-alt="Professional Indonesian male software engineer in his late 20s wearing modern glasses and casual corporate navy collared shirt with soft studio lighting and corporate clean aesthetic." src="https://lh3.googleusercontent.com/aida-public/AB6AXuAd4gvs0zlDwC8r2jeVS3cI-6Dt4SRrF2n54ett2XwuyDaGlDZdVmCn0lvnoK05pqY8MWv64hhr71TF3eOuAKlxIMZHqnrNIXjkFv2WdQ7BIp2UqPoGzHPxmOa8Jqd0rUws9R_mZoV5LLU3RezXQllx3trag3LHSXYvjJ33tAXwHd9KIz1Os6ISnNTvQwN72_Db1RCBbMAO5uN1d72yUr3Mo5CzaQOBBv9Ay75TMfnwdEeJ9Kqa22PO" />
      <div className="flex-1 flex flex-col min-w-0">
      <div className="flex flex-wrap items-baseline justify-between gap-1">
      <h3 className="font-headline-sm text-headline-sm text-on-surface font-bold group-hover:text-primary transition-colors">Dimas Aditya, S.Kom</h3>
      <span className="font-title-sm text-title-sm text-primary font-bold">Rp 24.500.000 <span className="font-body-sm text-body-sm text-on-surface-variant font-normal">/bulan (Sesuai Budget)</span></span>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Senior Backend &amp; Cloud Specialist • 5.5 Tahun Pengalaman Relevan • Lulusan Ilmu Komputer UI</p>
      {/* Tech tags */}
      <div className="flex flex-wrap gap-1.5 mt-2.5">
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Golang (Microservices)</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">React &amp; Redux</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">PostgreSQL Sharding</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Docker &amp; K8s</span>
      <span className="px-2 py-0.5 rounded bg-surface-container-high font-label-sm text-label-sm text-secondary font-medium">Kafka Enterprise</span>
      <span className="px-2 py-0.5 rounded bg-surface-container-high font-label-sm text-label-sm text-secondary font-medium">Redis Caching</span>
      </div>
      </div>
      </div>
      {/* AI Match Dimensional Breakdown Bar */}
      <div className="p-space-sm rounded-lg bg-surface-container-low flex flex-col gap-space-xs">
      <span className="font-label-sm text-label-sm text-on-surface font-semibold flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[16px] text-primary">analytics</span>
                    Breakdown Parameter AI Match:
                  </span>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-2 pt-1">
      <div className="bg-surface-container-lowest p-2 rounded flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Tech Stack Fit</span>
      <div className="flex items-center justify-between mt-1">
      <div className="w-full bg-surface-container rounded-full h-1.5 mr-2">
      <div className="bg-tertiary-container h-1.5 rounded-full" style={{ width: "98%" }}></div>
      </div>
      <span className="font-label-sm text-label-sm font-bold text-on-surface">98%</span>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-2 rounded flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Kesesuaian Rate</span>
      <div className="flex items-center justify-between mt-1">
      <div className="w-full bg-surface-container rounded-full h-1.5 mr-2">
      <div className="bg-tertiary-container h-1.5 rounded-full" style={{ width: "95%" }}></div>
      </div>
      <span className="font-label-sm text-label-sm font-bold text-on-surface">95%</span>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-2 rounded flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Ketersediaan</span>
      <div className="flex items-center justify-between mt-1">
      <div className="w-full bg-surface-container rounded-full h-1.5 mr-2">
      <div className="bg-tertiary-container h-1.5 rounded-full" style={{ width: "100%" }}></div>
      </div>
      <span className="font-label-sm text-label-sm font-bold text-on-surface">100%</span>
      </div>
      </div>
      <div className="bg-surface-container-lowest p-2 rounded flex flex-col">
      <span className="font-label-sm text-label-sm text-on-surface-variant">Culture &amp; Soft Skill</span>
      <div className="flex items-center justify-between mt-1">
      <div className="w-full bg-surface-container rounded-full h-1.5 mr-2">
      <div className="bg-tertiary-container h-1.5 rounded-full" style={{ width: "92%" }}></div>
      </div>
      <span className="font-label-sm text-label-sm font-bold text-on-surface">92%</span>
      </div>
      </div>
      </div>
      </div>
      {/* Screening Results by Talent Support Lead */}
      <div className="p-3 rounded-lg bg-surface-container-lowest flex items-start gap-space-sm shadow-xs">
      <span className="material-symbols-outlined text-on-tertiary-container text-[20px] shrink-0 mt-0.5">verified_user</span>
      <div className="flex-1 text-body-sm font-body-sm">
      <span className="font-semibold text-on-surface">Hasil Screening Talent Support:</span>
      <p className="text-on-surface-variant mt-0.5">"Lolos Technical Assessment (Skor 92/100, live coding Golang concurrency &amp; PostgreSQL query optimizer). Background check ijazah dan riwayat kerja di Fintech Payment Gateway verified tanpa catatan."</p>
      <div className="flex items-center gap-3 mt-1.5 font-label-sm text-label-sm text-outline">
      <span>Diperiksa oleh: Bambang Pratama (Lead Support)</span>
      <span>•</span>
      <span>Kemarin, 16:30 WIB</span>
      </div>
      </div>
      </div>
      {/* Actions */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm pt-2">
      <div className="flex items-center gap-2">
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">visibility</span>
      <span>Lihat CV Lengkap</span>
      </button>
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">calendar_month</span>
      <span>Jadwalkan Mock Interview</span>
      </button>
      </div>
      <button className="h-9 px-4 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md flex items-center gap-2 shadow-sm transition-all" onClick={() => alert("Kandidat Dimas Aditya diajukan ke Account Manager Mandiri!")} type="button">
      <span className="material-symbols-outlined text-[18px]">send</span>
      <span>Ajukan ke Klien (Account Manager)</span>
      </button>
      </div>
      </div>
      {/* CARD 2: 91% MATCH */}
      <div className="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex flex-col gap-space-md transition-all hover:shadow-md group">
      {/* Top Status */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm">
      <div className="flex items-center gap-2">
      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="material-symbols-outlined text-[14px]">auto_awesome</span>
                      Rekomendasi Kuat
                    </span>
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-label-sm text-label-sm font-semibold">
      <span className="w-2 h-2 rounded-full bg-secondary"></span>
                      Available 14 Hari (Notice Period)
                    </span>
      </div>
      <div className="flex items-center gap-2">
      <span className="text-on-surface-variant font-label-sm text-label-sm">Talent ID: #INT-8834</span>
      <div className="px-2.5 py-0.5 rounded-full bg-surface-container-low flex items-center gap-1 text-primary font-bold font-title-sm text-title-sm">
      <span className="material-symbols-outlined text-[16px]">verified</span>
      <span>91% Kecocokan</span>
      </div>
      </div>
      </div>
      {/* Profile Info */}
      <div className="flex flex-col sm:flex-row items-start gap-space-md">
      <img className="w-16 h-16 rounded-xl object-cover shadow-sm ring-2 ring-surface-container-high shrink-0" data-alt="Portrait of an Indonesian professional Muslim woman in her late 20s wearing an elegant soft beige modern hijab and business casual navy blazer in an office setting." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCm0_S7_au0fZKLgHEdic72p-21rVKCLyz7LSletvOjcBiDzMrljHSJQmnQ_TeQ33ou9tQxDW9WZhkRG4IB08qH6HoZQjXJwBrW37W-sITusE5Mwcg47XEm3gaGtjPqlhGzyi2ZeHLJJWXwZacodQtyY01oym16-SFXQ-YyxtRLd_9Zvg7Kd-W_4uGmkWij4vers84h8lvxNCXF9L55lTP1wVUFMIIysiDxqSgt78xKAnAhV_RdiavR" />
      <div className="flex-1 flex flex-col min-w-0">
      <div className="flex flex-wrap items-baseline justify-between gap-1">
      <h3 className="font-headline-sm text-headline-sm text-on-surface font-bold group-hover:text-primary transition-colors">Siti Nurhaliza, MT</h3>
      <span className="font-title-sm text-title-sm text-primary font-bold">Rp 26.000.000 <span className="font-body-sm text-body-sm text-on-surface-variant font-normal">/bulan</span></span>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Fullstack Golang &amp; Frontend Architect • 4 Tahun Pengalaman • Magister Teknik Informatika ITB</p>
      <div className="flex flex-wrap gap-1.5 mt-2.5">
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Golang Core</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">React &amp; Vue.js</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Apache Kafka</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">CI/CD Jenkins &amp; GitLab</span>
      <span className="px-2 py-0.5 rounded bg-surface-container-high font-label-sm text-label-sm text-secondary font-medium">PostgreSQL</span>
      </div>
      </div>
      </div>
      {/* Screening Note */}
      <div className="p-3 rounded-lg bg-surface-container-low flex items-start gap-space-sm">
      <span className="material-symbols-outlined text-primary text-[20px] shrink-0 mt-0.5">assignment_turned_in</span>
      <div className="flex-1 text-body-sm font-body-sm">
      <span className="font-semibold text-on-surface">Hasil Screening Talent Support:</span>
      <p className="text-on-surface-variant mt-0.5">"Technical Score 88/100. Portofolio FinTech Verified pada sistem core microservices Bank BCA via sub-kontraktor. Komunikasi bahasa Inggris sangat baik (TOEFL 580)."</p>
      </div>
      </div>
      {/* Actions */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm pt-1">
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">account_circle</span>
      <span>Detail Profil</span>
      </button>
      <div className="flex items-center gap-2">
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">mark_chat_unread</span>
      <span>Kirim Pesan</span>
      </button>
      <button className="h-9 px-4 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md flex items-center gap-2 shadow-sm transition-all" onClick={() => alert("Kandidat Siti Nurhaliza ditambahkan ke daftar pengajuan")} type="button">
      <span className="material-symbols-outlined text-[18px]">send</span>
      <span>Ajukan ke Klien</span>
      </button>
      </div>
      </div>
      </div>
      {/* CARD 3: 87% MATCH */}
      <div className="bg-surface-container-lowest rounded-xl shadow-sm p-space-lg flex flex-col gap-space-md transition-all hover:shadow-md group">
      {/* Top Status */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm">
      <div className="flex items-center gap-2">
      <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-fixed font-label-sm text-label-sm font-semibold">
      <span className="material-symbols-outlined text-[14px]">bolt</span>
                      Kandidat Potensial
                    </span>
      <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-low text-on-tertiary-container font-label-sm text-label-sm font-semibold">
      <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
                      Siap Ditempatkan (Bench)
                    </span>
      </div>
      <div className="flex items-center gap-2">
      <span className="text-on-surface-variant font-label-sm text-label-sm">Talent ID: #INT-7612</span>
      <div className="px-2.5 py-0.5 rounded-full bg-surface-container-low flex items-center gap-1 text-on-surface font-bold font-title-sm text-title-sm">
      <span>87% Potensial</span>
      </div>
      </div>
      </div>
      {/* Profile Info */}
      <div className="flex flex-col sm:flex-row items-start gap-space-md">
      <img className="w-16 h-16 rounded-xl object-cover shadow-sm ring-2 ring-surface-container-high shrink-0" data-alt="Portrait of an experienced Indonesian male tech specialist in his early 30s with calm confident expression, wearing polo shirt in contemporary tech hub office." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDbe97oyQpndKFGBybf6zjBwmc7PEwBm76ShIWyxMDQQZHamXHry_cYYtIK_6-OFYMscjZxnTUO_T8YAVhjNkxsXer8MttFP_pAX6T8xFzQEJJCPmZSuzkb3Y3wu-6lmvsGBJjq-zw0ktI-oEJJlEar__uNpnw5ygH5rJJhODuzrilog6l4OFNkvJFgTki4CF9B4I78zuvOHBCXOHnt33jl_IeWkKluu_Cc6QbwSwg7L_GcWrvlDBVH" />
      <div className="flex-1 flex flex-col min-w-0">
      <div className="flex flex-wrap items-baseline justify-between gap-1">
      <h3 className="font-headline-sm text-headline-sm text-on-surface font-bold group-hover:text-primary transition-colors">Reza Pratama</h3>
      <span className="font-title-sm text-title-sm text-primary font-bold">Rp 23.000.000 <span className="font-body-sm text-body-sm text-on-surface-variant font-normal">/bulan</span></span>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Senior Fullstack Engineer • 6 Tahun Pengalaman • Spesialis Cloud Modernization</p>
      <div className="flex flex-wrap gap-1.5 mt-2.5">
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Golang</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Node.js</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Next.js &amp; React</span>
      <span className="px-2 py-0.5 rounded bg-surface-container font-label-sm text-label-sm text-on-surface font-semibold">Google Cloud (GCP)</span>
      <span className="px-2 py-0.5 rounded bg-surface-container-high font-label-sm text-label-sm text-secondary font-medium">PostgreSQL</span>
      </div>
      </div>
      </div>
      {/* Screening Note */}
      <div className="p-3 rounded-lg bg-surface-container-low flex items-start gap-space-sm">
      <span className="material-symbols-outlined text-outline text-[20px] shrink-0 mt-0.5">pending_actions</span>
      <div className="flex-1 text-body-sm font-body-sm">
      <span className="font-semibold text-on-surface">Hasil Screening Talent Support:</span>
      <p className="text-on-surface-variant mt-0.5">"Lolos Screening Tahap 1 (HR &amp; Basic Coding). Menunggu final approval dari Tech Lead Intellisys mengenai arsitektur Kafka cluster."</p>
      </div>
      </div>
      {/* Actions */}
      <div className="flex flex-wrap items-center justify-between gap-space-sm pt-1">
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">badge</span>
      <span>Detail Profil</span>
      </button>
      <div className="flex items-center gap-2">
      <button className="h-9 px-3.5 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-label-md flex items-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">quiz</span>
      <span>Tes Tambahan Kafka</span>
      </button>
      <button className="h-9 px-4 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md flex items-center gap-2 shadow-sm transition-all" onClick={() => alert("Kandidat Reza Pratama diajukan")} type="button">
      <span className="material-symbols-outlined text-[18px]">send</span>
      <span>Ajukan ke Klien</span>
      </button>
      </div>
      </div>
      </div>
      {/* Pagination / Load more */}
      <div className="flex items-center justify-between py-2 text-on-surface-variant font-label-md text-label-md">
      <span>Menampilkan 3 dari 9 kandidat potensial di talent pool</span>
      <button className="h-9 px-4 rounded-lg bg-surface-container-lowest hover:bg-surface-container text-primary font-semibold flex items-center gap-2 shadow-xs transition-colors" type="button">
      <span>Muat Kandidat Lainnya (6 Orang)</span>
      <span className="material-symbols-outlined text-[18px]">expand_more</span>
      </button>
      </div>
      </div>
      {/* SIDEBAR WIDGETS COLUMN (4 COLS) */}
      <div className="xl:col-span-4 flex flex-col gap-space-lg">
      {/* AI Engine Parameters Widget */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md flex flex-col gap-space-md">
      <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="w-7 h-7 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center">
      <span className="material-symbols-outlined text-[18px]">tune</span>
      </span>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Parameter Bobot AI Matching</h3>
      </div>
      <span className="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant font-label-sm text-label-sm">Mandiri v2</span>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Konfigurasi pembobotan algoritma yang digunakan untuk kalkulasi peringkat requisisi ini:</p>
      {/* Weights Visualization */}
      <div className="space-y-space-sm pt-1">
      <div className="flex flex-col gap-1">
      <div className="flex items-center justify-between font-label-sm text-label-sm">
      <span className="text-on-surface font-medium flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[15px] text-primary">code</span>
                        Keahlian Teknis (Stack Fit)
                      </span>
      <span className="font-bold text-primary">40%</span>
      </div>
      <div className="w-full bg-surface-container rounded-full h-2">
      <div className="bg-primary h-2 rounded-full" style={{ width: "40%" }}></div>
      </div>
      </div>
      <div className="flex flex-col gap-1">
      <div className="flex items-center justify-between font-label-sm text-label-sm">
      <span className="text-on-surface font-medium flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[15px] text-primary">work_history</span>
                        Pengalaman FinTech &amp; Proyek
                      </span>
      <span className="font-bold text-primary">25%</span>
      </div>
      <div className="w-full bg-surface-container rounded-full h-2">
      <div className="bg-primary h-2 rounded-full" style={{ width: "25%" }}></div>
      </div>
      </div>
      <div className="flex flex-col gap-1">
      <div className="flex items-center justify-between font-label-sm text-label-sm">
      <span className="text-on-surface font-medium flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[15px] text-primary">payments</span>
                        Kesesuaian Tarif / Billing Rate
                      </span>
      <span className="font-bold text-primary">20%</span>
      </div>
      <div className="w-full bg-surface-container rounded-full h-2">
      <div className="bg-primary h-2 rounded-full" style={{ width: "20%" }}></div>
      </div>
      </div>
      <div className="flex flex-col gap-1">
      <div className="flex items-center justify-between font-label-sm text-label-sm">
      <span className="text-on-surface font-medium flex items-center gap-1.5">
      <span className="material-symbols-outlined text-[15px] text-primary">speed</span>
                        Kecepatan Ketersediaan (Onboarding)
                      </span>
      <span className="font-bold text-primary">15%</span>
      </div>
      <div className="w-full bg-surface-container rounded-full h-2">
      <div className="bg-primary h-2 rounded-full" style={{ width: "15%" }}></div>
      </div>
      </div>
      </div>
      <div className="pt-2">
      <button className="w-full h-9 rounded-lg bg-surface-container-low hover:bg-surface-container text-primary font-label-sm text-label-sm font-semibold transition-colors flex items-center justify-center gap-1.5" type="button">
      <span className="material-symbols-outlined text-[16px]">edit_note</span>
      <span>Sesuaikan Bobot Khusus Klien Ini</span>
      </button>
      </div>
      </div>
      {/* Latest Talent Screening Audit Log Widget */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md flex flex-col gap-space-md">
      <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
      <span className="w-7 h-7 rounded-lg bg-secondary-container text-on-secondary-fixed flex items-center justify-center">
      <span className="material-symbols-outlined text-[18px]">history_edu</span>
      </span>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Log Screening Terakhir</h3>
      </div>
      <button className="text-primary hover:underline font-label-sm text-label-sm" type="button">Semua Log</button>
      </div>
      <p className="font-body-sm text-body-sm text-on-surface-variant">Audit trail interview dan verifikasi kompetensi oleh tim Talent Support PT Intellisys:</p>
      {/* Timeline items */}
      <div className="flex flex-col gap-space-md relative pl-4 before:content-[''] before:absolute before:left-1.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container">
      {/* Log item 1 */}
      <div className="relative flex flex-col gap-0.5">
      <span className="absolute -left-4 top-1 w-2.5 h-2.5 rounded-full bg-tertiary-container ring-4 ring-surface-container-lowest"></span>
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-bold text-on-surface">Dimas Aditya, S.Kom</span>
      <span className="font-label-sm text-label-sm text-outline">Kemarin 16:30</span>
      </div>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Lolos Tech Assessment (Skor 92) &amp; Verifikasi Sertifikat Docker CKA.</span>
      <span className="font-label-sm text-label-sm text-primary font-semibold">Oleh: Bambang Pratama</span>
      </div>
      {/* Log item 2 */}
      <div className="relative flex flex-col gap-0.5">
      <span className="absolute -left-4 top-1 w-2.5 h-2.5 rounded-full bg-primary ring-4 ring-surface-container-lowest"></span>
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-bold text-on-surface">Siti Nurhaliza, MT</span>
      <span className="font-label-sm text-label-sm text-outline">05 Nov, 11:15</span>
      </div>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Konfirmasi Kesepakatan Billing Rate &amp; Verifikasi Referensi Bank Mandiri.</span>
      <span className="font-label-sm text-label-sm text-primary font-semibold">Oleh: Anisa Zahra (HR Specialist)</span>
      </div>
      {/* Log item 3 */}
      <div className="relative flex flex-col gap-0.5">
      <span className="absolute -left-4 top-1 w-2.5 h-2.5 rounded-full bg-secondary ring-4 ring-surface-container-lowest"></span>
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-bold text-on-surface">Fauzan Kamil</span>
      <span className="font-label-sm text-label-sm text-outline">04 Nov, 14:00</span>
      </div>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Gagal Tahap 1: Ekspektasi rate di atas pagu (Minta Rp 34 Juta).</span>
      <span className="font-label-sm text-label-sm text-error font-semibold">Status: Disimpan di Talent Pool Umum</span>
      </div>
      {/* Log item 4 */}
      <div className="relative flex flex-col gap-0.5">
      <span className="absolute -left-4 top-1 w-2.5 h-2.5 rounded-full bg-tertiary-container ring-4 ring-surface-container-lowest"></span>
      <div className="flex items-center justify-between">
      <span className="font-label-sm text-label-sm font-bold text-on-surface">Reza Pratama</span>
      <span className="font-label-sm text-label-sm text-outline">03 Nov, 10:20</span>
      </div>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Lolos Screening Budaya Kerja &amp; Validasi Portofolio GCP Microservices.</span>
      <span className="font-label-sm text-label-sm text-primary font-semibold">Oleh: Bambang Pratama</span>
      </div>
      </div>
      <div className="pt-2">
      <button className="w-full h-9 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-label-sm text-label-sm flex items-center justify-center gap-1.5 transition-colors" type="button">
      <span className="material-symbols-outlined text-[16px]">post_add</span>
      <span>Catat Hasil Screening Baru</span>
      </button>
      </div>
      </div>
      {/* Bench Quick Insight Card */}
      <div className="rounded-xl bg-surface-container-lowest shadow-sm p-space-md flex flex-col gap-space-sm">
      <div className="flex items-center gap-2">
      <span className="material-symbols-outlined text-primary text-[20px]">groups</span>
      <h3 className="font-title-sm text-title-sm text-on-surface font-bold">Kapasitas Bench Internal</h3>
      </div>
      <div className="flex items-baseline gap-2">
      <span className="font-headline-lg text-headline-lg text-primary font-bold">14</span>
      <span className="font-body-sm text-body-sm text-on-surface-variant">Talenta IT Bench Intellisys Siap Onboarding dalam 48 Jam</span>
      </div>
      <div className="grid grid-cols-2 gap-2 pt-2 text-label-sm font-label-sm">
      <div className="bg-surface-container-low p-2 rounded">
      <span className="text-on-surface-variant">Backend (Go/Java)</span>
      <div className="font-bold text-on-surface mt-0.5">6 Talenta</div>
      </div>
      <div className="bg-surface-container-low p-2 rounded">
      <span className="text-on-surface-variant">Frontend (React/Vue)</span>
      <div className="font-bold text-on-surface mt-0.5">4 Talenta</div>
      </div>
      <div className="bg-surface-container-low p-2 rounded">
      <span className="text-on-surface-variant">DevOps &amp; Cloud</span>
      <div className="font-bold text-on-surface mt-0.5">3 Talenta</div>
      </div>
      <div className="bg-surface-container-low p-2 rounded">
      <span className="text-on-surface-variant">QA Automation</span>
      <div className="font-bold text-on-surface mt-0.5">1 Talenta</div>
      </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      </div>
    </>
  );
}
