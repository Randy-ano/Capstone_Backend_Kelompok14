import { NavLink } from "react-router-dom";
import { useAuth } from "../auth/AuthContext";
import { ROLE_LABEL } from "../auth/roles";
import { navGroupsFor } from "../data/navigation";

const LINK_BASE = "flex items-center gap-space-sm px-space-sm py-2 transition-all";
const LINK_ACTIVE = "bg-primary-container text-on-primary-container font-semibold rounded-lg shadow-sm";
const LINK_IDLE =
  "rounded-lg font-body-md text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface";

export default function Sidebar() {
  const { user } = useAuth();
  const navGroups = navGroupsFor(user.role);

  return (
    <aside className="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex flex-col overflow-y-auto">
      <div className="p-space-lg flex items-center gap-space-sm bg-surface-container-lowest">
        <img alt="Intellisys Talent Hub Logo" className="h-8 w-auto object-contain" src="/logo.jpg" />
        <div className="flex flex-col min-w-0">
          <span className="font-title-sm text-title-sm text-primary tracking-tight truncate">Intellisys Talent Hub</span>
          <span className="font-label-sm text-label-sm text-on-surface-variant truncate">PT Intellisys IT Outsourcing</span>
        </div>
      </div>

      <div className="px-space-md pb-space-sm">
        <div className="bg-surface-container-low p-space-xs rounded-full flex flex-col gap-space-xs">
          <div className="flex items-center justify-between px-space-sm py-space-xs">
            <span className="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Konteks Peran</span>
            <span className="w-2 h-2 rounded-full bg-tertiary-container"></span>
          </div>
          <div className="flex flex-wrap gap-1">
            <span className="px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-primary-container text-on-primary-container shadow-sm">
              {ROLE_LABEL[user.role]}
            </span>
          </div>
        </div>
      </div>

      <nav className="flex-1 px-space-md py-space-sm space-y-space-md">
        {navGroups.map((group) => (
          <div key={group.label} className="space-y-space-xs">
            <div className="px-space-sm font-label-sm text-label-sm uppercase tracking-wider text-outline">{group.label}</div>
            <div className="space-y-0.5">
              {group.items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  className={({ isActive }) => `${LINK_BASE} ${isActive ? LINK_ACTIVE : LINK_IDLE}`}
                >
                  <span className="material-symbols-outlined text-[20px]">{item.icon}</span>
                  <span>{item.label}</span>
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>

      <div className="p-space-md mt-auto bg-surface-container-low">
        <div className="flex items-center justify-between text-on-surface-variant font-label-sm text-label-sm">
          <span>Intellisys Core v2.4</span>
          <span className="flex items-center gap-1 text-on-tertiary-container">
            <span className="w-1.5 h-1.5 rounded-full bg-tertiary-container"></span>
            Online
          </span>
        </div>
      </div>
    </aside>
  );
}
