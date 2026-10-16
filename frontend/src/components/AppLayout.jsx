import { useState } from "react";
import { Outlet, useLocation } from "react-router-dom";
import Sidebar from "./Sidebar";
import Header from "./Header";
import { NAV_ITEMS, ROLES } from "../data/navigation";

export default function AppLayout() {
  const [role, setRole] = useState(ROLES[0]);
  const { pathname } = useLocation();
  const current = NAV_ITEMS.find((item) => item.path === pathname);

  return (
    <>
      <Sidebar role={role} onRoleChange={setRole} />
      <div className="pl-72">
        <Header title={current?.label ?? "Operasional Outsourcing"} />
        <main className="w-full pt-16 bg-surface min-h-screen">
          <Outlet />
        </main>
      </div>
    </>
  );
}
