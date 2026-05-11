import { createFileRoute } from "@tanstack/react-router";
import { AdminLayout } from "@/components/admin/AdminLayout";
import { PanelCard } from "@/components/admin/PanelCard";
import { salesTrend, branchPerformance } from "@/lib/zurimart-data";
import { ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip, CartesianGrid, PieChart, Pie, Cell, Legend } from "recharts";

export const Route = createFileRoute("/reports")({
  head: () => ({ meta: [{ title: "Reports — ZuriMart Admin" }] }),
  component: ReportsPage,
});

const COLORS = ["oklch(0.62 0.16 55)", "oklch(0.65 0.15 230)", "oklch(0.62 0.17 150)", "oklch(0.78 0.16 80)"];

function ReportsPage() {
  return (
    <AdminLayout title="Reports & Analytics" breadcrumbs={[{ label: "Reports" }]}>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <PanelCard title="Weekly Sales Comparison" subtitle="Retail vs Wholesale">
          <div className="h-72">
            <ResponsiveContainer>
              <LineChart data={salesTrend}>
                <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.9 0.01 250)" />
                <XAxis dataKey="day" stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <YAxis stroke="oklch(0.5 0.02 260)" fontSize={12} />
                <Tooltip contentStyle={{ borderRadius: 6, border: "1px solid oklch(0.9 0.01 250)" }} />
                <Legend />
                <Line type="monotone" dataKey="retail" stroke="oklch(0.65 0.15 230)" strokeWidth={2} />
                <Line type="monotone" dataKey="wholesale" stroke="oklch(0.62 0.16 55)" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </PanelCard>

        <PanelCard title="Branch Order Distribution" subtitle="Share of fulfilled orders">
          <div className="h-72">
            <ResponsiveContainer>
              <PieChart>
                <Pie data={branchPerformance} dataKey="orders" nameKey="name" cx="50%" cy="50%" outerRadius={90} label>
                  {branchPerformance.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                </Pie>
                <Tooltip contentStyle={{ borderRadius: 6, border: "1px solid oklch(0.9 0.01 250)" }} />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </PanelCard>
      </div>
    </AdminLayout>
  );
}
