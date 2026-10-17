import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "./Sidebar";
import Header from "./Header";
import AksesDitolak from "../pages/AksesDitolak";
import { useAuth } from "../auth/AuthContext";
import { NAV_ITEMS, canAccess } from "../data/navigation";

export default function AppLayout() {
  const { user } = useAuth();
  const { pathname } = useLocation();
  const current = NAV_ITEMS.find((item) => item.path === pathname);
  const boleh = !current || canAccess(current, user.role);

  return (
    <>
      <Sidebar />
      <div className="pl-72">
        <Header title={current?.label ?? "Operasional Outsourcing"} />
        <main className="w-full pt-16 bg-surface min-h-screen">{boleh ? <Outlet /> : <AksesDitolak />}</main>
      </div>
    </>
  );
}
