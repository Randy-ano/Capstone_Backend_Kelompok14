// Struktur menu sidebar sesuai mockup Stitch "Intellisys Talent Hub Dashboard".
// `ready: true` menandai halaman yang sudah memiliki desain di Stitch.
export const NAV_GROUPS = [
  {
    label: "Utama",
    items: [
      { path: "/dashboard-rekapitulasi", label: "Dashboard Rekapitulasi", icon: "grid_view", ready: true },
      { path: "/pipeline-rekrutmen", label: "Pipeline Rekrutmen", icon: "view_kanban" },
    ],
  },
  {
    label: "Talent Management",
    items: [
      { path: "/talent-pool-screening", label: "Talent Pool & Screening", icon: "group" },
      { path: "/ai-match-recommendation", label: "AI Match Recommendation", icon: "auto_awesome", ready: true },
      { path: "/penugasan-penempatan", label: "Penugasan Penempatan", icon: "assignment_ind", ready: true },
    ],
  },
  {
    label: "Klien & Rekuisisi",
    items: [
      { path: "/permintaan-kebutuhan", label: "Permintaan Kebutuhan", icon: "receipt_long", ready: true },
      { path: "/pengajuan-kandidat", label: "Pengajuan Kandidat", icon: "send" },
      { path: "/jadwal-interview", label: "Jadwal Interview", icon: "event_available" },
    ],
  },
  {
    label: "Operasional & HR",
    items: [
      { path: "/monitoring-kinerja-sla", label: "Monitoring Kinerja & SLA", icon: "monitoring" },
      { path: "/pengajuan-cuti-shift", label: "Pengajuan Cuti & Shift", icon: "calendar_clock" },
      { path: "/tiket-komplain-klien", label: "Tiket Komplain Klien", icon: "support_agent" },
      { path: "/dokumen-quotation-po", label: "Dokumen (Quotation & PO)", icon: "description" },
    ],
  },
  {
    label: "Pengaturan",
    items: [
      { path: "/master-data-skill", label: "Master Data Skill", icon: "database" },
      { path: "/pengaturan-akun", label: "Pengaturan Akun", icon: "settings" },
    ],
  },
];

export const NAV_ITEMS = NAV_GROUPS.flatMap((g) => g.items);

export const ROLES = ["Talent Support", "Account Mgr", "HR Client", "Manager", "Talent"];
