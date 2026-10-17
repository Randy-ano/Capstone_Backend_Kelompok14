import { useState } from "react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";
import { ROLE_LABEL } from "../auth/roles";

// Akun demo dari seeder backend (password semuanya "password"); hanya tampil saat development.
const AKUN_DEMO = [
  { email: "manager@intellisys.test", role: "manager" },
  { email: "am@intellisys.test", role: "account_manager" },
  { email: "ts@intellisys.test", role: "talent_support" },
  { email: "hr@intellisys.test", role: "hr_client" },
  { email: "talent@intellisys.test", role: "talent" },
];

export default function Login() {
  const { user, login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const tujuan = location.state?.from ?? "/dashboard-rekapitulasi";

  if (user) return <Navigate to={tujuan} replace />;

  async function handleSubmit(event) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    setFieldErrors({});

    try {
      await login(email, password);
      navigate(tujuan, { replace: true });
    } catch (err) {
      // 422: email/password salah atau kosong; 403: akun nonaktif; 0: backend mati.
      setError(err.message);
      setFieldErrors(err.errors ?? {});
    } finally {
      setSubmitting(false);
    }
  }

  function pakaiAkunDemo(akun) {
    setEmail(akun.email);
    setPassword("password");
    setError(null);
    setFieldErrors({});
  }

  const inputClass = (field) =>
    `w-full h-10 px-3 rounded-lg bg-surface-container-low font-body-md text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:ring-2 transition-all ${
      fieldErrors[field] ? "ring-2 ring-error focus:ring-error" : "focus:ring-primary-container"
    }`;

  return (
    <div className="min-h-screen bg-surface flex items-center justify-center px-space-md py-space-xl">
      <div className="w-full max-w-md space-y-space-lg">
        <div className="flex items-center gap-space-sm justify-center">
          <img alt="Intellisys Talent Hub Logo" className="h-10 w-auto object-contain" src="/logo.jpg" />
          <div className="flex flex-col">
            <span className="font-title-md text-title-md text-primary tracking-tight">Intellisys Talent Hub</span>
            <span className="font-label-sm text-label-sm text-on-surface-variant">PT Intellisys IT Outsourcing</span>
          </div>
        </div>

        <form
          onSubmit={handleSubmit}
          noValidate
          className="bg-surface-container-lowest rounded-xl shadow-sm p-space-xl space-y-space-md"
        >
          <div className="space-y-1">
            <h1 className="font-headline-sm text-headline-sm text-on-surface">Masuk ke akun Anda</h1>
            <p className="font-body-md text-body-md text-secondary">Gunakan email dan password yang terdaftar.</p>
          </div>

          {error && (
            <div role="alert" className="flex items-start gap-space-sm p-space-sm rounded-lg bg-error-container text-on-error-container font-body-md text-body-md">
              <span className="material-symbols-outlined text-[20px]">error</span>
              <span>{error}</span>
            </div>
          )}

          <label className="block space-y-1">
            <span className="font-label-lg text-label-lg text-on-surface">Email</span>
            <input
              className={inputClass("email")}
              type="email"
              autoComplete="email"
              placeholder="nama@perusahaan.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoFocus
            />
          </label>

          <label className="block space-y-1">
            <span className="font-label-lg text-label-lg text-on-surface">Password</span>
            <input
              className={inputClass("password")}
              type="password"
              autoComplete="current-password"
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
            {fieldErrors.password && <span className="font-body-sm text-body-sm text-error">{fieldErrors.password[0]}</span>}
          </label>

          <button
            type="submit"
            disabled={submitting}
            className="w-full h-10 flex items-center justify-center gap-2 rounded-lg bg-primary text-on-primary hover:bg-primary-container font-label-lg text-label-lg transition-all shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {submitting && <span className="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>}
            {submitting ? "Memproses..." : "Masuk"}
          </button>
        </form>

        {import.meta.env.DEV && (
          <div className="bg-surface-container-low rounded-xl p-space-md space-y-space-sm">
            <div className="flex items-center justify-between">
              <span className="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Akun demo (development)</span>
              <span className="font-body-sm text-body-sm text-outline">password: password</span>
            </div>
            <div className="grid grid-cols-1 gap-1">
              {AKUN_DEMO.map((akun) => (
                <button
                  key={akun.email}
                  type="button"
                  onClick={() => pakaiAkunDemo(akun)}
                  className="flex items-center justify-between px-space-sm py-1.5 rounded-lg hover:bg-surface-container transition-colors text-left"
                >
                  <span className="font-body-md text-body-md text-on-surface">{akun.email}</span>
                  <span className="font-label-sm text-label-sm text-on-surface-variant">{ROLE_LABEL[akun.role]}</span>
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
