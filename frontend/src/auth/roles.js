// Nilai `users.role` dari backend dan labelnya untuk tampilan.
export const ROLE = {
  TALENT: "talent",
  HR_CLIENT: "hr_client",
  ACCOUNT_MANAGER: "account_manager",
  TALENT_SUPPORT: "talent_support",
  MANAGER: "manager",
};

export const ROLE_LABEL = {
  [ROLE.TALENT]: "Talent",
  [ROLE.HR_CLIENT]: "HR Client",
  [ROLE.ACCOUNT_MANAGER]: "Account Manager",
  [ROLE.TALENT_SUPPORT]: "Talent Support",
  [ROLE.MANAGER]: "Manager",
};

export const INTERNAL = [ROLE.ACCOUNT_MANAGER, ROLE.TALENT_SUPPORT, ROLE.MANAGER];

// Nama & keterangan singkat user untuk header, sesuai profil per role.
export function displayUser(user) {
  const profil = user?.profil ?? {};
  const nama = profil.nama_pegawai ?? profil.nama_hr ?? profil.nama_lengkap ?? user?.email ?? "";
  const keterangan =
    user?.role === ROLE.HR_CLIENT
      ? [profil.jabatan, profil.klien?.nama_perusahaan].filter(Boolean).join(" · ")
      : profil.jabatan ?? ROLE_LABEL[user?.role];

  return { nama, keterangan: keterangan || ROLE_LABEL[user?.role] };
}

export function initials(nama) {
  return nama
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((kata) => kata[0].toUpperCase())
    .join("");
}
