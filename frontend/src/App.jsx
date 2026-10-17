import { Navigate, Route, Routes } from "react-router-dom";
import AppLayout from "./components/AppLayout";
import RequireAuth from "./auth/RequireAuth";
import Login from "./pages/Login";
import DashboardRekapitulasi from "./pages/DashboardRekapitulasi";
import AiMatchRecommendation from "./pages/AiMatchRecommendation";
import PenempatanMonitoring from "./pages/PenempatanMonitoring";
import PermintaanKebutuhan from "./pages/PermintaanKebutuhan";
import ComingSoon from "./pages/ComingSoon";

export default function App() {
  return (
    <Routes>
      <Route path="login" element={<Login />} />
      <Route element={<RequireAuth />}>
        <Route element={<AppLayout />}>
          <Route index element={<Navigate to="/dashboard-rekapitulasi" replace />} />
          <Route path="dashboard-rekapitulasi" element={<DashboardRekapitulasi />} />
          <Route path="ai-match-recommendation" element={<AiMatchRecommendation />} />
          <Route path="penugasan-penempatan" element={<PenempatanMonitoring />} />
          <Route path="permintaan-kebutuhan" element={<PermintaanKebutuhan />} />
          <Route path="*" element={<ComingSoon />} />
        </Route>
      </Route>
    </Routes>
  );
}
